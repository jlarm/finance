<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import FormDatePicker from '@/components/finance/FormDatePicker.vue';
import FormSelect from '@/components/finance/FormSelect.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { EXPENSE_CATEGORIES  } from '@/lib/categories';
import type {ExpenseCategory} from '@/lib/categories';
import type { RouteDefinition } from '@/wayfinder';

type Debt = { id: number; name: string };

type Bill = {
    name: string;
    amount: number | string;
    category: ExpenseCategory | null;
    debt_id?: number | string | null;
    frequency: 'monthly' | 'weekly' | 'biweekly' | 'quarterly' | 'annual' | 'custom';
    interval_days?: number | string | null;
    next_due_on: string;
    autopay_reminder: boolean;
    notes?: string | null;
};

const props = defineProps<{
    action: RouteDefinition<'post' | 'put' | 'patch'>;
    debts?: Debt[];
    bill?: Partial<Bill>;
    cancelHref?: string;
    submitLabel?: string;
    deletable?: boolean;
}>();

const emit = defineEmits<{
    success: [];
    cancel: [];
    delete: [];
}>();

const debtOptions = computed<{ value: string | number; label: string }[]>(
    () => [
        { value: '0', label: 'None' },
        ...(props.debts ?? []).map((debt) => ({
            value: debt.id,
            label: debt.name,
        })),
    ],
);

const frequencyOptions: { value: Bill['frequency']; label: string }[] = [
    { value: 'monthly', label: 'Monthly' },
    { value: 'weekly', label: 'Weekly' },
    { value: 'biweekly', label: 'Every 2 weeks' },
    { value: 'quarterly', label: 'Quarterly' },
    { value: 'annual', label: 'Annual' },
    { value: 'custom', label: 'Custom' },
];

const frequency = ref<Bill['frequency']>(props.bill?.frequency ?? 'monthly');
</script>

<template>
    <Form
        :action="action"
        class="flex flex-col gap-4"
        @success="emit('success')"
        v-slot="{ errors, processing }"
    >
        <div class="grid gap-2">
            <Label for="name">Name</Label>
            <Input
                id="name"
                name="name"
                type="text"
                maxlength="120"
                :default-value="bill?.name"
                required
            />
            <InputError :message="errors.name" />
        </div>

        <div class="grid gap-2">
            <Label for="category">Category</Label>
            <FormSelect
                id="category"
                name="category"
                :options="EXPENSE_CATEGORIES"
                :default-value="bill?.category ?? null"
                placeholder="Choose a category"
                required
            />
            <InputError :message="errors.category" />
        </div>

        <div v-if="(debts?.length ?? 0) > 0" class="grid gap-2">
            <Label for="debt_id">Pays down debt (optional)</Label>
            <FormSelect
                id="debt_id"
                name="debt_id"
                :options="debtOptions"
                :default-value="bill?.debt_id ?? '0'"
                placeholder="None"
            />
            <p class="text-xs text-muted-foreground">
                Marking this bill paid will reduce the linked debt's balance by
                the bill amount.
            </p>
            <InputError :message="errors.debt_id" />
        </div>

        <div class="grid gap-2">
            <Label for="amount">Amount</Label>
            <Input
                id="amount"
                name="amount"
                type="number"
                step="0.01"
                min="0.01"
                inputmode="decimal"
                :default-value="bill?.amount"
                required
            />
            <InputError :message="errors.amount" />
        </div>

        <div class="grid gap-2">
            <Label for="frequency">Frequency</Label>
            <FormSelect
                id="frequency"
                name="frequency"
                v-model="frequency"
                :options="frequencyOptions"
                required
            />
            <InputError :message="errors.frequency" />
        </div>

        <div v-if="frequency === 'custom'" class="grid gap-2">
            <Label for="interval_days">Every N days</Label>
            <Input
                id="interval_days"
                name="interval_days"
                type="number"
                min="1"
                max="3650"
                :default-value="bill?.interval_days ?? ''"
            />
            <InputError :message="errors.interval_days" />
        </div>

        <div class="grid gap-2">
            <Label for="next_due_on">Next due</Label>
            <FormDatePicker
                id="next_due_on"
                name="next_due_on"
                :default-value="bill?.next_due_on"
                placeholder="Pick a due date"
                required
            />
            <InputError :message="errors.next_due_on" />
        </div>

        <div class="flex items-center gap-3">
            <input type="hidden" name="autopay_reminder" value="0" />
            <input
                id="autopay_reminder"
                name="autopay_reminder"
                type="checkbox"
                value="1"
                :checked="bill?.autopay_reminder ?? false"
                class="h-4 w-4"
            />
            <Label for="autopay_reminder" class="m-0">Remind me before autopay</Label>
        </div>
        <InputError :message="errors.autopay_reminder" />

        <div class="grid gap-2">
            <Label for="notes">Notes</Label>
            <textarea
                id="notes"
                name="notes"
                rows="3"
                class="rounded-md border bg-background px-3 py-2 text-sm"
            >{{ bill?.notes ?? '' }}</textarea>
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
                <Button v-if="cancelHref" variant="ghost" as-child>
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
                    {{ submitLabel ?? 'Save bill' }}
                </Button>
            </div>
        </div>
    </Form>
</template>
