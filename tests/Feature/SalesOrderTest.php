<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\User;
use Tests\TestCase;

class SalesOrderTest extends TestCase
{
    private function staffUser(): User
    {
        return User::factory()->create(['role' => 'staff']);
    }

    public function test_staff_can_create_sales_order(): void
    {
        $staff = $this->staffUser();
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['stock' => 50, 'price' => 100]);

        $response = $this->actingAs($staff)->postJson('/api/sales-orders', [
            'customerId' => $customer->id,
            'customerName' => $customer->name,
            'items' => [
                [
                    'productId' => $product->id,
                    'productName' => $product->name,
                    'quantity' => 2,
                    'price' => 100,
                    'subtotal' => 200,
                ],
            ],
            'subtotal' => 200,
            'tax' => 10,
            'shipping' => 60,
            'totalAmount' => 270,
            'paymentMethod' => '貨到付款',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('sales_orders', ['customerId' => $customer->id]);
    }

    public function test_staff_can_list_sales_orders(): void
    {
        $staff = $this->staffUser();
        $customer = Customer::factory()->create();
        SalesOrder::factory()->count(3)->create(['customerId' => $customer->id]);

        $response = $this->actingAs($staff)->getJson('/api/sales-orders');

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_staff_can_view_sales_order(): void
    {
        $staff = $this->staffUser();
        $customer = Customer::factory()->create();
        $order = SalesOrder::factory()->create(['customerId' => $customer->id]);

        $response = $this->actingAs($staff)->getJson("/api/sales-orders/{$order->id}");

        $response->assertOk()
            ->assertJson(['id' => $order->id]);
    }

    public function test_ship_requires_batch_selections(): void
    {
        $staff = $this->staffUser();
        $customer = Customer::factory()->create();
        $order = SalesOrder::factory()->create([
            'customerId' => $customer->id,
            'status' => 'processing',
        ]);

        $response = $this->actingAs($staff)->postJson("/api/sales-orders/{$order->id}/ship");

        $response->assertStatus(400)
            ->assertJson(['error' => '缺少批次選擇資料']);
    }

    public function test_ship_rejects_wrong_status(): void
    {
        $staff = $this->staffUser();
        $customer = Customer::factory()->create();
        $order = SalesOrder::factory()->create([
            'customerId' => $customer->id,
            'status' => 'shipped',
        ]);

        $response = $this->actingAs($staff)->postJson("/api/sales-orders/{$order->id}/ship", [
            'batchSelections' => [],
        ]);

        $response->assertStatus(400);
    }

    public function test_return_requires_shipped_status(): void
    {
        $staff = $this->staffUser();
        $customer = Customer::factory()->create();
        $order = SalesOrder::factory()->create([
            'customerId' => $customer->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($staff)->postJson("/api/sales-orders/{$order->id}/return", [
            'returnReason' => '商品瑕疵',
        ]);

        $response->assertStatus(409);
    }

    public function test_refund_requires_returned_status(): void
    {
        $staff = $this->staffUser();
        $customer = Customer::factory()->create();
        $order = SalesOrder::factory()->create([
            'customerId' => $customer->id,
            'status' => 'pending',
            'paymentStatus' => 'unpaid',
        ]);

        $response = $this->actingAs($staff)->postJson("/api/sales-orders/{$order->id}/refund");

        $response->assertStatus(409);
    }

    public function test_soft_delete_sales_order(): void
    {
        $staff = $this->staffUser();
        $customer = Customer::factory()->create();
        $order = SalesOrder::factory()->create(['customerId' => $customer->id]);

        $response = $this->actingAs($staff)->deleteJson("/api/sales-orders/{$order->id}");

        $response->assertOk();
        $this->assertSoftDeleted('sales_orders', ['id' => $order->id]);
    }
}
