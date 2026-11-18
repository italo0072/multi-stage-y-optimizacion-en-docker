<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * @OA\Get(
     *     path="/orders",
     *     tags={"Ordenes"},
     *     summary="Historial de compras del usuario autenticado",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Listado de ordenes", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Order"))
     *     )),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function index()
    {
        $orders = Auth::guard('api')->user()
            ->orders()
            ->with(['items', 'payment'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/orders/{id}",
     *     tags={"Ordenes"},
     *     summary="Ver detalle de una orden propia",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Detalle de la orden", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="data", ref="#/components/schemas/Order")
     *     )),
     *     @OA\Response(response=403, description="No autorizado para ver esta orden", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Orden no encontrada", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function show(Order $order)
    {
        if ($order->user_id !== Auth::guard('api')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para ver esta orden.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $order->load(['items', 'payment']),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/orders",
     *     tags={"Ordenes"},
     *     summary="Crear una nueva orden de compra",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"items"},
     *             @OA\Property(property="items", type="array", @OA\Items(
     *                 type="object",
     *                 required={"product_id","quantity"},
     *                 @OA\Property(property="product_id", type="integer", example=1),
     *                 @OA\Property(property="quantity", type="integer", example=2)
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=201, description="Orden creada", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="message", type="string", example="Orden creada correctamente."),
     *         @OA\Property(property="data", ref="#/components/schemas/Order")
     *     )),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="Stock insuficiente o error de validacion", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function store(StoreOrderRequest $request)
    {
        $user = Auth::guard('api')->user();

        try {
            $order = DB::transaction(function () use ($request, $user) {
                $order = Order::create([
                    'user_id' => $user->id,
                    'total_amount' => 0,
                    'status' => Order::STATUS_PENDING,
                ]);

                $total = 0;

                foreach ($request->validated('items') as $item) {
                    /** @var Product $product */
                    $product = Product::where('id', $item['product_id'])->lockForUpdate()->firstOrFail();

                    if ($product->stock < $item['quantity']) {
                        throw new \RuntimeException("Stock insuficiente para el producto: {$product->name}");
                    }

                    $subtotal = $product->price * $item['quantity'];
                    $total += $subtotal;

                    $order->items()->create([
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'quantity' => $item['quantity'],
                        'unit_price' => $product->price,
                        'subtotal' => $subtotal,
                    ]);

                    $product->decrement('stock', $item['quantity']);
                }

                $order->update(['total_amount' => $total]);

                return $order;
            });
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Orden creada correctamente.',
            'data' => $order->load('items'),
        ], 201);
    }
}
