<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="API de E-commerce Segura",
 *     version="1.0.0",
 *     description="API RESTful para gestion de clientes, catalogo de productos y procesamiento de compras con Stripe. Construida con Laravel 12, autenticacion JWT y documentada con Swagger/OpenAPI.",
 *     @OA\Contact(email="soporte@ecommerce-api.test")
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="Servidor de la API"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Introducir el token JWT obtenido en /api/auth/login o /api/auth/register."
 * )
 *
 * @OA\Tag(name="Autenticacion", description="Registro, login y gestion de sesion de clientes")
 * @OA\Tag(name="Productos", description="Catalogo de productos (CRUD)")
 * @OA\Tag(name="Ordenes", description="Creacion y consulta de ordenes de compra")
 * @OA\Tag(name="Pagos", description="Procesamiento de pagos con Stripe")
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Ha ocurrido un error."),
 *     @OA\Property(property="errors", type="object", nullable=true)
 * )
 */
abstract class Controller
{
}
