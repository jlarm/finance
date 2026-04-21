<script setup lang="ts">
import { computed } from 'vue';
import { Form, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import type { RouteDefinition } from '@/wayfinder';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import FormSelect from '@/components/finance/FormSelect.vue';
import FormDatePicker from '@/components/finance/FormDatePicker.vue';

type Category = { id: number; name: string };

type Expense = {
    amount: number | string;
    expense_category_id: number | string;
    occurred_on: string;
    description: string;
    notes?: string | null;
};

const props = defineProps<{
    action: RouteDefinition<'post' | 'put' | 'patch'>;
    categories: Category[];
    expense?: Partial<Expense>;
    cancelHref?: string;
    submitLabel?: string;
    deletable?: boolean;
}>();

const emit = defineEmits<{
    success: [];
    cancel: [];
    delete: [];
}>();

const categoryOptions = computed(() =>
    props.categories.map((cat) => ({ value: cat.id, label: cat.name })),
);
</script>

<template>
    <Form
        :action="action"
        class="flex flex-col gap-4"
        @success="emit('success')"
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
                :default-value="expense?.amount"
                required
            />
            <InputError :message="errors.amount" />
        </div>

        <div class="grid gap-2">
            <Label for="expense_category_id">Category</Label>
            <FormSelect
                id="expense_category_id"
                name="expense_category_id"
                :options="categoryOptions"
                :default-value="expense?.expense_category_id ?? null"
                placeholder="Choose a category"
                required
            />
            <InputError :message="errors.expense_category_id" />
        </div>

        <div class="grid gap-2">
            <Label for="occurred_on">Date</Label>
            <FormDatePicker
                id="occurred_on"
                name="occurred_on"
                :default-value="expense?.occurred_on"
                placeholder="Pick a date"
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
                :default-value="expense?.description"
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
            >{{ expense?.notes ?? '' }}</textarea>
            <InputError :message="errors.notes" />
        </div>

        <div class="flex items-center justify-between gap-2">
            <Button
                v-if="deletable"
                type="button"
                variant="ghost"
                class="text-destructive hover:text-destructive"
                :disabled="processing"
                @click="emit('delete')"
            >
                Delete
            </Button>
            <span v-else></span>
            <div class="flex gap-2">
                <Button
                    v-if="cancelHref"
                    variant="ghost"
                    as-child
                >
                    <Link :href="cancelHref">Cancel</Link>
                </Button>
                <Button
                    v-else
                    type="button"
                    variant="ghost"
                    @click="emit('cancel')"
                >
                    Cancel
                </Button>
                <Button type="submit" :disabled="processing">
                    {{ submitLabel ?? 'Save expense' }}
                </Button>
            </div>
        </div>
    </Form>
</template>
