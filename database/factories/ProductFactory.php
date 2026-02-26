<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'name' => fake()->word(),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 10, 1000),
            'costPrice' => fake()->randomFloat(2, 5, 500),
            'sku' => strtoupper(fake()->bothify('??-####')),
            'stock' => fake()->numberBetween(0, 100),
            'minStock' => 5,
            'unit' => '個',
            'isActive' => true,
        ];
    }
}
