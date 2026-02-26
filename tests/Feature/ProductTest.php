<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Tests\TestCase;

class ProductTest extends TestCase
{
    private function adminUser(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_public_can_list_products(): void
    {
        Product::factory()->count(3)->create();

        $response = $this->getJson('/api/products');

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_public_product_list_hides_cost_price(): void
    {
        Product::factory()->create(['costPrice' => 50.00]);

        $response = $this->getJson('/api/products');

        $response->assertOk();
        $items = $response->json('data');
        foreach ($items as $item) {
            $this->assertArrayNotHasKey('costPrice', $item);
        }
    }

    public function test_authenticated_staff_can_see_cost_price(): void
    {
        $admin = $this->adminUser();
        Product::factory()->create(['costPrice' => 50.00]);

        $response = $this->actingAs($admin)->getJson('/api/products');

        $response->assertOk();
        $items = $response->json('data');
        $this->assertArrayHasKey('costPrice', $items[0]);
    }

    public function test_public_can_view_single_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertOk()
            ->assertJson(['id' => $product->id]);
    }

    public function test_admin_can_create_product(): void
    {
        $admin = $this->adminUser();
        $category = Category::factory()->create();

        $response = $this->actingAs($admin)->postJson('/api/products', [
            'name' => 'New Product',
            'price' => 99.99,
            'costPrice' => 49.99,
            'categoryId' => $category->id,
            'sku' => 'NP-001',
            'stock' => 10,
            'unit' => '個',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('products', ['name' => 'New Product']);
    }

    public function test_admin_can_update_product(): void
    {
        $admin = $this->adminUser();
        $product = Product::factory()->create();

        $response = $this->actingAs($admin)->putJson("/api/products/{$product->id}", [
            'name' => 'Updated Product',
            'price' => 199.99,
            'costPrice' => $product->costPrice,
            'sku' => $product->sku,
            'unit' => $product->unit ?? '個',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Updated Product']);
    }

    public function test_admin_can_delete_product(): void
    {
        $admin = $this->adminUser();
        $product = Product::factory()->create();

        $response = $this->actingAs($admin)->deleteJson("/api/products/{$product->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('products', ['id' => $product->id, 'deletedAt' => null]);
    }

    public function test_customer_cannot_create_product(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($customer)->postJson('/api/products', [
            'name' => 'Forbidden Product',
            'price' => 10,
        ]);

        $response->assertStatus(403);
    }

    public function test_unauthenticated_cannot_create_product(): void
    {
        $response = $this->postJson('/api/products', [
            'name' => 'Forbidden Product',
            'price' => 10,
        ]);

        $response->assertStatus(401);
    }
}
