<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSlotConfigRequest;
use App\Models\CareAction;
use App\Models\User;
use App\Support\PinnedSlots;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * S9（ピン留め設定画面）の表示と保存を担当する。ID を URL に含めず常に自分自身のピン留めのみを
 * 操作するため、Policy は持たない（docs/screens.md Controller構成の補足）。
 */
class SlotConfigController extends Controller
{
    /**
     * ピン留め設定画面（S9）を表示する。
     */
    public function edit(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $careActions = CareAction::query()
            ->accessibleTo($user)
            ->orderBy('sort_order')
            ->get(['id', 'name']);

        return Inertia::render('Settings/Slots', [
            'slots' => PinnedSlots::forUser($user),
            'careActions' => $careActions->map(fn (CareAction $careAction): array => [
                'id' => $careAction->id,
                'name' => $careAction->name,
            ]),
        ]);
    }

    /**
     * ピン留めを一括で入れ替える（S9保存）。
     *
     * 対象ユーザーの既存行を全削除してから送信された行（最大8行）を挿入する
     * delete-insert方式を1トランザクションで行う。1行ずつ`UPDATE`すると、入れ替え途中で
     * 同じ`care_action_id`が一時的に2スロットに存在する状態を通過し
     * `UNIQUE(user_id, care_action_id)`に触れうるため（docs/decisions.md §1.3）。
     */
    public function update(UpdateSlotConfigRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var list<array{slot_position: int, care_action_id: int}> $slots */
        $slots = $request->validated('slots');

        try {
            DB::transaction(function () use ($slots, $user): void {
                $user->userSlotConfigs()->delete();
                $user->userSlotConfigs()->createMany($slots);
            });
        } catch (UniqueConstraintViolationException) {
            // 複数タブからほぼ同時に保存すると、delete-insertの2トランザクションが競合し
            // `UNIQUE(user_id, care_action_id)`に触れうる。先に確定した方のピン留めが残っており
            // 「ピン留めを更新する」という利用者の意図はいずれにせよ達成されているため、
            // 500にせず通常の保存完了と同じ着地点へ流す（`ProfileController@store`の
            // 同種の競合吸収と同じ方針）。
        }

        Inertia::flash('success', __('settings.slots_updated'));

        return redirect()->route('settings.index');
    }
}
