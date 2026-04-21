<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import FormSelect from '@/components/finance/FormSelect.vue';
import FormDatePicker from '@/components/finance/FormDatePicker.vue';
import type { RouteDefinition } from '@/wayfinder';

type Bill = {
    name: string;
    amount: number | string;
    frequency: 'monthly' | 'weekly' | 'biweekly' | 'quarterly' | 'annual' | 'custom';
    next_due_on: string;
    autopay: boolean;
    notes?: string | null;
};

const props = defineProps<{
    action: RouteDefinition<'post' | 'put' | 'patch'>;
    bill?: Partial<Bill>;
    cancelHref?: string;
    submitLabel?: string;
}>();

const frequencyOptions: { value: Bill['frequency']; label: string }[] = [
    { value: 'monthly', label: 'Monthly' },
    { value: 'weekly', label: 'Weekly' },
    { value: 'biweekly', label: 'Every 2 weeks' },
    { value: 'quarterly', label: 'Quarterly' },
    { value: 'annual', label: 'Annual' },
    { value: 'custom', label: 'Custom' },
];

const selectedFrequency = props.bill?.frequency ?? 'monthly';
</script>

<template>
    <Form
        :action="action"
        class="flex flex-col gap-4"
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
                :options="frequencyOptions"
                :default-value="selectedFrequency"
                required
            />
            <InputError :message="errors.frequency" />
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
            <input
                id="autopay"
                name="autopay"
                type="checkbox"
                value="1"
                :checked="bill?.autopay ?? false"
                class="h-4 w-4"
            />
            <Label for="autopay" class="m-0">On autopay</Label>
        </div>
        <InputError :message="errors.autopay" />

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

        <div class="flex justify-end gap-2">
            <Button v-if="cancelHref" variant="ghost" as-child>
                <Link :href="cancelHref">Cancel</Link>
            </Button>
            <Button type="submit" :disabled="processing">
                {{ submitLabel ?? 'Save bill' }}
            </Button>
        </div>
    </Form>
</template>
