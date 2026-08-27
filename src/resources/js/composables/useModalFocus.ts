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
 * S5（`TitleUnlockedModal`）・S6（`XShareModal`）の両方で使うため composable に切り出した。
 * M6以降の削除確認・スロット入替モーダルでも同じ形で再利用できる。
 *
 * @param containerRef ダイアログ本体（`role="dialog"`の要素）への template ref。
 *   呼び出し側で`tabindex="-1"`を付けておくこと（Tabの通常巡回対象には含めず、
 *   マウント時の`focus()`だけで初期フォーカスを移すため）。
 * @param onEscape Escapeキー押下時に呼ぶコールバック（通常は`close`イベントのemit）。
 *
 * @see review-results/pr-11-review.md Medium「モーダルにフォーカストラップ・Escape・初期フォーカスがない」
 */
export function useModalFocus(containerRef: Ref<HTMLElement | null>, onEscape: () => void): void {
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

        // ダイアログ本体自体（`tabindex="-1"`）を含めて先頭・末尾を判定するため、
        // `document.activeElement`をそのまま比較する（通常のTab巡回はブラウザ標準に任せ、
        // 両端に達した時だけ折り返す）。
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    }

    onMounted(() => {
        // S5は「育児ログ登録の結果として自動表示」（ユーザー操作による遷移ではない）ため、
        // フォーカスを移さないとキーボード・支援技術の利用者はモーダルの出現に気づけない
        // （docs/wireframes.md S5）。
        containerRef.value?.focus();
        document.addEventListener('keydown', handleKeydown);
    });

    onBeforeUnmount(() => {
        document.removeEventListener('keydown', handleKeydown);
    });
}
