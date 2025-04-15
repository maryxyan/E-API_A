<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all users and products
        $users = User::all();
        $products = Product::all();

        if ($users->isEmpty() || $products->isEmpty()) {
            $this->command->info('No users or products found. Please run UserSeeder and ProductSeeder first.');
            return;
        }

        // Create 3-5 orders for each user
        foreach ($users as $user) {
            $orderCount = rand(3, 5);
            
            for ($i = 0; $i < $orderCount; $i++) {
                // Start a database transaction
                DB::transaction(function () use ($user, $products) {
                    // Create the order
                    $order = Order::factory()->create([
                        'user_id' => $user->id,
                        'status' => $this->getRandomStatus(),
                        'total_price' => 0, // Will be calculated based on order items
                    ]);

                    // Add 1-3 random products to the order
                    $itemsCount = rand(1, 3);
                    $selectedProducts = $products->random($itemsCount);
                    $totalPrice = 0;

                    foreach ($selectedProducts as $product) {
                        $quantity = rand(1, 3);
                        $itemPrice = $product->price * $quantity;
                        $totalPrice += $itemPrice;

                        // Create order item
                        OrderItem::create([
                            'order_id' => $order->id,
                            'product_id' => $product->id,
                            'quantity' => $quantity,
                            'price' => $product->price,
                        ]);
                    }

                    // Update the order's total price
                    $order->update(['total_price' => $totalPrice]);
                });
            }
        }

        $this->command->info('Created orders for all users successfully.');
    }

    /**
     * Get a random order status.
     */
    private function getRandomStatus(): string
    {
        $statuses = [
            Order::STATUS_PENDING,
            Order::STATUS_PROCESSING,
            Order::STATUS_COMPLETED,
            Order::STATUS_CANCELLED,
        ];

        return $statuses[array_rand($statuses)];
    }
} 