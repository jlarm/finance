<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { cashFlow, index } from '@/routes/reports';
import { formatDate } from '@/lib/utils';

type Bill = {
    bill_id: number;
    name: string;
    amount: number;
    due_on: string;
};

type Week = {
    week_start: string;
    week_end: string;
    bills: Bill[];
    bills_total: number;
    projected_income: number;
    net: number;
};

defineProps<{
    monthly_income: number;
    monthly_fixed_obligations: number;
    monthly_discretionary: number;
    weekly_breakdown: Week[];
    clustered_weeks: Week[];
    tight_periods: Week[];
    available_cash: number;
    safe_to_spend: {
        amount: number;
        days_remaining: number;
        per_day: number;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Reports', href: index() },
            { title: 'Cash flow', href: cashFlow() },
        ],
    },
});

const money = (v: number) =>
    new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: 'USD',
    }).format(v ?? 0);
</script>

<template>
    <Head title="Cash-flow report" />

    <div class="flex flex-col gap-6 p-4">
        <Heading
            title="Cash flow"
            description="Income vs. bills over the next eight weeks."
        />

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <Card>
                <CardHeader>
                    <CardDescription>Monthly income</CardDescription>
                    <CardTitle>{{ money(monthly_income) }}</CardTitle>
                </CardHeader>
            </Card>
            <Card>
                <CardHeader>
                    <CardDescription>Fixed obligations</CardDescription>
                    <CardTitle>
                        {{ money(monthly_fixed_obligations) }}
                    </CardTitle>
                </CardHeader>
            </Card>
            <Card>
                <CardHeader>
                    <CardDescription>Discretionary</CardDescription>
                    <CardTitle>{{ money(monthly_discretionary) }}</CardTitle>
                </CardHeader>
            </Card>
            <Card>
                <CardHeader>
                    <CardDescription>
                        Safe to spend ·
                        {{ safe_to_spend.days_remaining }} days left
                    </CardDescription>
                    <CardTitle>{{ money(safe_to_spend.per_day) }}/day</CardTitle>
                </CardHeader>
                <CardContent class="text-sm text-muted-foreground">
                    {{ money(available_cash) }} available this month
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Weekly breakdown</CardTitle>
                <CardDescription>
                    Projected bills and income by week.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <p
                    v-if="!weekly_breakdown.length"
                    class="text-sm text-muted-foreground"
                >
                    No forecast data yet.
                </p>
                <ul v-else class="divide-y text-sm">
                    <li
                        v-for="week in weekly_breakdown"
                        :key="week.week_start"
                        class="flex items-center justify-between py-2"
                    >
                        <div>
                            <p class="font-medium">
                                {{ formatDate(week.week_start) }} –
                                {{ formatDate(week.week_end) }}
                            </p>
                            <p class="text-muted-foreground">
                                {{ week.bills.length }}
                                {{ week.bills.length === 1 ? 'bill' : 'bills' }}
                                · {{ money(week.bills_total) }} out ·
                                {{ money(week.projected_income) }} in
                            </p>
                        </div>
                        <span
                            :class="
                                week.net < 0
                                    ? 'text-destructive'
                                    : 'text-muted-foreground'
                            "
                        >
                            {{ money(week.net) }}
                        </span>
                    </li>
                </ul>
            </CardContent>
        </Card>

        <div class="grid gap-4 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Heavy bill weeks</CardTitle>
                    <CardDescription>
                        Weeks where bills cluster above half of weekly income.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <p
                        v-if="!clustered_weeks.length"
                        class="text-sm text-muted-foreground"
                    >
                        Nothing clustered — your bills are spread out.
                    </p>
                    <ul v-else class="space-y-2 text-sm">
                        <li
                            v-for="week in clustered_weeks"
                            :key="week.week_start"
                            class="flex items-center justify-between"
                        >
                            <span>
                                {{ formatDate(week.week_start) }} –
                                {{ formatDate(week.week_end) }}
                            </span>
                            <span class="text-muted-foreground">
                                {{ money(week.bills_total) }}
                            </span>
                        </li>
                    </ul>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Tight periods</CardTitle>
                    <CardDescription>
                        Weeks where bills outpace projected income.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <p
                        v-if="!tight_periods.length"
                        class="text-sm text-muted-foreground"
                    >
                        No red-flag weeks ahead.
                    </p>
                    <ul v-else class="space-y-2 text-sm">
                        <li
                            v-for="week in tight_periods"
                            :key="week.week_start"
                            class="flex items-center justify-between"
                        >
                            <span>
                                {{ formatDate(week.week_start) }} –
                                {{ formatDate(week.week_end) }}
                            </span>
                            <span class="text-destructive">
                                {{ money(week.net) }}
                            </span>
                        </li>
                    </ul>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
