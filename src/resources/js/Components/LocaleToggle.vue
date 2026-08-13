<script setup lang="ts">
// Composition API: このファイル自体が1つのコンポーネントの定義（PHPのクラスに近い）
import { router, usePage } from '@inertiajs/vue3';

// サーバーの HandleInertiaRequests::share() が渡す共有propsの型
interface SharedProps {
    locale: string;
    [key: string]: unknown;
}

// 全ページ共通の共有props（現在の表示言語など）を読むためのオブジェクト
const page = usePage<SharedProps>();

// ページ全体を再読み込みせず POST /locale を叩き、レスポンスに応じて画面だけ差し替える
function setLocale(locale: string): void {
    if (locale === page.props.locale) {
        return;
    }

    router.post('/locale', { locale }, { preserveScroll: true });
}

// min-h-11/min-w-11 は DESIGN.md 9章のタップ領域44×44pxを満たすための下限。
const buttonClass =
    'flex min-h-11 min-w-11 items-center justify-center font-semibold focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-primary-deep/25';

// 選択中は白背景に置く緑の文字なので Primary ではなく Primary Deep を使う（DESIGN.md 5.2）。
const activeClass = 'text-primary-deep';

const inactiveClass = 'text-text-secondary hover:text-text-primary';
</script>

<template>
    <div class="flex items-center gap-1 text-body-sm">
        <!-- :class は動的クラス切り替え、@click はクリックイベントのハンドラ登録 -->
        <button
            type="button"
            :class="[buttonClass, page.props.locale === 'ja' ? activeClass : inactiveClass]"
            @click="setLocale('ja')"
        >
            JA
        </button>
        <span class="text-border">|</span>
        <button
            type="button"
            :class="[buttonClass, page.props.locale === 'en' ? activeClass : inactiveClass]"
            @click="setLocale('en')"
        >
            EN
        </button>
    </div>
</template>
