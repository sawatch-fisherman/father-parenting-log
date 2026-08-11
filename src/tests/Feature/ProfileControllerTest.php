<?php

namespace Tests\Feature;

use App\Enums\AgeGroup;
use App\Enums\ChildAgeGroup;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserSlotConfig;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Config;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * プロフィール登録・編集（M2）を検証する。
 *
 * @see docs/implementation-plan.md「M2 プロフィール（S2, S8）」
 */
class ProfileControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed();
    }

    /**
     * `POST /profile` でプロフィールが作成され、`config('totoops.initial_slot_care_action_ids')`
     * に基づく初期8個が `user_slot_configs` に作成されることを検証する。
     */
    public function test_registering_a_profile_creates_the_initial_eight_slots(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)->post('/profile', [
            'nickname' => 'とと',
            'age_group' => AgeGroup::Thirties->value,
            'child_age_group' => ChildAgeGroup::One->value,
        ]);

        // Assert
        $response->assertRedirect(route('home'));
        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
            'nickname' => 'とと',
            'age_group' => AgeGroup::Thirties->value,
            'child_age_group' => ChildAgeGroup::One->value,
        ]);

        $expectedCareActionIds = Config::array('totoops.initial_slot_care_action_ids');

        $this->assertSame(
            $expectedCareActionIds,
            UserSlotConfig::query()
                ->where('user_id', $user->id)
                ->orderBy('slot_position')
                ->pluck('care_action_id')
                ->all(),
        );
    }

    /**
     * `nickname` 必須のバリデーションが効き、プロフィールが作成されないことを検証する。
     */
    public function test_nickname_is_required(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)->post('/profile', [
            'nickname' => '',
        ]);

        // Assert
        $response->assertSessionHasErrors('nickname');
        $this->assertDatabaseMissing('profiles', ['user_id' => $user->id]);
    }

    /**
     * `age_group`／`child_age_group` を未選択のまま送信すると、DBのNULLではなく
     * `Unanswered`（未回答）として保存されることを検証する（カラムはNOT NULLのため）。
     */
    public function test_unselected_age_groups_default_to_unanswered(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $this->actingAs($user)->post('/profile', [
            'nickname' => 'とと',
        ]);

        // Assert
        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
            'age_group' => AgeGroup::Unanswered->value,
            'child_age_group' => ChildAgeGroup::Unanswered->value,
        ]);
    }

    /**
     * `PATCH /settings/profile` で既存プロフィールの値が更新されることを検証する。
     */
    public function test_updating_a_profile_persists_new_values(): void
    {
        // Arrange
        $user = User::factory()->create();
        Profile::factory()->create([
            'user_id' => $user->id,
            'nickname' => '旧ニックネーム',
            'age_group' => AgeGroup::Twenties,
            'child_age_group' => ChildAgeGroup::Zero,
        ]);

        // Act
        $response = $this->actingAs($user)->patch('/settings/profile', [
            'nickname' => '新ニックネーム',
            'age_group' => AgeGroup::Forties->value,
            'child_age_group' => ChildAgeGroup::Three->value,
        ]);

        // Assert
        $response->assertRedirect(route('settings.profile.edit'));
        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
            'nickname' => '新ニックネーム',
            'age_group' => AgeGroup::Forties->value,
            'child_age_group' => ChildAgeGroup::Three->value,
        ]);
    }

    /**
     * `age_group`・`child_age_group` を含まない更新リクエストが、バリデーションエラーには
     * ならず（両フィールドとも任意のため）、かつ既存の値を維持せず `Unanswered` に
     * リセットされることを検証する。
     *
     * `ProfileRequest` はキー自体が無くても未選択と同じ扱いにするため、PATCHは部分更新ではなく
     * 常に3項目（`nickname`／`age_group`／`child_age_group`）の全置換になる（`ProfileRequest`
     * クラスdocblock参照）。S8のフォームは常に3項目とも送信するため実害は無いが、その前提を
     * 崩す変更（部分送信フォームの追加等）を検知できるよう固定しておく。
     */
    public function test_omitting_age_groups_on_update_resets_them_to_unanswered(): void
    {
        // Arrange
        $user = User::factory()->create();
        Profile::factory()->create([
            'user_id' => $user->id,
            'nickname' => '旧ニックネーム',
            'age_group' => AgeGroup::Twenties,
            'child_age_group' => ChildAgeGroup::Zero,
        ]);

        // Act
        $response = $this->actingAs($user)->patch('/settings/profile', [
            'nickname' => '新ニックネーム',
        ]);

        // Assert
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
            'nickname' => '新ニックネーム',
            'age_group' => AgeGroup::Unanswered->value,
            'child_age_group' => ChildAgeGroup::Unanswered->value,
        ]);
    }

    /**
     * プロフィール未登録ユーザーが `/`（S3の暫定プレースホルダ）へ直接アクセスすると
     * `profile.register` へリダイレクトされることを検証する（`EnsureProfileIsComplete`）。
     */
    public function test_a_user_without_a_profile_is_redirected_to_registration_from_home(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)->get('/');

        // Assert
        $response->assertRedirect(route('profile.register'));
    }

    /**
     * プロフィール登録済みユーザーは `/` へアクセスできることを検証する。
     */
    public function test_a_user_with_a_profile_can_access_home(): void
    {
        // Arrange
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        // Act
        $response = $this->actingAs($user)->get('/');

        // Assert
        $response->assertOk();
    }

    /**
     * プロフィール未登録ユーザーは `/settings/profile` へ直接アクセスしても
     * `profile.register` へリダイレクトされることを検証する。
     */
    public function test_a_user_without_a_profile_is_redirected_away_from_profile_edit(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)->get('/settings/profile');

        // Assert
        $response->assertRedirect(route('profile.register'));
    }

    /**
     * `profile.register`・`profile.store`・`logout` は `EnsureProfileIsComplete` の対象外で、
     * プロフィール未登録でも無限リダイレクトにならずアクセスできることを検証する。
     */
    public function test_profile_registration_routes_are_accessible_without_a_profile(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act & Assert
        $this->actingAs($user)->get('/profile/register')->assertOk();
        $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));
    }

    /**
     * S2の選択肢に `Unanswered`（未回答）が含まれないことを検証する。
     *
     * 未選択の状態は空欄（プレースホルダ）で表現するため、選択肢としての「未回答」は
     * 重複した表現になる。
     */
    public function test_registration_page_offers_selectable_options_excluding_unanswered(): void
    {
        // Act
        $response = $this->actingAs(User::factory()->create())->get('/profile/register');

        // Assert
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Profile/Register')
            ->has('ageGroups', count(AgeGroup::cases()) - 1)
            ->has('childAgeGroups', count(ChildAgeGroup::cases()) - 1),
        );
    }

    /**
     * S8が現在のプロフィール値をInertia propsとして受け取ることを検証する。
     */
    public function test_edit_page_receives_the_current_profile_values(): void
    {
        // Arrange
        $user = User::factory()->create();
        Profile::factory()->create([
            'user_id' => $user->id,
            'nickname' => 'とと',
            'age_group' => AgeGroup::Forties,
            'child_age_group' => ChildAgeGroup::Two,
        ]);

        // Act
        $response = $this->actingAs($user)->get('/settings/profile');

        // Assert
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Settings/ProfileEdit')
            ->where('profile.nickname', 'とと')
            ->where('profile.age_group', AgeGroup::Forties->value)
            ->where('profile.child_age_group', ChildAgeGroup::Two->value),
        );
    }

    /**
     * `nickname` の上限（50文字）ちょうどは通ることを検証する。
     *
     * `ProfileRequest` の `max:50` と `profiles.nickname`（`varchar(50)`）の桁数が一致している
     * ことを固定する。片方だけ変更すると、SQLiteのテスト環境では気付けず本番MySQLで
     * 切り詰め・エラーになる。
     */
    public function test_nickname_at_the_max_length_is_accepted(): void
    {
        // Arrange
        $user = User::factory()->create();
        $nickname = str_repeat('あ', 50);

        // Act
        $response = $this->actingAs($user)->post('/profile', ['nickname' => $nickname]);

        // Assert
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('profiles', ['user_id' => $user->id, 'nickname' => $nickname]);
    }

    /**
     * `nickname` が上限を1文字超えると `max:50` で弾かれることを検証する。
     */
    public function test_nickname_over_the_max_length_is_rejected(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)->post('/profile', ['nickname' => str_repeat('あ', 51)]);

        // Assert
        $response->assertSessionHasErrors('nickname');
        $this->assertDatabaseMissing('profiles', ['user_id' => $user->id]);
    }

    /**
     * `age_group` に列挙外の値を送るとバリデーションで弾かれることを検証する。
     *
     * `Rule::enum(AgeGroup::class)` が機能していることと、`lang/ja/validation.php` に追加した
     * `enum` メッセージのキーが実際に解決されることを併せて確認する。
     */
    public function test_an_out_of_range_age_group_is_rejected(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)->post('/profile', [
            'nickname' => 'とと',
            'age_group' => 99,
        ]);

        // Assert
        $response->assertSessionHasErrors('age_group');
        $this->assertDatabaseMissing('profiles', ['user_id' => $user->id]);
    }

    /**
     * 登録済みユーザーが `GET /profile/register` に再訪しても `home` へリダイレクトされ、
     * 空の登録フォームが再表示されないことを検証する（`RedirectIfProfileIsComplete`）。
     */
    public function test_a_user_with_a_profile_is_redirected_away_from_registration_page(): void
    {
        // Arrange
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        // Act
        $response = $this->actingAs($user)->get('/profile/register');

        // Assert
        $response->assertRedirect(route('home'));
    }

    /**
     * 登録済みユーザーが `POST /profile` を再送しても、`profiles.user_id` のUNIQUE制約違反で
     * 500にならず `home` へリダイレクトされることを検証する（`RedirectIfProfileIsComplete`）。
     *
     * 登録完了後にブラウザバックでS2に戻って再送信する、S2を複数タブで開いたまま両方
     * 送信するといった通常操作で再現しうる経路のため、回帰テストとして固定する。
     */
    public function test_resubmitting_registration_does_not_cause_a_unique_constraint_error(): void
    {
        // Arrange
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id, 'nickname' => '最初の登録']);

        // Act
        $response = $this->actingAs($user)->post('/profile', [
            'nickname' => '再送された登録',
        ]);

        // Assert
        $response->assertRedirect(route('home'));
        $this->assertDatabaseHas('profiles', ['user_id' => $user->id, 'nickname' => '最初の登録']);
        $this->assertSame(1, Profile::query()->where('user_id', $user->id)->count());
    }

    /**
     * 未認証で `POST /profile` へアクセスすると `login` へリダイレクトされることを検証する。
     *
     * `GET /` については `GoogleAuthenticationTest` で確認済みの `auth` ミドルウェアの効果を、
     * プロフィール登録のアクション系ルートでも代表して固定する。
     */
    public function test_unauthenticated_access_to_profile_store_redirects_to_login(): void
    {
        // Act
        $response = $this->post('/profile', ['nickname' => 'とと']);

        // Assert
        $response->assertRedirect(route('login'));
    }

    /**
     * プロフィール未登録ユーザーが `PATCH /settings/profile` を直接叩いても
     * `profile.register` へリダイレクトされることを検証する。
     *
     * `EnsureProfileIsComplete` は `GET /settings/profile` だけでなく `PATCH` にも
     * 効くことを別途固定する（GETのみ検証していると更新アクション側の適用漏れに気付けない）。
     */
    public function test_a_user_without_a_profile_is_redirected_away_from_profile_update(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)->patch('/settings/profile', ['nickname' => 'とと']);

        // Assert
        $response->assertRedirect(route('profile.register'));
    }
}
