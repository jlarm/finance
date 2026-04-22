<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import FormSelect from '@/components/finance/FormSelect.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { EXPENSE_CATEGORIES  } from '@/lib/categories';
import type {ExpenseCategory} from '@/lib/categories';
import type { RouteDefinition } from '@/wayfinder';

type BudgetTarget = {
    category: ExpenseCategory | null;
    period_month: string;
    amount: number | string;
};

const props = defineProps<{
    action: RouteDefinition<'post' | 'put' | 'patch'>;
    target?: Partial<BudgetTarget>;
    cancelHref?: string;
    submitLabel?: string;
    lockCategory?: boolean;
    deletable?: boolean;
}>();

const emit = defineEmits<{
    success: [];
    cancel: [];
    delete: [];
}>();

const monthValue = computed(() => {
    const v = props.target?.period_month;

    if (!v) {
        return undefined;
    }

    return typeof v === 'string' && v.length >= 7 ? v.slice(0, 7) : v;
});
</script>

<template>
    <Form
        :action="action"
        class="flex flex-col gap-4"
        @success="emit('success')"
        v-slot="{ errors, processing }"
    >
        <div class="grid gap-2">
            <Label for="category">Category</Label>
            <FormSelect
                id="category"
                name="category"
                :options="EXPENSE_CATEGORIES"
                :default-value="target?.category ?? null"
                :disabled="lockCategory"
                placeholder="Choose a category"
                required
            />
            <InputError :message="errors.category" />
        </div>

        <div class="grid gap-2">
            <Label for="period_month">Month</Label>
            <Input
                id="period_month"
                name="period_month"
                type="month"
                :default-value="monthValue"
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
                :default-value="target?.amount"
                required
            />
            <InputError :message="errors.amount" />
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
                    {{ submitLabel ?? 'Save target' }}
                </Button>
            </div>
        </div>
    </Form>
</template>
