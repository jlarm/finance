<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ExpenseController from '@/actions/App/Http/Controllers/ExpenseController';
import ExpenseForm from '@/components/finance/forms/ExpenseForm.vue';
import Heading from '@/components/Heading.vue';
import { Card, CardContent } from '@/components/ui/card';
import { index } from '@/routes/expenses';

defineProps<{
    categories: { id: number; name: string }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Expenses', href: index() },
            { title: 'Add expense' },
        ],
    },
});
</script>

<template>
    <Head title="Add expense" />

    <div class="mx-auto flex w-full max-w-xl flex-col gap-6 p-4">
        <Heading title="Add expense" description="Log something you spent." />

        <Card>
            <CardContent class="p-6">
                <ExpenseForm
                    :action="ExpenseController.store()"
                    :categories="categories"
                    :cancel-href="index().url"
                />
            </CardContent>
        </Card>
    </div>
</template>
