<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCareLogRequest;
use App\Models\CareAction;
use App\Models\User;
use App\Support\CareLogWindow;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * S10（実施日時指定画面）の表示と、S3短タップ／S10保存を兼ねる育児ログ登録処理を担当する。
 */
class CareLogController extends Controller
{
    /**
     * 実施日時指定画面（S10）を表示する。
     */
    public function create(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $careAction = CareAction::query()
            ->accessibleTo($user)
            ->findOrFail($request->integer('care_action_id'));

        return Inertia::render('CareLogs/Create', [
            'careAction' => [
                'id' => $careAction->id,
                'name' => $careAction->name,
            ],
            'backdateFloorDate' => CareLogWindow::backdateFloor()->toDateString(),
            'todayDate' => now()->toDateString(),
        ]);
    }

    /**
     * 育児ログを登録する（S3短タップ／S10保存の共通エンドポイント）。
     *
     * 称号判定（M5）はこのスライスでは未実装のため行わず、常にS3（記録画面）へ戻す。
     */
    public function store(StoreCareLogRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $profile = $user->profile()->firstOrFail();

        try {
            $user->careLogs()->create([
                'care_action_id' => $request->validated('care_action_id'),
                'occurred_at' => $request->validated('occurred_at') ?? now(),
                'age_group' => $profile->age_group,
                'child_age_group' => $profile->child_age_group,
                'memo' => $request->validated('memo'),
            ]);
        } catch (UniqueConstraintViolationException) {
            // アプリ層の Rule::unique（StoreCareLogRequest）はほぼ全ての二重送信を弾くが、
            // 複数タブからのほぼ同時送信のような真の競合状態はすり抜けうるため、
            // DBのUNIQUE制約違反も同じ分かりやすいエラーに変換する
            // （ProfileController@store のUNIQUE制約吸収と同型の対応）。
            throw ValidationException::withMessages([
                'occurred_at' => __('validation.care_log_occurred_at_duplicate'),
            ]);
        }

        return redirect()->route('home')->with(
            'success',
            __('care_logs.recorded', ['name' => $request->careAction()->name])
        );
    }
}
