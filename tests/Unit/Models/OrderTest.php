<?php

namespace Tests\Unit\Models;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_an_order()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'total_price' => 100.00,
            'status' => Order::STATUS_PENDING,
        ]);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals($user->id, $order->user_id);
        $this->assertEquals(100.00, $order->total_price);
        $this->assertEquals(Order::STATUS_PENDING, $order->status);
    }

    /** @test */
    public function it_has_user_relationship()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $order->user);
        $this->assertEquals($user->id, $order->user->id);
    }

    /** @test */
    public function it_has_order_items_relationship()
    {
        $order = Order::factory()->create();
        OrderItem::factory()->create(['order_id' => $order->id]);

        $this->assertInstanceOf(OrderItem::class, $order->orderItems->first());
    }

    /** @test */
    public function it_can_calculate_total_price()
    {
        $order = Order::factory()->create();
        $product1 = Product::factory()->create(['price' => 50.00]);
        $product2 = Product::factory()->create(['price' => 30.00]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product1->id,
            'quantity' => 2,
            'price' => $product1->price,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product2->id,
            'quantity' => 1,
            'price' => $product2->price,
        ]);

        $this->assertEquals(130.00, $order->calculateTotalPrice());
    }

    /** @test */
    public function it_can_check_if_order_can_be_cancelled()
    {
        $order = Order::factory()->create(['status' => Order::STATUS_PENDING]);
        $this->assertTrue($order->canBeCancelled());

        $order->update(['status' => Order::STATUS_PROCESSING]);
        $this->assertFalse($order->canBeCancelled());
    }

    /** @test */
    public function it_can_check_valid_status_transitions()
    {
        $order = Order::factory()->create(['status' => Order::STATUS_PENDING]);
        
        $this->assertTrue($order->canUpdateStatus(Order::STATUS_PROCESSING));
        $this->assertTrue($order->canUpdateStatus(Order::STATUS_CANCELLED));
        $this->assertFalse($order->canUpdateStatus(Order::STATUS_COMPLETED));

        $order->update(['status' => Order::STATUS_PROCESSING]);
        $this->assertTrue($order->canUpdateStatus(Order::STATUS_COMPLETED));
        $this->assertFalse($order->canUpdateStatus(Order::STATUS_CANCELLED));
        $this->assertFalse($order->canUpdateStatus(Order::STATUS_PENDING));

        $order->update(['status' => Order::STATUS_COMPLETED]);
        $this->assertFalse($order->canUpdateStatus(Order::STATUS_PENDING));
        $this->assertFalse($order->canUpdateStatus(Order::STATUS_PROCESSING));
        $this->assertFalse($order->canUpdateStatus(Order::STATUS_CANCELLED));
    }

    /** @test */
    public function it_can_update_status()
    {
        // Test valid transitions from PENDING
        $order = Order::factory()->create(['status' => Order::STATUS_PENDING]);
        
        // Test valid transition to PROCESSING
        $this->assertTrue($order->updateStatus(Order::STATUS_PROCESSING));
        $this->assertEquals(Order::STATUS_PROCESSING, $order->fresh()->status);
        
        // Test valid transition to CANCELLED
        $order->update(['status' => Order::STATUS_PENDING]);
        $this->assertTrue($order->updateStatus(Order::STATUS_CANCELLED));
        $this->assertEquals(Order::STATUS_CANCELLED, $order->fresh()->status);

        // Test invalid transitions from PENDING
        $order->update(['status' => Order::STATUS_PENDING]);
        $this->assertFalse($order->updateStatus(Order::STATUS_COMPLETED));
        $this->assertEquals(Order::STATUS_PENDING, $order->fresh()->status);

        // Test transitions from PROCESSING
        $order->update(['status' => Order::STATUS_PROCESSING]);
        
        // Test valid transition to COMPLETED
        $this->assertTrue($order->updateStatus(Order::STATUS_COMPLETED));
        $this->assertEquals(Order::STATUS_COMPLETED, $order->fresh()->status);
        
        // Test invalid transition to CANCELLED
        $order->update(['status' => Order::STATUS_PROCESSING]);
        $this->assertFalse($order->updateStatus(Order::STATUS_CANCELLED));
        $this->assertEquals(Order::STATUS_PROCESSING, $order->fresh()->status);

        // Test invalid transitions from PROCESSING
        $order->update(['status' => Order::STATUS_PROCESSING]);
        $this->assertFalse($order->updateStatus(Order::STATUS_PENDING));
        $this->assertEquals(Order::STATUS_PROCESSING, $order->fresh()->status);

        // Test transitions from COMPLETED
        $order->update(['status' => Order::STATUS_COMPLETED]);
        
        // Test that no transitions are allowed from COMPLETED
        $this->assertFalse($order->updateStatus(Order::STATUS_PENDING));
        $this->assertFalse($order->updateStatus(Order::STATUS_PROCESSING));
        $this->assertFalse($order->updateStatus(Order::STATUS_CANCELLED));
        $this->assertEquals(Order::STATUS_COMPLETED, $order->fresh()->status);

        // Test transitions from CANCELLED
        $order->update(['status' => Order::STATUS_CANCELLED]);
        
        // Test that no transitions are allowed from CANCELLED
        $this->assertFalse($order->updateStatus(Order::STATUS_PENDING));
        $this->assertFalse($order->updateStatus(Order::STATUS_PROCESSING));
        $this->assertFalse($order->updateStatus(Order::STATUS_COMPLETED));
        $this->assertEquals(Order::STATUS_CANCELLED, $order->fresh()->status);
    }

    /** @test */
    public function order_creation_validates_required_fields()
    {
        $order = new Order();
        $this->assertFalse($order->validate());

        $requiredFields = ['user_id', 'total_price', 'status'];
        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $order->errors());
        }
    }

    /** @test */
    public function order_creation_validates_product_existence()
    {
        $order = Order::factory()->make();
        $invalidProduct = OrderItem::factory()->make(['product_id' => 999]);
        $order->orderItems = collect([$invalidProduct]);

        $this->assertFalse($order->validate());
        $this->assertArrayHasKey('orderItems.0.product_id', $order->errors());
    }

    /** @test */
    public function order_creation_validates_quantity()
    {
        $order = Order::factory()->make();
        $invalidItem = OrderItem::factory()->make(['quantity' => 0]);
        $order->orderItems = collect([$invalidItem]);

        $this->assertFalse($order->validate());
        $this->assertArrayHasKey('orderItems.0.quantity', $order->errors());
    }
}
