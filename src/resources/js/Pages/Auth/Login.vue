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
    <div class="flex min-h-screen flex-col bg-background text-text-primary">
        <div class="flex justify-end px-4 pt-6">
            <LocaleToggle />
        </div>

        <div class="flex flex-1 flex-col items-center justify-center gap-6 px-4 text-center">
            <div class="space-y-2">
                <!--
                  ロゴは白背景に置く緑の文字なので Primary ではなく Primary Deep を使う。
                  Primary はBackgroundに対して2.11:1しかなく、大きな文字の3:1を満たさない（DESIGN.md 5.3）。
                -->
                <h1 class="text-display font-bold text-primary-deep">TotoOps</h1>
                <p class="text-body text-text-secondary">{{ t('auth.tagline') }}</p>
            </div>

            <!-- v-if は条件付き描画（@ifに相当）。エラーがなければこの要素自体が出力されない -->
            <p v-if="page.props.flash.error" class="text-body-sm text-error">
                {{ page.props.flash.error }}
            </p>

            <!--
              <Link>ではなく通常の<a>を使用。この先はSPA内遷移ではなく、Googleの認可画面への
              本物のHTTPリダイレクトなので、Inertiaの内部遷移にすると壊れるため。
            -->
            <a
                href="/auth/google/redirect"
                class="rounded-xl border border-primary-deep bg-primary px-5 py-3 text-label font-semibold text-text-primary hover:bg-primary-hover focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-primary-deep/25"
            >
                {{ t('auth.google_login') }}
            </a>
        </div>
    </div>
</template>
