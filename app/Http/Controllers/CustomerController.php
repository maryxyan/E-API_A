<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    /**
     * Get all available products with optional search and filters.
     */
    public function getProducts(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $validator = Validator::make($request->all(), [
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'search' => 'nullable|string|max:255',
            'in_stock' => 'nullable|in:true,false,1,0',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $query = Product::query();

            // Apply in_stock filter
            if ($request->has('in_stock')) {
                $query->where('stock_quantity', '>', 0);
            }

            // Apply price range filter
            if ($request->has('min_price') && $request->has('max_price')) {
                $query->priceRange($request->min_price, $request->max_price);
            }

            // Apply search
            if ($request->has('search')) {
                $query->search($request->search);
            }

            $products = $query->get();
            return response()->json($products);
        } catch (\Exception $e) {
            \Log::error('Error fetching products: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred while fetching products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific product.
     */
    public function getProduct(Product $product)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        try {
            if (!$product->isInStock()) {
                return response()->json([
                    'message' => 'Product is out of stock'
                ], 404);
            }

            return response()->json($product);
        } catch (\Exception $e) {
            \Log::error('Error fetching product: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred while fetching the product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customer's orders with optional filters.
     */
    public function getOrders(Request $request)
    {
        try {
            if (!Auth::check()) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'nullable|in:pending,processing,completed,cancelled',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            $query = Auth::user()->orders()->with('orderItems.product');

            // Apply status filter
            if ($request->has('status')) {
                $query->where('status', $request->status);
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
        } catch (\Exception $e) {
            \Log::error('Error fetching orders: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'message' => 'An error occurred while fetching orders',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * Get a specific order.
     */
    public function getOrder(Order $order)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if ($order->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($order->load('orderItems.product'));
    }

    /**
     * Create a new order.
     */
    public function createOrder(Request $request)
    {
        try {
            if (!Auth::check()) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            // Validate request data
            $validator = Validator::make($request->all(), [
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Start transaction
            return DB::transaction(function () use ($request) {
                $order = Order::createWithItems($request->items, Auth::id());
                return response()->json($order->load('orderItems'), 201);
            });

        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'Insufficient stock')) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            \Log::error('Error creating order: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred while creating the order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel an order.
     */
    public function cancelOrder(Order $order)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if ($order->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$order->canBeCancelled()) {
            return response()->json([
                'message' => 'Only pending orders can be cancelled'
            ], 422);
        }

        // Start transaction
        return DB::transaction(function () use ($order) {
            // Restore stock
            foreach ($order->orderItems as $item) {
                $item->product->increaseStock($item->quantity);
            }

            $order->updateStatus(Order::STATUS_CANCELLED);
            return response()->json($order->load('orderItems.product'));
        });
    }
}
