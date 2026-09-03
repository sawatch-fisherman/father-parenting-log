<script setup lang="ts">
// トースト通知の表示枠（DESIGN.md 10章「Dialogs and Notifications」・11章「Success」）。
// AppLayout（永続レイアウト）に1つだけ置く前提で、状態は useToast() のモジュールスコープに持つ。
//
// サーバー flash（`page.flash.success`）の監視もここで行う。S3短タップもS10保存も
// `POST /care-logs` → S3 へのリダイレクトで完了するため、リダイレクト先に必ず居る
// このレイアウトで拾えば、両経路の成功フィードバックを1箇所で賄える。
//
// `page.props`（通常の共有props）ではなく`page.flash`（Inertia v3のフラッシュ専用チャンネル）を
// 監視する。`page.props`はInertiaがブラウザのhistory stateにキャッシュするため、そこに乗せると
// ブラウザバックで復元したページに古い成功メッセージが再表示されてしまう。`page.flash`は
// history stateに永続化されないため、この問題が起きない
// （`CareLogController@store`の`Inertia::flash()`、`HandleInertiaRequests`参照）。
import { usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { useToast, type ToastVariant } from '@/composables/useToast';

const page = usePage();
const { current, show } = useToast();

// 状態を色だけで伝えない（DESIGN.md 12章）。塗りの色とアイコンを必ず対で切り替える。
// 状態色はいずれも白文字での使用を前提にした明度で設計されている（DESIGN.md 5.3節）。
const VARIANT_CLASSES: Record<ToastVariant, string> = {
    success: 'bg-success',
    info: 'bg-info',
};

const VARIANT_ICONS: Record<ToastVariant, string> = {
    success: '✓',
    info: 'ℹ️',
};

const variant = computed<ToastVariant>(() => current.value?.variant ?? 'success');

// `page.flash`はグローバルな`flashDataType`宣言をしていないため型上は`Record<string, unknown>`。
// 実行時に文字列であることを確認してから使う。
//
// `page.flash`はレスポンスごとに作り直されるため、同じ文言が続けて flash されても
// （同じ育児行動を連続で記録した場合など）オブジェクト参照が変わり watch が発火する。
// `immediate: true` は、リダイレクト後の初回レンダリングで既に flash が乗っている
// ケース（フルリロードを挟んだ場合）を取りこぼさないため。
watch(
    () => page.flash,
    (flash) => {
        const success = flash?.success;
        if (typeof success === 'string') {
            show(success);
        }
    },
    { immediate: true },
);

// サーバー通信を伴う本格的なエラーは既定どおりインラインバナーで出す方針のため（DESIGN.md 11章
// Error は「再試行導線を必ず添える」ことを要求し、トーストには操作を載せない設計と両立しない）、
// `flash.error` はここでは拾わない。
</script>

<template>
    <!--
        モバイル・デスクトップとも上段中央（DESIGN.md 10章）。操作した場所（S3のタイル群は
        コンテンツ領域の上部にある）の近くに出すため。トーストには操作を載せない設計なので、
        親指の届く下部に置く理由がなく、視認性だけを優先できる。
        グローバルナビが z-10、S5等のモーダルが z-30 なので、トーストは常に最前面という
        位置づけで z-40 にする（モーダル表示中の操作フィードバックも隠れずに届く必要があるため）。
        領域自体はクリックを透過させ、背後のヘッダー・タイルの操作を妨げない。

        `md:left-55` はデスクトップの左端をサイドバーの右端（AppLayoutの `md:w-55`＝220px）に
        合わせる指定。これが無いと「ビューポートの中央」になり、コンテンツカラムの中央から
        サイドバー幅の半分（110px）だけ左にずれる。

        aria-live をトースト本体ではなく常設のこのコンテナに置いているのは、
        読み上げソフトが「後から現れた要素」ではなく「既にあるライブリージョンの
        内容の変化」として検知するため。
    -->
    <div
        role="status"
        aria-live="polite"
        class="pointer-events-none fixed inset-x-0 top-0 z-40 flex justify-center p-4 md:left-55 md:p-8"
    >
        <!-- 「派手なアニメーションは付けず、静かに数秒で消える」（DESIGN.md 11章 Success）ため、
             画面上端から滑り込む向きのわずかなスライドとフェードのみに留める。
             `mode="out-in"` は、表示中に次のトーストへ置き換わるとき（`id` が変わるとき）に
             新旧2枚がflexコンテナ内で一瞬横並びになるのを防ぐため。 -->
        <Transition
            mode="out-in"
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="-translate-y-1 opacity-0"
            leave-active-class="transition duration-150 ease-in"
            leave-to-class="opacity-0"
        >
            <div
                v-if="current"
                :key="current.id"
                :class="[
                    'pointer-events-auto flex w-full max-w-sm items-center gap-3 rounded-2xl px-4 py-3 text-body-sm text-white shadow-level-2',
                    VARIANT_CLASSES[variant],
                ]"
            >
                <!-- 状態を色だけで伝えない（DESIGN.md 12章）。アイコンと文言を必ず併記する -->
                <span aria-hidden="true">{{ VARIANT_ICONS[variant] }}</span>
                <span>{{ current.message }}</span>
            </div>
        </Transition>
    </div>
</template>
