<script setup lang="ts">
import { Head, useHttp } from '@inertiajs/vue3';
import { Send } from 'lucide-vue-next';
import { nextTick, onMounted, ref } from 'vue';
import AssistantController from '@/actions/App/Http/Controllers/AssistantController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { index as assistantIndex } from '@/routes/assistant';

type ReferencedValue = {
    label: string;
    amount?: number;
    currency?: string;
    source?: string;
};

type AssistantMeta = {
    answer_type?: string;
    verdict?: string | null;
    referenced_values?: ReferencedValue[];
    followup_suggestions?: string[];
};

type Message = {
    id: string;
    conversation_id: string | null;
    role: 'user' | 'assistant';
    body: string;
    meta?: AssistantMeta | null;
    created_at?: string | null;
};

type ChatResponse = {
    conversation_id: string | null;
    message: {
        id?: string;
        role: 'assistant';
        answer_type: string;
        body: string;
        verdict?: string | null;
        referenced_values?: ReferencedValue[];
        followup_suggestions?: string[];
        created_at?: string;
    };
};

const props = defineProps<{
    initialConversationId: string | null;
    initialMessages: Message[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Assistant', href: assistantIndex() }],
    },
});

const http = useHttp<
    { message: string; conversation_id: string | null },
    ChatResponse
>(AssistantController.store(), {
    message: '',
    conversation_id: null,
});

const messages = ref<Message[]>([...props.initialMessages]);
const conversationId = ref<string | null>(props.initialConversationId);
const draft = ref('');
const sending = ref(false);
const errorText = ref<string | null>(null);
const scrollRef = ref<HTMLElement | null>(null);

const scrollToBottom = async (): Promise<void> => {
    await nextTick();
    const el = scrollRef.value;

    if (el) {
        el.scrollTop = el.scrollHeight;
    }
};

onMounted(() => {
    scrollToBottom();
});

const formatAmount = (value: number, currency = 'USD'): string =>
    new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency,
        maximumFractionDigits: 2,
    }).format(value);

const verdictAccent = (verdict?: string | null): string => {
    switch (verdict) {
        case 'yes':
            return 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400';
        case 'no':
            return 'bg-red-500/10 text-red-700 dark:text-red-400';
        case 'caution':
            return 'bg-amber-500/10 text-amber-700 dark:text-amber-400';
        default:
            return 'bg-muted text-muted-foreground';
    }
};

const sendMessage = async (text?: string): Promise<void> => {
    const content = (text ?? draft.value).trim();

    if (content.length === 0 || sending.value) {
        return;
    }

    const optimistic: Message = {
        id: `local-${Date.now()}`,
        conversation_id: conversationId.value,
        role: 'user',
        body: content,
        created_at: new Date().toISOString(),
    };

    messages.value.push(optimistic);
    draft.value = '';
    errorText.value = null;
    sending.value = true;
    scrollToBottom();

    try {
        http.message = content;
        http.conversation_id = conversationId.value;

        const result = await http.submit();

        conversationId.value = result.conversation_id;

        messages.value.push({
            id: result.message.id ?? `remote-${Date.now()}`,
            conversation_id: result.conversation_id,
            role: 'assistant',
            body: result.message.body,
            meta: {
                answer_type: result.message.answer_type,
                verdict: result.message.verdict ?? null,
                referenced_values: result.message.referenced_values ?? [],
                followup_suggestions: result.message.followup_suggestions ?? [],
            },
            created_at: result.message.created_at ?? new Date().toISOString(),
        });
    } catch {
        errorText.value =
            "Sorry — I couldn't reach the assistant. Check your connection and try again.";
    } finally {
        sending.value = false;
        scrollToBottom();
    }
};

const suggest = (text: string): void => {
    draft.value = text;
};
</script>

<template>
    <Head title="Assistant" />

    <div class="flex h-[calc(100vh-4rem)] flex-col gap-4 p-4">
        <Heading
            title="Financial assistant"
            description="Ask about your spending, bills, debts, goals, or whether you can afford something."
        />

        <Card class="flex min-h-0 flex-1 flex-col">
            <CardContent class="flex min-h-0 flex-1 flex-col gap-3 p-4">
                <div
                    ref="scrollRef"
                    class="flex-1 space-y-4 overflow-y-auto pr-1"
                >
                    <div
                        v-if="messages.length === 0"
                        class="flex h-full flex-col items-center justify-center gap-3 text-center text-sm text-muted-foreground"
                    >
                        <p class="max-w-md">
                            No conversation yet. Try one of these to get
                            started.
                        </p>
                        <div class="flex flex-wrap justify-center gap-2">
                            <Button
                                size="sm"
                                variant="outline"
                                @click="suggest('How did I do this month?')"
                            >
                                How did I do this month?
                            </Button>
                            <Button
                                size="sm"
                                variant="outline"
                                @click="
                                    suggest('Can I afford a $200 purchase?')
                                "
                            >
                                Can I afford a $200 purchase?
                            </Button>
                            <Button
                                size="sm"
                                variant="outline"
                                @click="
                                    suggest('What bills are coming up soon?')
                                "
                            >
                                What bills are coming up soon?
                            </Button>
                        </div>
                    </div>

                    <div
                        v-for="message in messages"
                        :key="message.id"
                        class="flex"
                        :class="
                            message.role === 'user'
                                ? 'justify-end'
                                : 'justify-start'
                        "
                    >
                        <div
                            class="max-w-[85%] space-y-2 rounded-2xl px-4 py-3 text-sm"
                            :class="
                                message.role === 'user'
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-muted'
                            "
                        >
                            <p class="whitespace-pre-wrap leading-relaxed">
                                {{ message.body }}
                            </p>

                            <div
                                v-if="
                                    message.role === 'assistant' &&
                                    message.meta?.verdict
                                "
                                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium uppercase tracking-wide"
                                :class="verdictAccent(message.meta.verdict)"
                            >
                                {{ message.meta.verdict }}
                            </div>

                            <div
                                v-if="
                                    message.role === 'assistant' &&
                                    (message.meta?.referenced_values
                                        ?.length ?? 0) > 0
                                "
                                class="mt-2 space-y-1 rounded-lg bg-background/60 p-2 text-xs"
                            >
                                <div
                                    v-for="(ref, i) in message.meta
                                        ?.referenced_values ?? []"
                                    :key="`${message.id}-ref-${i}`"
                                    class="flex items-center justify-between gap-2"
                                >
                                    <span class="text-muted-foreground">
                                        {{ ref.label }}
                                    </span>
                                    <span
                                        v-if="ref.amount !== undefined"
                                        class="font-mono"
                                    >
                                        {{
                                            formatAmount(
                                                ref.amount,
                                                ref.currency,
                                            )
                                        }}
                                    </span>
                                </div>
                            </div>

                            <div
                                v-if="
                                    message.role === 'assistant' &&
                                    (message.meta?.followup_suggestions
                                        ?.length ?? 0) > 0
                                "
                                class="mt-2 flex flex-wrap gap-1.5"
                            >
                                <Button
                                    v-for="(
                                        suggestion, i
                                    ) in message.meta?.followup_suggestions ??
                                    []"
                                    :key="`${message.id}-sg-${i}`"
                                    size="sm"
                                    variant="secondary"
                                    :disabled="sending"
                                    @click="sendMessage(suggestion)"
                                >
                                    {{ suggestion }}
                                </Button>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="sending"
                        class="flex items-center gap-2 text-xs text-muted-foreground"
                    >
                        <Spinner class="size-3" />
                        Thinking…
                    </div>
                </div>

                <p v-if="errorText" class="text-sm text-destructive">
                    {{ errorText }}
                </p>

                <form
                    class="flex items-end gap-2 border-t pt-3"
                    @submit.prevent="sendMessage()"
                >
                    <Input
                        v-model="draft"
                        placeholder="Ask about your finances…"
                        :disabled="sending"
                        class="flex-1"
                        maxlength="2000"
                        autocomplete="off"
                    />
                    <Button
                        type="submit"
                        :disabled="sending || draft.trim().length === 0"
                    >
                        <Send class="size-4" />
                        <span class="sr-only">Send</span>
                    </Button>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
