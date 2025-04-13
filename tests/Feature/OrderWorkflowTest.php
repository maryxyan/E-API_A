<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->customer = User::factory()->create(['role' => 'customer']);
        $this->admin = User::factory()->create(['role' => 'admin']);
        
        // Create test products
        $this->product1 = Product::factory()->create([
            'name' => 'Product 1',
            'price' => 100.00,
            'stock_quantity' => 10
        ]);
        
        $this->product2 = Product::factory()->create([
            'name' => 'Product 2',
            'price' => 200.00,
            'stock_quantity' => 5
        ]);
    }

    /** @test */
    public function customer_can_create_an_order()
    {
        Sanctum::actingAs($this->customer);

        $response = $this->postJson('/api/customer/orders', [
            'items' => [
                [
                    'product_id' => $this->product1->id,
                    'quantity' => 2
                ],
                [
                    'product_id' => $this->product2->id,
                    'quantity' => 1
                ]
            ]
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'user_id',
                'total_price',
                'status',
                'order_items' => [
                    '*' => [
                        'id',
                        'product_id',
                        'quantity',
                        'price'
                    ]
                ]
            ]);

        $this->assertEquals(400.00, $response->json('total_price'));
        $this->assertEquals('pending', $response->json('status'));
        
        // Verify stock was reduced
        $this->assertEquals(8, $this->product1->fresh()->stock_quantity);
        $this->assertEquals(4, $this->product2->fresh()->stock_quantity);
    }

    /** @test */
    public function customer_cannot_create_order_with_insufficient_stock()
    {
        Sanctum::actingAs($this->customer);

        $response = $this->postJson('/api/customer/orders', [
            'items' => [
                [
                    'product_id' => $this->product1->id,
                    'quantity' => 15
                ]
            ]
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Insufficient stock for product: Product 1'
            ]);

        // Verify stock was not reduced
        $this->assertEquals(10, $this->product1->fresh()->stock_quantity);
    }

    /** @test */
    public function customer_can_cancel_pending_order()
    {
        Sanctum::actingAs($this->customer);

        // Create an order
        $order = $this->customer->orders()->create([
            'total_price' => 100.00,
            'status' => 'pending'
        ]);

        $order->orderItems()->create([
            'product_id' => $this->product1->id,
            'quantity' => 2,
            'price' => 50.00
        ]);

        $this->product1->decreaseStock(2);

        // Cancel the order
        $response = $this->postJson("/api/customer/orders/{$order->id}/cancel");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'cancelled'
            ]);

        // Verify stock was restored
        $this->assertEquals(10, $this->product1->fresh()->stock_quantity);
    }

    /** @test */
    public function admin_can_update_order_status()
    {
        Sanctum::actingAs($this->admin);

        // Create an order
        $order = $this->customer->orders()->create([
            'total_price' => 100.00,
            'status' => 'pending'
        ]);

        // Update status to processing
        $response = $this->putJson("/api/admin/orders/{$order->id}/status", [
            'status' => 'processing'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'processing'
            ]);

        // Update status to completed
        $response = $this->putJson("/api/admin/orders/{$order->id}/status", [
            'status' => 'completed'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'completed'
            ]);
    }

    /** @test */
    public function customer_cannot_update_order_status()
    {
        Sanctum::actingAs($this->customer);

        // Create an order
        $order = $this->customer->orders()->create([
            'total_price' => 100.00,
            'status' => 'pending'
        ]);

        $response = $this->putJson("/api/admin/orders/{$order->id}/status", [
            'status' => 'processing'
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function customer_can_view_their_orders()
    {
        Sanctum::actingAs($this->customer);

        $order = Order::factory()->create(['user_id' => $this->customer->id]);
        $otherOrder = Order::factory()->create();

        $response = $this->getJson('/api/customer/orders');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment(['id' => $order->id])
            ->assertJsonMissing(['id' => $otherOrder->id]);
    }

    /** @test */
    public function order_creation_validates_required_fields()
    {
        Sanctum::actingAs($this->customer);

        $response = $this->postJson('/api/customer/orders', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    /** @test */
    public function order_creation_validates_product_existence()
    {
        Sanctum::actingAs($this->customer);

        $response = $this->postJson('/api/customer/orders', [
            'items' => [
                ['product_id' => 999, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.product_id']);
    }

    /** @test */
    public function order_creation_validates_quantity()
    {
        Sanctum::actingAs($this->customer);
        $product = Product::factory()->create(['stock_quantity' => 5]);

        $response = $this->postJson('/api/customer/orders', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 0],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.quantity']);
    }

    /** @test */
    public function customer_cannot_cancel_non_pending_order()
    {
        Sanctum::actingAs($this->customer);
        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => Order::STATUS_PROCESSING,
        ]);

        $response = $this->postJson("/api/customer/orders/{$order->id}/cancel");

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Only pending orders can be cancelled'
            ]);
    }
} 