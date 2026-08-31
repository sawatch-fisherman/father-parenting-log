import { readonly, ref } from 'vue';

/**
 * トースト通知の状態を保持する composable。
 *
 * 表示先は `AppLayout`（永続レイアウト）に1つだけ置く `ToastHost.vue`。状態をモジュール
 * スコープに置いているのはそのためで、どのページ・どの深さのコンポーネントから `show()` を
 * 呼んでも同じ1件の表示枠に流れ込む。
 *
 * 投入経路は現状サーバー flash（`HandleInertiaRequests::share()` の `flash.success`）→
 * `ToastHost` が watch して `show()` する経路のみ。用途は保存成功の通知（Success）専用
 * （DESIGN.md 10章「Dialogs and Notifications」・11章「Success」）。
 */

interface Toast {
    // 同じ文言を続けて表示したとき（同じ育児行動を連続で記録した場合など）にも
    // <Transition> が「別のトースト」と認識できるよう、表示ごとに一意なキーを振る。
    id: number;
    message: string;
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
    function show(message: string): void {
        clearTimer();

        current.value = { id: nextId++, message };

        dismissTimer = setTimeout(dismiss, TOAST_DURATION_MS);
    }

    return { current: readonly(current), show };
}
