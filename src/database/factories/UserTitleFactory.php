<?php

namespace Database\Factories;

use App\Models\Title;
use App\Models\User;
use App\Models\UserTitle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserTitle>
 */
class UserTitleFactory extends Factory
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
            'title_id' => Title::factory(),
            'unlocked_at' => fake()->dateTimeBetween('-1 month'),
        ];
    }
}
