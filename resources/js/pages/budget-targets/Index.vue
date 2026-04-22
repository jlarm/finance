<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import BudgetTargetController from '@/actions/App/Http/Controllers/BudgetTargetController';
import BudgetTargetForm from '@/components/finance/forms/BudgetTargetForm.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    EXPENSE_CATEGORIES
    
} from '@/lib/categories';
import type {ExpenseCategory} from '@/lib/categories';
import { index } from '@/routes/budget-targets';

type BudgetTarget = {
    id: number;
    category: ExpenseCategory;
    period_month: string;
    amount: number;
};

const props = defineProps<{
    periodMonth: string;
    targets: BudgetTarget[];
    actuals: Record<string, number>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Budgets', href: index() }],
    },
});

const money = (v: number) =>
    new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: 'USD',
    }).format(v ?? 0);

const monthInput = ref(props.periodMonth.slice(0, 7));

const monthLabel = computed(() => {
    const [y, m] = props.periodMonth.split('-');
    const date = new Date(Number(y), Number(m) - 1, 1);

    return date.toLocaleDateString(undefined, {
        month: 'long',
        year: 'numeric',
    });
});

const changeMonth = (value: string) => {
    if (!value) {
        return;
    }

    router.get(
        index().url,
        { month: value },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const rows = computed(() => {
    const byCategory = new Map(props.targets.map((t) => [t.category, t]));

    return EXPENSE_CATEGORIES.map((cat) => {
        const target = byCategory.get(cat.value) ?? null;
        const actual = Number(props.actuals?.[cat.value] ?? 0);
        const targetAmount = target ? Number(target.amount) : 0;
        const percent =
            targetAmount > 0 ? Math.min(999, (actual / targetAmount) * 100) : 0;

        return {
            category: cat,
            target,
            actual,
            targetAmount,
            percent,
            over: targetAmount > 0 && actual > targetAmount,
        };
    });
});

const totals = computed(() => {
    const targeted = props.targets.reduce((s, t) => s + Number(t.amount), 0);
    const spent = props.targets.reduce(
        (s, t) => s + Number(props.actuals?.[t.category] ?? 0),
        0,
    );

    return { targeted, spent, remaining: targeted - spent };
});

const dialogOpen = ref(false);
const editing = ref<BudgetTarget | null>(null);
const presetCategory = ref<ExpenseCategory | null>(null);

const openCreate = (category?: ExpenseCategory) => {
    editing.value = null;
    presetCategory.value = category ?? null;
    dialogOpen.value = true;
};

const openEdit = (target: BudgetTarget) => {
    editing.value = target;
    presetCategory.value = null;
    dialogOpen.value = true;
};

const handleSuccess = () => {
    dialogOpen.value = false;
    editing.value = null;
    presetCategory.value = null;
};

const handleDelete = () => {
    if (!editing.value) {
        return;
    }

    if (!confirm('Delete this budget target? This cannot be undone.')) {
        return;
    }

    router.delete(
        BudgetTargetController.destroy({ budget_target: editing.value.id }).url,
        {
            preserveScroll: true,
            onSuccess: () => {
                dialogOpen.value = false;
                editing.value = null;
            },
        },
    );
};

const formTarget = computed<Partial<BudgetTarget> | undefined>(() => {
    if (editing.value) {
        return editing.value;
    }

    if (presetCategory.value !== null) {
        return {
            category: presetCategory.value,
            period_month: props.periodMonth,
        };
    }

    return { period_month: props.periodMonth };
});
</script>

<template>
    <Head title="Budgets" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                title="Budgets"
                description="Set a monthly target per category and watch your spend against it."
            />
            <Button @click="openCreate()">Add target</Button>
        </div>

        <div class="flex items-end gap-3">
            <div class="grid gap-1">
                <Label for="month">Month</Label>
                <Input
                    id="month"
                    type="month"
                    v-model="monthInput"
                    @change="changeMonth(monthInput)"
                />
            </div>
            <p class="pb-2 text-sm text-muted-foreground">
                Viewing {{ monthLabel }}
            </p>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <Card>
                <CardHeader>
                    <CardDescription>Targeted</CardDescription>
                    <CardTitle>{{ money(totals.targeted) }}</CardTitle>
                </CardHeader>
            </Card>
            <Card>
                <CardHeader>
                    <CardDescription>Spent</CardDescription>
                    <CardTitle>{{ money(totals.spent) }}</CardTitle>
                </CardHeader>
            </Card>
            <Card>
                <CardHeader>
                    <CardDescription>
                        {{ totals.remaining >= 0 ? 'Remaining' : 'Over budget' }}
                    </CardDescription>
                    <CardTitle
                        :class="
                            totals.remaining < 0 ? 'text-destructive' : ''
                        "
                    >
                        {{ money(Math.abs(totals.remaining)) }}
                    </CardTitle>
                </CardHeader>
            </Card>
        </div>

        <Card>
            <CardContent class="p-0">
                <table class="w-full text-sm">
                    <thead class="border-b text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">Category</th>
                            <th class="px-4 py-3 text-right font-medium">Target</th>
                            <th class="px-4 py-3 text-right font-medium">Spent</th>
                            <th class="px-4 py-3 font-medium">Progress</th>
                            <th class="w-24"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in rows"
                            :key="row.category.value"
                            class="border-b last:border-0"
                        >
                            <td class="px-4 py-3 font-medium">
                                {{ row.category.label }}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">
                                <span v-if="row.target">
                                    {{ money(row.targetAmount) }}
                                </span>
                                <span v-else class="text-muted-foreground">—</span>
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">
                                {{ money(row.actual) }}
                            </td>
                            <td class="px-4 py-3">
                                <div
                                    v-if="row.target"
                                    class="flex items-center gap-2"
                                >
                                    <div class="h-2 w-full min-w-[80px] rounded-full bg-muted">
                                        <div
                                            class="h-full rounded-full"
                                            :class="
                                                row.over
                                                    ? 'bg-destructive'
                                                    : 'bg-primary'
                                            "
                                            :style="{
                                                width: `${Math.min(100, row.percent)}%`,
                                            }"
                                        />
                                    </div>
                                    <span
                                        class="text-xs tabular-nums text-muted-foreground"
                                    >
                                        {{ Math.round(row.percent) }}%
                                    </span>
                                </div>
                                <span
                                    v-else
                                    class="text-xs text-muted-foreground"
                                >
                                    No target
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button
                                    v-if="row.target"
                                    type="button"
                                    class="text-sm text-muted-foreground underline-offset-4 hover:underline"
                                    @click="openEdit(row.target)"
                                >
                                    Edit
                                </button>
                                <button
                                    v-else
                                    type="button"
                                    class="text-sm text-primary underline-offset-4 hover:underline"
                                    @click="openCreate(row.category.value)"
                                >
                                    Set target
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </CardContent>
        </Card>

        <Dialog v-model:open="dialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {{ editing ? 'Edit target' : 'Add target' }}
                    </DialogTitle>
                    <DialogDescription>
                        {{
                            editing
                                ? 'Update this category target.'
                                : 'Set a monthly spend cap for a category.'
                        }}
                    </DialogDescription>
                </DialogHeader>

                <BudgetTargetForm
                    v-if="dialogOpen"
                    :key="editing?.id ?? `new-${presetCategory ?? 'x'}`"
                    :action="
                        editing
                            ? BudgetTargetController.update({
                                  budget_target: editing.id,
                              })
                            : BudgetTargetController.store()
                    "
                    :target="formTarget"
                    :lock-category="!!editing"
                    :submit-label="editing ? 'Save changes' : 'Save target'"
                    :deletable="!!editing"
                    @success="handleSuccess"
                    @cancel="dialogOpen = false"
                    @delete="handleDelete"
                />
            </DialogContent>
        </Dialog>
    </div>
</template>
