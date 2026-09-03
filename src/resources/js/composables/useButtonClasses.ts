// DESIGN.md 10章 Buttons のバリアント仕様をここに集約する。`useFormFieldClasses`（Forms）と
// 同じ理由で、複数の画面が同じクラス文字列を個別に書くと、Buttons仕様の変更時に一部だけ
// 追従漏れが起きるため。
//
// 返すのは「バリアントとしての見た目」だけで、`w-full`・`min-h-11`・`mt-4`・
// `inline-flex items-center justify-center` のような**配置に属するユーティリティは呼び出し側で足す**
// （同じバリアントでも画面ごとに幅や並べ方が違うため）。`inputClass` に枠線色を含めず
// エラー時だけ各フィールドで付けるのと同じ切り分け。
//
// `disabled:` はバリアントに含める。DESIGN.md 11章 Disabled は「不透明度ではなく Text Secondary の
// 専用トーン＋`cursor: not-allowed`」を全ボタン共通で要求しており、送信中の二重送信防止で
// disabled にするボタンが増えても見た目が揃うようにするため。`<a>`／`<Link>`（SSOログイン・
// Xへの投稿・空状態のCTA）は disabled になりえず `&:disabled` が一致しないので、
// 付いていても無害な死にクラスになる。
export function useButtonClasses() {
    const variantBase = 'rounded-xl px-5 py-3 text-label font-semibold';

    const focusRing = 'focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-primary/25';

    /** Primary：画面の主要操作。1画面に原則1つ（DESIGN.md 10章 Buttons）。 */
    const primaryButtonClass = `${variantBase} ${focusRing} bg-primary text-white hover:bg-primary-hover disabled:cursor-not-allowed disabled:bg-border disabled:text-text-secondary`;

    /** Secondary：主要操作と並ぶ補助操作（キャンセル・戻る等）。 */
    const secondaryButtonClass = `${variantBase} ${focusRing} border border-border bg-transparent text-secondary disabled:cursor-not-allowed disabled:text-text-secondary`;

    /** Destructive：記録削除など取り消し不能な操作。塗りにはせず枠線と文字色をError色にする。 */
    const destructiveButtonClass = `${variantBase} ${focusRing} border border-error bg-transparent text-error disabled:cursor-not-allowed disabled:border-border disabled:text-text-secondary`;

    return { primaryButtonClass, secondaryButtonClass, destructiveButtonClass };
}
