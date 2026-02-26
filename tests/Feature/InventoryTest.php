<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    private function staffUser(): User
    {
        return User::factory()->create(['role' => 'staff']);
    }

    public function test_staff_can_create_inventory_movement(): void
    {
        $staff = $this->staffUser();
        $product = Product::factory()->create(['stock' => 50]);

        $response = $this->actingAs($staff)->postJson('/api/inventory/movements', [
            'productId' => $product->id,
            'productName' => $product->name,
            'type' => 'in',
            'quantity' => 10,
            'reason' => '進貨補充',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('inventory_movements', [
            'productId' => $product->id,
            'type' => 'in',
        ]);
    }

    public function test_staff_can_list_inventory_movements(): void
    {
        $staff = $this->staffUser();

        $response = $this->actingAs($staff)->getJson('/api/inventory/movements');

        $response->assertOk();
    }

    public function test_staff_can_create_stock_count(): void
    {
        $staff = $this->staffUser();

        $response = $this->actingAs($staff)->postJson('/api/inventory/stock-counts', [
            'countNumber' => 'SC-000001',
            'items' => [
                ['productId' => 'test-id', 'productName' => 'Product A', 'systemStock' => 50, 'actualStock' => 48],
            ],
            'notes' => '月度盤點',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('stock_counts', ['notes' => '月度盤點']);
    }

    public function test_staff_can_list_stock_counts(): void
    {
        $staff = $this->staffUser();

        $response = $this->actingAs($staff)->getJson('/api/inventory/stock-counts');

        $response->assertOk();
    }

    public function test_staff_can_adjust_inventory(): void
    {
        $staff = $this->staffUser();
        $product = Product::factory()->create(['stock' => 50, 'costPrice' => 100]);

        $response = $this->actingAs($staff)->postJson('/api/inventory/adjust', [
            'productId' => $product->id,
            'adjustType' => 'set',
            'quantity' => 48,
            'reason' => '盤點調整',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 48]);
    }

    public function test_unauthenticated_cannot_access_inventory(): void
    {
        $response = $this->getJson('/api/inventory/movements');

        $response->assertStatus(401);
    }
}
