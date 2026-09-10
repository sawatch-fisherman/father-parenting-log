<?php

namespace App\Http\Requests;

use App\Models\CareAction;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * `PUT /settings/slots`（S9保存）のバリデーション。
 *
 * ID を URL に含めず常に自分自身のピン留めのみを操作するため、Policy は持たない
 * （docs/screens.md Controller構成の補足）。行数の不変条件は「最大8個」であり、
 * 8個未満（空配列を含む）での保存も許可する（docs/data-model.md ⑤、docs/decisions.md §1.3）。
 *
 * @see docs/implementation-plan.md「M8 設定（S7, S9）」
 */
class UpdateSlotConfigRequest extends FormRequest
{
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
            // `array`キー自体は必須だが、8個未満（0個を含む）の保存も通すため`required`ではなく
            // `present`にする（`required`は空配列を「未入力」として弾いてしまうため）。
            'slots' => ['present', 'array', 'max:8'],
            'slots.*.slot_position' => ['required', 'integer', 'between:1,8', 'distinct'],
            'slots.*.care_action_id' => [
                'required',
                'integer',
                'distinct',
                // `StoreCareLogRequest`と同じ理由（`Rule::exists()->where()`のクロージャは
                // `CareAction::scopeAccessibleTo()`を解決できない）で、実在チェックを
                // 独自クロージャに置き換える。
                function (string $attribute, mixed $value, Closure $fail) use ($user): void {
                    if (! CareAction::query()->accessibleTo($user)->whereKey($value)->exists()) {
                        $fail(__('validation.exists', ['attribute' => __('validation.attributes.care_action_id')]));
                    }
                },
            ],
        ];
    }
}
