<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { RouteDefinition } from '@/wayfinder';

type Category = { id: number; name: string };

type BudgetTarget = {
    expense_category_id: number | string;
    period_month: string;
    amount: number | string;
};

defineProps<{
    action: RouteDefinition<'post' | 'put' | 'patch'>;
    categories: Category[];
    target?: Partial<BudgetTarget>;
    cancelHref?: string;
    submitLabel?: string;
    lockCategory?: boolean;
}>();
</script>

<template>
    <Form
        :action="action"
        class="flex flex-col gap-4"
        v-slot="{ errors, processing }"
    >
        <div class="grid gap-2">
            <Label for="expense_category_id">Category</Label>
            <select
                id="expense_category_id"
                name="expense_category_id"
                class="h-10 rounded-md border bg-background px-3 text-sm disabled:opacity-60"
                :value="target?.expense_category_id"
                :disabled="lockCategory"
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
            <Label for="period_month">Month</Label>
            <Input
                id="period_month"
                name="period_month"
                type="month"
                :value="target?.period_month"
                required
            />
            <InputError :message="errors.period_month" />
        </div>

        <div class="grid gap-2">
            <Label for="amount">Monthly target</Label>
            <Input
                id="amount"
                name="amount"
                type="number"
                step="0.01"
                min="0.01"
                inputmode="decimal"
                :value="target?.amount"
                required
            />
            <InputError :message="errors.amount" />
        </div>

        <div class="flex justify-end gap-2">
            <Button v-if="cancelHref" variant="ghost" as-child>
                <Link :href="cancelHref">Cancel</Link>
            </Button>
            <Button type="submit" :disabled="processing">
                {{ submitLabel ?? 'Save target' }}
            </Button>
        </div>
    </Form>
</template>
