<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCareLogRequest;
use App\Http\Requests\UpdateCareLogRequest;
use App\Models\CareAction;
use App\Models\CareLog;
use App\Models\User;
use App\Services\TitleGrantService;
use App\Support\CareLogWindow;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * S10（実施日時指定画面）の表示と、S3短タップ／S10保存を兼ねる育児ログ登録処理、
 * および S11（ログ編集画面）の表示・更新・削除を担当する。
 */
class CareLogController extends Controller
{
    public function __construct(private readonly TitleGrantService $titleGrantService) {}

    /**
     * 実施日時指定画面（S10）を表示する。
     */
    public function create(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $careAction = CareAction::query()
            ->accessibleTo($user)
            ->findOrFail($request->integer('care_action_id'), ['id', 'name']);

        return Inertia::render('CareLogs/Create', [
            'careAction' => [
                'id' => $careAction->id,
                'name' => $careAction->name,
            ],
            ...$this->backdateWindowProps(),
        ]);
    }

    /**
     * ログ編集画面（S11）を表示する。
     *
     * 表示（GET）も更新と同じ `update` の ability で守る。他人の記録の育児行動名・メモを
     * 覗けないようにするためと、締め切り済み（「7日前の00:00」より前）の記録は開いても
     * 保存できず、編集画面を見せる意味がないため（docs/screens.md Controller構成、
     * docs/decisions.md §1.3）。
     */
    public function edit(CareLog $careLog): Response
    {
        Gate::authorize('update', $careLog);

        $careLog->loadMissing('careAction:id,name');

        return Inertia::render('CareLogs/Edit', [
            'careLog' => [
                'id' => $careLog->id,
                // 育児行動は表示のみ（変更不可）。変えたい場合は削除→再作成する
                // （docs/wireframes.md S11）。
                'careActionName' => $careLog->careAction?->name,
                'occurredDate' => $careLog->occurred_at->toDateString(),
                'occurredTime' => $careLog->occurred_at->format('H:i'),
                'memo' => $careLog->memo,
            ],
            ...$this->backdateWindowProps(),
        ]);
    }

    /**
     * 育児ログの実施日時・メモを更新する（S11保存）。
     *
     * 認可（所有者／遡り期限）は `UpdateCareLogRequest::authorize()` が
     * `CareLogPolicy@update` に委ねて済ませている（バリデーションより先に走らせるため）。
     * 更新対象は `occurred_at`・`memo` のみで、`care_action_id` と年代2列のスナップショットは
     * `validated()` に現れないため書き換わらない。
     */
    public function update(UpdateCareLogRequest $request, CareLog $careLog): RedirectResponse
    {
        try {
            $careLog->update([
                'occurred_at' => $request->validated('occurred_at'),
                'memo' => $request->validated('memo'),
            ]);
        } catch (UniqueConstraintViolationException) {
            // 別タブからのほぼ同時操作など、アプリ層の`Rule::unique`をすり抜けた真の競合を
            // 同じ分かりやすいエラーに変換する（`store`と同型の対応）。
            throw ValidationException::withMessages([
                'occurred_at' => __('validation.care_log_occurred_at_duplicate'),
            ]);
        }

        Inertia::flash('success', __('care_logs.updated'));

        return redirect()->route('history.index');
    }

    /**
     * 育児ログを削除する（S11削除）。
     *
     * 誤って記録した場合の唯一の取り消し手段のため物理削除する（`user_titles` は永久保持で、
     * 削除しても称号の再判定・取り消しはしない。docs/decisions.md §1.3）。
     */
    public function destroy(CareLog $careLog): RedirectResponse
    {
        Gate::authorize('delete', $careLog);

        $careLog->delete();

        Inertia::flash('success', __('care_logs.deleted'));

        return redirect()->route('history.index');
    }

    /**
     * 日時入力欄（S10・S11共通）の選択可能範囲をVueへ渡すためのpropsを返す。
     *
     * @return array{backdateFloorDate: string, todayDate: string, backdateDays: int}
     */
    private function backdateWindowProps(): array
    {
        return [
            'backdateFloorDate' => CareLogWindow::backdateFloor()->toDateString(),
            'todayDate' => now()->toDateString(),
            'backdateDays' => Config::integer('totoops.care_log.backdate_days'),
        ];
    }

    /**
     * 育児ログを登録する（S3短タップ／S10保存の共通エンドポイント）。
     *
     * 保存直後に`TitleGrantService`でCount・Streak両方式の称号を同期判定し、新規獲得分を
     * レスポンスに含める（S5の称号獲得モーダルがVue側で自動表示する。docs/screens.md）。
     */
    public function store(StoreCareLogRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $profile = $user->profile()->firstOrFail(['age_group', 'child_age_group']);

        try {
            $careLog = $user->careLogs()->create([
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

        // 通常の共有props（`->with()`→session→`HandleInertiaRequests::share()`経由）だとInertiaが
        // ページpropsをブラウザのhistory stateにキャッシュするため、ブラウザバックで復元した
        // ページに古いメッセージが乗ったまま再表示されてしまう。`Inertia::flash()`はhistory state
        // に永続化されない専用チャンネル（`page.flash`）に乗るため、この用途に正しく対応する。
        Inertia::flash('success', __('care_logs.recorded', ['name' => $request->careAction()->name]));

        // 育児ログの保存を称号付与より優先する（叱責ではなく振り返り。docs/concept.md）。
        // `care_logs`は既にCOMMIT済みのため、ここで想定外の例外が起きても記録自体は失われて
        // 良くない：記録済みなのに500が返るとユーザーは記録が失敗したと誤解して再タップしうる。
        // Count・Streakは都度計算（専用の集計テーブルを持たない）なので、この回で称号を
        // 取りこぼしても次回の記録時に同じしきい値へ再度到達した時点で自然に再判定される。
        try {
            $grantedTitles = $this->titleGrantService->grant($user, $careLog);
        } catch (Throwable $exception) {
            report($exception);
            $grantedTitles = [];
        }

        if ($grantedTitles !== []) {
            Inertia::flash('titles', $grantedTitles);
        }

        return redirect()->route('home');
    }
}
