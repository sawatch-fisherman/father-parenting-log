<?php

namespace Tests\Feature;

use App\Enums\TitleConditionType;
use App\Models\CareEventType;
use App\Models\Title;
use App\Support\CareEventTypeId;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DatabaseFoundationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_seeding_creates_seventeen_standard_care_event_types(): void
    {
        $this->seed();

        $this->assertSame(17, CareEventType::query()->whereNull('user_id')->count());
    }

    public function test_seeding_creates_both_count_and_streak_titles(): void
    {
        $this->seed();

        $this->assertGreaterThan(0, Title::query()->where('condition_type', TitleConditionType::Count)->count());
        $this->assertGreaterThan(0, Title::query()->where('condition_type', TitleConditionType::Streak)->count());
    }

    public function test_standard_care_event_type_ids_are_valid_ulids_matching_the_fixed_constants(): void
    {
        $this->seed();

        $diaperChange = CareEventType::query()->findOrFail(CareEventTypeId::DIAPER_CHANGE);

        $this->assertTrue(Str::isUlid($diaperChange->id));
        $this->assertSame('おむつ交換', $diaperChange->name);
    }
}
