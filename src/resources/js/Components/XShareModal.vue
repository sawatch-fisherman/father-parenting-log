<script setup lang="ts">
// X投稿文生成モーダル（S6）。S5「Xに投稿」から遷移する（docs/screens.md）。
// 絵文字・称号名・ハッシュタグ等の固定レイアウトはクライアント側で組み立て、`achievementText`
// （称号ごとに異なる達成内容の一文）だけをサーバーから受け取った値のまま埋め込む
// （docs/decisions.md §1.3。追加のサーバー往復は発生しない）。
import { computed, ref } from 'vue';
import { useModalFocus } from '@/composables/useModalFocus';
import { useToast } from '@/composables/useToast';
import { useTrans } from '@/composables/useTrans';

const props = defineProps<{
    name: string;
    achievementText: string;
}>();

const emit = defineEmits<{
    close: [];
}>();

const { t } = useTrans();
const { show } = useToast();

const dialogRef = ref<HTMLElement | null>(null);
useModalFocus(dialogRef, () => emit('close'));

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

function copyShareText(): void {
    // Clipboard APIはセキュアコンテキスト（HTTPS・localhost）でのみ公開されるため、
    // `http://<LAN内IP>` のような非セキュアな接続では`navigator.clipboard`自体が`undefined`になり、
    // 存在確認なしで`.writeText`にアクセスすると同期的にTypeErrorが発生する（`.catch`では拾えない）。
    // 失敗時は無反応にせず、上の投稿文を手動選択してコピーする代替手段をトーストで案内する
    // （review-results/pr-11-review.md Medium「clipboard未提供環境で同期例外・失敗時のフィードバックがない」）。
    if (!navigator.clipboard) {
        show(t('titles.copy_failed'), 'error');
        return;
    }

    navigator.clipboard
        .writeText(shareText.value)
        .then(() => show(t('titles.copied')))
        .catch(() => show(t('titles.copy_failed'), 'error'));
}
</script>

<template>
    <div class="fixed inset-0 z-30 flex items-center justify-center bg-overlay p-4">
        <div
            ref="dialogRef"
            role="dialog"
            aria-modal="true"
            aria-labelledby="x-share-heading"
            tabindex="-1"
            class="w-full max-w-sm rounded-2xl bg-surface p-6 text-center shadow-level-2 focus:outline-none"
        >
            <h2 id="x-share-heading" class="text-heading-m font-semibold text-text-primary">
                <span aria-hidden="true">🏅</span>
                {{ t('titles.unlocked') }}
            </h2>

            <p class="mt-4 whitespace-pre-line text-left text-body text-text-primary">{{ shareText }}</p>

            <div class="mt-6 flex flex-col gap-3">
                <button
                    type="button"
                    class="min-h-11 rounded-xl bg-primary px-5 py-3 text-label font-semibold text-white hover:bg-primary-hover focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-primary/25"
                    @click="copyShareText"
                >
                    {{ t('titles.copy') }}
                </button>
                <a
                    :href="xIntentUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="min-h-11 rounded-xl border border-border bg-transparent px-5 py-3 text-label font-semibold text-secondary focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-primary/25"
                >
                    {{ t('titles.open_x') }}
                </a>
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
