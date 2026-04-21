<script setup lang="ts">
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <Card>
        <CardHeader class="flex flex-row items-start justify-between gap-2 space-y-0">
            <div class="space-y-1">
                <CardTitle>{{ title }}</CardTitle>
                <CardDescription v-if="description">
                    {{ description }}
                </CardDescription>
            </div>
            <slot name="actions" />
        </CardHeader>
        <CardContent>
            <slot />
        </CardContent>
    </Card>
</template>
