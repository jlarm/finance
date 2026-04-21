<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { RouteDefinition } from '@/wayfinder';

type IncomeSource = {
    name: string;
    amount: number | string;
    frequency: 'monthly' | 'weekly' | 'biweekly' | 'semimonthly' | 'annual' | 'one_time';
    next_expected_on?: string | null;
    notes?: string | null;
};

defineProps<{
    action: RouteDefinition<'post' | 'put' | 'patch'>;
    incomeSource?: Partial<IncomeSource>;
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
            <Label for="name">Source</Label>
            <Input
                id="name"
                name="name"
                type="text"
                maxlength="120"
                :value="incomeSource?.name"
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
                :value="incomeSource?.amount"
                required
            />
            <InputError :message="errors.amount" />
        </div>

        <div class="grid gap-2">
            <Label for="frequency">Frequency</Label>
            <select
                id="frequency"
                name="frequency"
                class="h-10 rounded-md border bg-background px-3 text-sm"
                :value="incomeSource?.frequency ?? 'monthly'"
                required
            >
                <option value="monthly">Monthly</option>
                <option value="semimonthly">Twice a month</option>
                <option value="biweekly">Every 2 weeks</option>
                <option value="weekly">Weekly</option>
                <option value="annual">Annual</option>
                <option value="one_time">One-time</option>
            </select>
            <InputError :message="errors.frequency" />
        </div>

        <div class="grid gap-2">
            <Label for="next_expected_on">Next expected on</Label>
            <Input
                id="next_expected_on"
                name="next_expected_on"
                type="date"
                :value="incomeSource?.next_expected_on ?? ''"
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
                :value="incomeSource?.notes ?? ''"
            ></textarea>
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
