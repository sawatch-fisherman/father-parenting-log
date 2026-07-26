<?php

namespace Tests\Feature;

use App\Models\CareEvent;
use App\Models\CareEventType;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CareEventUniqueConstraintTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_duplicate_user_type_and_occurred_at_is_rejected(): void
    {
        $user = User::factory()->create();
        $careEventType = CareEventType::factory()->create();
        $occurredAt = now();

        CareEvent::factory()->create([
            'user_id' => $user->id,
            'care_event_type_id' => $careEventType->id,
            'occurred_at' => $occurredAt,
        ]);

        $this->expectException(QueryException::class);

        CareEvent::factory()->create([
            'user_id' => $user->id,
            'care_event_type_id' => $careEventType->id,
            'occurred_at' => $occurredAt,
        ]);
    }
}
