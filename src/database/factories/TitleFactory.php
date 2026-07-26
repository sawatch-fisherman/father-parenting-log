<?php

namespace Database\Factories;

use App\Enums\TitleConditionType;
use App\Models\Title;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Title>
 */
class TitleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'care_event_type_id' => null,
            'name' => fake()->words(2, true),
            'condition_type' => TitleConditionType::Count,
            'condition_value' => fake()->numberBetween(1, 100),
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }
}
