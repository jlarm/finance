<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import FormDatePicker from '@/components/finance/FormDatePicker.vue';
import type { RouteDefinition } from '@/wayfinder';

type IncomeSource = {
    name: string;
    amount: number | string;
    received_on: string;
    notes?: string | null;
};

defineProps<{
    action: RouteDefinition<'post' | 'put' | 'patch'>;
    incomeSource?: Partial<IncomeSource>;
    cancelHref?: string;
    submitLabel?: string;
    deletable?: boolean;
}>();

const emit = defineEmits<{
    success: [];
    cancel: [];
    delete: [];
}>();
</script>

<template>
    <Form
        :action="action"
        class="flex flex-col gap-4"
        @success="emit('success')"
        v-slot="{ errors, processing }"
    >
        <div class="grid gap-2">
            <Label for="name">Source</Label>
            <Input
                id="name"
                name="name"
                type="text"
                maxlength="160"
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
            <Label for="received_on">Date received</Label>
            <FormDatePicker
                id="received_on"
                name="received_on"
                :default-value="incomeSource?.received_on"
                placeholder="Pick a date"
                required
            />
            <InputError :message="errors.received_on" />
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
                    {{ submitLabel ?? 'Save income' }}
                </Button>
            </div>
        </div>
    </Form>
</template>
