<script setup lang="ts">
// Inertia::render('Settings/Slots')（SlotConfigController@edit）が読み込むS9のページ。
// 単機能画面のためグローバルナビは表示しない（AppLayout未使用。docs/wireframes.md S8/S11と同型）。
//
// スロットのタップ→候補選択はすべてクライアント側の状態（`slots`）を差し替えるだけで、
// サーバーへは「保存する」タップ時に8枠分をまとめて`PUT /settings/slots`で送る
// （docs/wireframes.md S9「スロット入れ替えモーダル」）。
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import SlotSwapModal from '@/Components/SlotSwapModal.vue';
import { useButtonClasses } from '@/composables/useButtonClasses';
import { useTrans } from '@/composables/useTrans';

interface Slot {
    careActionId: number;
    name: string | null;
}

interface CareAction {
    id: number;
    name: string;
}

const props = defineProps<{
    // slot_position（1〜8）順の配列。行が無い位置はnull（空きスロット）。
    slots: (Slot | null)[];
    // アクセス可能な育児行動全件（ピン留め中かどうかに関わらず全件）。
    careActions: CareAction[];
}>();

const { t } = useTrans();
const { primaryButtonClass, focusRing } = useButtonClasses();

// サーバーpropsを直接書き換えず、モーダルでの入れ替えをこのローカルコピー上で行う
// （保存を確定するまでサーバーには反映しない）。
const slots = ref<(Slot | null)[]>([...props.slots]);

const pinnedCareActionIds = computed(
    () => new Set(slots.value.filter((slot): slot is Slot => slot !== null).map((slot) => slot.careActionId)),
);

const candidates = computed(() => props.careActions.filter((careAction) => !pinnedCareActionIds.value.has(careAction.id)));

const activeSlotIndex = ref<number | null>(null);

function openSwapModal(index: number): void {
    activeSlotIndex.value = index;
}

function closeSwapModal(): void {
    activeSlotIndex.value = null;
}

function selectCareAction(careAction: CareAction): void {
    if (activeSlotIndex.value === null) {
        return;
    }

    slots.value[activeSlotIndex.value] = { careActionId: careAction.id, name: careAction.name };
    closeSwapModal();
}

const activeSlotName = computed(() => (activeSlotIndex.value === null ? null : (slots.value[activeSlotIndex.value]?.name ?? null)));

const form = useForm<{ slots: { slot_position: number; care_action_id: number }[] }>({
    slots: [],
});

// バリデーションエラーはネスト配列のキー（`slots`／`slots.0.care_action_id`等）になり、
// S8・S11のように特定の入力欄へ添えられる形にならないため、先頭の1件をバナーで示す
// （`Pages/Record/Index.vue`のインラインエラーと同じ理由。DESIGN.md 11章 Error）。
const errorMessage = computed<string | null>(() => Object.values(form.errors)[0] ?? null);

function submit(): void {
    form.slots = slots.value
        .map((slot, index) => (slot ? { slot_position: index + 1, care_action_id: slot.careActionId } : null))
        .filter((entry): entry is { slot_position: number; care_action_id: number } => entry !== null);

    form.put('/settings/slots');
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-background text-text-primary">
        <div class="mx-auto w-full max-w-[960px] px-4 pt-6 md:px-8">
            <Link
                href="/settings"
                :class="['inline-flex min-h-11 items-center text-body-sm text-secondary hover:text-text-primary', focusRing]"
            >
                {{ t('settings.back') }}
            </Link>
        </div>

        <div class="mx-auto w-full max-w-[960px] px-4 pb-10 md:px-8">
            <h1 class="mb-2 text-heading-l font-bold">{{ t('settings.slots_title') }}</h1>
            <p class="mb-4 text-body-sm text-text-secondary">{{ t('settings.slots_hint') }}</p>

            <div
                v-if="errorMessage"
                role="alert"
                class="mb-4 flex items-center gap-2 rounded-md border border-error bg-surface px-4 py-3 text-body-sm text-error"
            >
                <span aria-hidden="true">⚠️</span>
                <span>{{ errorMessage }}</span>
            </div>

            <!-- グリッドは4列×2段固定（S3と同じ組み立て。docs/wireframes.md S9） -->
            <div class="grid grid-cols-4 gap-2">
                <button
                    v-for="(slot, index) in slots"
                    :key="index"
                    type="button"
                    :class="[
                        'flex aspect-square flex-col items-center justify-center gap-1 rounded-[20px] border border-border bg-surface px-2 text-center',
                        focusRing,
                    ]"
                    @click="openSwapModal(index)"
                >
                    <span v-if="slot" class="line-clamp-3 text-label font-semibold text-text-primary">{{ slot.name }}</span>
                    <template v-else>
                        <span class="text-heading-m font-semibold text-text-secondary" aria-hidden="true">＋</span>
                        <span class="text-body-sm text-text-secondary">{{ t('settings.slots_empty_slot') }}</span>
                    </template>
                </button>
            </div>

            <button
                type="button"
                :class="['mt-6 w-full', primaryButtonClass]"
                :disabled="form.processing"
                @click="submit"
            >
                {{ t('settings.slots_save') }}
            </button>
        </div>

        <SlotSwapModal
            v-if="activeSlotIndex !== null"
            :current-name="activeSlotName"
            :candidates="candidates"
            @select="selectCareAction"
            @close="closeSwapModal"
        />
    </div>
</template>
