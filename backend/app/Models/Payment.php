<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Payment",
 *     type="object",
 *     title="Pago (Stripe)",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="order_id", type="integer", example=1),
 *     @OA\Property(property="stripe_payment_intent_id", type="string", example="pi_3Qxxxxxxxxxxxxxx"),
 *     @OA\Property(property="amount", type="number", format="float", example=149.97),
 *     @OA\Property(property="currency", type="string", example="usd"),
 *     @OA\Property(property="status", type="string", enum={"requires_payment_method","requires_confirmation","processing","succeeded","canceled","failed"}, example="succeeded"),
 *     @OA\Property(property="payment_method", type="string", nullable=true, example="card"),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 */
class Payment extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'stripe_payment_intent_id',
        'stripe_client_secret',
        'amount',
        'currency',
        'status',
        'payment_method',
        'raw_response',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'raw_response' => 'array',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
