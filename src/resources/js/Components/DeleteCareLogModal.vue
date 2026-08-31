<script setup lang="ts">
// 育児ログの削除確認モーダル（S11の「削除する」から開く）。削除は取り消し不能な操作のため、
// 確認を挟む（DESIGN.md 10章 Dialogs and Notifications）。
//
// S11ページ内に直接書かず独立したコンポーネントにしているのは、`useModalFocus` が
// onMounted/onBeforeUnmount で初期フォーカスとフォーカストラップを張る設計のため。
// ページ側に置くと「モーダルを開いた瞬間」ではなく「ページを開いた瞬間」に張られてしまう。
import { ref } from 'vue';
import { useModalFocus } from '@/composables/useModalFocus';
import { useTrans } from '@/composables/useTrans';

defineProps<{
    // 削除リクエスト送信中。二重送信防止のためボタンをdisableする。
    processing: boolean;
}>();

const emit = defineEmits<{
    confirm: [];
    close: [];
}>();

const { t } = useTrans();

const dialogRef = ref<HTMLElement | null>(null);
useModalFocus(dialogRef, () => emit('close'));
</script>

<template>
    <!-- DESIGN.md 10章：オーバーレイrgba(51,48,44,0.5)＋中央配置。コンテンツは角丸16px・Level 2の影 -->
    <div class="fixed inset-0 z-30 flex items-center justify-center bg-overlay p-4">
        <div
            ref="dialogRef"
            role="dialog"
            aria-modal="true"
            aria-labelledby="delete-care-log-heading"
            tabindex="-1"
            class="w-full max-w-sm rounded-2xl bg-surface p-6 shadow-level-2 focus:outline-none"
        >
            <h2 id="delete-care-log-heading" class="text-heading-m font-semibold text-text-primary">
                {{ t('care_logs.delete_confirm_title') }}
            </h2>
            <p class="mt-2 text-body text-text-secondary">{{ t('care_logs.delete_confirm_body') }}</p>

            <div class="mt-6 flex flex-col gap-3">
                <!-- Destructive（DESIGN.md 10章 Buttons）：塗りにはせず枠線と文字色をError色にする -->
                <button
                    type="button"
                    class="min-h-11 rounded-xl border border-error bg-transparent px-5 py-3 text-label font-semibold text-error focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-primary/25 disabled:cursor-not-allowed disabled:border-border disabled:text-text-secondary"
                    :disabled="processing"
                    @click="emit('confirm')"
                >
                    {{ t('care_logs.delete_confirm_submit') }}
                </button>
                <button
                    type="button"
                    class="min-h-11 rounded-xl border border-border bg-transparent px-5 py-3 text-label font-semibold text-secondary focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-primary/25"
                    @click="emit('close')"
                >
                    {{ t('care_logs.delete_confirm_cancel') }}
                </button>
            </div>
        </div>
    </div>
</template>
