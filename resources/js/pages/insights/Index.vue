<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InsightController from '@/actions/App/Http/Controllers/InsightController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { index } from '@/routes/insights';

type Insight = {
    id: number;
    kind: string;
    severity: 'info' | 'warning' | 'critical';
    title: string;
    body: string;
    status: 'new' | 'dismissed' | 'acted_on';
    created_at: string;
};

type Paginated<T> = { data: T[] };

defineProps<{ insights: Paginated<Insight> }>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Insights', href: index() }],
    },
});

const accent = {
    info: 'bg-blue-500',
    warning: 'bg-amber-500',
    critical: 'bg-red-500',
} as const;
</script>

<template>
    <Head title="Insights" />

    <div class="flex flex-col gap-6 p-4">
        <Heading
            title="Insights"
            description="Patterns and nudges to keep you on track."
        />

        <div v-if="!insights.data.length">
            <Card>
                <CardContent class="flex flex-col items-center gap-2 p-10 text-center">
                    <p class="font-medium">No insights yet</p>
                    <p class="text-sm text-muted-foreground">
                        Check back after a week of logging — we'll surface
                        patterns here.
                    </p>
                </CardContent>
            </Card>
        </div>

        <div v-else class="flex flex-col gap-3">
            <Card
                v-for="insight in insights.data"
                :key="insight.id"
                class="relative overflow-hidden"
            >
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
                    <div class="flex items-center justify-end gap-2">
                        <Form
                            :action="InsightController.update({ insight: insight.id })"
                            v-slot="{ processing }"
                        >
                            <input type="hidden" name="status" value="acted_on" />
                            <Button
                                type="submit"
                                size="sm"
                                :disabled="processing"
                            >
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
        </div>
    </div>
</template>
