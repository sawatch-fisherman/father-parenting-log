import { usePage } from '@inertiajs/vue3';
import { computed, type ComputedRef } from 'vue';

type Messages = Record<string, Record<string, unknown>>;

interface SharedProps {
    locale: string;
    messages: Messages;
    [key: string]: unknown;
}

function resolve(messages: Messages, key: string): unknown {
    return key.split('.').reduce<unknown>((value, segment) => {
        if (value && typeof value === 'object' && segment in value) {
            return (value as Record<string, unknown>)[segment];
        }
        return undefined;
    }, messages);
}

export function useTrans() {
    const page = usePage<SharedProps>();

    function t(key: string, replacements: Record<string, string | number> = {}): string {
        const value = resolve(page.props.messages, key);
        let text = typeof value === 'string' ? value : key;

        for (const [placeholder, replacement] of Object.entries(replacements)) {
            text = text.replaceAll(`:${placeholder}`, String(replacement));
        }

        return text;
    }

    // 日付・数値の整形（`Intl.*`）にはBCP47のロケールタグが要る。翻訳キーを持たない
    // 「2026年7月15日」のような表記は`lang/*`に文言として持たず、現ロケールを
    // `Intl.DateTimeFormat`へ渡して組み立てる（S13の日付グループ見出し）。
    const locale: ComputedRef<string> = computed(() => page.props.locale);

    return { t, locale };
}
