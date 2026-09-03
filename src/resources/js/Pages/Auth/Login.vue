<script setup lang="ts">
// Inertia::render('Auth/Login')（routes/web.php）が読み込むページコンポーネント
import { usePage } from '@inertiajs/vue3';
import LocaleToggle from '@/Components/LocaleToggle.vue'; // 別コンポーネントの読み込み（Bladeのcomponentに近い）
import { useButtonClasses } from '@/composables/useButtonClasses';
import { useTrans } from '@/composables/useTrans'; // 自作の翻訳ヘルパー（PHPの __() のVue版）

// サーバーの HandleInertiaRequests::share() が渡す flash.error の型
interface SharedProps {
    flash: { error: string | null };
    [key: string]: unknown;
}

const { t } = useTrans();
const { primaryButtonClass } = useButtonClasses();
const page = usePage<SharedProps>();
</script>

<template>
    <div class="flex min-h-screen flex-col bg-background text-text-primary">
        <div class="flex justify-end px-4 pt-6">
            <LocaleToggle />
        </div>

        <div class="flex flex-1 flex-col items-center justify-center gap-6 px-4 text-center">
            <div class="space-y-2">
                <!-- ブランドロゴ色は Primary（DESIGN.md 5.2）。Background に対して約4.7:1 で本文基準を満たす -->
                <h1 class="text-display font-bold text-primary">TotoOps</h1>
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
                :class="primaryButtonClass"
            >
                {{ t('auth.google_login') }}
            </a>
        </div>
    </div>
</template>
