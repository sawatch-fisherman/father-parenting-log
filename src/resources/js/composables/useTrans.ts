import { usePage } from '@inertiajs/vue3';

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
    function t(key: string, replacements: Record<string, string | number> = {}): string {
        const page = usePage<SharedProps>();
        const value = resolve(page.props.messages, key);
        let text = typeof value === 'string' ? value : key;

        for (const [placeholder, replacement] of Object.entries(replacements)) {
            text = text.replaceAll(`:${placeholder}`, String(replacement));
        }

        return text;
    }

    return { t };
}
