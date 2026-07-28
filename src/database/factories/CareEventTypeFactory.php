<?php

namespace Database\Factories;

use App\Models\CareEventType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CareEventType>
 */
class CareEventTypeFactory extends Factory
{
    /**
     * モデルのデフォルト状態を定義する。
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'name' => fake()->words(2, true),
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }
}
