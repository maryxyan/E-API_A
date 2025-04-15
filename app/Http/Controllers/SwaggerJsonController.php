<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OpenApi\Annotations as OA;
use OpenApi\Generator;

/**
 * @OA\OpenApi(
 *     @OA\Info(
 *         title="E-Commerce API",
 *         version="1.0.0",
 *         description="API documentation for the E-Commerce platform"
 *     ),
 *     @OA\Server(
 *         url="http://localhost:8000",
 *         description="API Server"
 *     ),
 *     @OA\SecurityScheme(
 *         securityScheme="bearerAuth",
 *         type="http",
 *         scheme="bearer",
 *         bearerFormat="JWT"
 *     )
 * )
 */

/**
 * @OA\PathItem(
 *     path="/api/customer/orders",
 *     @OA\Post(
 *         summary="Create a new order",
 *         tags={"Orders"},
 *         security={{"bearerAuth": {}}},
 *         @OA\RequestBody(
 *             required=true,
 *             @OA\JsonContent(
 *                 required={"items"},
 *                 @OA\Property(
 *                     property="items",
 *                     type="array",
 *                     @OA\Items(
 *                         required={"product_id", "quantity"},
 *                         @OA\Property(property="product_id", type="integer"),
 *                         @OA\Property(property="quantity", type="integer", minimum=1)
 *                     )
 *                 )
 *             )
 *         ),
 *         @OA\Response(
 *             response=201,
 *             description="Order created successfully"
 *         ),
 *         @OA\Response(
 *             response=422,
 *             description="Validation error"
 *         )
 *     ),
 *     @OA\Get(
 *         summary="Get customer orders",
 *         tags={"Orders"},
 *         security={{"bearerAuth": {}}},
 *         @OA\Parameter(
 *             name="status",
 *             in="query",
 *             description="Filter by status",
 *             required=false,
 *             @OA\Schema(type="string")
 *         ),
 *         @OA\Parameter(
 *             name="date_from",
 *             in="query",
 *             description="Filter by start date",
 *             required=false,
 *             @OA\Schema(type="string", format="date")
 *         ),
 *         @OA\Parameter(
 *             name="date_to",
 *             in="query",
 *             description="Filter by end date",
 *             required=false,
 *             @OA\Schema(type="string", format="date")
 *         ),
 *         @OA\Response(
 *             response=200,
 *             description="Successful operation"
 *         )
 *     )
 * )
 */

/**
 * @OA\PathItem(
 *     path="/api/admin/orders/{order}/status",
 *     @OA\Put(
 *         summary="Update order status",
 *         tags={"Orders"},
 *         security={{"bearerAuth": {}}},
 *         @OA\Parameter(
 *             name="order",
 *             in="path",
 *             required=true,
 *             @OA\Schema(type="integer")
 *         ),
 *         @OA\RequestBody(
 *             required=true,
 *             @OA\JsonContent(
 *                 required={"status"},
 *                 @OA\Property(property="status", type="string")
 *             )
 *         ),
 *         @OA\Response(
 *             response=200,
 *             description="Status updated successfully"
 *         ),
 *         @OA\Response(
 *             response=422,
 *             description="Invalid status transition"
 *         )
 *     )
 * )
 */

/**
 * @OA\PathItem(
 *     path="/api/register",
 *     @OA\Post(
 *         summary="Register a new user",
 *         tags={"Authentication"},
 *         @OA\RequestBody(
 *             required=true,
 *             @OA\JsonContent(
 *                 required={"name", "email", "password", "password_confirmation", "role"},
 *                 @OA\Property(property="name", type="string"),
 *                 @OA\Property(property="email", type="string", format="email"),
 *                 @OA\Property(property="password", type="string", format="password"),
 *                 @OA\Property(property="password_confirmation", type="string", format="password"),
 *                 @OA\Property(property="role", type="string", enum={"admin", "customer"})
 *             )
 *         ),
 *         @OA\Response(
 *             response=201,
 *             description="User registered successfully"
 *         ),
 *         @OA\Response(
 *             response=422,
 *             description="Validation error"
 *         )
 *     )
 * )
 */

/**
 * @OA\PathItem(
 *     path="/api/login",
 *     @OA\Post(
 *         summary="Login user",
 *         tags={"Authentication"},
 *         @OA\RequestBody(
 *             required=true,
 *             @OA\JsonContent(
 *                 required={"email", "password"},
 *                 @OA\Property(property="email", type="string", format="email"),
 *                 @OA\Property(property="password", type="string", format="password")
 *             )
 *         ),
 *         @OA\Response(
 *             response=200,
 *             description="Login successful"
 *         ),
 *         @OA\Response(
 *             response=401,
 *             description="Invalid credentials"
 *         )
 *     )
 * )
 */

/**
 * @OA\PathItem(
 *     path="/api/logout",
 *     @OA\Post(
 *         summary="Logout user",
 *         tags={"Authentication"},
 *         security={{"bearerAuth": {}}},
 *         @OA\Response(
 *             response=200,
 *             description="Logged out successfully"
 *         )
 *     )
 * )
 */
class SwaggerJsonController extends Controller
{
    public function index()
    {
        try {
            $openapi = Generator::scan([app_path()]);
            return response()->json($openapi);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate OpenAPI documentation',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}
