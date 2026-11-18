<?php

namespace App\Services;

use App\Models\Order;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\StripeClient;

class StripeService
{
    public function isConfigured(): bool
    {
        return filled(config('services.stripe.secret'));
    }

    private function client(): StripeClient
    {
        return new StripeClient(config('services.stripe.secret'));
    }

    /**
     * Crea un PaymentIntent en Stripe para una orden.
     *
     * @throws ApiErrorException
     */
    public function createPaymentIntent(Order $order, ?string $paymentMethod = null): PaymentIntent
    {
        $amountInCents = (int) round(((float) $order->total_amount) * 100);

        $params = [
            'amount' => $amountInCents,
            'currency' => config('services.stripe.currency', 'usd'),
            'metadata' => [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
            ],
            'automatic_payment_methods' => ['enabled' => true],
        ];

        if ($paymentMethod) {
            $params['payment_method'] = $paymentMethod;
            $params['confirm'] = true;
            $params['automatic_payment_methods'] = [
                'enabled' => true,
                'allow_redirects' => 'never',
            ];
        }

        return $this->client()->paymentIntents->create($params, [
            // Stripe reutiliza el intento si se repite la solicitud de esta orden.
            'idempotency_key' => hash_hmac('sha256', 'order-'.$order->id, config('app.key')),
        ]);
    }

    /**
     * Recupera el estado actual de un PaymentIntent.
     *
     * @throws ApiErrorException
     */
    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntent
    {
        return $this->client()->paymentIntents->retrieve($paymentIntentId);
    }

    /**
     * Confirma un PaymentIntent existente (cuando se paga en dos pasos).
     *
     * @throws ApiErrorException
     */
    public function confirmPaymentIntent(string $paymentIntentId, ?string $paymentMethod = null): PaymentIntent
    {
        $params = [];
        if ($paymentMethod) {
            $params['payment_method'] = $paymentMethod;
        }

        return $this->client()->paymentIntents->confirm($paymentIntentId, $params);
    }

    /**
     * Construye y valida un evento de webhook de Stripe.
     *
     * @throws \UnexpectedValueException|\Stripe\Exception\SignatureVerificationException
     */
    public function constructWebhookEvent(string $payload, string $signatureHeader): \Stripe\Event
    {
        return \Stripe\Webhook::constructEvent(
            $payload,
            $signatureHeader,
            config('services.stripe.webhook_secret')
        );
    }
}
