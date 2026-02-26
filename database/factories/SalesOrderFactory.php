<?php

namespace Database\Factories;

use App\Models\SalesOrder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SalesOrderFactory extends Factory
{
    protected $model = SalesOrder::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'orderNumber' => 'SO-' . fake()->unique()->numerify('######'),
            'status' => 'pending',
            'items' => [
                [
                    'productId' => (string) Str::ulid(),
                    'productName' => fake()->word(),
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
            'paymentStatus' => 'unpaid',
        ];
    }
}
