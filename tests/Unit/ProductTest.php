<?php

namespace Tests\Unit;

use App\Models\Product;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_product()
    {
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'price' => 100.00,
            'stock_quantity' => 10
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

        $product = Product::factory()->create(['stock_quantity' => 0]);
        $this->assertFalse($product->isInStock());
    }

    /** @test */
    public function it_can_check_if_product_has_sufficient_stock()
    {
        $product = Product::factory()->create(['stock_quantity' => 10]);
        $this->assertTrue($product->hasSufficientStock(5));
        $this->assertFalse($product->hasSufficientStock(15));
    }

    /** @test */
    public function it_can_decrease_stock()
    {
        $product = Product::factory()->create(['stock_quantity' => 10]);
        $product->decreaseStock(3);
        $this->assertEquals(7, $product->fresh()->stock_quantity);
    }

    /** @test */
    public function it_can_increase_stock()
    {
        $product = Product::factory()->create(['stock_quantity' => 10]);
        $product->increaseStock(5);
        $this->assertEquals(15, $product->fresh()->stock_quantity);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Product::create([
            'name' => '',
            'price' => null,
            'stock_quantity' => null
        ]);
    }
} 