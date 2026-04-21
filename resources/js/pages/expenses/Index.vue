<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { create, edit, index } from '@/routes/expenses';

type Expense = {
    id: number;
    amount: number;
    occurred_on: string;
    description: string;
    category: { id: number; name: string } | null;
};

type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
};

defineProps<{
    expenses: Paginated<Expense>;
    categories: { id: number; name: string }[];
    filters: { from?: string; to?: string; category?: number; search?: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Expenses', href: index() }],
    },
});

const money = (v: number) =>
    new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: 'USD',
    }).format(v ?? 0);
</script>

<template>
    <Head title="Expenses" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                title="Expenses"
                description="Everything you've spent, in one place."
            />
            <Button as-child>
                <Link :href="create()">Add expense</Link>
            </Button>
        </div>

        <Card>
            <CardContent class="p-0">
                <div
                    v-if="!expenses.data.length"
                    class="flex flex-col items-center gap-2 p-10 text-center"
                >
                    <p class="font-medium">No expenses yet</p>
                    <p class="text-sm text-muted-foreground">
                        Log your first expense to start seeing trends.
                    </p>
                    <Button as-child class="mt-2">
                        <Link :href="create()">Add expense</Link>
                    </Button>
                </div>

                <table v-else class="w-full text-sm">
                    <thead class="border-b text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">Date</th>
                            <th class="px-4 py-3 font-medium">Description</th>
                            <th class="px-4 py-3 font-medium">Category</th>
                            <th class="px-4 py-3 text-right font-medium">
                                Amount
                            </th>
                            <th class="w-10"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="expense in expenses.data"
                            :key="expense.id"
                            class="border-b last:border-0"
                        >
                            <td class="px-4 py-3">{{ expense.occurred_on }}</td>
                            <td class="px-4 py-3">{{ expense.description }}</td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ expense.category?.name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">
                                {{ money(expense.amount) }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link
                                    :href="edit({ expense: expense.id })"
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
