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
import { dashboard, planner } from '@/routes';
import { create as createExpense } from '@/routes/expenses';
import { formatDate } from '@/lib/utils';

type Summary = {
    expenses_this_month: number;
    income_this_month: number;
    net_this_month: number;
    total_debt: number;
    total_savings: number;
    net_worth: number;
};

type Bill = { id: number; name: string; amount: number; next_due_on: string };
type Goal = {
    id: number;
    name: string;
    target_amount: number;
    current_amount: number;
};
type Insight = {
    id: number;
    title: string;
    body: string;
    severity: 'info' | 'warning' | 'critical';
};

defineProps<{
    summary: Summary;
    upcomingBills: Bill[];
    activeGoals: Goal[];
    recentInsights: Insight[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

const money = (v: number) =>
    new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: 'USD',
    }).format(v ?? 0);
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                title="Dashboard"
                description="A quick look at where your money is right now."
            />
            <Button as-child>
                <Link :href="createExpense()">Add expense</Link>
            </Button>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <Card>
                <CardHeader>
                    <CardDescription>Spent this month</CardDescription>
                    <CardTitle>{{ money(summary.expenses_this_month) }}</CardTitle>
                </CardHeader>
            </Card>
            <Card>
                <CardHeader>
                    <CardDescription>Income this month</CardDescription>
                    <CardTitle>{{ money(summary.income_this_month) }}</CardTitle>
                </CardHeader>
            </Card>
            <Card>
                <CardHeader>
                    <CardDescription>Net this month</CardDescription>
                    <CardTitle>{{ money(summary.net_this_month) }}</CardTitle>
                </CardHeader>
            </Card>
            <Card>
                <CardHeader>
                    <CardDescription>Net worth</CardDescription>
                    <CardTitle>{{ money(summary.net_worth) }}</CardTitle>
                </CardHeader>
            </Card>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <Card>
                <CardHeader>
                    <CardTitle>Upcoming bills</CardTitle>
                    <CardDescription>Next 5 due.</CardDescription>
                </CardHeader>
                <CardContent>
                    <p
                        v-if="!upcomingBills.length"
                        class="text-sm text-muted-foreground"
                    >
                        No bills on the horizon.
                    </p>
                    <ul v-else class="space-y-2 text-sm">
                        <li
                            v-for="bill in upcomingBills"
                            :key="bill.id"
                            class="flex items-center justify-between"
                        >
                            <span>{{ bill.name }}</span>
                            <span class="text-muted-foreground">
                                {{ money(bill.amount) }} · {{ formatDate(bill.next_due_on) }}
                            </span>
                        </li>
                    </ul>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Active goals</CardTitle>
                    <CardDescription>Your savings in progress.</CardDescription>
                </CardHeader>
                <CardContent>
                    <p
                        v-if="!activeGoals.length"
                        class="text-sm text-muted-foreground"
                    >
                        Nothing tracked yet.
                    </p>
                    <ul v-else class="space-y-2 text-sm">
                        <li
                            v-for="goal in activeGoals"
                            :key="goal.id"
                            class="flex items-center justify-between"
                        >
                            <span>{{ goal.name }}</span>
                            <span class="text-muted-foreground">
                                {{ money(goal.current_amount) }} /
                                {{ money(goal.target_amount) }}
                            </span>
                        </li>
                    </ul>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Recent insights</CardTitle>
                    <CardDescription>Fresh from your coach.</CardDescription>
                </CardHeader>
                <CardContent>
                    <p
                        v-if="!recentInsights.length"
                        class="text-sm text-muted-foreground"
                    >
                        No insights yet — keep logging and we'll spot patterns.
                    </p>
                    <ul v-else class="space-y-3 text-sm">
                        <li v-for="i in recentInsights" :key="i.id">
                            <p class="font-medium">{{ i.title }}</p>
                            <p class="text-muted-foreground">{{ i.body }}</p>
                        </li>
                    </ul>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader class="flex-row items-center justify-between">
                <div>
                    <CardTitle>Debt payoff</CardTitle>
                    <CardDescription>
                        See your plan to debt-free.
                    </CardDescription>
                </div>
                <Button variant="outline" as-child>
                    <Link :href="planner()">Open planner</Link>
                </Button>
            </CardHeader>
        </Card>
    </div>
</template>
