<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;

class AdminController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/products",
     *     summary="Get all products with optional filters",
     *     tags={"Admin Products"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="min_price",
     *         in="query",
     *         description="Minimum price",
     *         required=false,
     *         @OA\Schema(type="number")
     *     ),
     *     @OA\Parameter(
     *         name="max_price",
     *         in="query",
     *         description="Maximum price",
     *         required=false,
     *         @OA\Schema(type="number")
     *     ),
     *     @OA\Parameter(
     *         name="in_stock",
     *         in="query",
     *         description="Filter by stock availability",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search term",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of products"
     *     )
     * )
     */
    public function getProducts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0|gt:min_price',
            'in_stock' => 'nullable|string|in:true,false,1,0',
            'search' => 'nullable|string|max:255',
        ], [
            'max_price.gt' => 'The max price must be greater than min price'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $query = Product::query();

        // Apply price range filter
        if ($request->has(['min_price', 'max_price'])) {
            $query->whereBetween('price', [
                $request->min_price,
                $request->max_price
            ]);
        } elseif ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        } elseif ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Apply stock availability filter
        if ($request->has('in_stock')) {
            $inStock = filter_var($request->in_stock, FILTER_VALIDATE_BOOLEAN);
            if ($inStock) {
                $query->where('stock_quantity', '>', 0);
            } else {
                $query->where('stock_quantity', '=', 0);
            }
        }

        // Apply search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $products = $query->get()->map(function($product) {
            $product->price = (float)$product->price;
            return $product;
        });
        return response()->json($products);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/products",
     *     summary="Create a new product",
     *     tags={"Admin Products"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "description", "price", "stock_quantity"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="price", type="number", format="float"),
     *             @OA\Property(property="stock_quantity", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Product created successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function createProduct(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'required|string',
                'price' => 'required|numeric|min:0.01',
                'stock_quantity' => 'required|integer|min:0',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        $product = Product::create($request->all());
        return response()->json($product, 201);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/products/{product}",
     *     summary="Update a product",
     *     tags={"Admin Products"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="product",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="price", type="number", format="float"),
     *             @OA\Property(property="stock_quantity", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product updated successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function updateProduct(Request $request, Product $product)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'description' => 'string',
            'price' => 'numeric|min:0',
            'stock_quantity' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $product->update($request->all());
        return response()->json($product);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/products/{product}",
     *     summary="Delete a product",
     *     tags={"Admin Products"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="product",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Product deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Cannot delete product with associated orders"
     *     )
     * )
     */
    public function deleteProduct(Product $product)
    {
        // Check if product has any orders
        if ($product->orderItems()->exists()) {
            return response()->json([
                'message' => 'Cannot delete product that has associated orders'
            ], 422);
        }

        $product->delete();
        return response()->json(null, 204);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/orders",
     *     summary="Get all orders with optional filters",
     *     tags={"Admin Orders"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pending", "processing", "completed", "cancelled"})
     *     ),
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="date_from",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="date_to",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of orders"
     *     )
     * )
     */
    public function getOrders(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|in:pending,processing,completed,cancelled',
            'user_id' => 'nullable|exists:users,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $query = Order::with(['user', 'orderItems.product']);

        // Apply status filter
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Apply user filter
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Apply date range filter
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->latest()->get();
        return response()->json($orders);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/orders/{order}/status",
     *     summary="Update order status",
     *     tags={"Admin Orders"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="order",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Status updated successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Invalid status transition"
     *     )
     * )
     */
    public function updateOrderStatus(Request $request, Order $order)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,processing,completed,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if (!$order->canUpdateStatus($request->status)) {
            return response()->json([
                'message' => 'Invalid status transition'
            ], 422);
        }

        if ($order->updateStatus($request->status)) {
            return response()->json($order->load('orderItems.product'));
        }

        return response()->json([
            'message' => 'Failed to update order status'
        ], 500);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/orders/statistics",
     *     summary="Get order statistics",
     *     tags={"Admin Orders"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Order statistics"
     *     )
     * )
     */
    public function getOrderStatistics()
    {
        $totalOrders = Order::count();
        $totalRevenue = Order::where('status', Order::STATUS_COMPLETED)->sum('total_price');
        $pendingOrders = Order::where('status', Order::STATUS_PENDING)->count();
        $processingOrders = Order::where('status', Order::STATUS_PROCESSING)->count();

        return response()->json([
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
            'pending_orders' => $pendingOrders,
            'processing_orders' => $processingOrders,
        ]);
    }
}
