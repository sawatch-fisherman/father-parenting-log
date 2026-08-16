// DESIGN.md 10章 Formsの入力欄仕様をここに集約する。`ProfileFormFields.vue`・`Pages/CareLogs/Create.vue`
// など複数のフォームコンポーネントが同じクラス文字列を個別に書くと、Forms仕様の変更時に
// 一部だけ追従漏れが起きるため（PR #10レビュー指摘）。
export function useFormFieldClasses() {
    const inputClass =
        'w-full rounded-md border bg-surface px-4 py-3 text-body text-text-primary focus:border-primary focus:outline-none focus:ring-[3px] focus:ring-primary/25';

    const labelClass = 'block text-label font-semibold text-text-primary';

    const errorClass = 'text-body-sm text-error';

    return { inputClass, labelClass, errorClass };
}
