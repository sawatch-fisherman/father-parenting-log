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
</script>

<template>
    <div class="flex items-center gap-1 text-sm">
        <!-- :class は動的クラス切り替え、@click はクリックイベントのハンドラ登録 -->
        <button
            type="button"
            class="font-medium"
            :class="page.props.locale === 'ja' ? 'text-gray-900 dark:text-white' : 'text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300'"
            @click="setLocale('ja')"
        >
            JA
        </button>
        <span class="text-gray-300 dark:text-gray-600">|</span>
        <button
            type="button"
            class="font-medium"
            :class="page.props.locale === 'en' ? 'text-gray-900 dark:text-white' : 'text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300'"
            @click="setLocale('en')"
        >
            EN
        </button>
    </div>
</template>
