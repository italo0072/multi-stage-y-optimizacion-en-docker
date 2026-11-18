<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/auth/register",
     *     tags={"Autenticacion"},
     *     summary="Registrar un nuevo cliente",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password","password_confirmation"},
     *             @OA\Property(property="name", type="string", example="Juan Perez"),
     *             @OA\Property(property="email", type="string", format="email", example="juan@example.com"),
     *             @OA\Property(property="phone", type="string", example="+503 7000-0000"),
     *             @OA\Property(property="password", type="string", format="password", example="secreto123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="secreto123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Cliente registrado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Registro exitoso."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/User"),
     *                 @OA\Property(property="access_token", type="string"),
     *                 @OA\Property(property="token_type", type="string", example="bearer"),
     *                 @OA\Property(property="expires_in", type="integer", example=3600)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Error de validacion", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'phone' => $request->validated('phone'),
            'password' => Hash::make($request->validated('password')),
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'success' => true,
            'message' => 'Registro exitoso.',
            'data' => $this->respondWithToken($token, $user),
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/auth/login",
     *     tags={"Autenticacion"},
     *     summary="Iniciar sesion y obtener token JWT",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="juan@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="secreto123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login exitoso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Login exitoso."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/User"),
     *                 @OA\Property(property="access_token", type="string"),
     *                 @OA\Property(property="token_type", type="string", example="bearer"),
     *                 @OA\Property(property="expires_in", type="integer", example=3600)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Credenciales invalidas", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="Error de validacion", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        if (! $token = Auth::guard('api')->attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales invalidas.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Login exitoso.',
            'data' => $this->respondWithToken($token, Auth::guard('api')->user()),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/auth/me",
     *     tags={"Autenticacion"},
     *     summary="Obtener datos del usuario autenticado",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Datos del usuario", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="data", ref="#/components/schemas/User")
     *     )),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function me()
    {
        return response()->json([
            'success' => true,
            'data' => Auth::guard('api')->user(),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/auth/refresh",
     *     tags={"Autenticacion"},
     *     summary="Refrescar el token JWT",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Token refrescado", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="token_type", type="string", example="bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=3600)
     *         )
     *     )),
     *     @OA\Response(response=401, description="Token invalido o expirado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function refresh()
    {
        $token = Auth::guard('api')->refresh();

        return response()->json([
            'success' => true,
            'data' => $this->respondWithToken($token, Auth::guard('api')->user(), false),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/auth/logout",
     *     tags={"Autenticacion"},
     *     summary="Cerrar sesion (invalidar token)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Sesion cerrada", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="message", type="string", example="Sesion cerrada correctamente.")
     *     )),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function logout()
    {
        Auth::guard('api')->logout();

        return response()->json([
            'success' => true,
            'message' => 'Sesion cerrada correctamente.',
        ]);
    }

    protected function respondWithToken(string $token, User $user, bool $includeUser = true): array
    {
        $data = [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
        ];

        if ($includeUser) {
            $data = ['user' => $user] + $data;
        }

        return $data;
    }
}
