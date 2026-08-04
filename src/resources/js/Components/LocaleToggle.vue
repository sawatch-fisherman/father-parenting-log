<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';

interface SharedProps {
    locale: string;
    [key: string]: unknown;
}

const page = usePage<SharedProps>();

function setLocale(locale: string): void {
    if (locale === page.props.locale) {
        return;
    }

    router.post('/locale', { locale }, { preserveScroll: true });
}
</script>

<template>
    <div class="flex items-center gap-1 text-sm">
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
