import { onBeforeUnmount, onMounted, type Ref } from 'vue';

const FOCUSABLE_SELECTOR =
    'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';

/**
 * モーダルダイアログに最低限のフォーカス管理（初期フォーカス・Tabキーのフォーカストラップ・
 * Escapeキーでの閉じる操作）を追加する。
 *
 * `role="dialog" aria-modal="true"` は「背景は操作できない」と宣言する一方、実際にフォーカスを
 * ダイアログ内へ移し・閉じ込めないと、キーボード・支援技術の利用者はTabで背景要素（S3の
 * 8タイル・グローバルナビ等）を巡回してしまい宣言と実挙動が食い違う。マウス利用者は
 * オーバーレイ（`fixed inset-0`）がクリックを遮るため影響を受けないが、キーボード操作は
 * オーバーレイの有無に関係なくDOM順で背景へフォーカスが移ってしまうため、この対応が必要。
 *
 * S5（`TitleUnlockedModal`）で使うほか、M6以降の削除確認・スロット入替モーダルでも同じ形で
 * 再利用できるよう composable に切り出している。
 *
 * @param containerRef ダイアログ本体（`role="dialog"`の要素）への template ref。
 *   呼び出し側で`tabindex="-1"`を付けておくこと（Tabの通常巡回対象には含めず、
 *   マウント時の`focus()`だけで初期フォーカスを移すため）。
 * @param onEscape Escapeキー押下時に呼ぶコールバック（通常は`close`イベントのemit）。
 *
 * @see review-results/pr-11-review.md Medium「モーダルにフォーカストラップ・Escape・初期フォーカスがない」
 * @see review-results/pr-11-review-2.md Medium「Shift+Tabでフォーカストラップを抜ける」
 * @see review-results/pr-11-review-2.md Low「モーダルを閉じたあとフォーカスが復帰しない」
 */
export function useModalFocus(containerRef: Ref<HTMLElement | null>, onEscape: () => void): void {
    // 開く前にフォーカスされていた要素（S3のタイル等）。閉じたときにここへ戻す。
    let previouslyFocused: HTMLElement | null = null;

    function focusableElements(): HTMLElement[] {
        if (!containerRef.value) {
            return [];
        }

        return Array.from(containerRef.value.querySelectorAll<HTMLElement>(FOCUSABLE_SELECTOR));
    }

    function handleKeydown(event: KeyboardEvent): void {
        if (event.key === 'Escape') {
            onEscape();
            return;
        }

        if (event.key !== 'Tab') {
            return;
        }

        const elements = focusableElements();
        if (elements.length === 0) {
            return;
        }

        const first = elements[0];
        const last = elements[elements.length - 1];
        const active = document.activeElement;

        // `focusableElements()`は`containerRef.value.querySelectorAll()`（子孫のみ）が対象のため、
        // `tabindex="-1"`のダイアログ本体自身は含まれない。ところが初期フォーカスは
        // `onMounted`でその本体に置かれるため、開いた直後の最初の1打鍵がShift+Tabだと
        // `active`は`first`でも`last`でもなくなり、どちらの分岐にも一致しないまま
        // ブラウザ標準の挙動で背景へフォーカスが漏れる。本体自身も「先頭」として扱うことで防ぐ。
        if (event.shiftKey && (active === first || active === containerRef.value)) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && active === last) {
            event.preventDefault();
            first.focus();
        }
    }

    onMounted(() => {
        previouslyFocused = document.activeElement instanceof HTMLElement ? document.activeElement : null;

        // S5は「育児ログ登録の結果として自動表示」（ユーザー操作による遷移ではない）ため、
        // フォーカスを移さないとキーボード・支援技術の利用者はモーダルの出現に気づけない
        // （docs/wireframes.md S5）。
        containerRef.value?.focus();
        document.addEventListener('keydown', handleKeydown);
    });

    onBeforeUnmount(() => {
        document.removeEventListener('keydown', handleKeydown);

        // 記録直後の操作でタイル構成が変わっている等、退避先が既にDOMから外れている
        // 場合は無視する（`isConnected`が`false`の要素に`focus()`しても無害だが、
        // 意図が伝わりにくいため明示的に確認する）。
        if (previouslyFocused?.isConnected) {
            previouslyFocused.focus();
        }
    });
}
