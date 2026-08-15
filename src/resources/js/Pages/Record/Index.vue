<script setup lang="ts">
// Inertia::render('Record/Index')（RecordController@index）が読み込むS3のページコンポーネント。
// M3時点ではピン留め済みの育児行動を表示するだけで、短タップ＝即記録／長押し＝S10・
// 「その他」ボタン→S4といったタップ操作の配線はM4（育児ログ登録）で行う。
import AppLayout from '@/Layouts/AppLayout.vue';
import { useTrans } from '@/composables/useTrans';

interface Slot {
    careActionId: number;
    name: string;
}

defineProps<{
    // slot_position（1〜8）順の配列。行が無い位置はnull（空きスロット）。
    slots: (Slot | null)[];
}>();

const { t } = useTrans();

defineOptions({
    layout: [AppLayout, { active: 'record' }],
});
</script>

<template>
    <div>
        <header class="mb-6 flex items-center justify-between">
            <!-- Google SSOのユーザーアイコンは表示のみ・DB非保存の想定（docs/screens.md S3）。
                 実際のアイコン取得・表示はこのマイルストーンの対象外のため、仮の絵文字を置く -->
            <span class="text-heading-l" aria-hidden="true">👤</span>
            <h1 class="text-heading-l font-bold">{{ t('record.title') }}</h1>
        </header>

        <!-- グリッドは4列×2段固定（docs/wireframes.md S3） -->
        <div class="grid grid-cols-4 gap-2">
            <div
                v-for="(slot, index) in slots"
                :key="index"
                class="flex aspect-square flex-col items-center justify-center gap-1 rounded-[20px] border border-border bg-surface px-2 text-center"
            >
                <span v-if="slot" class="text-label font-semibold text-text-primary">{{ slot.name }}</span>
                <template v-else>
                    <span class="text-heading-m font-semibold text-text-secondary" aria-hidden="true">＋</span>
                    <span class="text-body-sm text-text-secondary">{{ t('record.empty_slot') }}</span>
                </template>
            </div>
        </div>

        <div class="mt-6 flex justify-center">
            <!-- M4でCareActionController@other（S4）へのLinkに置き換える -->
            <button
                type="button"
                class="rounded-xl border border-border bg-transparent px-5 py-3 text-label font-semibold text-secondary"
            >
                {{ t('record.other') }}
            </button>
        </div>
    </div>
</template>
