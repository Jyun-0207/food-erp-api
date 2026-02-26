<?php

namespace Database\Factories;

use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'orderNumber' => 'PO-' . fake()->unique()->numerify('######'),
            'status' => 'pending',
            'items' => [
                [
                    'productId' => (string) Str::ulid(),
                    'productName' => fake()->word(),
                    'quantity' => 10,
                    'price' => 50,
                    'subtotal' => 500,
                ],
            ],
            'totalAmount' => 500,
        ];
    }
}
