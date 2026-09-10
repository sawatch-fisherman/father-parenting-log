<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\PinnedSlots;
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

        return Inertia::render('Record/Index', [
            'slots' => PinnedSlots::forUser($user),
        ]);
    }
}
