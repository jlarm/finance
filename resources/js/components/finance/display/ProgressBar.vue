<script setup lang="ts">
import { computed } from 'vue';

type Props = {
    value: number;
    max?: number;
    tone?: 'default' | 'positive' | 'warning' | 'danger';
    label?: string;
    showPercentage?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    max: 100,
    tone: 'default',
    showPercentage: false,
});

const pct = computed(() => {
    if (!props.max || props.max <= 0) {
        return 0;
    }
    return Math.max(0, Math.min(100, (props.value / props.max) * 100));
});

const barClass = {
    default: 'bg-primary',
    positive: 'bg-emerald-500',
    warning: 'bg-amber-500',
    danger: 'bg-rose-500',
} as const;
</script>

<template>
    <div class="flex flex-col gap-1">
        <div
            v-if="label || showPercentage"
            class="flex items-center justify-between text-xs text-muted-foreground"
        >
            <span v-if="label">{{ label }}</span>
            <span v-if="showPercentage" class="tabular-nums">
                {{ Math.round(pct) }}%
            </span>
        </div>
        <div class="h-2 w-full overflow-hidden rounded-full bg-muted">
            <div
                class="h-full rounded-full transition-all"
                :class="barClass[tone]"
                :style="{ width: `${pct}%` }"
            />
        </div>
    </div>
</template>
