<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { planner } from '@/routes';
import { create, index, show } from '@/routes/debts';

type Debt = {
    id: number;
    name: string;
    type: string;
    balance: number;
    original_balance: number | null;
    apr: number | null;
    progress_percentage: number | null;
};

defineProps<{
    debts: Debt[];
    totals: { balance: number; minimums: number };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Debts', href: index() }],
    },
});

const money = (v: number) =>
    new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: 'USD',
    }).format(v ?? 0);
</script>

<template>
    <Head title="Debts" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                title="Debts"
                description="Track every balance and your progress paying it down."
            />
            <div class="flex gap-2">
                <Button variant="outline" as-child>
                    <Link :href="planner()">Open planner</Link>
                </Button>
                <Button as-child>
                    <Link :href="create()">Add debt</Link>
                </Button>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardDescription>Total balance</CardDescription>
                    <CardTitle>{{ money(totals.balance) }}</CardTitle>
                </CardHeader>
            </Card>
            <Card>
                <CardHeader>
                    <CardDescription>Monthly minimums</CardDescription>
                    <CardTitle>{{ money(totals.minimums) }}</CardTitle>
                </CardHeader>
            </Card>
        </div>

        <div v-if="!debts.length">
            <Card>
                <CardContent class="flex flex-col items-center gap-2 p-10 text-center">
                    <p class="font-medium">No debts tracked</p>
                    <p class="text-sm text-muted-foreground">
                        Add any balances you owe to plan a path to paid off.
                    </p>
                    <Button as-child class="mt-2">
                        <Link :href="create()">Add debt</Link>
                    </Button>
                </CardContent>
            </Card>
        </div>

        <div v-else class="grid gap-4 md:grid-cols-2">
            <Card v-for="debt in debts" :key="debt.id">
                <CardHeader>
                    <div class="flex items-start justify-between">
                        <div>
                            <CardTitle>{{ debt.name }}</CardTitle>
                            <CardDescription class="capitalize">
                                {{ debt.type.replace('_', ' ') }}
                                <span v-if="debt.apr"> · {{ debt.apr }}% APR</span>
                            </CardDescription>
                        </div>
                        <Link
                            :href="show({ debt: debt.id })"
                            class="text-sm text-muted-foreground underline-offset-4 hover:underline"
                        >
                            Details
                        </Link>
                    </div>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div class="flex items-end justify-between">
                        <span class="text-sm text-muted-foreground">
                            Current balance
                        </span>
                        <span class="text-xl font-semibold tabular-nums">
                            {{ money(debt.balance) }}
                        </span>
                    </div>
                    <div
                        v-if="debt.progress_percentage !== null"
                        class="space-y-1"
                    >
                        <div class="flex justify-between text-xs text-muted-foreground">
                            <span>Paid off</span>
                            <span>{{ debt.progress_percentage }}%</span>
                        </div>
                        <div class="h-2 w-full rounded-full bg-muted">
                            <div
                                class="h-full rounded-full bg-primary"
                                :style="{ width: `${debt.progress_percentage}%` }"
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
