<?php

namespace Tests\Feature;

use App\Enums\AgeGroup;
use App\Enums\ChildAgeGroup;
use App\Models\CareAction;
use App\Models\CareLog;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * 退会は行を削除せず、users・profiles の値だけを書き換える in-place 匿名化で行う
 * （docs/decisions.md §1.1「退会処理の方式」）。退会UI自体はPhase 2以降だが、
 * その方式がスキーマ上成立することをM0の時点で担保する。
 *
 * 退会処理のサービス層はまだ存在しないため、各テストのActでは退会後の状態を
 * `forceFill()` で直接再現している。
 */
class WithdrawalAnonymizationTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * 退会者が複数いても `UNIQUE(provider, provider_id)` に衝突しない。
     * MySQLはUNIQUE内のNULL同士を別物として扱うため、`provider_id = NULL`
     * を入れる方式が成り立つ。
     */
    public function test_multiple_withdrawn_users_can_share_a_null_provider_id(): void
    {
        // Arrange
        $users = User::factory()->count(2)->create(['provider' => 'google']);

        // Act: 2人とも退会させる
        foreach ($users as $user) {
            $user->forceFill([
                'provider' => 'withdrawn',
                'provider_id' => null,
                'remember_token' => null,
                'withdrawn_at' => now(),
            ])->save();
        }

        // Assert
        $this->assertSame(2, User::whereNotNull('withdrawn_at')->count());
    }

    /**
     * 退会しても育児ログは1件も減らない。行を削除しないため
     * `ON DELETE CASCADE` が発火せず、全体集計が過去に遡って変動しない。
     */
    public function test_withdrawal_keeps_every_care_log(): void
    {
        // Arrange
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        CareLog::factory()->count(3)->create(['user_id' => $user->id]);

        // Act
        $user->forceFill([
            'provider' => 'withdrawn',
            'provider_id' => null,
            'withdrawn_at' => now(),
        ])->save();

        $user->profile->forceFill([
            'nickname' => '退会したユーザー',
            'age_group' => AgeGroup::Unanswered,
            'child_age_group' => ChildAgeGroup::Unanswered,
        ])->save();

        // Assert
        $this->assertSame(3, CareLog::where('user_id', $user->id)->count());
        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
            'nickname' => '退会したユーザー',
        ]);
    }

    /**
     * 退会で `profiles` の年代を Unanswered に潰しても、`care_logs` 側の
     * スナップショットは書き換わらない（docs/data-model.md ④）。これにより
     * 退会後も年代別・子ども年齢帯別の集計が無傷で残る。
     */
    public function test_withdrawal_does_not_touch_the_care_log_snapshot(): void
    {
        // Arrange
        $user = User::factory()->create();
        Profile::factory()->create([
            'user_id' => $user->id,
            'age_group' => AgeGroup::Thirties,
            'child_age_group' => ChildAgeGroup::Zero,
        ]);
        $careLog = CareLog::factory()->create([
            'user_id' => $user->id,
            'care_action_id' => CareAction::factory(),
            'age_group' => AgeGroup::Thirties,
            'child_age_group' => ChildAgeGroup::Zero,
        ]);

        // Act: プロフィール側の年代だけを潰す
        $user->profile->forceFill([
            'nickname' => '退会したユーザー',
            'age_group' => AgeGroup::Unanswered,
            'child_age_group' => ChildAgeGroup::Unanswered,
        ])->save();

        // Assert
        $careLog->refresh();

        $this->assertSame(AgeGroup::Thirties, $careLog->age_group);
        $this->assertSame(ChildAgeGroup::Zero, $careLog->child_age_group);
    }
}
