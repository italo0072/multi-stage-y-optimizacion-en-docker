"use client";

const genericImage = "/imagenes/productos/sin-imagen.webp";

type Props = {
  sku: string;
  name: string;
  url?: string | null;
  detail?: boolean;
};

export default function ProductImage({ sku, name, url, detail = false }: Props) {
  const localImage = /^[a-z0-9_-]+$/i.test(sku || "")
    ? `/imagenes/productos/${sku.toUpperCase()}.webp`
    : genericImage;
  const source = url?.trim() || localImage;

  const showFallback = (image: HTMLImageElement) => {
    const failed = image.getAttribute("src");
    if (failed !== genericImage) image.src = failed === localImage ? genericImage : localImage;
  };

  return (
    <img
      className={detail ? "product-image product-image--detail" : "product-image"}
      src={source}
      alt={name}
      width={380}
      height={342}
      loading={detail ? "eager" : "lazy"}
      fetchPriority={detail ? "high" : "auto"}
      referrerPolicy="no-referrer"
      ref={image => {
        if (image?.complete && image.naturalWidth === 0) showFallback(image);
      }}
      onError={({ currentTarget }) => showFallback(currentTarget)}
    />
  );
}
