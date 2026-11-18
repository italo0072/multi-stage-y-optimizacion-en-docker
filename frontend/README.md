# Frontend E-commerce (Next.js 16 + Laravel API)

Next.js App Router + TypeScript. Lecturas con Server Components, mutaciones con Server Actions, token en cookie httpOnly.

Este frontend consume la API de e-commerce desarrollada durante el bootcamp. [Repositorio de la API original](https://github.com/italo0072/API-E-commerce-con-Swagger)

## Ejecutar con Docker Compose

Prepara manualmente el `.env` de la raíz, valida la configuración y arranca el proyecto siguiendo los comandos en orden. [Instrucciones paso a paso](../README.md#arrancar-el-proyecto-en-windows)

El catálogo y el detalle leen `image_url` de la API. Las fotos locales de `public/imagenes/productos/` sirven de respaldo cuando una URL falta o falla. [Importar las URLs originales](../README.md#traer-las-imágenes-originales-de-phpmyadmin-o-xampp)

Next.js consulta la API en `http://nginx:8000/api` desde el servidor. Para ver el catálogo en el navegador, abre la tienda. [Tienda local](http://localhost:3000)

| Variable | Uso |
| --- | --- |
| `API_BASE_URL` | Dirección interna de Nginx que Compose entrega al servidor Next.js. |
| `CHECKOUT_MODE` | `order` registra la orden pendiente. `stripe` habilita el pago de prueba. |
| `STRIPE_TEST_PM` | Método de pago de prueba usado al crear el PaymentIntent. |

## Endpoints de la API que consume
| Uso | Endpoint |
|---|---|
| Registro | POST /auth/register (name, email, password, password_confirmation) |
| Login | POST /auth/login → `data.access_token` (JWT) |
| Catalogo | GET /products, GET /products/{id} |
| Crear orden | POST /orders (items: product_id, quantity) |
| Pagar | POST /payments/create-intent y POST /payments/{order}/confirm |
| Historial | GET /orders |

Registra un cliente en `/register` para probar el carrito y el historial de órdenes.

## Rutas
| Ruta | Descripcion |
|---|---|
| / | Catalogo (Suspense) |
| /products/[id] | Detalle |
| /login, /register | Autenticacion |
| /checkout | Carrito, crear orden y pagar (protegida) |
| /checkout/success | Confirmacion |
| /orders | Historial (protegida, Suspense) |

## Decisiones
- Cookie httpOnly (`src/lib/session.ts`) y proteccion con `src/proxy.ts`.
- `revalidatePath` tras crear/pagar orden.
- `loading.tsx` y `error.tsx` en raiz, checkout y orders.
- El checkout registra la orden antes de iniciar el pago opcional con Stripe.
