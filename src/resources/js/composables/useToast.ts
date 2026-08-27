import { readonly, ref } from 'vue';

/**
 * トースト通知の状態を保持する composable。
 *
 * 表示先は `AppLayout`（永続レイアウト）に1つだけ置く `ToastHost.vue`。状態をモジュール
 * スコープに置いているのはそのためで、どのページ・どの深さのコンポーネントから `show()` を
 * 呼んでも同じ1件の表示枠に流れ込む。
 *
 * 投入経路は2つある：
 * - サーバー flash（`HandleInertiaRequests::share()` の `flash.success`）→ `ToastHost` が watch して `show()` する
 * - クライアント起因（サーバー通信を伴わない操作）→ 各コンポーネントが直接 `show()` を呼ぶ
 *
 * 用途は保存成功の通知（Success）と、クライアント側だけで完結する軽微な失敗の通知（Error。
 * 例：`XShareModal`のクリップボードコピー失敗）の2色。DESIGN.md 10章はトーストに
 * Success/Error 両方の色を認めている。サーバー通信を伴う本格的なエラーは既定どおり
 * インラインバナーに任せ（DESIGN.md 11章 Error の「再試行導線を必ず添える」要件はトーストでは
 * 満たしにくいため）、ここでのError用途は「その場で完結する単純な失敗を一言伝える」ものに限る。
 *
 * @see DESIGN.md 10章「Dialogs and Notifications」・11章「Success」「Error」
 */

type ToastVariant = 'success' | 'error';

interface Toast {
    // 同じ文言を続けて表示したとき（同じ育児行動を連続で記録した場合など）にも
    // <Transition> が「別のトースト」と認識できるよう、表示ごとに一意なキーを振る。
    id: number;
    message: string;
    variant: ToastVariant;
}

/**
 * 自動で消えるまでの時間。
 *
 * DESIGN.md 10章が「目安4秒以上／疲れた状態でも読み切れるように」と定めているため、
 * 下限の4秒ではなく5秒を採る。
 */
const TOAST_DURATION_MS = 5000;

// 同時に複数は積まず、常に最新の1件だけを表示する（DESIGN.md 11章 Success「静かに数秒で消える」）。
const current = ref<Toast | null>(null);

let nextId = 0;
let dismissTimer: ReturnType<typeof setTimeout> | null = null;

export function useToast() {
    function clearTimer(): void {
        if (dismissTimer !== null) {
            clearTimeout(dismissTimer);
            dismissTimer = null;
        }
    }

    function dismiss(): void {
        clearTimer();
        current.value = null;
    }

    /**
     * トーストを表示する。表示中に呼ばれた場合は最新の内容で置き換え、消えるまでの時間も測り直す。
     */
    function show(message: string, variant: ToastVariant = 'success'): void {
        clearTimer();

        current.value = { id: nextId++, message, variant };

        dismissTimer = setTimeout(dismiss, TOAST_DURATION_MS);
    }

    return { current: readonly(current), show };
}
