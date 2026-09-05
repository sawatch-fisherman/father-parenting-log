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
 * S12（期間別集計画面）を検証する。
 *
 * @see docs/implementation-plan.md「M7 集計（S12）」
 */
class StatsControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /**
     * クエリパラメータ省略時に既定タブ（日）＋今日の7バケット（今日を含む直近7日）が返ることを検証する。
     *
     * 窓の境界（4日前00:00〜今日）ぎりぎりの記録を含め、育児行動ごとの内訳・バケット合計・
     * 窓外の記録の除外までまとめて固定する。
     */
    public function test_index_defaults_to_day_tab_with_todays_seven_day_window(): void
    {
        // Arrange
        $this->travelTo(Carbon::parse('2024-01-10 12:00:00'));
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $diaperChange = CareAction::factory()->create(['name' => 'おむつ交換', 'sort_order' => 1]);
        $bath = CareAction::factory()->create(['name' => 'お風呂', 'sort_order' => 2]);

        CareLog::factory()->create(['user_id' => $user->id, 'care_action_id' => $diaperChange->id, 'occurred_at' => '2024-01-10 08:00:00']);
        CareLog::factory()->create(['user_id' => $user->id, 'care_action_id' => $diaperChange->id, 'occurred_at' => '2024-01-10 09:00:00']);
        CareLog::factory()->create(['user_id' => $user->id, 'care_action_id' => $bath->id, 'occurred_at' => '2024-01-08 19:00:00']);
        // 窓の外（4日前00:00の直前）のため集計から除外されるべき記録
        CareLog::factory()->create(['user_id' => $user->id, 'care_action_id' => $diaperChange->id, 'occurred_at' => '2024-01-03 23:59:59']);

        // Act
        $response = $this->actingAs($user)->get('/stats');

        // Assert
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Stats/Index')
            ->where('tab', 'day')
            ->where('baseDate', '2024-01-10')
            ->has('period.buckets', 7)
            ->where('period.buckets.0.start', '2024-01-04')
            ->where('period.buckets.6.start', '2024-01-10')
            ->where('period.buckets.6.total', 2)
            ->where('period.buckets.4.total', 1)
            ->has('period.series', 2)
            ->where('period.series.0.careActionId', $diaperChange->id)
            ->where('period.series.0.counts.6', 2)
            ->where('period.series.1.careActionId', $bath->id)
            ->where('period.series.1.counts.4', 1)
            ->where('period.hasRecords', true)
            ->where('period.prevBaseDate', '2024-01-03')
            ->where('period.nextBaseDate', '2024-01-17')
            ->where('allTime', null),
        );
    }

    /**
     * 期間送りが7バケットぶん窓をスライドさせることを検証する。
     *
     * 日タブの`nextBaseDate`をそのまま次の`base_date`として送り、窓が過不足なく
     * 7日ぶん先へ移動することを固定する（docs/decisions.md §1.3「S12 集計グラフの仕様」）。
     */
    public function test_period_navigation_slides_the_window_by_seven_buckets(): void
    {
        // Arrange
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        // Act
        $response = $this->actingAs($user)->get('/stats?tab=day&base_date=2024-01-17');

        // Assert
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('baseDate', '2024-01-17')
            ->where('period.buckets.0.start', '2024-01-11')
            ->where('period.buckets.6.start', '2024-01-17')
            ->where('period.prevBaseDate', '2024-01-10')
            ->where('period.nextBaseDate', '2024-01-24'),
        );
    }

    /**
     * 週タブが月曜始まりの週を1バケットとして7週ぶん返すことを検証する。
     *
     * 基準日（水曜）が属する週が最終バケットになり、境界（月曜〜日曜）がずれていないことを固定する。
     */
    public function test_week_tab_buckets_are_monday_start_weeks(): void
    {
        // Arrange
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        // Act
        $response = $this->actingAs($user)->get('/stats?tab=week&base_date=2024-01-10');

        // Assert
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('tab', 'week')
            ->has('period.buckets', 7)
            ->where('period.buckets.6.start', '2024-01-08')
            ->where('period.buckets.6.end', '2024-01-14')
            ->where('period.buckets.0.start', '2023-11-27')
            ->where('period.buckets.0.end', '2023-12-03'),
        );
    }

    /**
     * 月タブが暦月を1バケットとして7か月ぶん返すことを検証する。
     */
    public function test_month_tab_buckets_are_calendar_months(): void
    {
        // Arrange
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        // Act
        $response = $this->actingAs($user)->get('/stats?tab=month&base_date=2024-01-10');

        // Assert
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('tab', 'month')
            ->has('period.buckets', 7)
            ->where('period.buckets.6.start', '2024-01-01')
            ->where('period.buckets.6.end', '2024-01-31')
            ->where('period.buckets.0.start', '2023-07-01')
            ->where('period.buckets.0.end', '2023-07-31'),
        );
    }

    /**
     * 全期間タブでは期間の窓を持たず、月別累計・育児行動ごとの累計（多い順）・
     * 累計記録数／記録日数を返すことを検証する。
     */
    public function test_all_tab_returns_cumulative_stats_without_a_period_window(): void
    {
        // Arrange: 「今月」を固定しないと月別累計が実行時の現在月まで延び、件数の期待値が
        // テスト実行日に依存してしまうため、基準日を記録の最終月と同じ月に固定する。
        $this->travelTo(Carbon::parse('2024-01-10 12:00:00'));
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $diaperChange = CareAction::factory()->create(['name' => 'おむつ交換']);
        $bath = CareAction::factory()->create(['name' => 'お風呂']);

        CareLog::factory()->create(['user_id' => $user->id, 'care_action_id' => $diaperChange->id, 'occurred_at' => '2023-11-05 08:00:00']);
        CareLog::factory()->create(['user_id' => $user->id, 'care_action_id' => $diaperChange->id, 'occurred_at' => '2023-12-10 08:00:00']);
        CareLog::factory()->create(['user_id' => $user->id, 'care_action_id' => $diaperChange->id, 'occurred_at' => '2024-01-10 08:00:00']);
        CareLog::factory()->create(['user_id' => $user->id, 'care_action_id' => $bath->id, 'occurred_at' => '2024-01-10 20:00:00']);

        // Act
        $response = $this->actingAs($user)->get('/stats?tab=all');

        // Assert
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Stats/Index')
            ->where('tab', 'all')
            ->where('baseDate', null)
            ->where('period', null)
            ->where('allTime.totalCount', 4)
            ->where('allTime.totalDays', 3)
            ->has('allTime.monthlyCumulative', 3)
            ->where('allTime.monthlyCumulative.0.label', '2023-11')
            ->where('allTime.monthlyCumulative.0.cumulativeTotal', 1)
            ->where('allTime.monthlyCumulative.1.label', '2023-12')
            ->where('allTime.monthlyCumulative.1.cumulativeTotal', 2)
            ->where('allTime.monthlyCumulative.2.label', '2024-01')
            ->where('allTime.monthlyCumulative.2.cumulativeTotal', 4)
            ->has('allTime.careActionTotals', 2)
            ->where('allTime.careActionTotals.0.careActionId', $diaperChange->id)
            ->where('allTime.careActionTotals.0.total', 3)
            ->where('allTime.careActionTotals.1.careActionId', $bath->id)
            ->where('allTime.careActionTotals.1.total', 1)
            ->where('allTime.hasRecords', true),
        );
    }

    /**
     * 不正なタブ種別が既定タブ（日）にフォールバックすることを検証する。
     */
    public function test_invalid_tab_falls_back_to_the_default_tab(): void
    {
        // Arrange
        $this->travelTo(Carbon::parse('2024-01-10 12:00:00'));
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        // Act
        $response = $this->actingAs($user)->get('/stats?tab=bogus');

        // Assert
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('tab', 'day')
            ->where('baseDate', '2024-01-10'),
        );
    }

    /**
     * 不正な基準日が今日にフォールバックすることを検証する。
     */
    public function test_invalid_base_date_falls_back_to_today(): void
    {
        // Arrange
        $this->travelTo(Carbon::parse('2024-01-10 12:00:00'));
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        // Act
        $response = $this->actingAs($user)->get('/stats?tab=week&base_date=not-a-date');

        // Assert
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('tab', 'week')
            ->where('baseDate', '2024-01-10'),
        );
    }

    /**
     * 対象期間に記録が1件も無いとき、`hasRecords`が`false`になることを検証する（Vue側の空状態表示の条件）。
     *
     * 期間タブ自体（バケット構造）は空状態でも表示したままにするため、`buckets`は7件のまま返る。
     */
    public function test_index_reports_no_records_when_the_period_is_empty(): void
    {
        // Arrange
        $this->travelTo(Carbon::parse('2024-01-10 12:00:00'));
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        // Act
        $response = $this->actingAs($user)->get('/stats');

        // Assert
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('period.buckets', 7)
            ->has('period.series', 0)
            ->where('period.hasRecords', false),
        );
    }

    /**
     * アカウント全体で記録が1件も無いとき、全期間タブの`hasRecords`が`false`になることを検証する。
     */
    public function test_all_tab_reports_no_records_when_the_account_has_no_care_logs(): void
    {
        // Arrange
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        // Act
        $response = $this->actingAs($user)->get('/stats?tab=all');

        // Assert
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('allTime.totalCount', 0)
            ->where('allTime.totalDays', 0)
            ->has('allTime.monthlyCumulative', 0)
            ->has('allTime.careActionTotals', 0)
            ->where('allTime.hasRecords', false),
        );
    }

    /**
     * 他ユーザーの記録が自分の集計に混ざらないことを検証する。
     *
     * この画面はIDを受け取らず常に自分のログへ暗黙スコープするため Policy を持たない。
     */
    public function test_index_excludes_other_users_care_logs(): void
    {
        // Arrange
        $this->travelTo(Carbon::parse('2024-01-10 12:00:00'));
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $otherUser = User::factory()->create();
        $careAction = CareAction::factory()->create();

        CareLog::factory()->create(['user_id' => $otherUser->id, 'care_action_id' => $careAction->id, 'occurred_at' => '2024-01-10 08:00:00']);

        // Act
        $response = $this->actingAs($user)->get('/stats');

        // Assert
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('period.hasRecords', false)
            ->has('period.series', 0),
        );
    }

    /**
     * 未認証で`GET /stats`にアクセスすると`login`へリダイレクトされることを検証する。
     */
    public function test_unauthenticated_access_redirects_to_login(): void
    {
        // Act
        $response = $this->get('/stats');

        // Assert
        $response->assertRedirect(route('login'));
    }

    /**
     * 月タブの基準日が月末日（29〜31日）でも、7バケットが連続する7か月（重複・欠落なし）で
     * 返ることを検証する。
     *
     * `Carbon::subMonths()`は既定でオーバーフローするため、「日を保持したまま月を引いてから
     * 月初へ丸める」実装だと基準日が月末のときにバケットが重複・欠落する（PRレビュー指摘：
     * `base_date=2024-03-31`で2023-11月・2024-02月が消え、10月・3月が2列ずつ並ぶ）。
     * 消えるはずだった2月の記録が正しいバケットに入ることまであわせて固定する。
     */
    public function test_month_tab_buckets_do_not_overflow_when_base_date_is_a_month_end_day(): void
    {
        // Arrange
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $careAction = CareAction::factory()->create();
        CareLog::factory()->create(['user_id' => $user->id, 'care_action_id' => $careAction->id, 'occurred_at' => '2024-02-15 08:00:00']);

        // Act
        $response = $this->actingAs($user)->get('/stats?tab=month&base_date=2024-03-31');

        // Assert
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('period.buckets.0.start', '2023-09-01')
            ->where('period.buckets.1.start', '2023-10-01')
            ->where('period.buckets.2.start', '2023-11-01')
            ->where('period.buckets.3.start', '2023-12-01')
            ->where('period.buckets.4.start', '2024-01-01')
            ->where('period.buckets.5.start', '2024-02-01')
            ->where('period.buckets.5.end', '2024-02-29')
            ->where('period.buckets.6.start', '2024-03-01')
            ->where('period.buckets.5.total', 1)
            ->where('period.prevBaseDate', '2023-08-01')
            ->where('period.nextBaseDate', '2024-10-01'),
        );
    }

    /**
     * 月タブの期間送りが、月初を起点に往復すると元の窓に一致することを検証する
     * （`次へ`で進んだ先の`前へ`が出発点と同じ月になる）。
     */
    public function test_month_tab_period_navigation_round_trips(): void
    {
        // Arrange
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        // Act
        $forward = $this->actingAs($user)->get('/stats?tab=month&base_date=2024-03-01');

        // Assert
        $forward->assertInertia(fn (AssertableInertia $page) => $page->where('period.nextBaseDate', '2024-10-01'));

        // Act
        $backward = $this->actingAs($user)->get('/stats?tab=month&base_date=2024-10-01');

        // Assert
        $backward->assertInertia(fn (AssertableInertia $page) => $page->where('period.prevBaseDate', '2024-03-01'));
    }

    /**
     * 全期間タブの月別累計が、記録の無い月を直前の累計値で埋め、今月に記録が無くても
     * 今月ぶんまで延ばすことを検証する（docs/wireframes.md S12「記録開始月〜今月の月別累計」）。
     */
    public function test_all_tab_monthly_cumulative_fills_gap_months_and_extends_to_the_current_month(): void
    {
        // Arrange: 2月は記録が無い月、3月（今月）も記録が無い状態を固定する
        $this->travelTo(Carbon::parse('2024-03-15 12:00:00'));
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $careAction = CareAction::factory()->create();
        CareLog::factory()->create(['user_id' => $user->id, 'care_action_id' => $careAction->id, 'occurred_at' => '2024-01-05 08:00:00']);
        CareLog::factory()->create(['user_id' => $user->id, 'care_action_id' => $careAction->id, 'occurred_at' => '2024-01-20 08:00:00']);

        // Act
        $response = $this->actingAs($user)->get('/stats?tab=all');

        // Assert
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('allTime.monthlyCumulative', 3)
            ->where('allTime.monthlyCumulative.0.label', '2024-01')
            ->where('allTime.monthlyCumulative.0.cumulativeTotal', 2)
            ->where('allTime.monthlyCumulative.1.label', '2024-02')
            ->where('allTime.monthlyCumulative.1.cumulativeTotal', 2)
            ->where('allTime.monthlyCumulative.2.label', '2024-03')
            ->where('allTime.monthlyCumulative.2.cumulativeTotal', 2)
            ->where('allTime.totalCount', 2),
        );
    }

    /**
     * 暦として無効な基準日（"2024-02-30"のように`Y-m-d`形式には一致するが実在しない日付）が
     * 今日にフォールバックすることを検証する。
     *
     * `Carbon::createFromFormat()`はこの種の値を例外にせず翌月へ繰り上げて解釈するため、
     * フォーマット不一致（`InvalidFormatException`）とは別経路でのフォールバックを固定する。
     */
    public function test_calendar_invalid_base_date_falls_back_to_today(): void
    {
        // Arrange
        $this->travelTo(Carbon::parse('2024-01-10 12:00:00'));
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        // Act
        $response = $this->actingAs($user)->get('/stats?tab=week&base_date=2024-02-30');

        // Assert
        $response->assertInertia(fn (AssertableInertia $page) => $page->where('baseDate', '2024-01-10'));
    }

    /**
     * クエリパラメータが配列で送られても（`?tab[]=day`）500にならず既定値へフォールバックすることを検証する。
     *
     * `Request::query()`は配列入力をそのまま返すため、`?string`型のみを想定した受け側に直接渡すと
     * `TypeError`になる（PRレビュー指摘）。
     */
    public function test_array_query_parameters_fall_back_to_defaults_instead_of_erroring(): void
    {
        // Arrange
        $this->travelTo(Carbon::parse('2024-01-10 12:00:00'));
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        // Act
        $response = $this->actingAs($user)->get('/stats?tab[]=day&base_date[]=2024-01-01');

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('tab', 'day')
            ->where('baseDate', '2024-01-10'),
        );
    }

    /**
     * 未来日を`base_date`に指定しても今日に丸められることを検証する。
     *
     * 育児ログは未来日時に存在しえないため（`occurred_at`は現在+5分が上限）、未来の基準日を
     * そのまま受け入れると必ず空の期間を表示することになる。URL直接指定分もここで防ぐ。
     */
    public function test_future_base_date_is_clamped_to_today(): void
    {
        // Arrange
        $this->travelTo(Carbon::parse('2024-01-10 12:00:00'));
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        // Act
        $response = $this->actingAs($user)->get('/stats?tab=day&base_date=2024-02-01');

        // Assert
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('baseDate', '2024-01-10')
            ->where('period.buckets.6.start', '2024-01-10'),
        );
    }

    /**
     * 直近バケットが今日を含む期間では`atLatestPeriod`が`true`になり、
     * それより過去の期間では`false`になることを検証する（「次」の期間送りを非活性にする材料）。
     */
    public function test_at_latest_period_flag_reflects_whether_the_window_reaches_today(): void
    {
        // Arrange
        $this->travelTo(Carbon::parse('2024-01-10 12:00:00'));
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        // Act: 今日を含む窓（既定の基準日＝今日）
        $latest = $this->actingAs($user)->get('/stats?tab=day');

        // Assert
        $latest->assertInertia(fn (AssertableInertia $page) => $page->where('period.atLatestPeriod', true));

        // Act: 7日ぶん過去にずらした窓（今日を含まない）
        $past = $this->actingAs($user)->get('/stats?tab=day&base_date=2024-01-03');

        // Assert
        $past->assertInertia(fn (AssertableInertia $page) => $page->where('period.atLatestPeriod', false));
    }
}
