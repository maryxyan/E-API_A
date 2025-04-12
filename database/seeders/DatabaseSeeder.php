<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Create admin user
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        // Create customer user
        User::factory()->create([
            'name' => 'Customer User',
            'email' => 'customer@example.com',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        // Create sample products
        $products = [
            [
                'name' => 'Laptop',
                'description' => 'High-performance laptop with 16GB RAM and 512GB SSD',
                'price' => 999.99,
                'stock_quantity' => 10,
            ],
            [
                'name' => 'Smartphone',
                'description' => 'Latest smartphone with 5G capability',
                'price' => 699.99,
                'stock_quantity' => 15,
            ],
            [
                'name' => 'Headphones',
                'description' => 'Wireless noise-cancelling headphones',
                'price' => 199.99,
                'stock_quantity' => 20,
            ],
            [
                'name' => 'Smart Watch',
                'description' => 'Fitness tracker with heart rate monitor',
                'price' => 249.99,
                'stock_quantity' => 8,
            ],
            [
                'name' => 'Tablet',
                'description' => '10-inch tablet with stylus support',
                'price' => 449.99,
                'stock_quantity' => 12,
            ],
        ];

        foreach ($products as $product) {
            Product::factory()->create($product);
        }

        // Create sample orders
        $customer = User::where('email', 'customer@example.com')->first();
        $laptop = Product::where('name', 'Laptop')->first();
        $phone = Product::where('name', 'Smartphone')->first();

        // Create a pending order
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => Order::STATUS_PENDING,
            'total_price' => $laptop->price + $phone->price,
        ]);

        $order->orderItems()->createMany([
            [
                'product_id' => $laptop->id,
                'quantity' => 1,
                'price' => $laptop->price,
            ],
            [
                'product_id' => $phone->id,
                'quantity' => 1,
                'price' => $phone->price,
            ],
        ]);

        // Create a processing order
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => Order::STATUS_PROCESSING,
            'total_price' => $laptop->price * 2,
        ]);

        $order->orderItems()->create([
            'product_id' => $laptop->id,
            'quantity' => 2,
            'price' => $laptop->price,
        ]);

        // Create a completed order
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => Order::STATUS_COMPLETED,
            'total_price' => $phone->price * 3,
        ]);

        $order->orderItems()->create([
            'product_id' => $phone->id,
            'quantity' => 3,
            'price' => $phone->price,
        ]);
    }
}
