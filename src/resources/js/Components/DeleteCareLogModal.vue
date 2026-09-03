<script setup lang="ts">
// 育児ログの削除確認モーダル（S11の「削除する」から開く）。削除は取り消し不能な操作のため、
// 確認を挟む（DESIGN.md 10章 Dialogs and Notifications）。
//
// S11ページ内に直接書かず独立したコンポーネントにしているのは、`useModalFocus` が
// onMounted/onBeforeUnmount で初期フォーカスとフォーカストラップを張る設計のため。
// ページ側に置くと「モーダルを開いた瞬間」ではなく「ページを開いた瞬間」に張られてしまう。
import { ref } from 'vue';
import { useButtonClasses } from '@/composables/useButtonClasses';
import { useModalFocus } from '@/composables/useModalFocus';
import { useTrans } from '@/composables/useTrans';

const props = defineProps<{
    // 削除リクエスト送信中。二重送信防止のため「削除する」ボタンをdisableするだけでなく、
    // 「キャンセル」・Escapeでも閉じられないようにする（下記参照）。
    processing: boolean;
}>();

const emit = defineEmits<{
    confirm: [];
    close: [];
}>();

const { t } = useTrans();
const { secondaryButtonClass, destructiveButtonClass } = useButtonClasses();

const dialogRef = ref<HTMLElement | null>(null);

// 送信中に閉じてしまうと、削除リクエストは既にサーバーへ渡っているため
// （`router.delete`の`onFinish`は残る）データ不整合は起きないが、利用者には
// 「キャンセルしたのに削除された」ように見えてしまう。Escapeキーもここで同様に無視する。
function closeUnlessProcessing(): void {
    if (!props.processing) {
        emit('close');
    }
}

useModalFocus(dialogRef, closeUnlessProcessing);
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
                <button
                    type="button"
                    :class="['min-h-11', destructiveButtonClass]"
                    :disabled="processing"
                    @click="emit('confirm')"
                >
                    {{ t('care_logs.delete_confirm_submit') }}
                </button>
                <button
                    type="button"
                    :class="['min-h-11', secondaryButtonClass]"
                    :disabled="processing"
                    @click="emit('close')"
                >
                    {{ t('care_logs.delete_confirm_cancel') }}
                </button>
            </div>
        </div>
    </div>
</template>
