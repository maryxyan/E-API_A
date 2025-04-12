<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customer = User::factory()->create(['role' => 'customer']);
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    /** @test */
    public function customer_can_create_order_with_multiple_products()
    {
        Sanctum::actingAs($this->customer);

        $product1 = Product::factory()->create([
            'stock_quantity' => 5,
            'price' => 100.00
        ]);
        $product2 = Product::factory()->create([
            'stock_quantity' => 3,
            'price' => 50.00
        ]);

        $response = $this->postJson('/api/customer/orders', [
            'items' => [
                ['product_id' => $product1->id, 'quantity' => 2],
                ['product_id' => $product2->id, 'quantity' => 1],
            ],
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
                        'price',
                    ],
                ],
            ]);

        $this->assertEquals(250.00, $response->json('total_price'));
        $this->assertEquals(Order::STATUS_PENDING, $response->json('status'));

        // Check stock updates
        $this->assertEquals(3, $product1->fresh()->stock_quantity);
        $this->assertEquals(2, $product2->fresh()->stock_quantity);
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
    public function customer_cannot_create_order_with_insufficient_stock()
    {
        Sanctum::actingAs($this->customer);
        $product = Product::factory()->create(['stock_quantity' => 2]);

        $response = $this->postJson('/api/customer/orders', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => "Insufficient stock for product: {$product->name}"
            ]);

        // Check stock remains unchanged
        $this->assertEquals(2, $product->fresh()->stock_quantity);
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
    public function admin_can_update_order_status()
    {
        Sanctum::actingAs($this->admin);
        $order = Order::factory()->create(['status' => Order::STATUS_PENDING]);

        $response = $this->putJson("/api/admin/orders/{$order->id}/status", [
            'status' => Order::STATUS_PROCESSING,
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => Order::STATUS_PROCESSING]);
    }

    /** @test */
    public function admin_cannot_make_invalid_status_transition()
    {
        Sanctum::actingAs($this->admin);
        $order = Order::factory()->create(['status' => Order::STATUS_PENDING]);

        $response = $this->putJson("/api/admin/orders/{$order->id}/status", [
            'status' => Order::STATUS_COMPLETED,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Invalid status transition'
            ]);
    }

    /** @test */
    public function customer_can_cancel_pending_order()
    {
        Sanctum::actingAs($this->customer);
        $product = Product::factory()->create(['stock_quantity' => 5]);
        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => Order::STATUS_PENDING,
        ]);
        $order->orderItems()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => $product->price,
        ]);
        $product->decreaseStock(2);

        $response = $this->postJson("/api/customer/orders/{$order->id}/cancel");

        $response->assertStatus(200)
            ->assertJson(['status' => Order::STATUS_CANCELLED]);

        // Check stock is restored
        $this->assertEquals(5, $product->fresh()->stock_quantity);
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