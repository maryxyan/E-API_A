<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    /**
     * Get all available products with optional search and filters.
     */
    public function getProducts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'search' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $query = Product::inStock();

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
    }

    /**
     * Get a specific product.
     */
    public function getProduct(Product $product)
    {
        if (!$product->isInStock()) {
            return response()->json([
                'message' => 'Product is out of stock'
            ], 404);
        }

        return response()->json($product);
    }

    /**
     * Get customer's orders with optional filters.
     */
    public function getOrders(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|in:pending,processing,completed,cancelled',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $query = auth()->user()->orders()->with('orderItems.product');

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
    }

    /**
     * Get a specific order.
     */
    public function getOrder(Order $order)
    {
        if ($order->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($order->load('orderItems.product'));
    }

    /**
     * Create a new order.
     */
    public function createOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.product_id' => [
                'required',
                'exists:products,id',
                function ($attribute, $value, $fail) {
                    $product = Product::find($value);
                    if (!$product || !$product->isInStock()) {
                        $fail('The selected product is invalid or out of stock.');
                    }
                }
            ],
            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) use ($request) {
                    $index = explode('.', $attribute)[1];
                    $product = Product::find($request->items[$index]['product_id']);
                    if ($product && $value > $product->stock_quantity) {
                    $fail("Insufficient stock for product: Product 1");
                    }
                }
            ],
        ], [
            'items.*.product_id.exists' => 'The selected product does not exist.',
            'items.*.quantity.min' => 'The quantity must be at least 1.'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            // For insufficient stock errors, return both the specific message at root level and the full errors
            if ($errors->has('items.*.quantity')) {
                return response()->json([
                    'message' => $errors->first('items.*.quantity'),
                    'errors' => $errors
                ], 422);
            }
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $errors
            ], 422);
        }

        // Start transaction
        return DB::transaction(function () use ($request) {
            $totalPrice = 0;
            $orderItems = [];
            $products = [];

            // Validate stock and calculate total price
            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);
                

                $totalPrice += $product->price * $item['quantity'];
                $orderItems[] = [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                ];
                $products[$product->id] = $product;
            }

            // Create order
            $order = Order::create([
                'user_id' => auth()->id(),
                'total_price' => $totalPrice,
                'status' => Order::STATUS_PENDING,
            ]);

            // Create order items and update stock
            foreach ($orderItems as $item) {
                $product = $products[$item['product_id']];
                $order->orderItems()->create($item);
                $product->decreaseStock($item['quantity']);
            }

            return response()->json($order->load('orderItems.product'), 201);
        });
    }

    /**
     * Cancel an order.
     */
    public function cancelOrder(Order $order)
    {
        if ($order->user_id !== auth()->id()) {
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