<?php

namespace App\Http\Controllers;

use App\Enums\AgeGroup;
use App\Enums\ChildAgeGroup;
use App\Http\Requests\ProfileRequest;
use App\Models\User;
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
    public function create(): Response
    {
        return Inertia::render('Profile/Register', [
            'ageGroups' => $this->ageGroupOptions(),
            'childAgeGroups' => $this->childAgeGroupOptions(),
        ]);
    }

    public function store(ProfileRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        DB::transaction(function () use ($request, $user): void {
            $user->profile()->create($request->profileData());

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

        return redirect()->route('home');
    }

    public function edit(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $profile = $user->profile()->firstOrFail();

        return Inertia::render('Settings/ProfileEdit', [
            'profile' => [
                'nickname' => $profile->nickname,
                'age_group' => $profile->age_group->value,
                'child_age_group' => $profile->child_age_group->value,
            ],
            'ageGroups' => $this->ageGroupOptions(),
            'childAgeGroups' => $this->childAgeGroupOptions(),
        ]);
    }

    public function update(ProfileRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->profile()->update($request->profileData());

        // M8で settings.index（S7）ができるまでの暫定リダイレクト先。
        return redirect()->route('settings.profile.edit');
    }

    /**
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
