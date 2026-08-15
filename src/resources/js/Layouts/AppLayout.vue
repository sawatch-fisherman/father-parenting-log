<script setup lang="ts">
// S3（記録）・S13（履歴）・S12（集計）・S7（設定）の4画面が共有するグローバルナビゲーション
// （docs/screens.md「共通UI：グローバルナビゲーション」）。各ページの<script setup>で
// defineOptions({ layout: [AppLayout, { active: '...' }] }) を指定すると、Inertiaがページ遷移の
// 間もこのレイアウトのインスタンスを維持したまま中身（<slot />）だけを差し替える
// （Bladeの@extends/@sectionと違い、ナビ自体は毎回作り直されない）。
import { Link } from '@inertiajs/vue3';
import { useTrans } from '@/composables/useTrans';

type NavKey = 'record' | 'history' | 'stats' | 'settings';

defineProps<{
    active: NavKey;
}>();

const { t } = useTrans();

const navItems: { key: NavKey; label: string; href: string }[] = [
    { key: 'record', label: t('nav.record'), href: '/' },
    { key: 'history', label: t('nav.history'), href: '/history' },
    { key: 'stats', label: t('nav.stats'), href: '/stats' },
    { key: 'settings', label: t('nav.settings'), href: '/settings' },
];
</script>

<template>
    <div class="min-h-screen bg-background text-text-primary md:flex">
        <!-- デスクトップ：左サイドバー（DESIGN.md 10章 Navigation。幅220px・右端border） -->
        <nav class="hidden shrink-0 border-r border-border bg-surface px-4 py-8 md:block md:w-[220px]">
            <ul class="space-y-1">
                <li v-for="item in navItems" :key="item.key">
                    <Link
                        :href="item.href"
                        class="block rounded-md px-4 py-3 text-label font-semibold"
                        :class="item.key === active ? 'bg-primary-subtle text-primary' : 'text-text-secondary hover:text-primary'"
                    >
                        {{ item.label }}
                    </Link>
                </li>
            </ul>
        </nav>

        <main class="flex-1 pb-24 md:pb-0">
            <!-- 8.3 セクション構成：ナビを持つ画面はメインエリアをspace-xl(32px)の内側パディングで囲む -->
            <div class="mx-auto max-w-[960px] p-8">
                <slot />
            </div>
        </main>

        <!-- モバイル：下部固定タブバー（DESIGN.md 10章 Navigation。高さ56px・上端border・Level2影） -->
        <nav class="fixed inset-x-0 bottom-0 z-10 flex h-14 border-t border-border bg-surface shadow-level-2 md:hidden">
            <Link
                v-for="item in navItems"
                :key="item.key"
                :href="item.href"
                class="flex min-w-11 flex-1 flex-col items-center justify-center text-label font-semibold"
                :class="item.key === active ? 'text-primary' : 'text-text-secondary'"
            >
                {{ item.label }}
            </Link>
        </nav>
    </div>
</template>
