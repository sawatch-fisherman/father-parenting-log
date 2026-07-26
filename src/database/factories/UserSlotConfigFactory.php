<?php

namespace Database\Factories;

use App\Models\CareEventType;
use App\Models\User;
use App\Models\UserSlotConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserSlotConfig>
 */
class UserSlotConfigFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'slot_position' => fake()->numberBetween(1, 8),
            'care_event_type_id' => CareEventType::factory(),
        ];
    }
}
