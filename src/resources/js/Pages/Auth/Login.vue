<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import LocaleToggle from '@/Components/LocaleToggle.vue';
import { useTrans } from '@/composables/useTrans';

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

            <p v-if="page.props.flash.error" class="text-sm text-red-600 dark:text-red-400">
                {{ page.props.flash.error }}
            </p>

            <a
                href="/auth/google/redirect"
                class="rounded-md bg-gray-900 px-6 py-3 text-sm font-medium text-white hover:bg-gray-700 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200"
            >
                {{ t('auth.google_login') }}
            </a>
        </div>
    </div>
</template>
