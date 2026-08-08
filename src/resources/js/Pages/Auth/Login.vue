<script setup lang="ts">
// Inertia::render('Auth/Login')（routes/web.php）が読み込むページコンポーネント
import { usePage } from '@inertiajs/vue3';
import LocaleToggle from '@/Components/LocaleToggle.vue'; // 別コンポーネントの読み込み（Bladeのcomponentに近い）
import { useTrans } from '@/composables/useTrans'; // 自作の翻訳ヘルパー（PHPの __() のVue版）

// サーバーの HandleInertiaRequests::share() が渡す flash.error の型
interface SharedProps {
    flash: { error: string | null };
    [key: string]: unknown;
}

const { t } = useTrans();
const page = usePage<SharedProps>();
</script>

<template>
    <div class="flex min-h-screen flex-col bg-white text-gray-900 dark:bg-gray-900 dark:text-white">
        <div class="flex justify-end p-4">
            <LocaleToggle />
        </div>

        <div class="flex flex-1 flex-col items-center justify-center gap-6 px-4 text-center">
            <div class="space-y-2">
                <h1 class="text-3xl font-semibold">TotoOps</h1>
                <p class="text-gray-500 dark:text-gray-400">{{ t('auth.tagline') }}</p>
            </div>

            <!-- v-if は条件付き描画（@ifに相当）。エラーがなければこの要素自体が出力されない -->
            <p v-if="page.props.flash.error" class="text-sm text-red-600 dark:text-red-400">
                {{ page.props.flash.error }}
            </p>

            <!--
              <Link>ではなく通常の<a>を使用。この先はSPA内遷移ではなく、Googleの認可画面への
              本物のHTTPリダイレクトなので、Inertiaの内部遷移にすると壊れるため。
            -->
            <a
                href="/auth/google/redirect"
                class="rounded-md bg-gray-900 px-6 py-3 text-sm font-medium text-white hover:bg-gray-700 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200"
            >
                {{ t('auth.google_login') }}
            </a>
        </div>
    </div>
</template>
