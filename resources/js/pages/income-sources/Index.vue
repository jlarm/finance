<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { create, edit, index } from '@/routes/income-sources';

type Income = {
    id: number;
    name: string;
    amount: number;
    received_on: string;
};

type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
};

defineProps<{
    incomes: Paginated<Income>;
    filters: { from?: string; to?: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Income', href: index() }],
    },
});

const money = (v: number) =>
    new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: 'USD',
    }).format(v ?? 0);
</script>

<template>
    <Head title="Income" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                title="Income"
                description="Everything that came in, logged by you."
            />
            <Button as-child>
                <Link :href="create()">Add income</Link>
            </Button>
        </div>

        <Card>
            <CardContent class="p-0">
                <div
                    v-if="!incomes.data.length"
                    class="flex flex-col items-center gap-2 p-10 text-center"
                >
                    <p class="font-medium">No income logged</p>
                    <p class="text-sm text-muted-foreground">
                        Add a paycheck, side income, or anything else that
                        landed.
                    </p>
                    <Button as-child class="mt-2">
                        <Link :href="create()">Add income</Link>
                    </Button>
                </div>

                <table v-else class="w-full text-sm">
                    <thead class="border-b text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">Date</th>
                            <th class="px-4 py-3 font-medium">Source</th>
                            <th class="px-4 py-3 text-right font-medium">
                                Amount
                            </th>
                            <th class="w-10"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="income in incomes.data"
                            :key="income.id"
                            class="border-b last:border-0"
                        >
                            <td class="px-4 py-3">{{ income.received_on }}</td>
                            <td class="px-4 py-3">{{ income.name }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">
                                {{ money(income.amount) }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link
                                    :href="edit({ income_source: income.id })"
                                    class="text-sm text-muted-foreground underline-offset-4 hover:underline"
                                >
                                    Edit
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </CardContent>
        </Card>
    </div>
</template>
