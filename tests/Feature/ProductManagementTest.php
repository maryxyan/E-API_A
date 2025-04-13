<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class ProductManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->customer = User::factory()->create(['role' => 'customer']);
    }

    /** @test */
    public function admin_can_create_product()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/admin/products', [
            'name' => 'New Product',
            'description' => 'Product description',
            'price' => 100.00,
            'stock_quantity' => 10
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'name' => 'New Product',
                'description' => 'Product description',
                'price' => 100.00,
                'stock_quantity' => 10
            ]);

        $this->assertDatabaseHas('products', [
            'name' => 'New Product',
            'price' => 100.00,
            'stock_quantity' => 10
        ]);
    }

    /** @test */
    public function product_creation_validates_required_fields()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/admin/products', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'price', 'stock_quantity']);
    }

    /** @test */
    public function product_creation_validates_price_and_stock()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/admin/products', [
            'name' => 'Test Product',
            'price' => -100.00,
            'stock_quantity' => -10,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['price', 'stock_quantity']);
    }

    /** @test */
    public function admin_can_update_product()
    {
        Sanctum::actingAs($this->admin);
        $product = Product::factory()->create();

        $response = $this->putJson("/api/admin/products/{$product->id}", [
            'name' => 'Updated Product',
            'price' => 150.00,
            'stock_quantity' => 20
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'name' => 'Updated Product',
                'price' => 150.00,
                'stock_quantity' => 20
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product',
            'price' => 150.00,
            'stock_quantity' => 20
        ]);
    }

    /** @test */
    public function admin_can_delete_product()
    {
        Sanctum::actingAs($this->admin);
        $product = Product::factory()->create();

        $response = $this->deleteJson("/api/admin/products/{$product->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    /** @test */
    public function admin_cannot_delete_product_with_orders()
    {
        Sanctum::actingAs($this->admin);
        $product = Product::factory()->create();
        $order = $this->customer->orders()->create(['total_price' => 100.00]);
        $order->orderItems()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 100.00
        ]);

        $response = $this->deleteJson("/api/admin/products/{$product->id}");

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Cannot delete product that has associated orders'
            ]);

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    /** @test */
    public function customer_cannot_create_product()
    {
        Sanctum::actingAs($this->customer);

        $response = $this->postJson('/api/admin/products', [
            'name' => 'New Product',
            'description' => 'Product description',
            'price' => 100.00,
            'stock_quantity' => 10
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function customer_cannot_update_product()
    {
        Sanctum::actingAs($this->customer);
        $product = Product::factory()->create();

        $response = $this->putJson("/api/admin/products/{$product->id}", [
            'name' => 'Updated Product',
            'price' => 150.00
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function customer_cannot_delete_product()
    {
        Sanctum::actingAs($this->customer);
        $product = Product::factory()->create();

        $response = $this->deleteJson("/api/admin/products/{$product->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function customer_can_view_products()
    {
        Sanctum::actingAs($this->customer);
        $product = Product::factory()->create();

        $response = $this->getJson('/api/customer/products');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
            ]);
    }

    /** @test */
    public function customer_can_filter_products_by_price_range()
    {
        Sanctum::actingAs($this->customer);
        Product::factory()->create(['price' => 50.00]);
        Product::factory()->create(['price' => 100.00]);
        Product::factory()->create(['price' => 150.00]);

        $response = $this->getJson('/api/customer/products?min_price=75&max_price=125');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment(['price' => 100.00]);
    }

    /** @test */
    public function customer_can_filter_products_by_stock_availability()
    {
        Sanctum::actingAs($this->customer);
        Product::factory()->create(['stock_quantity' => 5]);
        Product::factory()->create(['stock_quantity' => 0]);

        $response = $this->getJson('/api/customer/products?in_stock=true');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment(['stock_quantity' => 5]);
    }

    /** @test */
    public function customer_can_search_products()
    {
        Sanctum::actingAs($this->customer);
        Product::factory()->create(['name' => 'Laptop', 'description' => 'High performance']);
        Product::factory()->create(['name' => 'Phone', 'description' => 'Smart device']);

        $response = $this->getJson('/api/customer/products?search=laptop');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment(['name' => 'Laptop']);
    }

    /** @test */
    public function admin_can_search_products()
    {
        Sanctum::actingAs($this->admin);
        
        Product::factory()->create(['name' => 'Test Product 1', 'price' => 100.00]);
        Product::factory()->create(['name' => 'Test Product 2', 'price' => 200.00]);
        Product::factory()->create(['name' => 'Another Product', 'price' => 300.00]);

        $response = $this->getJson('/api/admin/products?search=Test');

        $response->assertStatus(200)
            ->assertJsonCount(2);
    }

    /** @test */
    public function admin_can_filter_products_by_price_range()
    {
        Sanctum::actingAs($this->admin);
        
        Product::factory()->create(['price' => 100.00]);
        Product::factory()->create(['price' => 200.00]);
        Product::factory()->create(['price' => 300.00]);

        $response = $this->getJson('/api/admin/products?min_price=150&max_price=250');

        $response->assertStatus(200)
            ->assertJsonCount(1);
    }

    /** @test */
    public function admin_can_filter_products_by_stock_availability()
    {
        Sanctum::actingAs($this->admin);
        
        Product::factory()->create(['stock_quantity' => 10]);
        Product::factory()->create(['stock_quantity' => 0]);
        Product::factory()->create(['stock_quantity' => 5]);

        $response = $this->getJson('/api/admin/products?in_stock=true');

        $response->assertStatus(200)
            ->assertJsonCount(2);
    }
} 