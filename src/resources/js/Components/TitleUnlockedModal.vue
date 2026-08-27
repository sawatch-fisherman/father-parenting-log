<script setup lang="ts">
// 称号獲得モーダル（S5）。育児ログ登録の結果として自動表示される（ユーザー操作による遷移ではない。
// docs/screens.md）。「Xに投稿」でS6（XShareModal）へ、「閉じる」でS3に戻る（docs/wireframes.md S5）。
import { useTrans } from '@/composables/useTrans';

defineProps<{
    name: string;
}>();

const emit = defineEmits<{
    share: [];
    close: [];
}>();

const { t } = useTrans();
</script>

<template>
    <!-- DESIGN.md 10章：オーバーレイrgba(51,48,44,0.5)＋中央配置。コンテンツは角丸16px・Level 2の影・bg-surface -->
    <div class="fixed inset-0 z-30 flex items-center justify-center bg-overlay p-4">
        <div
            role="dialog"
            aria-modal="true"
            aria-labelledby="title-unlocked-heading"
            class="w-full max-w-sm rounded-2xl bg-surface p-6 text-center shadow-level-2"
        >
            <!-- DESIGN.md 10章：称号解除モーダルはAccentカラー（#D97757）を使った祝福演出にする -->
            <span class="text-display" aria-hidden="true">🏅</span>
            <h2 id="title-unlocked-heading" class="mt-2 text-heading-l font-bold text-accent">{{ t('titles.unlocked') }}</h2>
            <p class="mt-4 text-heading-m font-semibold text-text-primary">「{{ name }}」</p>

            <div class="mt-6 flex flex-col gap-3">
                <button
                    type="button"
                    class="min-h-11 rounded-xl bg-primary px-5 py-3 text-label font-semibold text-white hover:bg-primary-hover focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-primary/25"
                    @click="emit('share')"
                >
                    {{ t('titles.share_to_x') }}
                </button>
                <button
                    type="button"
                    class="min-h-11 rounded-xl border border-border bg-transparent px-5 py-3 text-label font-semibold text-secondary focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-primary/25"
                    @click="emit('close')"
                >
                    {{ t('titles.close') }}
                </button>
            </div>
        </div>
    </div>
</template>
