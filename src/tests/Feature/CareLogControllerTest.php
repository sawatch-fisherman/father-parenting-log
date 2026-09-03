<?php

namespace Tests\Feature;

use App\Enums\AgeGroup;
use App\Enums\ChildAgeGroup;
use App\Models\CareAction;
use App\Models\CareLog;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * S3短タップ／S10保存の共通エンドポイント（M4）を検証する。
 *
 * @see docs/implementation-plan.md「M4 育児ログ登録（S3 短タップ, S4, S10）」
 */
class CareLogControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /**
     * 育児ログが作成され、プロフィールの年代・末子の年齢帯がスナップショットとして
     * コピーされることを検証する。
     */
    public function test_it_creates_a_care_log_with_a_profile_snapshot(): void
    {
        // Arrange
        $user = User::factory()->create();
        Profile::factory()->create([
            'user_id' => $user->id,
            'age_group' => AgeGroup::Thirties,
            'child_age_group' => ChildAgeGroup::One,
        ]);
        $careAction = CareAction::factory()->create();

        // 送信と検証で `now()` を2回評価すると、その間に秒が繰り上がったときだけ落ちるため固定する。
        $occurredAt = now()->subDay()->format('Y-m-d H:i:s');

        // Act
        $response = $this->actingAs($user)->post('/care-logs', [
            'care_action_id' => $careAction->id,
            'occurred_at' => $occurredAt,
            'memo' => 'よく寝た',
        ]);

        // Assert
        $response->assertRedirect(route('home'));
        $this->assertDatabaseHas('care_logs', [
            'user_id' => $user->id,
            'care_action_id' => $careAction->id,
            'occurred_at' => $occurredAt,
            'age_group' => AgeGroup::Thirties->value,
            'child_age_group' => ChildAgeGroup::One->value,
            'memo' => 'よく寝た',
        ]);
    }

    /**
     * `occurred_at` を省略すると、コントローラ側で `now()` にフォールバックすることを検証する。
     */
    public function test_omitting_occurred_at_falls_back_to_the_current_time(): void
    {
        // Arrange
        $this->travelTo(Carbon::parse('2024-01-10 12:00:00'));
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $careAction = CareAction::factory()->create();

        // Act
        $response = $this->actingAs($user)->post('/care-logs', [
            'care_action_id' => $careAction->id,
        ]);

        // Assert
        $response->assertRedirect(route('home'));
        $this->assertDatabaseHas('care_logs', [
            'user_id' => $user->id,
            'occurred_at' => '2024-01-10 12:00:00',
        ]);
    }

    /**
     * 保存成功時に、記録した育児行動名を含むメッセージが Inertia のフラッシュ専用チャンネル
     * （`page.flash`）で送られることを検証する。
     *
     * 通常のセッションフラッシュ（`->with()`）ではなく`Inertia::flash()`を使う理由は、
     * `page.props`と違いブラウザのhistory stateに永続化されないため（ブラウザバックで復元した
     * ページに古い成功メッセージが再表示されるのを防ぐため）。
     * S3短タップは記録しても画面が変わらないため、このフラッシュを AppLayout の `ToastHost` が
     * トーストとして出すまでが成功フィードバックの経路になる（DESIGN.md 11章 Success）。
     */
    public function test_it_flashes_a_success_message_naming_the_recorded_care_action(): void
    {
        // Arrange
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $careAction = CareAction::factory()->create(['name' => 'おむつ交換']);

        // Act
        $response = $this->actingAs($user)->post('/care-logs', [
            'care_action_id' => $careAction->id,
            'occurred_at' => now()->format('Y-m-d H:i:s'),
        ]);

        // Assert
        $response->assertRedirect(route('home'));
        $response->assertInertiaFlash('success', 'おむつ交換を記録しました');
    }

    /**
     * 空文字のメモが`NULL`に正規化されて保存されることを検証する。
     */
    public function test_an_empty_memo_is_stored_as_null(): void
    {
        // Arrange
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $careAction = CareAction::factory()->create();

        // Act
        $this->actingAs($user)->post('/care-logs', [
            'care_action_id' => $careAction->id,
            'occurred_at' => now()->subDay()->format('Y-m-d H:i:s'),
            'memo' => '',
        ]);

        // Assert
        $this->assertDatabaseHas('care_logs', ['user_id' => $user->id, 'memo' => null]);
    }

    /**
     * 同一ユーザー・同一育児行動・同一`occurred_at`の二重送信が、アプリ層の`Rule::unique`で
     * 「同じ日時に同じ記録があります」という分かりやすいメッセージとともに弾かれることを検証する
     * （DBレベルのUNIQUE制約自体は`CareLogUniqueConstraintTest`で別途検証済み）。
     */
    public function test_duplicate_submission_returns_a_friendly_validation_error(): void
    {
        // Arrange
        $this->travelTo(Carbon::parse('2024-01-10 12:00:00'));
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $careAction = CareAction::factory()->create();

        CareLog::factory()->create([
            'user_id' => $user->id,
            'care_action_id' => $careAction->id,
            'occurred_at' => '2024-01-10 09:00:00',
        ]);

        // Act
        $response = $this->actingAs($user)->post('/care-logs', [
            'care_action_id' => $careAction->id,
            'occurred_at' => '2024-01-10 09:00:00',
        ]);

        // Assert
        $response->assertSessionHasErrors(['occurred_at' => '同じ日時に同じ記録があります。']);
        $this->assertSame(1, CareLog::query()->where('user_id', $user->id)->count());
    }

    /**
     * 別ユーザーが同じ`care_action_id`／`occurred_at`の記録を持っていても、
     * 自分の記録がブロックされないことを検証する（`Rule::unique`の`user_id`スコープ）。
     *
     * `where('user_id', ...)`が将来のリファクタで欠落すると、他人の記録有無だけで
     * 自分の記録がブロックされる（＝他ユーザーの記録有無が推測できる情報漏洩でもある）ため、
     * DBのUNIQUE制約（`user_id`を含む）だけでなくアプリ層のスコープも別途固定する。
     */
    public function test_another_users_identical_care_log_does_not_block_registration(): void
    {
        // Arrange
        $this->travelTo(Carbon::parse('2024-01-10 12:00:00'));
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $careAction = CareAction::factory()->create();

        $otherUser = User::factory()->create();
        CareLog::factory()->create([
            'user_id' => $otherUser->id,
            'care_action_id' => $careAction->id,
            'occurred_at' => '2024-01-10 09:00:00',
        ]);

        // Act
        $response = $this->actingAs($user)->post('/care-logs', [
            'care_action_id' => $careAction->id,
            'occurred_at' => '2024-01-10 09:00:00',
        ]);

        // Assert
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('care_logs', [
            'user_id' => $user->id,
            'care_action_id' => $careAction->id,
            'occurred_at' => '2024-01-10 09:00:00',
        ]);
    }

    /**
     * 同一`occurred_at`でも育児行動が異なれば登録できることを検証する
     * （`Rule::unique`が`care_action_id`もスコープに含んでいるため、時刻の一致だけでは弾かれない）。
     */
    public function test_same_occurred_at_with_a_different_care_action_is_accepted(): void
    {
        // Arrange
        $this->travelTo(Carbon::parse('2024-01-10 12:00:00'));
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $firstCareAction = CareAction::factory()->create();
        $secondCareAction = CareAction::factory()->create();

        CareLog::factory()->create([
            'user_id' => $user->id,
            'care_action_id' => $firstCareAction->id,
            'occurred_at' => '2024-01-10 09:00:00',
        ]);

        // Act
        $response = $this->actingAs($user)->post('/care-logs', [
            'care_action_id' => $secondCareAction->id,
            'occurred_at' => '2024-01-10 09:00:00',
        ]);

        // Assert
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('care_logs', [
            'user_id' => $user->id,
            'care_action_id' => $secondCareAction->id,
            'occurred_at' => '2024-01-10 09:00:00',
        ]);
    }

    /**
     * 遡り境界が「その日の00:00」であり、`now()->subDays()`（時刻を保持したまま日付だけ引く）
     * ではないことを検証する。誤って`now()->subDays(7)`にすると、未明3時のような時刻では
     * 「7日前ちょうどの00:00」の記録まで誤って拒否してしまう（docs/decisions.md §1.3）。
     */
    public function test_occurred_at_exactly_at_the_backdate_floor_is_accepted(): void
    {
        // Arrange: 未明3時に固定し、「時刻を保持したまま7日引く」誤りが起きた場合に検知できるようにする
        $this->travelTo(Carbon::parse('2024-01-10 03:00:00'));
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $careAction = CareAction::factory()->create();

        // Act: ちょうど floor（7日前の00:00）
        $response = $this->actingAs($user)->post('/care-logs', [
            'care_action_id' => $careAction->id,
            'occurred_at' => '2024-01-03 00:00:00',
        ]);

        // Assert
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('care_logs', ['user_id' => $user->id, 'occurred_at' => '2024-01-03 00:00:00']);
    }

    /**
     * 遡り境界（7日前の00:00）より1秒前は拒否され、`messages()`で割り当てた
     * 専用文言が表示されることを検証する。
     *
     * `lang/ja/validation.php` に汎用の `after_or_equal` キーが定義されていないため、
     * `messages()` のキーが実際のルール名からズレると `validation.after_or_equal` という
     * 生キーがそのまま表示されてしまう。キーの存在だけでなく文言まで固定して検知できるようにする。
     */
    public function test_occurred_at_before_the_backdate_floor_is_rejected(): void
    {
        // Arrange
        $this->travelTo(Carbon::parse('2024-01-10 03:00:00'));
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $careAction = CareAction::factory()->create();

        // Act
        $response = $this->actingAs($user)->post('/care-logs', [
            'care_action_id' => $careAction->id,
            'occurred_at' => '2024-01-02 23:59:59',
        ]);

        // Assert
        $response->assertSessionHasErrors(['occurred_at' => '記録できるのは7日前までです。']);
        $this->assertDatabaseMissing('care_logs', ['user_id' => $user->id]);
    }

    /**
     * `now() + 5分`以内の未来日時は許容されることを検証する
     * （端末クロックの軽微なズレを吸収するバッファ。docs/decisions.md §1.3）。
     */
    public function test_occurred_at_within_the_five_minute_future_buffer_is_accepted(): void
    {
        // Arrange
        $this->travelTo(Carbon::parse('2024-01-10 12:00:00'));
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $careAction = CareAction::factory()->create();

        // Act
        $response = $this->actingAs($user)->post('/care-logs', [
            'care_action_id' => $careAction->id,
            'occurred_at' => '2024-01-10 12:05:00',
        ]);

        // Assert
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('care_logs', ['user_id' => $user->id, 'occurred_at' => '2024-01-10 12:05:00']);
    }

    /**
     * `now() + 5分`を超える未来日時は拒否され、`messages()`で割り当てた専用文言が
     * 表示されることを検証する（理由は`test_occurred_at_before_the_backdate_floor_is_rejected`と同じ）。
     *
     * 文言には「端末の時刻設定をご確認ください」を含める。短タップは端末のローカル時計をそのまま
     * 送信するため、端末TZがJST以外・時計がズレている場合にもこのエラーになりうる。
     */
    public function test_occurred_at_beyond_the_five_minute_future_buffer_is_rejected(): void
    {
        // Arrange
        $this->travelTo(Carbon::parse('2024-01-10 12:00:00'));
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $careAction = CareAction::factory()->create();

        // Act
        $response = $this->actingAs($user)->post('/care-logs', [
            'care_action_id' => $careAction->id,
            'occurred_at' => '2024-01-10 12:05:01',
        ]);

        // Assert
        $response->assertSessionHasErrors(['occurred_at' => '未来の日時は記録できません。端末の時刻設定をご確認ください。']);
        $this->assertDatabaseMissing('care_logs', ['user_id' => $user->id]);
    }

    /**
     * 他ユーザーのカスタム育児行動IDを指定すると`care_action_id`が拒否されることを検証する
     * （`CareAction::scopeAccessibleTo`）。
     */
    public function test_another_users_custom_care_action_is_rejected(): void
    {
        // Arrange
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $otherUser = User::factory()->create();
        $othersCareAction = CareAction::factory()->create(['user_id' => $otherUser->id]);

        // Act
        $response = $this->actingAs($user)->post('/care-logs', [
            'care_action_id' => $othersCareAction->id,
            'occurred_at' => now()->subDay()->format('Y-m-d H:i:s'),
        ]);

        // Assert
        $response->assertSessionHasErrors('care_action_id');
        $this->assertDatabaseMissing('care_logs', ['user_id' => $user->id]);
    }

    /**
     * 未認証で`POST /care-logs`にアクセスすると`login`へリダイレクトされることを検証する。
     */
    public function test_unauthenticated_access_to_store_redirects_to_login(): void
    {
        // Act
        $response = $this->post('/care-logs', []);

        // Assert
        $response->assertRedirect(route('login'));
    }

    /**
     * S10（実施日時指定画面）が、選択済みの育児行動と遡り可能範囲を受け取ることを検証する。
     */
    public function test_create_page_receives_the_selected_care_action_and_backdate_window(): void
    {
        // Arrange
        $this->travelTo(Carbon::parse('2024-01-10 12:00:00'));
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $careAction = CareAction::factory()->create(['name' => 'おむつ交換']);

        // Act
        $response = $this->actingAs($user)->get("/care-logs/create?care_action_id={$careAction->id}");

        // Assert
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('CareLogs/Create')
            ->where('careAction.id', $careAction->id)
            ->where('careAction.name', 'おむつ交換')
            ->where('backdateFloorDate', '2024-01-03')
            ->where('todayDate', '2024-01-10')
            ->where('backdateDays', 7),
        );
    }

    /**
     * アクセス不能な`care_action_id`（他ユーザーのカスタム育児行動）を指定すると404になることを検証する。
     */
    public function test_create_page_404s_for_an_inaccessible_care_action_id(): void
    {
        // Arrange
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $otherUser = User::factory()->create();
        $othersCareAction = CareAction::factory()->create(['user_id' => $otherUser->id]);

        // Act
        $response = $this->actingAs($user)->get("/care-logs/create?care_action_id={$othersCareAction->id}");

        // Assert
        $response->assertNotFound();
    }

    /**
     * `care_action_id`クエリを省略すると404になることを検証する。
     */
    public function test_create_page_404s_when_care_action_id_is_missing(): void
    {
        // Arrange
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        // Act
        $response = $this->actingAs($user)->get('/care-logs/create');

        // Assert
        $response->assertNotFound();
    }

    /**
     * 未認証で`GET /care-logs/create`にアクセスすると`login`へリダイレクトされることを検証する。
     */
    public function test_unauthenticated_access_to_create_redirects_to_login(): void
    {
        // Act
        $response = $this->get('/care-logs/create');

        // Assert
        $response->assertRedirect(route('login'));
    }

    /**
     * S11（ログ編集画面）が、対象の記録の現在値と遡り可能範囲を受け取ることを検証する。
     */
    public function test_edit_page_receives_the_care_log_and_backdate_window(): void
    {
        // Arrange
        $this->travelTo(Carbon::parse('2024-01-10 12:00:00'));
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $careAction = CareAction::factory()->create(['name' => 'おむつ交換']);
        $careLog = CareLog::factory()->create([
            'user_id' => $user->id,
            'care_action_id' => $careAction->id,
            'occurred_at' => '2024-01-09 21:30:00',
            'memo' => 'よく寝た',
        ]);

        // Act
        $response = $this->actingAs($user)->get("/care-logs/{$careLog->id}/edit");

        // Assert
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('CareLogs/Edit')
            ->where('careLog.id', $careLog->id)
            ->where('careLog.careActionName', 'おむつ交換')
            ->where('careLog.occurredDate', '2024-01-09')
            ->where('careLog.occurredTime', '21:30')
            ->where('careLog.memo', 'よく寝た')
            ->where('backdateFloorDate', '2024-01-03')
            ->where('todayDate', '2024-01-10')
            ->where('backdateDays', 7),
        );
    }

    /**
     * 他人の記録のS11を開こうとすると`CareLogPolicy`で弾かれることを検証する。
     *
     * `{care_log}`はURLにID付きで現れる唯一のリソースのため、IDを直接叩かれても
     * 他人の育児行動名・メモを覗けないことまで担保する必要がある（docs/screens.md 補足）。
     */
    public function test_edit_page_is_forbidden_for_another_users_care_log(): void
    {
        // Arrange
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $othersCareLog = CareLog::factory()->create(['occurred_at' => now()->subDay()]);

        // Act
        $response = $this->actingAs($user)->get("/care-logs/{$othersCareLog->id}/edit");

        // Assert
        $response->assertForbidden();
    }

    /**
     * 「7日前の00:00」より前の記録のS11を開こうとすると`CareLogPolicy`で弾かれることを検証する。
     */
    public function test_edit_page_is_forbidden_for_a_care_log_before_the_backdate_floor(): void
    {
        // Arrange
        $this->travelTo(Carbon::parse('2024-01-10 03:00:00'));
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $careLog = CareLog::factory()->create([
            'user_id' => $user->id,
            'occurred_at' => '2024-01-02 23:59:59',
        ]);

        // Act
        $response = $this->actingAs($user)->get("/care-logs/{$careLog->id}/edit");

        // Assert
        $response->assertForbidden();
    }

    /**
     * 未認証で`GET /care-logs/{care_log}/edit`にアクセスすると`login`へリダイレクトされることを検証する。
     */
    public function test_unauthenticated_access_to_edit_redirects_to_login(): void
    {
        // Arrange
        $careLog = CareLog::factory()->create();

        // Act
        $response = $this->get("/care-logs/{$careLog->id}/edit");

        // Assert
        $response->assertRedirect(route('login'));
    }

    /**
     * 実施日時とメモが更新され、履歴へ戻ることを検証する。
     */
    public function test_it_updates_occurred_at_and_memo(): void
    {
        // Arrange
        $this->travelTo(Carbon::parse('2024-01-10 12:00:00'));
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $careLog = CareLog::factory()->create([
            'user_id' => $user->id,
            'occurred_at' => '2024-01-09 21:30:00',
            'memo' => 'よく寝た',
        ]);

        // Act
        $response = $this->actingAs($user)->patch("/care-logs/{$careLog->id}", [
            'occurred_at' => '2024-01-08 07:15:00',
            'memo' => '朝の分',
        ]);

        // Assert
        $response->assertRedirect(route('history.index'));
        $response->assertInertiaFlash('success', '記録を更新しました');
        $this->assertDatabaseHas('care_logs', [
            'id' => $careLog->id,
            'occurred_at' => '2024-01-08 07:15:00',
            'memo' => '朝の分',
        ]);
    }

    /**
     * メモを空にして保存するとメモが削除されることを検証する。
     *
     * 自由入力欄に意図せず個人情報を書いた場合の自己訂正手段のため、空文字のまま
     * 残さず`NULL`に正規化する（docs/privacy.md §9、docs/wireframes.md S11）。
     */
    public function test_update_clears_the_memo_when_an_empty_value_is_submitted(): void
    {
        // Arrange
        $this->travelTo(Carbon::parse('2024-01-10 12:00:00'));
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $careLog = CareLog::factory()->create([
            'user_id' => $user->id,
            'occurred_at' => '2024-01-09 21:30:00',
            'memo' => '消したいメモ',
        ]);

        // Act
        $response = $this->actingAs($user)->patch("/care-logs/{$careLog->id}", [
            'occurred_at' => '2024-01-09 21:30:00',
            'memo' => '',
        ]);

        // Assert
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('care_logs', ['id' => $careLog->id, 'memo' => null]);
    }

    /**
     * `occurred_at`を変えずにメモだけ保存しても、自分自身とは重複衝突しないことを検証する
     * （`Rule::unique`の`ignore()`が効いていること）。
     *
     * `StoreCareLogRequest`の`unique`をそのまま流用すると、編集中の行自身が既存行として
     * 引っかかり、メモだけの修正が永久に保存できなくなる。
     */
    public function test_updating_only_the_memo_does_not_conflict_with_itself(): void
    {
        // Arrange
        $this->travelTo(Carbon::parse('2024-01-10 12:00:00'));
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $careLog = CareLog::factory()->create([
            'user_id' => $user->id,
            'occurred_at' => '2024-01-09 21:30:00',
            'memo' => null,
        ]);

        // Act
        $response = $this->actingAs($user)->patch("/care-logs/{$careLog->id}", [
            'occurred_at' => '2024-01-09 21:30:00',
            'memo' => '書き足したメモ',
        ]);

        // Assert
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('care_logs', [
            'id' => $careLog->id,
            'occurred_at' => '2024-01-09 21:30:00',
            'memo' => '書き足したメモ',
        ]);
    }

    /**
     * `care_action_id`・`age_group`・`child_age_group`をリクエストに含めても変更されないことを検証する。
     *
     * 育児行動の変更は「削除→再作成」に限る仕様で、年代2列は記録時点のスナップショットのため
     * 事後に書き換えない（docs/decisions.md §1.3、docs/data-model.md ④）。
     */
    public function test_update_does_not_change_the_care_action_or_the_profile_snapshot(): void
    {
        // Arrange
        $this->travelTo(Carbon::parse('2024-01-10 12:00:00'));
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $careAction = CareAction::factory()->create();
        $otherCareAction = CareAction::factory()->create();
        $careLog = CareLog::factory()->create([
            'user_id' => $user->id,
            'care_action_id' => $careAction->id,
            'occurred_at' => '2024-01-09 21:30:00',
            'age_group' => AgeGroup::Thirties,
            'child_age_group' => ChildAgeGroup::One,
        ]);

        // Act
        $response = $this->actingAs($user)->patch("/care-logs/{$careLog->id}", [
            'occurred_at' => '2024-01-09 22:00:00',
            'care_action_id' => $otherCareAction->id,
            'age_group' => AgeGroup::Forties->value,
            'child_age_group' => ChildAgeGroup::Three->value,
        ]);

        // Assert
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('care_logs', [
            'id' => $careLog->id,
            'occurred_at' => '2024-01-09 22:00:00',
            'care_action_id' => $careAction->id,
            'age_group' => AgeGroup::Thirties->value,
            'child_age_group' => ChildAgeGroup::One->value,
        ]);
    }

    /**
     * 遡り下限（「7日前の00:00」）より前の日時への変更が拒否されることを検証する。
     *
     * `update`の遡り境界は2つの別々の仕組みで守られている：`CareLogPolicy`は
     * **変更前**の`occurred_at`を見て締め切り済みの記録そのものを弾き、
     * `occurredAtRangeRule()`の`after_or_equal`は**変更後**の`occurred_at`を見て、
     * 窓の内側にある記録を窓の外へ移動させる操作を弾く。この経路はPolicyでは
     * 止まらず`ValidatesOccurredAt`（`StoreCareLogRequest`と共通のトレイト）だけが
     * 守っているため、抽出時の書き間違いや`CareLogWindow`の将来の変更で緩んでも
     * 検知できるよう、`StoreCareLogRequest`側の同種テストと同じ粒度で固定する。
     */
    public function test_update_to_a_datetime_before_the_backdate_floor_is_rejected(): void
    {
        // Arrange
        $this->travelTo(Carbon::parse('2024-01-10 03:00:00'));
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $careLog = CareLog::factory()->create([
            'user_id' => $user->id,
            'occurred_at' => '2024-01-08 21:30:00',
        ]);

        // Act
        $response = $this->actingAs($user)->patch("/care-logs/{$careLog->id}", [
            'occurred_at' => '2024-01-02 23:59:59',
        ]);

        // Assert
        $response->assertSessionHasErrors(['occurred_at' => '記録できるのは7日前までです。']);
        $this->assertDatabaseHas('care_logs', ['id' => $careLog->id, 'occurred_at' => '2024-01-08 21:30:00']);
    }

    /**
     * 遡り下限ちょうど（「7日前の00:00」）への変更は許容されることを検証する。
     *
     * 前項のテストと対にして、境界自体を1日ズラして誤って弾いていないことも固定する
     * （`StoreCareLogRequest`側の`test_occurred_at_exactly_at_the_backdate_floor_is_accepted`と対応）。
     */
    public function test_update_to_the_backdate_floor_exactly_is_accepted(): void
    {
        // Arrange
        $this->travelTo(Carbon::parse('2024-01-10 03:00:00'));
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $careLog = CareLog::factory()->create([
            'user_id' => $user->id,
            'occurred_at' => '2024-01-08 21:30:00',
        ]);

        // Act
        $response = $this->actingAs($user)->patch("/care-logs/{$careLog->id}", [
            'occurred_at' => '2024-01-03 00:00:00',
        ]);

        // Assert
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('care_logs', ['id' => $careLog->id, 'occurred_at' => '2024-01-03 00:00:00']);
    }

    /**
     * 既存の別の記録と同じ日時へ変更しようとすると、分かりやすいバリデーションエラーになることを検証する。
     */
    public function test_update_to_a_datetime_taken_by_another_log_is_rejected(): void
    {
        // Arrange
        $this->travelTo(Carbon::parse('2024-01-10 12:00:00'));
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $careAction = CareAction::factory()->create();
        CareLog::factory()->create([
            'user_id' => $user->id,
            'care_action_id' => $careAction->id,
            'occurred_at' => '2024-01-09 08:00:00',
        ]);
        $careLog = CareLog::factory()->create([
            'user_id' => $user->id,
            'care_action_id' => $careAction->id,
            'occurred_at' => '2024-01-09 21:30:00',
        ]);

        // Act
        $response = $this->actingAs($user)->patch("/care-logs/{$careLog->id}", [
            'occurred_at' => '2024-01-09 08:00:00',
        ]);

        // Assert
        $response->assertSessionHasErrors(['occurred_at' => '同じ日時に同じ記録があります。']);
        $this->assertDatabaseHas('care_logs', ['id' => $careLog->id, 'occurred_at' => '2024-01-09 21:30:00']);
    }

    /**
     * `now() + 5分`を超える未来日時への変更が拒否されることを検証する
     * （範囲チェックは`StoreCareLogRequest`と共通のルール）。
     */
    public function test_update_to_a_datetime_beyond_the_five_minute_future_buffer_is_rejected(): void
    {
        // Arrange
        $this->travelTo(Carbon::parse('2024-01-10 12:00:00'));
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $careLog = CareLog::factory()->create([
            'user_id' => $user->id,
            'occurred_at' => '2024-01-09 21:30:00',
        ]);

        // Act
        $response = $this->actingAs($user)->patch("/care-logs/{$careLog->id}", [
            'occurred_at' => '2024-01-10 12:05:01',
        ]);

        // Assert
        $response->assertSessionHasErrors(['occurred_at' => '未来の日時は記録できません。端末の時刻設定をご確認ください。']);
        $this->assertDatabaseHas('care_logs', ['id' => $careLog->id, 'occurred_at' => '2024-01-09 21:30:00']);
    }

    /**
     * 他人の記録を更新しようとすると`CareLogPolicy`で弾かれることを検証する。
     */
    public function test_update_is_forbidden_for_another_users_care_log(): void
    {
        // Arrange
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $othersCareLog = CareLog::factory()->create([
            'occurred_at' => now()->subDay()->format('Y-m-d H:i:s'),
            'memo' => null,
        ]);

        // Act
        $response = $this->actingAs($user)->patch("/care-logs/{$othersCareLog->id}", [
            'occurred_at' => now()->format('Y-m-d H:i:s'),
            'memo' => '乗っ取り',
        ]);

        // Assert
        $response->assertForbidden();
        $this->assertDatabaseHas('care_logs', ['id' => $othersCareLog->id, 'memo' => null]);
    }

    /**
     * 「7日前の00:00」より前の記録は、日時を許容範囲内に付け替える更新であっても
     * `CareLogPolicy`で弾かれることを検証する。
     *
     * バリデーションが見るのは「変更後の日時」だけなので、締め切りの判定をPolicy側に
     * 置いていないと、8日前の記録の日時を今日に書き換える操作が素通りしてしまう
     * （docs/decisions.md §1.3）。
     */
    public function test_update_is_forbidden_for_a_care_log_before_the_backdate_floor(): void
    {
        // Arrange
        $this->travelTo(Carbon::parse('2024-01-10 03:00:00'));
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $careLog = CareLog::factory()->create([
            'user_id' => $user->id,
            'occurred_at' => '2024-01-02 23:59:59',
        ]);

        // Act
        $response = $this->actingAs($user)->patch("/care-logs/{$careLog->id}", [
            'occurred_at' => '2024-01-10 01:00:00',
        ]);

        // Assert
        $response->assertForbidden();
        $this->assertDatabaseHas('care_logs', ['id' => $careLog->id, 'occurred_at' => '2024-01-02 23:59:59']);
    }

    /**
     * 未認証で`PATCH /care-logs/{care_log}`にアクセスすると`login`へリダイレクトされることを検証する。
     */
    public function test_unauthenticated_access_to_update_redirects_to_login(): void
    {
        // Arrange
        $careLog = CareLog::factory()->create();

        // Act
        $response = $this->patch("/care-logs/{$careLog->id}", []);

        // Assert
        $response->assertRedirect(route('login'));
    }

    /**
     * 育児ログが削除され、履歴へ戻ることを検証する。
     */
    public function test_it_deletes_a_care_log(): void
    {
        // Arrange
        $this->travelTo(Carbon::parse('2024-01-10 12:00:00'));
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $careLog = CareLog::factory()->create([
            'user_id' => $user->id,
            'occurred_at' => '2024-01-09 21:30:00',
        ]);

        // Act
        $response = $this->actingAs($user)->delete("/care-logs/{$careLog->id}");

        // Assert
        $response->assertRedirect(route('history.index'));
        $response->assertInertiaFlash('success', '記録を削除しました');
        $this->assertDatabaseMissing('care_logs', ['id' => $careLog->id]);
    }

    /**
     * 他人の記録を削除しようとすると`CareLogPolicy`で弾かれることを検証する。
     */
    public function test_destroy_is_forbidden_for_another_users_care_log(): void
    {
        // Arrange
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $othersCareLog = CareLog::factory()->create(['occurred_at' => now()->subDay()]);

        // Act
        $response = $this->actingAs($user)->delete("/care-logs/{$othersCareLog->id}");

        // Assert
        $response->assertForbidden();
        $this->assertDatabaseHas('care_logs', ['id' => $othersCareLog->id]);
    }

    /**
     * 「7日前の00:00」より前の記録は削除できないことを検証する。
     */
    public function test_destroy_is_forbidden_for_a_care_log_before_the_backdate_floor(): void
    {
        // Arrange
        $this->travelTo(Carbon::parse('2024-01-10 03:00:00'));
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $careLog = CareLog::factory()->create([
            'user_id' => $user->id,
            'occurred_at' => '2024-01-02 23:59:59',
        ]);

        // Act
        $response = $this->actingAs($user)->delete("/care-logs/{$careLog->id}");

        // Assert
        $response->assertForbidden();
        $this->assertDatabaseHas('care_logs', ['id' => $careLog->id]);
    }

    /**
     * 未認証で`DELETE /care-logs/{care_log}`にアクセスすると`login`へリダイレクトされることを検証する。
     */
    public function test_unauthenticated_access_to_destroy_redirects_to_login(): void
    {
        // Arrange
        $careLog = CareLog::factory()->create();

        // Act
        $response = $this->delete("/care-logs/{$careLog->id}");

        // Assert
        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('care_logs', ['id' => $careLog->id]);
    }
}
