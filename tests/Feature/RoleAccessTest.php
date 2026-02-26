<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    public function test_admin_can_access_user_management(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->getJson('/api/users');

        $response->assertOk();
    }

    public function test_staff_cannot_access_user_management(): void
    {
        $staff = User::factory()->staff()->create();

        $response = $this->actingAs($staff)->getJson('/api/users');

        $response->assertStatus(403);
    }

    public function test_customer_cannot_access_user_management(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($customer)->getJson('/api/users');

        $response->assertStatus(403);
    }

    public function test_admin_can_access_internal_routes(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->getJson('/api/customers');

        $response->assertOk();
    }

    public function test_staff_can_access_internal_routes(): void
    {
        $staff = User::factory()->staff()->create();

        $response = $this->actingAs($staff)->getJson('/api/customers');

        $response->assertOk();
    }

    public function test_customer_cannot_access_internal_routes(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($customer)->getJson('/api/customers');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_cannot_access_internal_routes(): void
    {
        $response = $this->getJson('/api/customers');

        $response->assertStatus(401);
    }

    public function test_customer_can_access_public_products(): void
    {
        $response = $this->getJson('/api/products');

        $response->assertOk();
    }

    public function test_customer_can_access_public_categories(): void
    {
        $response = $this->getJson('/api/categories');

        $response->assertOk();
    }
}
