<?php

namespace App\Http\Requests;

use App\Enums\AgeGroup;
use App\Enums\ChildAgeGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * S2（登録）・S8（編集）で共用するプロフィールのバリデーション。
 *
 * `age_group`／`child_age_group` は未選択（空文字）を許可し、`profileData()` で
 * `AgeGroup::Unanswered`／`ChildAgeGroup::Unanswered` に補って返す
 * （カラム自体は NOT NULL のため、DB上のNULLではなく列挙値の「未回答」で表現する）。
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

    /**
     * バリデーション済みの値を `Profile` へそのまま保存できる形に変換する。
     *
     * @return array{nickname: string, age_group: AgeGroup, child_age_group: ChildAgeGroup}
     */
    public function profileData(): array
    {
        $validated = $this->validated();

        return [
            'nickname' => $validated['nickname'],
            'age_group' => isset($validated['age_group'])
                ? AgeGroup::from((int) $validated['age_group'])
                : AgeGroup::Unanswered,
            'child_age_group' => isset($validated['child_age_group'])
                ? ChildAgeGroup::from((int) $validated['child_age_group'])
                : ChildAgeGroup::Unanswered,
        ];
    }
}
