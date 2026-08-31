<?php

namespace Tests\Feature;

use App\Models\CareAction;
use App\Models\CareLog;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * S13（記録履歴画面・タイムライン）を検証する。
 *
 * @see docs/implementation-plan.md「M6 履歴（S13, S11）」
 */
class HistoryControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /**
     * 育児ログが日付ごとにグループ化され、日付・時刻とも新しい順で返ることを検証する。
     *
     * グループの順序（日付）と、グループ内の順序（時刻）は別々の処理で決まるため、
     * 2日分×複数件のデータで両方を同時に固定する。
     */
    public function test_index_groups_care_logs_by_date_in_descending_order(): void
    {
        // Arrange
        $this->travelTo(Carbon::parse('2024-01-10 12:00:00'));
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $diaperChange = CareAction::factory()->create(['name' => 'おむつ交換']);
        $bath = CareAction::factory()->create(['name' => 'お風呂']);

        CareLog::factory()->create([
            'user_id' => $user->id,
            'care_action_id' => $bath->id,
            'occurred_at' => '2024-01-09 19:00:00',
        ]);
        CareLog::factory()->create([
            'user_id' => $user->id,
            'care_action_id' => $diaperChange->id,
            'occurred_at' => '2024-01-10 08:00:00',
        ]);
        CareLog::factory()->create([
            'user_id' => $user->id,
            'care_action_id' => $bath->id,
            'occurred_at' => '2024-01-10 21:30:00',
        ]);

        // Act
        $response = $this->actingAs($user)->get('/history');

        // Assert
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('History/Index')
            ->has('days', 2)
            ->where('days.0.date', '2024-01-10')
            ->where('days.0.logs.0.time', '21:30')
            ->where('days.0.logs.0.careActionName', 'お風呂')
            ->where('days.0.logs.1.time', '08:00')
            ->where('days.0.logs.1.careActionName', 'おむつ交換')
            ->where('days.1.date', '2024-01-09')
            ->where('days.1.logs.0.time', '19:00')
            ->where('backdateDays', 7),
        );
    }

    /**
     * メモが各行にそのまま渡ることを検証する。
     *
     * S13はメモを表示する唯一の画面で、メモの有無で行の見た目（2行目の有無）が変わるため、
     * 未入力が空文字ではなく`null`のまま届くことまで固定する（docs/wireframes.md S13）。
     */
    public function test_index_passes_the_memo_of_each_care_log(): void
    {
        // Arrange
        $this->travelTo(Carbon::parse('2024-01-10 12:00:00'));
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $careAction = CareAction::factory()->create();

        CareLog::factory()->create([
            'user_id' => $user->id,
            'care_action_id' => $careAction->id,
            'occurred_at' => '2024-01-10 19:00:00',
            'memo' => 'めずらしく大人しい',
        ]);
        CareLog::factory()->create([
            'user_id' => $user->id,
            'care_action_id' => $careAction->id,
            'occurred_at' => '2024-01-10 08:00:00',
            'memo' => null,
        ]);

        // Act
        $response = $this->actingAs($user)->get('/history');

        // Assert
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('History/Index')
            ->where('days.0.logs.0.memo', 'めずらしく大人しい')
            ->where('days.0.logs.1.memo', null),
        );
    }

    /**
     * 「7日前の00:00」より前の行だけが編集不可（`editable = false`）になることを検証する。
     *
     * S13の「…」の活性／非活性の判定はこの値だけに依存するため、境界がサーバー側の
     * `CareLogPolicy` とズレると「操作できるのに保存できない」行が生まれる
     * （docs/decisions.md §1.3）。境界ちょうどの行を含めて固定する。
     */
    public function test_index_marks_logs_before_the_backdate_floor_as_not_editable(): void
    {
        // Arrange: 未明3時に固定し、「時刻を保持したまま7日引く」誤りが起きた場合に検知できるようにする
        $this->travelTo(Carbon::parse('2024-01-10 03:00:00'));
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $careAction = CareAction::factory()->create();

        CareLog::factory()->create([
            'user_id' => $user->id,
            'care_action_id' => $careAction->id,
            'occurred_at' => '2024-01-03 00:00:00',
        ]);
        CareLog::factory()->create([
            'user_id' => $user->id,
            'care_action_id' => $careAction->id,
            'occurred_at' => '2024-01-02 23:59:59',
        ]);

        // Act
        $response = $this->actingAs($user)->get('/history');

        // Assert
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('History/Index')
            ->where('days.0.date', '2024-01-03')
            ->where('days.0.logs.0.editable', true)
            ->where('days.1.date', '2024-01-02')
            ->where('days.1.logs.0.editable', false),
        );
    }

    /**
     * 記録が1件も無い場合に空の配列を返すことを検証する（Vue側の空状態表示の条件）。
     */
    public function test_index_returns_no_days_when_the_user_has_no_care_logs(): void
    {
        // Arrange
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        // Act
        $response = $this->actingAs($user)->get('/history');

        // Assert
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('History/Index')
            ->has('days', 0),
        );
    }

    /**
     * 他ユーザーの記録が自分の履歴に混ざらないことを検証する。
     *
     * この画面はIDを受け取らず常に自分のログへ暗黙スコープするため Policy を持たない。
     * 絞り込みが`$user->careLogs()`から外れると即座に他人の記録が見えるので、テストで固定する。
     */
    public function test_index_excludes_other_users_care_logs(): void
    {
        // Arrange
        $this->travelTo(Carbon::parse('2024-01-10 12:00:00'));
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $otherUser = User::factory()->create();
        $careAction = CareAction::factory()->create();

        CareLog::factory()->create([
            'user_id' => $otherUser->id,
            'care_action_id' => $careAction->id,
            'occurred_at' => '2024-01-10 08:00:00',
        ]);

        // Act
        $response = $this->actingAs($user)->get('/history');

        // Assert
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('History/Index')
            ->has('days', 0),
        );
    }

    /**
     * 未認証で`GET /history`にアクセスすると`login`へリダイレクトされることを検証する。
     */
    public function test_unauthenticated_access_redirects_to_login(): void
    {
        // Act
        $response = $this->get('/history');

        // Assert
        $response->assertRedirect(route('login'));
    }
}
