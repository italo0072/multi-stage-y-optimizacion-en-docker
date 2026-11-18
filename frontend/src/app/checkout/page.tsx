import { connection } from "next/server";
import CheckoutClient from "@/components/CheckoutClient";

export default async function Page() {
  await connection();
  const stripeEnabled = process.env.CHECKOUT_MODE === "stripe";

  return (
    <>
      <h1>Checkout</h1>
      <CheckoutClient stripeEnabled={stripeEnabled} />
    </>
  );
}
