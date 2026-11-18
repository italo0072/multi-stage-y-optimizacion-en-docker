<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * @OA\Get(
     *     path="/products",
     *     tags={"Productos"},
     *     summary="Listar catalogo de productos (publico)",
     *     @OA\Parameter(name="search", in="query", description="Buscar por nombre", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", description="Resultados por pagina", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Listado de productos",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Product")),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Product::query()->active();

        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $products = $query->orderBy('name')->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/products/{id}",
     *     tags={"Productos"},
     *     summary="Ver detalle de un producto (publico)",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Detalle del producto", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="data", ref="#/components/schemas/Product")
     *     )),
     *     @OA\Response(response=404, description="Producto no encontrado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function show(Product $product)
    {
        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/products",
     *     tags={"Productos"},
     *     summary="Crear un nuevo producto (requiere autenticacion)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","price","stock","sku"},
     *             @OA\Property(property="name", type="string", example="Mouse inalambrico"),
     *             @OA\Property(property="description", type="string", example="Mouse ergonomico con sensor optico"),
     *             @OA\Property(property="price", type="number", format="float", example=19.99),
     *             @OA\Property(property="stock", type="integer", example=100),
     *             @OA\Property(property="sku", type="string", example="MS-WL-002"),
     *             @OA\Property(property="image_url", type="string", example="https://picsum.photos/seed/2/400"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Producto creado", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="message", type="string", example="Producto creado correctamente."),
     *         @OA\Property(property="data", ref="#/components/schemas/Product")
     *     )),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="Error de validacion", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function store(StoreProductRequest $request)
    {
        $product = Product::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Producto creado correctamente.',
            'data' => $product,
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/products/{id}",
     *     tags={"Productos"},
     *     summary="Actualizar un producto (requiere autenticacion)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Mouse inalambrico Pro"),
     *             @OA\Property(property="description", type="string", example="Version actualizada"),
     *             @OA\Property(property="price", type="number", format="float", example=24.99),
     *             @OA\Property(property="stock", type="integer", example=80),
     *             @OA\Property(property="sku", type="string", example="MS-WL-002"),
     *             @OA\Property(property="image_url", type="string", example="https://picsum.photos/seed/2/400"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Producto actualizado", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="message", type="string", example="Producto actualizado correctamente."),
     *         @OA\Property(property="data", ref="#/components/schemas/Product")
     *     )),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Producto no encontrado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="Error de validacion", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $product->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Producto actualizado correctamente.',
            'data' => $product->fresh(),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/products/{id}",
     *     tags={"Productos"},
     *     summary="Eliminar un producto (requiere autenticacion)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Producto eliminado", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="message", type="string", example="Producto eliminado correctamente.")
     *     )),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Producto no encontrado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Producto eliminado correctamente.',
        ]);
    }
}
