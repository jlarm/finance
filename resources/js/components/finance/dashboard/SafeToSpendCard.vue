<script setup lang="ts">
import { computed } from 'vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

type Props = {
    amount: number;
    daysRemaining: number;
    currency?: string;
    cycleLabel?: string;
};

const props = withDefaults(defineProps<Props>(), {
    currency: 'USD',
    cycleLabel: 'until end of cycle',
});

const money = (v: number) =>
    new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: props.currency,
    }).format(v ?? 0);

const perDay = computed(() => {
    if (props.daysRemaining <= 0) {
        return 0;
    }
    return Math.max(0, props.amount / props.daysRemaining);
});

const tone = computed(() => {
    if (props.amount <= 0) {
        return 'text-rose-600 dark:text-rose-400';
    }
    if (perDay.value < 5) {
        return 'text-amber-600 dark:text-amber-400';
    }
    return 'text-emerald-600 dark:text-emerald-400';
});
</script>

<template>
    <Card>
        <CardHeader class="pb-2">
            <CardDescription>Safe to spend</CardDescription>
            <CardTitle :class="['text-3xl tabular-nums', tone]">
                {{ money(amount) }}
            </CardTitle>
        </CardHeader>
        <CardContent class="space-y-1 pt-0 text-sm text-muted-foreground">
            <p>
                About
                <strong class="tabular-nums">{{ money(perDay) }}</strong>
                per day for
                <strong>{{ daysRemaining }}</strong>
                {{ daysRemaining === 1 ? 'day' : 'days' }} {{ cycleLabel }}.
            </p>
            <p class="text-xs">
                After setting aside upcoming bills and goal contributions.
            </p>
        </CardContent>
    </Card>
</template>
