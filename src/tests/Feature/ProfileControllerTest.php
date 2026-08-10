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
     * `care_action_id`・`age_group`・`child_age_group` を含まない部分更新でも、
     * 送信していないフィールドがバリデーションエラーにならず更新できることを検証する
     * （`ProfileRequest` は store/update 共用で両フィールドとも任意のため）。
     */
    public function test_updating_only_the_nickname_keeps_other_fields_untouched_by_validation(): void
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
}
