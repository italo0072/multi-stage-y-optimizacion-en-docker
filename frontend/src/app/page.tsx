import { Suspense } from "react";
import Link from "next/link";
import ProductImage from "@/components/ProductImage";
import { connection } from "next/server";
import { api, list, Product } from "@/lib/api";
async function ProductList() {
  await connection();
  const products = list<Product>(await api("/products"));
  if (!products.length) return <p className="muted">No hay productos todavía.</p>;
  return (
    <div className="grid">
      {products.map((p) => (
        <Link key={p.id} href={`/products/${p.id}`} className="card">
          <ProductImage sku={p.sku} name={p.name} url={p.image_url} />
          <h3>{p.name}</h3><p>${Number(p.price).toFixed(2)}</p>
        </Link>
      ))}
    </div>
  );
}
export default function Home() {
  return (<><h1>Catálogo</h1><Suspense fallback={<p className="muted">Cargando productos…</p>}><ProductList /></Suspense></>);
}
