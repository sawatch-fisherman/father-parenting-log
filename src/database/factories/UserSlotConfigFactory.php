<?php

namespace Database\Factories;

use App\Models\CareAction;
use App\Models\User;
use App\Models\UserSlotConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserSlotConfig>
 */
class UserSlotConfigFactory extends Factory
{
    /**
     * モデルのデフォルト状態を定義する。
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'slot_position' => fake()->numberBetween(1, 8),
            'care_action_id' => CareAction::factory(),
        ];
    }
}
