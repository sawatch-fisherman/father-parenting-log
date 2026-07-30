<?php

namespace Database\Factories;

use App\Models\CareAction;
use App\Models\CareLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CareLog>
 */
class CareLogFactory extends Factory
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
            'care_action_id' => CareAction::factory(),
            'occurred_at' => fake()->dateTimeBetween('-1 month'),
            'memo' => null,
        ];
    }
}
