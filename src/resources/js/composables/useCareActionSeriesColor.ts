// S12（期間別集計画面）の積み上げ棒・内訳表で使う「育児行動ごとの固定色」を解決する。
// DESIGN.md 5.5節の規則どおり、色のindexは`care_action_id`（不変）から求め、`sort_order`は使わない。
// 実値（25色）は`--color-series-1`〜`--color-series-25`として`src/resources/css/app.css`の`@theme`に
// 定義済みで、ここではそのCSS変数名を解決するだけに留める（HEX値を二重管理しない）。

// `App\Support\CareActionId::CUSTOM_ID_FLOOR`と同じ値（標準行の予約域の上限）。
const CUSTOM_ID_FLOOR = 1000;

function seriesColorIndex(careActionId: number): number {
    if (careActionId < CUSTOM_ID_FLOOR) {
        return careActionId;
    }

    // Phase 2のユーザーカスタム育児行動用（18〜25）。MVPでは到達しない。
    return 18 + ((careActionId - CUSTOM_ID_FLOOR) % 8);
}

export function useCareActionSeriesColor() {
    function cssVarName(careActionId: number): string {
        return `--color-series-${seriesColorIndex(careActionId)}`;
    }

    /**
     * 内訳表の色チップ（DOM要素）用。ブラウザがCSS変数をそのまま解決するので、
     * inline styleにCSS変数の参照を渡すだけでよい。
     */
    function chipStyle(careActionId: number): Record<string, string> {
        return { backgroundColor: `var(${cssVarName(careActionId)})` };
    }

    /**
     * Chart.js（Canvas描画）用。CanvasはCSS変数を解釈できないため、`getComputedStyle`で
     * 実際のHEX値を読んでから渡す（DESIGN.md 5.5節・implementation-plan.md M7備考）。
     */
    function resolvedColor(careActionId: number): string {
        return getComputedStyle(document.documentElement).getPropertyValue(cssVarName(careActionId)).trim();
    }

    return { chipStyle, resolvedColor };
}
