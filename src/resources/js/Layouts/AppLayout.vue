<script setup lang="ts">
// S3（記録）・S13（履歴）・S12（集計）・S7（設定）の4画面が共有するグローバルナビゲーション
// （docs/screens.md「共通UI：グローバルナビゲーション」）。各ページの<script setup>で
// defineOptions({ layout: [AppLayout, { active: '...' }] }) を指定すると、Inertiaがページ遷移の
// 間もこのレイアウトのインスタンスを維持したまま中身（<slot />）だけを差し替える
// （Bladeの@extends/@sectionと違い、ナビ自体は毎回作り直されない）。
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useTrans } from '@/composables/useTrans';

type NavKey = 'record' | 'history' | 'stats' | 'settings';

const props = defineProps<{
    active: NavKey;
}>();

const { t } = useTrans();

// computed にしているのは、S7（設定画面）にロケール切替トグルが置かれるM8以降、
// ロケール変更後もこのレイアウトが再マウントされずラベルだけ古い言語のまま残ることを防ぐため
// （永続レイアウトのためインスタンスがページ遷移をまたいで維持される＝script setup直下の評価は1回きり）。
const navItems = computed(() => [
    { key: 'record' as const, label: t('nav.record'), href: '/' },
    { key: 'history' as const, label: t('nav.history'), href: '/history' },
    { key: 'stats' as const, label: t('nav.stats'), href: '/stats' },
    { key: 'settings' as const, label: t('nav.settings'), href: '/settings' },
]);

// フォーカスリング（DESIGN.md 11章 Focus）。既存コンポーネント（LocaleToggle等）と同じ組み合わせ。
const focusRingClass = 'focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-primary/25';
</script>

<template>
    <div class="min-h-screen bg-background text-text-primary md:flex">
        <!-- デスクトップ：左サイドバー（DESIGN.md 10章 Navigation。幅220px・右端border） -->
        <nav aria-label="グローバルナビゲーション" class="hidden shrink-0 border-r border-border bg-surface px-4 py-8 md:block md:w-55">
            <ul class="space-y-1">
                <li v-for="item in navItems" :key="item.key">
                    <Link
                        :href="item.href"
                        :aria-current="item.key === props.active ? 'page' : undefined"
                        :class="[
                            'block rounded-md px-4 py-3 text-label font-semibold',
                            focusRingClass,
                            item.key === props.active ? 'bg-primary-subtle text-primary' : 'text-text-secondary hover:text-primary',
                        ]"
                    >
                        {{ item.label }}
                    </Link>
                </li>
            </ul>
        </nav>

        <main class="flex-1 pb-24 md:pb-0">
            <!-- 8.1/8.3 セクション構成：モバイルはspace-md(16px)、デスクトップはspace-xl(32px)の内側パディング -->
            <div class="mx-auto max-w-[960px] p-4 md:p-8">
                <slot />
            </div>
        </main>

        <!-- モバイル：下部固定タブバー（DESIGN.md 10章 Navigation。高さ56px・上端border・Level2影） -->
        <nav aria-label="グローバルナビゲーション" class="fixed inset-x-0 bottom-0 z-10 flex h-14 border-t border-border bg-surface shadow-level-2 md:hidden">
            <Link
                v-for="item in navItems"
                :key="item.key"
                :href="item.href"
                :aria-current="item.key === props.active ? 'page' : undefined"
                :class="[
                    'flex min-w-11 flex-1 flex-col items-center justify-center text-label font-semibold',
                    focusRingClass,
                    item.key === props.active ? 'text-primary' : 'text-text-secondary',
                ]"
            >
                {{ item.label }}
            </Link>
        </nav>
    </div>
</template>
