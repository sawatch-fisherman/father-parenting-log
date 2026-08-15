<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * S3（記録画面）の表示を担当する。育児ログの登録処理はM4の `CareLogController` が担当する。
 */
class RecordController extends Controller
{
    /**
     * ピン留め済みの育児行動を `slot_position`（1〜8）順に並べた記録画面を表示する。
     *
     * 行が無い `slot_position` は空きスロット（`null`）として渡す。
     *
     * @see docs/data-model.md ⑤
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $slotConfigsByPosition = $user->userSlotConfigs()
            ->with('careAction')
            ->orderBy('slot_position')
            ->get()
            ->keyBy('slot_position');

        $slots = collect(range(1, 8))
            ->map(function (int $position) use ($slotConfigsByPosition): ?array {
                $slotConfig = $slotConfigsByPosition->get($position);

                if ($slotConfig === null) {
                    return null;
                }

                return [
                    'careActionId' => $slotConfig->care_action_id,
                    'name' => $slotConfig->careAction?->name,
                ];
            })
            ->all();

        return Inertia::render('Record/Index', [
            'slots' => $slots,
        ]);
    }
}
