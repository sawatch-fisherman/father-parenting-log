<script setup lang="ts">
// 称号獲得モーダル（S5）。育児ログ登録の結果として自動表示される（ユーザー操作による遷移ではない。
// docs/screens.md）。「Xに投稿」でXの投稿画面を投稿文プリフィル済みで開き、「閉じる」でS3に戻る
// （docs/wireframes.md S5）。
//
// 投稿文のうち絵文字・称号名・タグライン・ハッシュタグの固定レイアウトはクライアント側で組み立て、
// `achievementText`（称号ごとに異なる達成内容の一文）だけをサーバーから受け取った値のまま埋め込む
// （docs/decisions.md §1.3。追加のサーバー往復は発生しない）。
import { computed, ref } from 'vue';
import { useButtonClasses } from '@/composables/useButtonClasses';
import { useModalFocus } from '@/composables/useModalFocus';
import { useTrans } from '@/composables/useTrans';

const props = defineProps<{
    name: string;
    achievementText: string;
}>();

const emit = defineEmits<{
    close: [];
}>();

const { t } = useTrans();
const { primaryButtonClass, secondaryButtonClass } = useButtonClasses();

const dialogRef = ref<HTMLElement | null>(null);
useModalFocus(dialogRef, () => emit('close'));

// 投稿文の全文。モーダル上に出すのは称号名と`achievementText`だけで、タグライン・ハッシュタグは
// 個人情報を含まない定型部のため表示しない（何が投稿されるかの確認はXの投稿画面で行える）。
const shareText = computed(() =>
    [
        `🏅${t('titles.unlocked')}`,
        '',
        `「${props.name}」`,
        props.achievementText,
        t('titles.share_tagline'),
        t('titles.share_hashtags'),
    ].join('\n'),
);

const xIntentUrl = computed(() => `https://x.com/intent/post?text=${encodeURIComponent(shareText.value)}`);
</script>

<template>
    <!-- DESIGN.md 10章：オーバーレイrgba(51,48,44,0.5)＋中央配置。コンテンツは角丸16px・Level 2の影・bg-surface -->
    <div class="fixed inset-0 z-30 flex items-center justify-center bg-overlay p-4">
        <div
            ref="dialogRef"
            role="dialog"
            aria-modal="true"
            aria-labelledby="title-unlocked-heading"
            tabindex="-1"
            class="w-full max-w-sm rounded-2xl bg-surface p-6 text-center shadow-level-2 focus:outline-none"
        >
            <!-- DESIGN.md 10章：称号解除モーダルはAccentカラー（#D97757）を使った祝福演出にする -->
            <span class="text-display" aria-hidden="true">🏅</span>
            <h2 id="title-unlocked-heading" class="mt-2 text-heading-l font-bold text-accent">{{ t('titles.unlocked') }}</h2>
            <p class="mt-4 text-heading-m font-semibold text-text-primary">「{{ name }}」</p>
            <p class="mt-2 text-body text-text-secondary">{{ achievementText }}</p>

            <div class="mt-6 flex flex-col gap-3">
                <!-- 外部サイト（X）への遷移のためbuttonではなくaを使う。DESIGN.md 10章のPrimaryボタンと
                     同じ見た目に揃えるため、inline-flexで中央寄せする。 -->
                <a
                    :href="xIntentUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    :class="['inline-flex min-h-11 items-center justify-center', primaryButtonClass]"
                >
                    {{ t('titles.share_to_x') }}
                </a>
                <button
                    type="button"
                    :class="['min-h-11', secondaryButtonClass]"
                    @click="emit('close')"
                >
                    {{ t('titles.close') }}
                </button>
            </div>
        </div>
    </div>
</template>
