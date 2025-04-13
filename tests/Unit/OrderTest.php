<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
            'status' => 'pending'
        ]);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals($user->id, $order->user_id);
        $this->assertEquals(100.00, $order->total_price);
        $this->assertEquals('pending', $order->status);
    }

    /** @test */
    public function it_has_a_user_relationship()
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
        $product = Product::factory()->create();
        
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 50.00
        ]);

        $this->assertInstanceOf(OrderItem::class, $order->orderItems->first());
        $this->assertEquals(2, $order->orderItems->first()->quantity);
        $this->assertEquals(50.00, $order->orderItems->first()->price);
    }

    /** @test */
    public function it_can_update_status()
    {
        $order = Order::factory()->create(['status' => 'pending']);
        
        $this->assertTrue($order->updateStatus('processing'));
        $this->assertEquals('processing', $order->fresh()->status);
        
        $this->assertTrue($order->updateStatus('completed'));
        $this->assertEquals('completed', $order->fresh()->status);
    }

    /** @test */
    public function it_can_check_if_order_can_be_cancelled()
    {
        $order = Order::factory()->create(['status' => 'pending']);
        $this->assertTrue($order->canBeCancelled());

        $order->updateStatus('processing');
        $this->assertFalse($order->canBeCancelled());

        $order->updateStatus('completed');
        $this->assertFalse($order->canBeCancelled());

        $order->updateStatus('cancelled');
        $this->assertFalse($order->canBeCancelled());
    }

    /** @test */
    public function it_can_check_if_status_can_be_updated()
    {
        $order = Order::factory()->create(['status' => 'pending']);
        
        $this->assertTrue($order->canUpdateStatus('processing'));
        $this->assertTrue($order->canUpdateStatus('cancelled'));
        $this->assertFalse($order->canUpdateStatus('completed'));
        
        $order->updateStatus('processing');
        $this->assertTrue($order->canUpdateStatus('completed'));
        $this->assertFalse($order->canUpdateStatus('cancelled'));
    }
} 