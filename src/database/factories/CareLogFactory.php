<?php

namespace Database\Factories;

use App\Enums\AgeGroup;
use App\Enums\ChildAgeGroup;
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
            // 実運用では記録時に profiles からコピーする値（docs/data-model.md ④）。
            // テストデータでは profiles との整合を要求しないため独立に採番する。
            'age_group' => fake()->randomElement(AgeGroup::cases()),
            'child_age_group' => fake()->randomElement(ChildAgeGroup::cases()),
            'memo' => null,
        ];
    }
}
