<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ExpenseController from '@/actions/App/Http/Controllers/ExpenseController';
import Heading from '@/components/Heading.vue';
import { Card, CardContent } from '@/components/ui/card';
import ExpenseForm from '@/components/finance/forms/ExpenseForm.vue';
import { index } from '@/routes/expenses';

type Expense = {
    id: number;
    amount: number | string;
    occurred_on: string;
    description: string;
    notes: string | null;
    expense_category_id: number;
};

defineProps<{
    expense: Expense;
    categories: { id: number; name: string }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Expenses', href: index() },
            { title: 'Edit expense' },
        ],
    },
});
</script>

<template>
    <Head title="Edit expense" />

    <div class="mx-auto flex w-full max-w-xl flex-col gap-6 p-4">
        <Heading title="Edit expense" description="Update what you logged." />

        <Card>
            <CardContent class="p-6">
                <ExpenseForm
                    :action="ExpenseController.update({ expense: expense.id })"
                    :categories="categories"
                    :expense="expense"
                    :cancel-href="index().url"
                    submit-label="Save changes"
                />
            </CardContent>
        </Card>
    </div>
</template>
