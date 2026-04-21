<script setup lang="ts">
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

type Props = {
    label: string;
    value: string | number;
    hint?: string;
    tone?: 'default' | 'positive' | 'negative' | 'muted';
};

withDefaults(defineProps<Props>(), {
    tone: 'default',
});

const toneClass = {
    default: '',
    positive: 'text-emerald-600 dark:text-emerald-400',
    negative: 'text-rose-600 dark:text-rose-400',
    muted: 'text-muted-foreground',
} as const;
</script>

<template>
    <Card>
        <CardHeader class="pb-2">
            <CardDescription>{{ label }}</CardDescription>
            <CardTitle :class="['text-2xl tabular-nums', toneClass[tone]]">
                {{ value }}
            </CardTitle>
        </CardHeader>
        <CardContent v-if="hint" class="pt-0">
            <p class="text-xs text-muted-foreground">{{ hint }}</p>
        </CardContent>
    </Card>
</template>
