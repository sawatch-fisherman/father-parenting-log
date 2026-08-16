<?php

namespace App\Http\Requests;

use App\Models\CareAction;
use App\Models\User;
use App\Support\CareLogWindow;
use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * `POST /care-logs`（S3短タップ／S10保存の共通エンドポイント）のバリデーション。
 *
 * `occurred_at` はクライアントが必ず送信する運用だが（[decisions.md](decisions.md) §1.3）、
 * 省略された場合は `CareLogController@store` 側で `now()` にフォールバックするため、
 * ここでは `nullable` として扱う（省略時は下記の範囲・重複チェックの対象外になる）。
 *
 * @see docs/implementation-plan.md「M4 育児ログ登録（S3 短タップ, S4, S10）」
 */
class StoreCareLogRequest extends FormRequest
{
    /**
     * {@see self::careAction()} が解決した育児行動のキャッシュ。
     */
    private ?CareAction $careAction = null;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->user();

        return [
            'care_action_id' => [
                'required',
                'integer',
                // `Rule::exists()` のクロージャは `Illuminate\Database\Query\Builder` しか受け取れず
                // `CareAction::scopeAccessibleTo()`（Eloquentスコープ）をそのまま呼べないため、
                // 実在チェックそのものを`accessibleTo()`経由に置き換えて認可条件を1箇所に保つ。
                function (string $attribute, mixed $value, Closure $fail) use ($user): void {
                    if (! CareAction::query()->accessibleTo($user)->whereKey($value)->exists()) {
                        $fail(__('validation.exists', ['attribute' => __('validation.attributes.care_action_id')]));
                    }
                },
            ],
            'occurred_at' => [
                'nullable',
                // `->format()`を`betweenOrEqual()`より先に呼ぶ必要がある：`Rule::date`は
                // Carbonインスタンスを既定で`Y-m-d`（時刻を捨てる）にフォーマットするため、
                // 先に秒精度のフォーマットを指定しておかないと「now()+5分」の上限が
                // 「今日中ならいつでも可」まで緩んでしまう。
                Rule::date()->format('Y-m-d H:i:s')->betweenOrEqual(
                    CareLogWindow::backdateFloor(),
                    now()->addMinutes(5)
                ),
                Rule::unique('care_logs')->where(
                    fn (Builder $query) => $query
                        ->where('user_id', $user->id)
                        ->where('care_action_id', $this->integer('care_action_id'))
                ),
            ],
            'memo' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * バリデーション済みの `care_action_id` が指す育児行動を返す。
     *
     * 保存成功トーストに育児行動名を出すために `CareLogController@store` が使う。8個のタイルが
     * 密に並ぶ S3 では「保存された」だけでなく「どれが保存されたか」まで示さないと、
     * 誤タップに気付けないため（DESIGN.md 11章 Success）。
     */
    public function careAction(): CareAction
    {
        /** @var User $user */
        $user = $this->user();

        return $this->careAction ??= CareAction::query()
            ->accessibleTo($user)
            ->findOrFail($this->integer('care_action_id'));
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'occurred_at.after_or_equal' => __('validation.care_log_occurred_at_too_old'),
            'occurred_at.before_or_equal' => __('validation.care_log_occurred_at_future'),
            'occurred_at.unique' => __('validation.care_log_occurred_at_duplicate'),
        ];
    }

    /**
     * メモの未入力（空文字）を`null`に正規化する（`ProfileRequest`と同じ理由）。
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'memo' => $this->memo === '' ? null : $this->memo,
        ]);
    }
}
