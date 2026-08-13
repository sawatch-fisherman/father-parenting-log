<?php

namespace App\Http\Requests;

use App\Enums\AgeGroup;
use App\Enums\ChildAgeGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * S2（登録）・S8（編集）で共用するプロフィールのバリデーション。
 *
 * `age_group`／`child_age_group` は未選択（空文字）を許可する。空文字は
 * `prepareForValidation()` で `null` に正規化され、`null` から `AgeGroup::Unanswered`／
 * `ChildAgeGroup::Unanswered` への変換は `Profile` モデルのミューテータ
 * （`Profile::ageGroup()`／`childAgeGroup()`）が担う（カラム自体は NOT NULL のため、
 * DB上のNULLではなく列挙値の「未回答」で表現する）。
 *
 * **`store`／`update` とも、リクエストにキー自体が無い場合も未選択と同じ扱いになり
 * `Unanswered` に補われる（＝S8のPATCHは部分更新ではなく全置換）。** S8は現在の値を
 * 常にプリフィルした3項目（`nickname`／`age_group`／`child_age_group`）を毎回送信する
 * フォーム（`ProfileEdit.vue`）が唯一の送信元のため実害は無いが、将来「ニックネームだけ
 * 送る部分フォーム」等を追加する場合はこの前提が壊れる（未送信キーが `Unanswered` に
 * 巻き戻る）点に注意する。部分更新にしたい場合は `prepareForValidation()` を
 * `$this->has()` で分岐させるか、store/update でFormRequestを分ける必要がある。
 *
 * @see docs/implementation-plan.md「M2 プロフィール（S2, S8）」
 */
class ProfileRequest extends FormRequest
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
        return [
            'nickname' => ['required', 'string', 'max:50'],
            'age_group' => ['nullable', Rule::enum(AgeGroup::class)],
            'child_age_group' => ['nullable', Rule::enum(ChildAgeGroup::class)],
        ];
    }

    /**
     * 未選択（空文字）を、後続のバリデーションが `nullable` として扱えるよう null に正規化する。
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'age_group' => $this->age_group === '' ? null : $this->age_group,
            'child_age_group' => $this->child_age_group === '' ? null : $this->child_age_group,
        ]);
    }
}
