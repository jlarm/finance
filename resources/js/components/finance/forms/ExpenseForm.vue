<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import type { RouteDefinition } from '@/wayfinder';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Category = { id: number; name: string };

type Expense = {
    amount: number | string;
    expense_category_id: number | string;
    occurred_on: string;
    description: string;
    notes?: string | null;
};

defineProps<{
    action: RouteDefinition<'post' | 'put' | 'patch'>;
    categories: Category[];
    expense?: Partial<Expense>;
    cancelHref?: string;
    submitLabel?: string;
}>();
</script>

<template>
    <Form
        :action="action"
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
                :value="expense?.amount"
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
                :value="expense?.expense_category_id"
                required
            >
                <option value="">Choose a category</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
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
                :value="expense?.occurred_on"
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
                :value="expense?.description"
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
                :value="expense?.notes ?? ''"
            ></textarea>
            <InputError :message="errors.notes" />
        </div>

        <div class="flex justify-end gap-2">
            <Button v-if="cancelHref" variant="ghost" as-child>
                <Link :href="cancelHref">Cancel</Link>
            </Button>
            <Button type="submit" :disabled="processing">
                {{ submitLabel ?? 'Save expense' }}
            </Button>
        </div>
    </Form>
</template>
