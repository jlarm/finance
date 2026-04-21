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
import { index, spending } from '@/routes/reports';
import { formatDate } from '@/lib/utils';

type Totals = {
    this_month: number;
    prior_month: number;
    average_monthly: number;
    peak_month: { month: string; total: number } | null;
};

type MonthTotal = { month: string; total: number };
type CategoryTotal = {
    category_id: number;
    name: string;
    color: string | null;
    total: number;
};

defineProps<{
    range: { from: string; to: string };
    totals: Totals;
    by_month: MonthTotal[];
    by_category_current_month: CategoryTotal[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Reports', href: index() },
            { title: 'Spending', href: spending() },
        ],
    },
});

const money = (v: number) =>
    new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: 'USD',
    }).format(v ?? 0);

const monthLabel = (key: string) => {
    const [year, month] = key.split('-');
    const date = new Date(Number(year), Number(month) - 1, 1);
    return date.toLocaleDateString(undefined, {
        month: 'short',
        year: 'numeric',
    });
};
</script>

<template>
    <Head title="Spending report" />

    <div class="flex flex-col gap-6 p-4">
        <Heading
            title="Spending"
            :description="`From ${formatDate(range.from)} to ${formatDate(range.to)}`"
        />

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <Card>
                <CardHeader>
                    <CardDescription>This month</CardDescription>
                    <CardTitle>{{ money(totals.this_month) }}</CardTitle>
                </CardHeader>
            </Card>
            <Card>
                <CardHeader>
                    <CardDescription>Prior month</CardDescription>
                    <CardTitle>{{ money(totals.prior_month) }}</CardTitle>
                </CardHeader>
            </Card>
            <Card>
                <CardHeader>
                    <CardDescription>Average monthly</CardDescription>
                    <CardTitle>{{ money(totals.average_monthly) }}</CardTitle>
                </CardHeader>
            </Card>
            <Card>
                <CardHeader>
                    <CardDescription>
                        Peak month{{
                            totals.peak_month
                                ? ` · ${monthLabel(totals.peak_month.month)}`
                                : ''
                        }}
                    </CardDescription>
                    <CardTitle>
                        {{
                            totals.peak_month
                                ? money(totals.peak_month.total)
                                : '—'
                        }}
                    </CardTitle>
                </CardHeader>
            </Card>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>By month</CardTitle>
                    <CardDescription>Totals across the range.</CardDescription>
                </CardHeader>
                <CardContent>
                    <p
                        v-if="!by_month.length"
                        class="text-sm text-muted-foreground"
                    >
                        No expenses logged in this range.
                    </p>
                    <ul v-else class="space-y-2 text-sm">
                        <li
                            v-for="row in by_month"
                            :key="row.month"
                            class="flex items-center justify-between"
                        >
                            <span>{{ monthLabel(row.month) }}</span>
                            <span class="text-muted-foreground">
                                {{ money(row.total) }}
                            </span>
                        </li>
                    </ul>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>By category, this month</CardTitle>
                    <CardDescription>Where it went.</CardDescription>
                </CardHeader>
                <CardContent>
                    <p
                        v-if="!by_category_current_month.length"
                        class="text-sm text-muted-foreground"
                    >
                        Nothing spent this month yet.
                    </p>
                    <ul v-else class="space-y-2 text-sm">
                        <li
                            v-for="cat in by_category_current_month"
                            :key="cat.category_id"
                            class="flex items-center justify-between"
                        >
                            <span class="flex items-center gap-2">
                                <span
                                    v-if="cat.color"
                                    class="inline-block size-3 rounded-full"
                                    :style="{ backgroundColor: cat.color }"
                                />
                                {{ cat.name }}
                            </span>
                            <span class="text-muted-foreground">
                                {{ money(cat.total) }}
                            </span>
                        </li>
                    </ul>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
