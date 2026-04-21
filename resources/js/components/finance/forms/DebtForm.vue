<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { RouteDefinition } from '@/wayfinder';

type Debt = {
    name: string;
    kind: 'credit_card' | 'loan' | 'student_loan' | 'medical' | 'personal' | 'other';
    balance: number | string;
    original_balance?: number | string | null;
    apr?: number | string | null;
    minimum_payment?: number | string | null;
    due_day?: number | string | null;
    notes?: string | null;
};

defineProps<{
    action: RouteDefinition<'post' | 'put' | 'patch'>;
    debt?: Partial<Debt>;
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
                :value="debt?.name"
                required
            />
            <InputError :message="errors.name" />
        </div>

        <div class="grid gap-2">
            <Label for="kind">Type</Label>
            <select
                id="kind"
                name="kind"
                class="h-10 rounded-md border bg-background px-3 text-sm"
                :value="debt?.kind ?? 'credit_card'"
                required
            >
                <option value="credit_card">Credit card</option>
                <option value="loan">Loan</option>
                <option value="student_loan">Student loan</option>
                <option value="medical">Medical</option>
                <option value="personal">Personal</option>
                <option value="other">Other</option>
            </select>
            <InputError :message="errors.kind" />
        </div>

        <div class="grid gap-2 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="balance">Current balance</Label>
                <Input
                    id="balance"
                    name="balance"
                    type="number"
                    step="0.01"
                    min="0"
                    inputmode="decimal"
                    :value="debt?.balance"
                    required
                />
                <InputError :message="errors.balance" />
            </div>
            <div class="grid gap-2">
                <Label for="original_balance">Original balance</Label>
                <Input
                    id="original_balance"
                    name="original_balance"
                    type="number"
                    step="0.01"
                    min="0"
                    inputmode="decimal"
                    :value="debt?.original_balance ?? ''"
                />
                <InputError :message="errors.original_balance" />
            </div>
        </div>

        <div class="grid gap-2 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="apr">APR %</Label>
                <Input
                    id="apr"
                    name="apr"
                    type="number"
                    step="0.01"
                    min="0"
                    max="100"
                    inputmode="decimal"
                    :value="debt?.apr ?? ''"
                />
                <InputError :message="errors.apr" />
            </div>
            <div class="grid gap-2">
                <Label for="minimum_payment">Minimum payment</Label>
                <Input
                    id="minimum_payment"
                    name="minimum_payment"
                    type="number"
                    step="0.01"
                    min="0"
                    inputmode="decimal"
                    :value="debt?.minimum_payment ?? ''"
                />
                <InputError :message="errors.minimum_payment" />
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="due_day">Due day of month</Label>
            <Input
                id="due_day"
                name="due_day"
                type="number"
                min="1"
                max="31"
                :value="debt?.due_day ?? ''"
            />
            <InputError :message="errors.due_day" />
        </div>

        <div class="grid gap-2">
            <Label for="notes">Notes</Label>
            <textarea
                id="notes"
                name="notes"
                rows="3"
                class="rounded-md border bg-background px-3 py-2 text-sm"
                :value="debt?.notes ?? ''"
            ></textarea>
            <InputError :message="errors.notes" />
        </div>

        <div class="flex justify-end gap-2">
            <Button v-if="cancelHref" variant="ghost" as-child>
                <Link :href="cancelHref">Cancel</Link>
            </Button>
            <Button type="submit" :disabled="processing">
                {{ submitLabel ?? 'Save debt' }}
            </Button>
        </div>
    </Form>
</template>
