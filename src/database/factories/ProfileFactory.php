<?php

namespace Database\Factories;

use App\Enums\AgeGroup;
use App\Enums\ChildAgeGroup;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profile>
 */
class ProfileFactory extends Factory
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
            'nickname' => fake()->firstName(),
            'age_group' => fake()->randomElement(AgeGroup::cases()),
            'child_age_group' => fake()->randomElement(ChildAgeGroup::cases()),
        ];
    }
}
