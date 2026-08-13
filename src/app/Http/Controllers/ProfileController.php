<?php

namespace App\Http\Controllers;

use App\Enums\AgeGroup;
use App\Enums\ChildAgeGroup;
use App\Http\Requests\ProfileRequest;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * S2（登録）・S8（編集）を担当する。IDをURLに含めず常に自分自身のプロフィールのみを
 * 操作するため、Policyは持たない（docs/screens.md Controller構成の補足）。
 */
class ProfileController extends Controller
{
    /**
     * プロフィール登録画面（S2）を表示する。
     */
    public function create(): Response
    {
        return Inertia::render('Profile/Register', [
            'ageGroups' => $this->ageGroupOptions(),
            'childAgeGroups' => $this->childAgeGroupOptions(),
        ]);
    }

    /**
     * プロフィールを新規登録し、初期スロット設定を作成する。
     */
    public function store(ProfileRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            DB::transaction(function () use ($request, $user): void {
                $user->profile()->create($request->validated());

                $user->userSlotConfigs()->createMany(
                    collect(Config::array('totoops.initial_slot_care_action_ids'))
                        ->values()
                        ->map(fn (int $careActionId, int $index): array => [
                            'slot_position' => $index + 1,
                            'care_action_id' => $careActionId,
                        ])
                        ->all(),
                );
            });
        } catch (UniqueConstraintViolationException) {
            // `RedirectIfProfileIsComplete` は事前チェックと書き込みが別トランザクションのため、
            // ほぼ同時に複数タブから送信された場合はここまで素通りしうる。
            // `profiles.user_id` のUNIQUE制約違反はDBが保証しており、二重登録が成立することはないため、
            // 500にせず「既に登録済み」と同じ着地点（home）へ流す。
            return redirect()->route('home');
        }

        return redirect()->route('home');
    }

    /**
     * プロフィール編集画面（S8）を表示する。
     */
    public function edit(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $profile = $user->profile()->firstOrFail();

        return Inertia::render('Settings/ProfileEdit', [
            'profile' => [
                'nickname' => $profile->nickname,
                // `Unanswered`（0）はS2の未選択プレースホルダ（value=""）と同じ状態を表すため、
                // 選択肢からは除外している値をそのまま渡さずnullにする。0のまま渡すと、
                // どの<option>とも一致せずセレクトが空欄表示になってしまう。
                'age_group' => $profile->age_group === AgeGroup::Unanswered ? null : $profile->age_group->value,
                'child_age_group' => $profile->child_age_group === ChildAgeGroup::Unanswered ? null : $profile->child_age_group->value,
            ],
            'ageGroups' => $this->ageGroupOptions(),
            'childAgeGroups' => $this->childAgeGroupOptions(),
        ]);
    }

    /**
     * プロフィールを更新する。
     */
    public function update(ProfileRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        // `$user->profile()->update()`（リレーション経由）はクエリビルダへ委譲され、
        // `Profile` のミューテータ（null→Unansweredの正規化）を経由しないため、
        // 一度モデルインスタンスを取得してからインスタンスの `update()` を呼ぶ。
        $profile = $user->profile()->firstOrFail();
        $profile->update($request->validated());

        // M8で settings.index（S7）ができるまでの暫定リダイレクト先。
        return redirect()->route('settings.profile.edit');
    }

    /**
     * 年代の選択肢一覧を返す（未回答を除く）。
     *
     * @return array<int, array{value: int, label: string}>
     */
    private function ageGroupOptions(): array
    {
        return collect(AgeGroup::cases())
            ->reject(fn (AgeGroup $case): bool => $case === AgeGroup::Unanswered)
            ->map(fn (AgeGroup $case): array => ['value' => $case->value, 'label' => $case->label()])
            ->values()
            ->all();
    }

    /**
     * いちばん下のお子さんの年齢帯の選択肢一覧を返す（未回答を除く）。
     *
     * @return array<int, array{value: int, label: string}>
     */
    private function childAgeGroupOptions(): array
    {
        return collect(ChildAgeGroup::cases())
            ->reject(fn (ChildAgeGroup $case): bool => $case === ChildAgeGroup::Unanswered)
            ->map(fn (ChildAgeGroup $case): array => ['value' => $case->value, 'label' => $case->label()])
            ->values()
            ->all();
    }
}
