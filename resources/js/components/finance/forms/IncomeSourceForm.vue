<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import FormSelect from '@/components/finance/FormSelect.vue';
import FormDatePicker from '@/components/finance/FormDatePicker.vue';
import type { RouteDefinition } from '@/wayfinder';

type IncomeSource = {
    name: string;
    amount: number | string;
    frequency: 'monthly' | 'weekly' | 'biweekly' | 'semimonthly' | 'annual' | 'one_time';
    next_expected_on?: string | null;
    notes?: string | null;
};

const props = defineProps<{
    action: RouteDefinition<'post' | 'put' | 'patch'>;
    incomeSource?: Partial<IncomeSource>;
    cancelHref?: string;
    submitLabel?: string;
}>();

const frequencyOptions: { value: IncomeSource['frequency']; label: string }[] = [
    { value: 'monthly', label: 'Monthly' },
    { value: 'semimonthly', label: 'Twice a month' },
    { value: 'biweekly', label: 'Every 2 weeks' },
    { value: 'weekly', label: 'Weekly' },
    { value: 'annual', label: 'Annual' },
    { value: 'one_time', label: 'One-time' },
];

const selectedFrequency = props.incomeSource?.frequency ?? 'monthly';
</script>

<template>
    <Form
        :action="action"
        class="flex flex-col gap-4"
        v-slot="{ errors, processing }"
    >
        <div class="grid gap-2">
            <Label for="name">Source</Label>
            <Input
                id="name"
                name="name"
                type="text"
                maxlength="120"
                :default-value="incomeSource?.name"
                placeholder="Day job, side gig, etc."
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
                :default-value="incomeSource?.amount"
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
            <Label for="next_expected_on">Next expected on</Label>
            <FormDatePicker
                id="next_expected_on"
                name="next_expected_on"
                :default-value="incomeSource?.next_expected_on"
                placeholder="Pick next expected date"
            />
            <InputError :message="errors.next_expected_on" />
        </div>

        <div class="grid gap-2">
            <Label for="notes">Notes</Label>
            <textarea
                id="notes"
                name="notes"
                rows="3"
                class="rounded-md border bg-background px-3 py-2 text-sm"
            >{{ incomeSource?.notes ?? '' }}</textarea>
            <InputError :message="errors.notes" />
        </div>

        <div class="flex justify-end gap-2">
            <Button v-if="cancelHref" variant="ghost" as-child>
                <Link :href="cancelHref">Cancel</Link>
            </Button>
            <Button type="submit" :disabled="processing">
                {{ submitLabel ?? 'Save income source' }}
            </Button>
        </div>
    </Form>
</template>
