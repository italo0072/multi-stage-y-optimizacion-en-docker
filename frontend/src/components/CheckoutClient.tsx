"use client";
import Link from "next/link";
import { useState, useTransition } from "react";
import { useCart } from "./CartProvider";
import { createOrderAction, payOrderAction } from "@/actions/orders";

export default function CheckoutClient({ stripeEnabled }: { stripeEnabled: boolean }) {
  const { items, total, dispatch } = useCart();
  const [orderId, setOrderId] = useState<number>();
  const [error, setError] = useState("");
  const [pending, start] = useTransition();

  if (orderId && !stripeEnabled) {
    return (
      <div className="card">
        <p>Orden #{orderId} registrada. Estado: pendiente de pago.</p>
        <Link href="/orders">Ver mis órdenes</Link>
      </div>
    );
  }

  if (!items.length && !orderId) return <p>Tu carrito está vacío.</p>;

  const checkout = () => start(async () => {
    setError("");
    let id = orderId;

    if (!id) {
      const order = await createOrderAction(
        items.map((item) => ({ product_id: item.id, quantity: item.quantity })),
      );
      if (!order.ok) {
        setError(order.error);
        return;
      }
      // Conserva la orden para reintentar el pago sin volver a descontar stock.
      id = order.orderId;
      setOrderId(id);
      dispatch({ t: "clear" });
    }

    if (stripeEnabled) {
      const payment = await payOrderAction(id);
      if (payment && !payment.ok) setError(payment.error);
    }
  });

  return (
    <div className="card">
      {orderId ? (
        <p>Orden #{orderId} registrada.</p>
      ) : (
        <>
          {items.map((item) => (
            <div key={item.id} className="row">
              <span>{item.name} × {item.quantity}</span>
              <span>${(item.price * item.quantity).toFixed(2)}</span>
              <button className="ghost" disabled={pending} onClick={() => dispatch({ t: "remove", id: item.id })}>
                Quitar
              </button>
            </div>
          ))}
          <h2>Total: ${total.toFixed(2)}</h2>
        </>
      )}
      {!stripeEnabled && <p className="muted">La orden se registrará como pendiente de pago.</p>}
      {error && <p className="err" role="alert">{error}</p>}
      <button onClick={checkout} disabled={pending}>
        {pending ? "Procesando…" : orderId ? "Reintentar pago" : stripeEnabled ? "Crear orden y pagar" : "Crear orden"}
      </button>
    </div>
  );
}
