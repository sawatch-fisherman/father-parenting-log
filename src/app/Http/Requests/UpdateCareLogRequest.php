<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesBlankMemo;
use App\Http\Requests\Concerns\ValidatesOccurredAt;
use App\Models\CareLog;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * `PATCH /care-logs/{care_log}`（S11保存）のバリデーション。
 *
 * 変更できるのは `occurred_at` と `memo` だけで、`care_action_id`・`age_group`・
 * `child_age_group` は受け取らない（育児行動を変えたい場合は削除→再作成。年代2列は
 * 記録時点のスナップショットのため事後に書き換えない。[docs/decisions.md](../../../../docs/decisions.md) §1.3）。
 * リクエストに含めて送られてきても `validated()` に現れないため、コントローラ側で除外する必要はない。
 *
 * @see docs/implementation-plan.md「M6 履歴（S13, S11）」
 */
class UpdateCareLogRequest extends FormRequest
{
    use NormalizesBlankMemo, ValidatesOccurredAt;

    /**
     * 対象の育児ログを操作できるか（所有者／遡り期限）を `CareLogPolicy` に委ねる。
     *
     * コントローラ側の `Gate::authorize()` ではなくここで判定するのは、FormRequest の
     * 認可がバリデーションより先に走るため。逆順だと、締め切り済みの記録に対する更新が
     * 「権限が無い（403）」ではなく「日時が古すぎる（422）」として返ってしまい、
     * 実際には日時を今日に変えても保存できないのに直せそうな見た目になる。
     */
    public function authorize(): bool
    {
        return Gate::allows('update', $this->careLog());
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->user();
        $careLog = $this->careLog();

        return [
            'occurred_at' => [
                // S11は日時を空にできる画面ではない（実施日・実施時刻とも入力必須）ため、
                // `StoreCareLogRequest`（短タップ経由の省略を許容する）と違い `required` にする。
                'required',
                $this->occurredAtRangeRule(),
                // 編集中の行を `ignore()` で除外しないと、`occurred_at` を変えずにメモだけ
                // 保存した場合に自分自身と衝突して弾かれる。`care_action_id` はリクエストから
                // ではなく対象の記録から取る（この画面では変更できない項目のため）。
                Rule::unique('care_logs')->where(
                    fn (Builder $query) => $query
                        ->where('user_id', $user->id)
                        ->where('care_action_id', $careLog->care_action_id)
                )->ignore($careLog),
            ],
            'memo' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->occurredAtMessages();
    }

    /**
     * メモの未入力（空文字）を`null`に正規化する。
     *
     * S11 は「空にして保存すればメモを削除できる」画面のため、空文字のまま保存すると
     * 見た目上は消えても `memo` に空文字が残る（[docs/wireframes.md](../../../../docs/wireframes.md) S11）。
     */
    protected function prepareForValidation(): void
    {
        $this->normalizeBlankMemo();
    }

    /**
     * ルートモデルバインディングで解決済みの対象育児ログを返す。
     */
    private function careLog(): CareLog
    {
        $careLog = $this->route('care_log');

        assert($careLog instanceof CareLog);

        return $careLog;
    }
}
