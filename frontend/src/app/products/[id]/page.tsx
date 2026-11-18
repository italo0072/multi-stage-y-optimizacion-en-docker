import { api, one, Product } from "@/lib/api";
import AddToCart from "@/components/AddToCart";
import ProductImage from "@/components/ProductImage";
export default async function ProductPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const p = one<Product>(await api(`/products/${id}`));
  return (
    <div className="card">
      <ProductImage sku={p.sku} name={p.name} url={p.image_url} detail />
      <h1>{p.name}</h1><p>{p.description}</p><h2>${Number(p.price).toFixed(2)}</h2>
      <p className="muted">Stock: {p.stock}</p>
      <AddToCart id={p.id} name={p.name} price={Number(p.price)} />
    </div>
  );
}
