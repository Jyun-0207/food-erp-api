<?php

namespace Tests\Feature;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Tests\TestCase;

class PurchaseOrderTest extends TestCase
{
    private function staffUser(): User
    {
        return User::factory()->create(['role' => 'staff']);
    }

    public function test_staff_can_create_purchase_order(): void
    {
        $staff = $this->staffUser();
        $supplier = Supplier::factory()->create();

        $product = \App\Models\Product::factory()->create();

        $response = $this->actingAs($staff)->postJson('/api/purchase-orders', [
            'supplierId' => $supplier->id,
            'supplierName' => $supplier->name,
            'items' => [
                [
                    'productId' => $product->id,
                    'productName' => 'Raw Material',
                    'quantity' => 100,
                    'unitPrice' => 10,
                    'subtotal' => 1000,
                ],
            ],
            'totalAmount' => 1000,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('purchase_orders', ['supplierId' => $supplier->id]);
    }

    public function test_staff_can_list_purchase_orders(): void
    {
        $staff = $this->staffUser();
        $supplier = Supplier::factory()->create();
        PurchaseOrder::factory()->count(3)->create(['supplierId' => $supplier->id]);

        $response = $this->actingAs($staff)->getJson('/api/purchase-orders');

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_staff_can_view_purchase_order(): void
    {
        $staff = $this->staffUser();
        $supplier = Supplier::factory()->create();
        $order = PurchaseOrder::factory()->create(['supplierId' => $supplier->id]);

        $response = $this->actingAs($staff)->getJson("/api/purchase-orders/{$order->id}");

        $response->assertOk()
            ->assertJson(['id' => $order->id]);
    }

    public function test_receive_requires_pending_status(): void
    {
        $staff = $this->staffUser();
        $supplier = Supplier::factory()->create();
        $order = PurchaseOrder::factory()->create([
            'supplierId' => $supplier->id,
            'status' => 'received',
        ]);

        $response = $this->actingAs($staff)->postJson("/api/purchase-orders/{$order->id}/receive");

        $response->assertStatus(400);
    }

    public function test_return_requires_received_status(): void
    {
        $staff = $this->staffUser();
        $supplier = Supplier::factory()->create();
        $order = PurchaseOrder::factory()->create([
            'supplierId' => $supplier->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($staff)->postJson("/api/purchase-orders/{$order->id}/return", [
            'returnReason' => '品質不良',
        ]);

        $response->assertStatus(409);
    }

    public function test_soft_delete_purchase_order(): void
    {
        $staff = $this->staffUser();
        $supplier = Supplier::factory()->create();
        $order = PurchaseOrder::factory()->create(['supplierId' => $supplier->id]);

        $response = $this->actingAs($staff)->deleteJson("/api/purchase-orders/{$order->id}");

        $response->assertOk();
        $this->assertSoftDeleted('purchase_orders', ['id' => $order->id]);
    }
}
