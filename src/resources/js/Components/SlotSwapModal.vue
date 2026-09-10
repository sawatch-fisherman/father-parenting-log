<script setup lang="ts">
// S9（ピン留め設定画面）のスロット入れ替えモーダル。一覧からの選択はクライアント側の状態を
// 差し替えるだけで、確定はSlots.vue側の「保存する」ボタン（PUT /settings/slots）まで送らない
// （docs/wireframes.md S9「スロット入れ替えモーダル」）。
//
// フォーカス管理は`DeleteCareLogModal`と同じ`useModalFocus`に委ねる。
import { ref } from 'vue';
import { useButtonClasses } from '@/composables/useButtonClasses';
import { useModalFocus } from '@/composables/useModalFocus';
import { useTrans } from '@/composables/useTrans';

interface Candidate {
    id: number;
    name: string;
}

defineProps<{
    // 現在このスロットに入っている育児行動名。空きスロットの場合はnull。
    currentName: string | null;
    // 現在ピン留めされていない育児行動（このスロットの選択候補）。
    candidates: Candidate[];
}>();

const emit = defineEmits<{
    select: [candidate: Candidate];
    close: [];
}>();

const { t } = useTrans();
const { focusRing } = useButtonClasses();

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
            aria-labelledby="slot-swap-heading"
            tabindex="-1"
            class="w-full max-w-sm rounded-2xl bg-surface p-6 shadow-level-2 focus:outline-none"
        >
            <div class="mb-4 flex items-center justify-between gap-2">
                <h2 id="slot-swap-heading" class="text-heading-m font-semibold text-text-primary">
                    {{ currentName ? t('settings.swap_modal_title', { name: currentName }) : t('settings.swap_modal_title_empty') }}
                </h2>
                <button
                    type="button"
                    :aria-label="t('settings.swap_modal_close')"
                    :class="['flex min-h-11 min-w-11 shrink-0 items-center justify-center text-heading-m text-text-secondary', focusRing]"
                    @click="emit('close')"
                >
                    <span aria-hidden="true">×</span>
                </button>
            </div>

            <!-- 候補一覧はモーダル内スクロール（docs/wireframes.md S9：画面下部に候補一覧を常設しない） -->
            <ul class="max-h-80 divide-y divide-border overflow-y-auto border-t border-border">
                <li v-for="candidate in candidates" :key="candidate.id">
                    <button
                        type="button"
                        :class="['flex min-h-11 w-full items-center px-1 py-3 text-left text-body text-text-primary', focusRing]"
                        @click="emit('select', candidate)"
                    >
                        {{ candidate.name }}
                    </button>
                </li>
            </ul>
        </div>
    </div>
</template>
