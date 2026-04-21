<script setup lang="ts">
import { computed } from 'vue';

type Props = {
    projectedIncome: number;
    projectedExpenses: number;
    currency?: string;
    horizon?: string;
};

const props = withDefaults(defineProps<Props>(), {
    currency: 'USD',
    horizon: 'this month',
});

const delta = computed(
    () => props.projectedIncome - props.projectedExpenses,
);

const isNegative = computed(() => delta.value < 0);

const money = (v: number) =>
    new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: props.currency,
    }).format(v ?? 0);
</script>

<template>
    <div
        v-if="isNegative"
        class="flex items-start gap-3 rounded-md border border-rose-200 bg-rose-50 p-4 text-sm dark:border-rose-900/40 dark:bg-rose-950/30"
    >
        <div
            class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-rose-500 text-white"
            aria-hidden="true"
        >
            !
        </div>
        <div class="flex-1">
            <p class="font-medium text-rose-900 dark:text-rose-200">
                Cash-flow warning
            </p>
            <p class="mt-1 text-rose-800/90 dark:text-rose-200/80">
                You're projected to spend
                <strong class="tabular-nums">
                    {{ money(Math.abs(delta)) }}
                </strong>
                more than you earn {{ horizon }}.
            </p>
            <div v-if="$slots.action" class="mt-3">
                <slot name="action" />
            </div>
        </div>
    </div>

    <div
        v-else
        class="flex items-start gap-3 rounded-md border border-emerald-200 bg-emerald-50 p-4 text-sm dark:border-emerald-900/40 dark:bg-emerald-950/30"
    >
        <div
            class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white"
            aria-hidden="true"
        >
            ✓
        </div>
        <div class="flex-1">
            <p class="font-medium text-emerald-900 dark:text-emerald-200">
                You're in the green
            </p>
            <p class="mt-1 text-emerald-800/90 dark:text-emerald-200/80">
                Projected surplus of
                <strong class="tabular-nums">{{ money(delta) }}</strong>
                {{ horizon }}.
            </p>
        </div>
    </div>
</template>
