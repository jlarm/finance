<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import ExpenseController from '@/actions/App/Http/Controllers/ExpenseController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
                <Form
                    :action="ExpenseController.store()"
                    class="flex flex-col gap-4"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-2">
                        <Label for="amount">Amount</Label>
                        <Input
                            id="amount"
                            name="amount"
                            type="number"
                            step="0.01"
                            min="0.01"
                            inputmode="decimal"
                            required
                        />
                        <InputError :message="errors.amount" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="expense_category_id">Category</Label>
                        <select
                            id="expense_category_id"
                            name="expense_category_id"
                            class="h-10 rounded-md border bg-background px-3 text-sm"
                            required
                        >
                            <option value="">Choose a category</option>
                            <option
                                v-for="cat in categories"
                                :key="cat.id"
                                :value="cat.id"
                            >
                                {{ cat.name }}
                            </option>
                        </select>
                        <InputError :message="errors.expense_category_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="occurred_on">Date</Label>
                        <Input
                            id="occurred_on"
                            name="occurred_on"
                            type="date"
                            required
                        />
                        <InputError :message="errors.occurred_on" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Description</Label>
                        <Input
                            id="description"
                            name="description"
                            type="text"
                            maxlength="160"
                            required
                        />
                        <InputError :message="errors.description" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="notes">Notes</Label>
                        <textarea
                            id="notes"
                            name="notes"
                            rows="3"
                            class="rounded-md border bg-background px-3 py-2 text-sm"
                        ></textarea>
                        <InputError :message="errors.notes" />
                    </div>

                    <div class="flex justify-end gap-2">
                        <Button variant="ghost" as-child>
                            <Link :href="index()">Cancel</Link>
                        </Button>
                        <Button type="submit" :disabled="processing">
                            Save expense
                        </Button>
                    </div>
                </Form>
            </CardContent>
        </Card>
    </div>
</template>
