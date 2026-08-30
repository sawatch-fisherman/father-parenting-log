<?php

namespace Tests\Feature;

use App\Models\CareAction;
use App\Models\CareLog;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserTitle;
use App\Support\CareActionId;
use App\Support\TitleId;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * `TitleGrantService`による称号の同期判定（Count・Streak両方式）を
 * `POST /care-logs`のレスポンス経由で検証する（M5）。
 *
 * @see docs/implementation-plan.md「M5 称号（S5）― Count・Streak 両方式」
 */
class TitleGrantTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed();
    }

    /**
     * 育児行動別Countのしきい値に到達した回で称号が付与され、`name`・`achievement_text`が
     * レスポンスのフラッシュ（`page.flash.titles`）に含まれることを検証する。
     */
    public function test_reaching_a_count_threshold_grants_the_care_action_title(): void
    {
        // Arrange: おむつ交換の銅（しきい値50）まであと1件の状態を用意する
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        CareLog::factory()
            ->count(49)
            ->sequence(fn ($sequence) => ['occurred_at' => now()->copy()->subDays(60 + $sequence->index)])
            ->create(['user_id' => $user->id, 'care_action_id' => CareActionId::DIAPER_CHANGE]);

        // Act
        $response = $this->actingAs($user)->post('/care-logs', [
            'care_action_id' => CareActionId::DIAPER_CHANGE,
            'occurred_at' => now()->format('Y-m-d H:i:s'),
        ]);

        // Assert
        $response->assertInertiaFlash('titles', [
            ['name' => 'おむつ交換見習い', 'achievement_text' => '累計おむつ交換：50回。'],
        ]);
        $this->assertDatabaseHas('user_titles', [
            'user_id' => $user->id,
            'title_id' => TitleId::DIAPER_CHANGE_COUNT_TIER1,
        ]);
    }

    /**
     * 全体合計Countのしきい値に到達した回で称号が付与され、達成内容の一文が
     * 「累計育児ログ：:value回。」の全体パターンで組み立てられることを検証する。
     *
     * 対象の育児行動をユーザーカスタム（Seeder未投入・専用称号なし）にすることで、
     * 育児行動別Countの称号が同時に混ざらないよう切り分ける。
     */
    public function test_reaching_the_overall_count_threshold_grants_the_overall_title(): void
    {
        // Arrange: 全体合計の銅（しきい値100）まであと1件の状態を用意する
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $customCareAction = CareAction::factory()->create();

        CareLog::factory()
            ->count(99)
            ->sequence(fn ($sequence) => ['occurred_at' => now()->copy()->subDays(200 + $sequence->index)])
            ->create(['user_id' => $user->id, 'care_action_id' => $customCareAction->id]);

        // Act
        $response = $this->actingAs($user)->post('/care-logs', [
            'care_action_id' => $customCareAction->id,
            'occurred_at' => now()->format('Y-m-d H:i:s'),
        ]);

        // Assert
        $response->assertInertiaFlash('titles', [
            ['name' => '育児見習い', 'achievement_text' => '累計育児ログ：100回。'],
        ]);
        $this->assertDatabaseHas('user_titles', [
            'user_id' => $user->id,
            'title_id' => TitleId::OVERALL_COUNT_TIER1,
        ]);
    }

    /**
     * 育児行動別Streakのしきい値（3日）に到達した回で称号が付与され、達成内容の一文が
     * 「:value日連続:action達成。」の育児行動別パターンで組み立てられることを検証する。
     */
    public function test_reaching_a_streak_threshold_grants_the_care_action_title(): void
    {
        // Arrange: 2024-01-08・09に記録済みの状態で、2024-01-10を今日とする
        $this->travelTo(Carbon::parse('2024-01-10 12:00:00'));
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        CareLog::factory()->create([
            'user_id' => $user->id,
            'care_action_id' => CareActionId::DIAPER_CHANGE,
            'occurred_at' => '2024-01-08 08:00:00',
        ]);
        CareLog::factory()->create([
            'user_id' => $user->id,
            'care_action_id' => CareActionId::DIAPER_CHANGE,
            'occurred_at' => '2024-01-09 08:00:00',
        ]);

        // Act: 2024-01-10に記録し、3日連続を達成する
        $response = $this->actingAs($user)->post('/care-logs', [
            'care_action_id' => CareActionId::DIAPER_CHANGE,
            'occurred_at' => '2024-01-10 08:00:00',
        ]);

        // Assert
        $response->assertInertiaFlash('titles', [
            ['name' => '3日連続おむつ交換', 'achievement_text' => '3日連続おむつ交換達成。'],
        ]);
        $this->assertDatabaseHas('user_titles', [
            'user_id' => $user->id,
            'title_id' => TitleId::DIAPER_CHANGE_STREAK_TIER1,
        ]);
    }

    /**
     * 全体Streakのしきい値（7日）に到達した回で称号が付与され、達成内容の一文が
     * 「:value日連続育児ログ達成。」の全体パターンで組み立てられることを検証する。
     */
    public function test_reaching_the_overall_streak_threshold_grants_the_overall_title(): void
    {
        // Arrange: 2024-01-04〜01-09の6日間連続で記録済みの状態を用意する
        $this->travelTo(Carbon::parse('2024-01-10 12:00:00'));
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $customCareAction = CareAction::factory()->create();

        foreach (range(4, 9) as $day) {
            CareLog::factory()->create([
                'user_id' => $user->id,
                'care_action_id' => $customCareAction->id,
                'occurred_at' => sprintf('2024-01-%02d 08:00:00', $day),
            ]);
        }

        // Act: 2024-01-10に記録し、7日連続を達成する
        $response = $this->actingAs($user)->post('/care-logs', [
            'care_action_id' => $customCareAction->id,
            'occurred_at' => '2024-01-10 08:00:00',
        ]);

        // Assert
        $response->assertInertiaFlash('titles', [
            ['name' => '1週間連続育児ログ', 'achievement_text' => '7日連続育児ログ達成。'],
        ]);
        $this->assertDatabaseHas('user_titles', [
            'user_id' => $user->id,
            'title_id' => TitleId::OVERALL_STREAK_TIER1,
        ]);
    }

    /**
     * 既に獲得済みの称号は、しきい値を満たし続けていても再付与されないことを検証する。
     */
    public function test_an_already_acquired_title_is_not_granted_again(): void
    {
        // Arrange: おむつ交換の銅を既に獲得済みの状態で、しきい値を超えた記録が50件ある
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        UserTitle::factory()->create([
            'user_id' => $user->id,
            'title_id' => TitleId::DIAPER_CHANGE_COUNT_TIER1,
        ]);

        CareLog::factory()
            ->count(50)
            ->sequence(fn ($sequence) => ['occurred_at' => now()->copy()->subDays(60 + $sequence->index)])
            ->create(['user_id' => $user->id, 'care_action_id' => CareActionId::DIAPER_CHANGE]);

        // Act: 51件目を記録する（次のしきい値である銀の200件にはまだ届かない）
        $response = $this->actingAs($user)->post('/care-logs', [
            'care_action_id' => CareActionId::DIAPER_CHANGE,
            'occurred_at' => now()->format('Y-m-d H:i:s'),
        ]);

        // Assert
        $response->assertInertiaFlashMissing('titles');
        $this->assertSame(1, UserTitle::query()
            ->where('user_id', $user->id)
            ->where('title_id', TitleId::DIAPER_CHANGE_COUNT_TIER1)
            ->count());
    }

    /**
     * 記録が1日途切れると連続日数がリセットされ、しきい値に届かないことを検証する。
     */
    public function test_streak_resets_after_a_missed_day(): void
    {
        // Arrange: 2024-01-07に記録済みだが、2024-01-08・09は記録が無い（1日どころか2日途切れている）
        $this->travelTo(Carbon::parse('2024-01-10 12:00:00'));
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        CareLog::factory()->create([
            'user_id' => $user->id,
            'care_action_id' => CareActionId::DIAPER_CHANGE,
            'occurred_at' => '2024-01-07 08:00:00',
        ]);

        // Act: 2024-01-10に記録しても、起点日から遡ると連続日数は1日しかない
        $response = $this->actingAs($user)->post('/care-logs', [
            'care_action_id' => CareActionId::DIAPER_CHANGE,
            'occurred_at' => '2024-01-10 08:00:00',
        ]);

        // Assert
        $response->assertInertiaFlashMissing('titles');
        $this->assertDatabaseMissing('user_titles', [
            'user_id' => $user->id,
            'title_id' => TitleId::DIAPER_CHANGE_STREAK_TIER1,
        ]);
    }

    /**
     * バックデート入力（S10）で過去の隙間を埋めた場合、連続日数が「今日」ではなく
     * 今回保存した育児ログの日付（起点日）を基準に計算されることを検証する。
     *
     * 2024-01-05・06に記録済みの状態で2024-01-10（今日）にも別途記録がある状態から、
     * 隙間の2024-01-07をバックデート入力する。「今日」を起点にすると01-09が空のため
     * 連続日数は1日にしかならないが、正しい実装は起点を01-07自身とするため
     * 01-05・06・07の3日連続として銅（しきい値3日）を達成する。
     */
    public function test_a_backdated_entry_computes_the_streak_from_its_own_date(): void
    {
        // Arrange
        $this->travelTo(Carbon::parse('2024-01-10 12:00:00'));
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        CareLog::factory()->create([
            'user_id' => $user->id,
            'care_action_id' => CareActionId::DIAPER_CHANGE,
            'occurred_at' => '2024-01-05 08:00:00',
        ]);
        CareLog::factory()->create([
            'user_id' => $user->id,
            'care_action_id' => CareActionId::DIAPER_CHANGE,
            'occurred_at' => '2024-01-06 08:00:00',
        ]);
        CareLog::factory()->create([
            'user_id' => $user->id,
            'care_action_id' => CareActionId::DIAPER_CHANGE,
            'occurred_at' => '2024-01-10 08:00:00',
        ]);

        // Act: 遡り可能な範囲（backdateFloor=2024-01-03）内で01-07を埋める
        $response = $this->actingAs($user)->post('/care-logs', [
            'care_action_id' => CareActionId::DIAPER_CHANGE,
            'occurred_at' => '2024-01-07 08:00:00',
        ]);

        // Assert
        $response->assertInertiaFlash('titles', [
            ['name' => '3日連続おむつ交換', 'achievement_text' => '3日連続おむつ交換達成。'],
        ]);
        $this->assertDatabaseHas('user_titles', [
            'user_id' => $user->id,
            'title_id' => TitleId::DIAPER_CHANGE_STREAK_TIER1,
        ]);
    }

    /**
     * 1回の記録で全体合計と育児行動別の両方のCountしきい値を同時に跨いだ場合、
     * 両方の称号が付与され、レスポンスの`titles`配列が`sort_order`順（全体→育児行動別）で
     * 並ぶことを検証する。
     *
     * `TitleGrantService::grant()`の`orderBy('sort_order')`（候補称号の取得順）と
     * `Record/Index.vue`のキュー処理は、いずれも複数同時獲得時の提示順を守るためだけに
     * 存在する。1件ずつしか称号を獲得しないテストではこの並び順が壊れても検知できないため、
     * 同時獲得のケースを別途固定する（docs/decisions.md §1.3「称号の提示順」）。
     */
    public function test_multiple_titles_granted_at_once_are_ordered_by_sort_order(): void
    {
        // Arrange: おむつ交換49件＋お風呂50件（計99件）で、全体合計・おむつ交換別Countとも
        // 銅（それぞれ100件・50件）まであと1件の状態を用意する
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        CareLog::factory()
            ->count(49)
            ->sequence(fn ($sequence) => ['occurred_at' => now()->copy()->subDays(60 + $sequence->index)])
            ->create(['user_id' => $user->id, 'care_action_id' => CareActionId::DIAPER_CHANGE]);
        CareLog::factory()
            ->count(50)
            ->sequence(fn ($sequence) => ['occurred_at' => now()->copy()->subDays(160 + $sequence->index)])
            ->create(['user_id' => $user->id, 'care_action_id' => CareActionId::BATH]);

        // Act: 100件目（＝おむつ交換としては50件目）を記録し、両方のしきい値を同時に跨ぐ
        $response = $this->actingAs($user)->post('/care-logs', [
            'care_action_id' => CareActionId::DIAPER_CHANGE,
            'occurred_at' => now()->format('Y-m-d H:i:s'),
        ]);

        // Assert: 全体合計（sort_order=1）が育児行動別（sort_order=7）より先に並ぶ
        $response->assertInertiaFlash('titles', [
            ['name' => '育児見習い', 'achievement_text' => '累計育児ログ：100回。'],
            ['name' => 'おむつ交換見習い', 'achievement_text' => '累計おむつ交換：50回。'],
        ]);
        $this->assertDatabaseHas('user_titles', ['user_id' => $user->id, 'title_id' => TitleId::OVERALL_COUNT_TIER1]);
        $this->assertDatabaseHas('user_titles', ['user_id' => $user->id, 'title_id' => TitleId::DIAPER_CHANGE_COUNT_TIER1]);
    }

    /**
     * 称号の対象範囲外（別の育児行動）の記録は、育児行動別Countのしきい値判定に
     * 算入されないことを検証する。
     */
    public function test_count_is_scoped_to_the_titles_care_action(): void
    {
        // Arrange: おむつ交換49件＋別の育児行動1件（合計はしきい値の50件と同数になるが対象外）
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        CareLog::factory()
            ->count(49)
            ->sequence(fn ($sequence) => ['occurred_at' => now()->copy()->subDays(60 + $sequence->index)])
            ->create(['user_id' => $user->id, 'care_action_id' => CareActionId::DIAPER_CHANGE]);
        CareLog::factory()->create([
            'user_id' => $user->id,
            'care_action_id' => CareActionId::BATH,
            'occurred_at' => now()->copy()->subDays(1),
        ]);

        // Act: 別の育児行動（お風呂）を記録する
        $response = $this->actingAs($user)->post('/care-logs', [
            'care_action_id' => CareActionId::BATH,
            'occurred_at' => now()->format('Y-m-d H:i:s'),
        ]);

        // Assert: おむつ交換の称号は対象外の記録では付与されない（お風呂側もまだ1件なので無称号）
        $response->assertInertiaFlashMissing('titles');
        $this->assertDatabaseMissing('user_titles', [
            'user_id' => $user->id,
            'title_id' => TitleId::DIAPER_CHANGE_COUNT_TIER1,
        ]);
    }
}
