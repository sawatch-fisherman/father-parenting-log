<?php

namespace Database\Factories;

use App\Models\CareEvent;
use App\Models\CareEventType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CareEvent>
 */
class CareEventFactory extends Factory
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
            'care_event_type_id' => CareEventType::factory(),
            'occurred_at' => fake()->dateTimeBetween('-1 month'),
            'memo' => null,
        ];
    }
}
