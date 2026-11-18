# API de E-commerce Segura con Swagger Completo

API RESTful para la gestión de un e-commerce básico: clientes, catálogo de productos y procesamiento de compras mediante **Stripe**. Construida con **Laravel 12**, autenticación **JWT**, base de datos **MySQL** y documentación **Swagger/OpenAPI**.

La guía del proyecto describe cómo iniciar la tienda, la API y MySQL juntos. [Instrucciones de arranque](../README.md#arrancar-el-proyecto-en-windows)

## Tecnologías

| Componente        | Tecnología                                   |
|--------------------|-----------------------------------------------|
| Framework          | Laravel 12 / PHP 8.2+                        |
| Base de datos      | MySQL 8.0 en Docker              |
| Autenticación      | JWT (`php-open-source-saver/jwt-auth`)       |
| Pagos              | Stripe (`stripe/stripe-php`)                 |
| Documentación API  | Swagger / OpenAPI (`darkaonline/l5-swagger`) |

## Características

- Registro y login de clientes con tokens JWT (login, refresh, logout, `me`).
- CRUD completo de productos (lectura pública, escritura protegida).
- Creación de órdenes de compra con control de stock (transacciones atómicas).
- Procesamiento de pagos con Stripe (PaymentIntents) y webhook de confirmación.
- Historial de compras por usuario.
- Validaciones con Form Requests y respuestas de error JSON consistentes.
- Documentación interactiva en `/api/documentation`.

## Estructura de la base de datos

- `users` — clientes registrados.
- `products` — catálogo de productos.
- `orders` — órdenes de compra (`pending`, `paid`, `cancelled`, `failed`).
- `order_items` — detalle de productos por orden.
- `payments` — registro de transacciones de Stripe (PaymentIntents).

## Ejecutar con Docker Compose

Prepara manualmente el `.env` junto a `docker-compose.yml`. La guía incluye un comando para cada clave y los pasos para levantar y probar la API. [Instrucciones paso a paso](../README.md#arrancar-el-proyecto-en-windows)

Laravel accede a `ecommerce_api` por `mysql:3306` con el usuario `ecommerce`. Compose le entrega `MYSQL_PASSWORD` como `DB_PASSWORD`. El servicio MySQL crea la base y el usuario al inicializar su volumen. PHP-FPM ejecuta las migraciones al arrancar.

Para incorporar imágenes desde otra copia del catálogo, importa el CSV por SKU. [Pasos de importación](../README.md#traer-las-imágenes-originales-de-phpmyadmin-o-xampp)

Puedes registrar un usuario desde la tienda o con `POST /api/auth/register`. En Swagger, usa el `data.access_token` que devuelve el registro o el inicio de sesión. [Abrir Swagger](http://localhost:8000/api/documentation)

## Flujo de uso típico

1. `POST /api/auth/register` o `POST /api/auth/login` → obtener `access_token`.
2. `GET /api/products` → explorar el catálogo (público).
3. `POST /api/orders` (con Bearer token) → crear una orden con uno o más productos.
4. `GET /api/orders` → consultar la orden pendiente y el historial del usuario.
5. Con el modo Stripe habilitado, `POST /api/payments/create-intent` crea el intento de pago y `POST /api/payments/{order}/confirm` consulta su estado.

## Principales endpoints

| Método | Ruta                              | Descripción                              | Auth |
|--------|-----------------------------------|-------------------------------------------|------|
| POST   | `/api/auth/register`              | Registrar cliente                         | No   |
| POST   | `/api/auth/login`                 | Iniciar sesión                            | No   |
| GET    | `/api/auth/me`                    | Usuario autenticado                       | Sí   |
| POST   | `/api/auth/refresh`               | Refrescar token                           | Sí   |
| POST   | `/api/auth/logout`                | Cerrar sesión                             | Sí   |
| GET    | `/api/products`                   | Listar productos                          | No   |
| GET    | `/api/products/{id}`              | Detalle de producto                       | No   |
| POST   | `/api/products`                   | Crear producto                            | Sí   |
| PUT    | `/api/products/{id}`              | Actualizar producto                       | Sí   |
| DELETE | `/api/products/{id}`              | Eliminar producto                         | Sí   |
| POST   | `/api/orders`                     | Crear orden de compra                     | Sí   |
| GET    | `/api/orders`                     | Historial de compras                      | Sí   |
| GET    | `/api/orders/{id}`                | Detalle de una orden                      | Sí   |
| POST   | `/api/payments/create-intent`     | Crear intento de pago (Stripe)            | Sí   |
| POST   | `/api/payments/{order}/confirm`   | Confirmar estado del pago                 | Sí   |
| POST   | `/api/payments/webhook`           | Webhook de Stripe                         | No*  |

\* Protegido mediante verificación de firma (`Stripe-Signature`), no por JWT.

Todos los endpoints y sus esquemas de request/response están documentados con ejemplos en Swagger UI (`/api/documentation`).

## Autenticación en Swagger UI

1. Ejecuta `POST /api/auth/login` desde Swagger UI para obtener el `access_token`.
2. Haz clic en **Authorize** (candado en la parte superior) y pega el valor de `data.access_token`.
3. A partir de ahí, todos los endpoints protegidos usarán ese token automáticamente.

## Pruebas con Stripe (modo test)

Usa las tarjetas de prueba oficiales de Stripe, por ejemplo:

- `4242 4242 4242 4242` — pago exitoso.
- `4000 0000 0000 9995` — fondos insuficientes.

Para probar el webhook localmente puedes usar la CLI de Stripe:

```bash
stripe listen --forward-to localhost:8000/api/payments/webhook
```

## Manejo de errores

Todas las respuestas de error siguen un formato consistente:

```json
{
  "success": false,
  "message": "Descripción del error.",
  "errors": { "campo": ["detalle de validación"] }
}
```

Códigos utilizados: `401` (no autenticado), `403` (sin permiso), `404` (no encontrado), `409` (conflicto, ej. orden ya pagada), `422` (validación / error de Stripe), `500` (error interno).

## Comandos útiles desde la raíz

```sh
docker compose exec backend php artisan migrate:status
docker compose exec backend php artisan route:list --path=api
docker compose logs --tail=80 backend
```

Para actualizar Swagger tras editar las anotaciones, reconstruye la imagen con `docker compose up -d --build --wait`.

## Estructura del proyecto

```
app/
  Http/
    Controllers/Api/   -> AuthController, ProductController, OrderController, PaymentController
    Requests/           -> Form Requests con validaciones
  Models/               -> User, Product, Order, OrderItem, Payment
  Services/             -> StripeService (encapsula la integración con Stripe)
bootstrap/app.php        -> Middleware, rutas y manejo global de excepciones JSON
config/                  -> auth.php, jwt.php, l5-swagger.php, services.php, cors.php...
database/
  migrations/            -> esquema inicial y ampliación de image_url
  seeders/                -> UserSeeder, ProductSeeder
  factories/              -> UserFactory, ProductFactory
routes/api.php            -> Definición de todos los endpoints
```

## Licencia

MIT
