<?php

namespace App\Http\Controllers;

use App\Models\CareAction;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * S4（「その他」育児行動選択画面）の表示を担当する。
 */
class CareActionController extends Controller
{
    /**
     * ピン留めされていない育児行動の一覧を表示する。
     */
    public function other(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $pinnedCareActionIds = $user->userSlotConfigs()->pluck('care_action_id');

        $careActions = CareAction::query()
            ->accessibleTo($user)
            ->whereNotIn('id', $pinnedCareActionIds)
            ->orderBy('sort_order')
            ->get(['id', 'name']);

        return Inertia::render('CareActions/Other', [
            'careActions' => $careActions->map(fn (CareAction $careAction): array => [
                'id' => $careAction->id,
                'name' => $careAction->name,
            ]),
        ]);
    }
}
