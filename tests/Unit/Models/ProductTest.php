<?php

namespace Tests\Unit\Models;

use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_product()
    {
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'price' => 100.00,
            'stock_quantity' => 10,
        ]);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals('Test Product', $product->name);
        $this->assertEquals(100.00, $product->price);
        $this->assertEquals(10, $product->stock_quantity);
    }

    /** @test */
    public function it_can_check_if_product_is_in_stock()
    {
        $product = Product::factory()->create(['stock_quantity' => 5]);
        $this->assertTrue($product->isInStock());

        $product->update(['stock_quantity' => 0]);
        $this->assertFalse($product->isInStock());
    }

    /** @test */
    public function it_can_check_sufficient_stock()
    {
        $product = Product::factory()->create(['stock_quantity' => 5]);
        $this->assertTrue($product->hasSufficientStock(3));
        $this->assertFalse($product->hasSufficientStock(6));
    }

    /** @test */
    public function it_can_decrease_stock()
    {
        $product = Product::factory()->create(['stock_quantity' => 5]);
        
        $this->assertTrue($product->decreaseStock(3));
        $this->assertEquals(2, $product->fresh()->stock_quantity);

        $this->assertFalse($product->decreaseStock(3));
        $this->assertEquals(2, $product->fresh()->stock_quantity);
    }

    /** @test */
    public function it_can_increase_stock()
    {
        $product = Product::factory()->create(['stock_quantity' => 5]);
        
        $product->increaseStock(3);
        $this->assertEquals(8, $product->fresh()->stock_quantity);
    }

    /** @test */
    public function it_has_order_items_relationship()
    {
        $product = Product::factory()->create();
        OrderItem::factory()->create(['product_id' => $product->id]);

        $this->assertInstanceOf(OrderItem::class, $product->orderItems->first());
    }

    /** @test */
    public function it_can_scope_in_stock_products()
    {
        Product::factory()->create(['stock_quantity' => 5]);
        Product::factory()->create(['stock_quantity' => 0]);

        $inStockProducts = Product::inStock()->get();
        $this->assertCount(1, $inStockProducts);
        $this->assertEquals(5, $inStockProducts->first()->stock_quantity);
    }

    /** @test */
    public function it_can_scope_products_by_price_range()
    {
        Product::factory()->create(['price' => 50.00]);
        Product::factory()->create(['price' => 100.00]);
        Product::factory()->create(['price' => 150.00]);

        $filteredProducts = Product::priceRange(75.00, 125.00)->get();
        $this->assertCount(1, $filteredProducts);
        $this->assertEquals(100.00, $filteredProducts->first()->price);
    }

    /** @test */
    public function it_can_scope_products_by_search()
    {
        Product::factory()->create(['name' => 'Laptop', 'description' => 'High performance']);
        Product::factory()->create(['name' => 'Phone', 'description' => 'Smart device']);

        $searchResults = Product::search('laptop')->get();
        $this->assertCount(1, $searchResults);
        $this->assertEquals('Laptop', $searchResults->first()->name);
    }
} 