<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import InsightController from '@/actions/App/Http/Controllers/InsightController';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';

type Insight = {
    id: number;
    kind: string;
    severity: 'info' | 'warning' | 'critical';
    title: string;
    body: string;
    status: 'new' | 'dismissed' | 'acted_on';
    created_at: string;
};

defineProps<{
    insight: Insight;
    showActions?: boolean;
}>();

const accent = {
    info: 'bg-blue-500',
    warning: 'bg-amber-500',
    critical: 'bg-red-500',
} as const;
</script>

<template>
    <Card class="relative overflow-hidden">
        <span
            class="absolute inset-y-0 left-0 w-1"
            :class="accent[insight.severity]"
        />
        <CardContent class="flex flex-col gap-3 p-5 pl-6">
            <div>
                <p class="font-medium">{{ insight.title }}</p>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ insight.body }}
                </p>
            </div>
            <div
                v-if="showActions !== false"
                class="flex items-center justify-end gap-2"
            >
                <Form
                    :action="InsightController.update({ insight: insight.id })"
                    v-slot="{ processing }"
                >
                    <input type="hidden" name="status" value="acted_on" />
                    <Button type="submit" size="sm" :disabled="processing">
                        Acted on
                    </Button>
                </Form>
                <Form
                    :action="InsightController.update({ insight: insight.id })"
                    v-slot="{ processing }"
                >
                    <input type="hidden" name="status" value="dismissed" />
                    <Button
                        type="submit"
                        size="sm"
                        variant="ghost"
                        :disabled="processing"
                    >
                        Dismiss
                    </Button>
                </Form>
            </div>
        </CardContent>
    </Card>
</template>
