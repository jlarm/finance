<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { RouteDefinition } from '@/wayfinder';

type SavingsGoal = {
    name: string;
    target_amount: number | string;
    current_amount: number | string;
    target_date?: string | null;
    notes?: string | null;
};

defineProps<{
    action: RouteDefinition<'post' | 'put' | 'patch'>;
    goal?: Partial<SavingsGoal>;
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
                :value="goal?.name"
                placeholder="Emergency fund, vacation, etc."
                required
            />
            <InputError :message="errors.name" />
        </div>

        <div class="grid gap-2 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="target_amount">Target amount</Label>
                <Input
                    id="target_amount"
                    name="target_amount"
                    type="number"
                    step="0.01"
                    min="0.01"
                    inputmode="decimal"
                    :value="goal?.target_amount"
                    required
                />
                <InputError :message="errors.target_amount" />
            </div>
            <div class="grid gap-2">
                <Label for="current_amount">Saved so far</Label>
                <Input
                    id="current_amount"
                    name="current_amount"
                    type="number"
                    step="0.01"
                    min="0"
                    inputmode="decimal"
                    :value="goal?.current_amount ?? '0'"
                />
                <InputError :message="errors.current_amount" />
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="target_date">Target date</Label>
            <Input
                id="target_date"
                name="target_date"
                type="date"
                :value="goal?.target_date ?? ''"
            />
            <InputError :message="errors.target_date" />
        </div>

        <div class="grid gap-2">
            <Label for="notes">Notes</Label>
            <textarea
                id="notes"
                name="notes"
                rows="3"
                class="rounded-md border bg-background px-3 py-2 text-sm"
                :value="goal?.notes ?? ''"
            ></textarea>
            <InputError :message="errors.notes" />
        </div>

        <div class="flex justify-end gap-2">
            <Button v-if="cancelHref" variant="ghost" as-child>
                <Link :href="cancelHref">Cancel</Link>
            </Button>
            <Button type="submit" :disabled="processing">
                {{ submitLabel ?? 'Save goal' }}
            </Button>
        </div>
    </Form>
</template>
