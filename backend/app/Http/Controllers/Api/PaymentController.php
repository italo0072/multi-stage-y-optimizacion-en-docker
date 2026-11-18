<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatePaymentIntentRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;

class PaymentController extends Controller
{
    public function __construct(protected StripeService $stripe)
    {
    }

    /**
     * @OA\Post(
     *     path="/payments/create-intent",
     *     tags={"Pagos"},
     *     summary="Crear un PaymentIntent de Stripe para pagar una orden",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"order_id"},
     *             @OA\Property(property="order_id", type="integer", example=1),
     *             @OA\Property(property="payment_method", type="string", example="pm_card_visa", description="ID de metodo de pago de Stripe (opcional, para confirmar en un solo paso)")
     *         )
     *     ),
     *     @OA\Response(response=201, description="PaymentIntent creado", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="message", type="string", example="Intento de pago creado."),
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="payment", ref="#/components/schemas/Payment"),
     *             @OA\Property(property="client_secret", type="string", example="pi_3Qxxxx_secret_xxxx")
     *         )
     *     )),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="La orden no pertenece al usuario", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=409, description="La orden ya fue pagada", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="Error de validacion / Stripe", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function createIntent(CreatePaymentIntentRequest $request)
    {
        $order = Order::findOrFail($request->validated('order_id'));

        if ($order->user_id !== Auth::guard('api')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para pagar esta orden.',
            ], 403);
        }

        if ($order->status === Order::STATUS_PAID) {
            return response()->json([
                'success' => false,
                'message' => 'Esta orden ya fue pagada.',
            ], 409);
        }

        if (! $this->stripe->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'El pago con Stripe no está configurado.',
            ], 503);
        }

        try {
            $intent = $this->stripe->createPaymentIntent($order, $request->validated('payment_method'));

            $payment = Payment::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'stripe_payment_intent_id' => $intent->id,
                    'stripe_client_secret' => $intent->client_secret,
                    'amount' => $order->total_amount,
                    'currency' => $intent->currency,
                    'status' => $intent->status,
                    'payment_method' => $intent->payment_method,
                    'raw_response' => $intent->toArray(),
                ]
            );

            if ($intent->status === 'succeeded') {
                $order->update(['status' => Order::STATUS_PAID]);
            }
        } catch (ApiErrorException $e) {
            Log::error('Stripe error creando PaymentIntent', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el pago con Stripe: '.$e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Intento de pago creado.',
            'data' => [
                'payment' => $payment,
                'client_secret' => $intent->client_secret,
            ],
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/payments/{order}/confirm",
     *     tags={"Pagos"},
     *     summary="Confirmar el estado de un pago consultando Stripe",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="order", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Estado del pago actualizado", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="data", ref="#/components/schemas/Payment")
     *     )),
     *     @OA\Response(response=404, description="Pago no encontrado para esta orden", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function confirm(Order $order)
    {
        if ($order->user_id !== Auth::guard('api')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para consultar esta orden.',
            ], 403);
        }

        $payment = $order->payment;

        if (! $payment) {
            return response()->json([
                'success' => false,
                'message' => 'Aun no existe un intento de pago para esta orden.',
            ], 404);
        }

        if (! $this->stripe->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'El pago con Stripe no está configurado.',
            ], 503);
        }

        try {
            $intent = $this->stripe->retrievePaymentIntent($payment->stripe_payment_intent_id);

            $payment->update([
                'status' => $intent->status,
                'payment_method' => $intent->payment_method,
                'raw_response' => $intent->toArray(),
            ]);

            if ($intent->status === 'succeeded') {
                $order->update(['status' => Order::STATUS_PAID]);
            } elseif (in_array($intent->status, ['canceled'], true)) {
                $order->update(['status' => Order::STATUS_FAILED]);
            }
        } catch (ApiErrorException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al consultar el pago en Stripe: '.$e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $payment->fresh(),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/payments/webhook",
     *     tags={"Pagos"},
     *     summary="Webhook de Stripe (llamado por Stripe, no por el cliente)",
     *     description="Endpoint publico que Stripe invoca para notificar eventos de pago (payment_intent.succeeded, payment_intent.payment_failed, etc). Debe configurarse en el Dashboard de Stripe con el header Stripe-Signature.",
     *     @OA\Response(response=200, description="Evento procesado"),
     *     @OA\Response(response=400, description="Firma invalida")
     * )
     */
    public function webhook(Request $request)
    {
        if (blank(config('services.stripe.webhook_secret'))) {
            return response()->json(['success' => false, 'message' => 'Webhook sin configurar.'], 503);
        }

        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature', '');

        try {
            $event = $this->stripe->constructWebhookEvent($payload, $signature);
        } catch (\Exception $e) {
            Log::warning('Webhook de Stripe invalido', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Firma invalida.'], 400);
        }

        $intent = $event->data->object ?? null;

        if ($intent && isset($intent->id)) {
            $payment = Payment::where('stripe_payment_intent_id', $intent->id)->first();

            if ($payment) {
                $payment->update(['status' => $intent->status ?? $payment->status]);

                if (($intent->status ?? null) === 'succeeded') {
                    $payment->order?->update(['status' => Order::STATUS_PAID]);
                } elseif (($intent->status ?? null) === 'canceled') {
                    $payment->order?->update(['status' => Order::STATUS_FAILED]);
                }
            }
        }

        return response()->json(['success' => true]);
    }
}
