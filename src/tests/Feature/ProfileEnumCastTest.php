<?php

namespace Tests\Feature;

use App\Enums\AgeGroup;
use App\Enums\ChildAgeGroup;
use App\Models\Profile;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ProfileEnumCastTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_age_group_and_child_age_group_round_trip_as_enums(): void
    {
        $profile = Profile::factory()->create([
            'age_group' => AgeGroup::Thirties,
            'child_age_group' => ChildAgeGroup::One,
        ]);

        $fresh = $profile->fresh();

        $this->assertSame(AgeGroup::Thirties, $fresh->age_group);
        $this->assertSame(ChildAgeGroup::One, $fresh->child_age_group);
    }
}
