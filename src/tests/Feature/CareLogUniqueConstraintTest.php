<?php

namespace Tests\Feature;

use App\Models\CareAction;
use App\Models\CareLog;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CareLogUniqueConstraintTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_duplicate_user_action_and_occurred_at_is_rejected(): void
    {
        $user = User::factory()->create();
        $careAction = CareAction::factory()->create();
        $occurredAt = now();

        CareLog::factory()->create([
            'user_id' => $user->id,
            'care_action_id' => $careAction->id,
            'occurred_at' => $occurredAt,
        ]);

        $this->expectException(QueryException::class);

        CareLog::factory()->create([
            'user_id' => $user->id,
            'care_action_id' => $careAction->id,
            'occurred_at' => $occurredAt,
        ]);
    }
}
