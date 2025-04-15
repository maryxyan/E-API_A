<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomerProductFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->artisan('db:seed');
    }

    /** @test */
    public function customer_can_filter_products_by_stock_availability()
    {
        // Create products with different stock quantities
        $inStockProduct = Product::factory()->create(['stock_quantity' => 10]);
        $outOfStockProduct = Product::factory()->create(['stock_quantity' => 0]);

        // Test filtering for in stock products
        $response = $this->getJson('/api/products?in_stock=true');
        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $inStockProduct->id]);
        $response->assertJsonMissing(['id' => $outOfStockProduct->id]);

        // Test filtering for out of stock products
        $response = $this->getJson('/api/products?in_stock=false');
        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $outOfStockProduct->id]);
        $response->assertJsonMissing(['id' => $inStockProduct->id]);
    }
}
