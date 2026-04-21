<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { RouteDefinition } from '@/wayfinder';

type Bill = {
    name: string;
    amount: number | string;
    frequency: 'monthly' | 'weekly' | 'biweekly' | 'quarterly' | 'annual' | 'custom';
    next_due_on: string;
    autopay: boolean;
    notes?: string | null;
};

defineProps<{
    action: RouteDefinition<'post' | 'put' | 'patch'>;
    bill?: Partial<Bill>;
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
            <Label for="name">Name</Label>
            <Input
                id="name"
                name="name"
                type="text"
                maxlength="120"
                :value="bill?.name"
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
                :value="bill?.amount"
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
                :value="bill?.frequency ?? 'monthly'"
                required
            >
                <option value="monthly">Monthly</option>
                <option value="weekly">Weekly</option>
                <option value="biweekly">Every 2 weeks</option>
                <option value="quarterly">Quarterly</option>
                <option value="annual">Annual</option>
                <option value="custom">Custom</option>
            </select>
            <InputError :message="errors.frequency" />
        </div>

        <div class="grid gap-2">
            <Label for="next_due_on">Next due</Label>
            <Input
                id="next_due_on"
                name="next_due_on"
                type="date"
                :value="bill?.next_due_on"
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
                :value="bill?.notes ?? ''"
            ></textarea>
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
