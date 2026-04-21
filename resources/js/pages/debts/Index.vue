<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
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
import DebtForm from '@/components/finance/forms/DebtForm.vue';
import DebtController from '@/actions/App/Http/Controllers/DebtController';
import { planner } from '@/routes';
import { index } from '@/routes/debts';

type Debt = {
    id: number;
    name: string;
    type:
        | 'credit_card'
        | 'student'
        | 'auto'
        | 'mortgage'
        | 'personal'
        | 'medical'
        | 'other';
    balance: number;
    original_balance: number | null;
    apr: number | null;
    minimum_payment: number | null;
    due_day: number | null;
    notes: string | null;
    progress_percentage: number | null;
};

defineProps<{
    debts: Debt[];
    totals: { balance: number; minimums: number };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Debts', href: index() }],
    },
});

const money = (v: number) =>
    new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: 'USD',
    }).format(v ?? 0);

const dialogOpen = ref(false);
const editing = ref<Debt | null>(null);

const openCreate = () => {
    editing.value = null;
    dialogOpen.value = true;
};

const openEdit = (debt: Debt) => {
    editing.value = debt;
    dialogOpen.value = true;
};

const handleSuccess = () => {
    dialogOpen.value = false;
    editing.value = null;
};

const handleDelete = () => {
    if (!editing.value) return;
    if (!confirm('Delete this debt? This cannot be undone.')) return;
    router.delete(DebtController.destroy({ debt: editing.value.id }).url, {
        preserveScroll: true,
        onSuccess: () => {
            dialogOpen.value = false;
            editing.value = null;
        },
    });
};
</script>

<template>
    <Head title="Debts" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                title="Debts"
                description="Track every balance and your progress paying it down."
            />
            <div class="flex gap-2">
                <Button variant="outline" as-child>
                    <Link :href="planner()">Open planner</Link>
                </Button>
                <Button @click="openCreate">Add debt</Button>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardDescription>Total balance</CardDescription>
                    <CardTitle>{{ money(totals.balance) }}</CardTitle>
                </CardHeader>
            </Card>
            <Card>
                <CardHeader>
                    <CardDescription>Monthly minimums</CardDescription>
                    <CardTitle>{{ money(totals.minimums) }}</CardTitle>
                </CardHeader>
            </Card>
        </div>

        <div v-if="!debts.length">
            <Card>
                <CardContent class="flex flex-col items-center gap-2 p-10 text-center">
                    <p class="font-medium">No debts tracked</p>
                    <p class="text-sm text-muted-foreground">
                        Add any balances you owe to plan a path to paid off.
                    </p>
                    <Button class="mt-2" @click="openCreate">Add debt</Button>
                </CardContent>
            </Card>
        </div>

        <div v-else class="grid gap-4 md:grid-cols-2">
            <Card v-for="debt in debts" :key="debt.id">
                <CardHeader>
                    <div class="flex items-start justify-between">
                        <div>
                            <CardTitle>{{ debt.name }}</CardTitle>
                            <CardDescription class="capitalize">
                                {{ debt.type.replace('_', ' ') }}
                                <span v-if="debt.apr"> · {{ debt.apr }}% APR</span>
                            </CardDescription>
                        </div>
                        <button
                            type="button"
                            class="text-sm text-muted-foreground underline-offset-4 hover:underline"
                            @click="openEdit(debt)"
                        >
                            Details
                        </button>
                    </div>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div class="flex items-end justify-between">
                        <span class="text-sm text-muted-foreground">
                            Current balance
                        </span>
                        <span class="text-xl font-semibold tabular-nums">
                            {{ money(debt.balance) }}
                        </span>
                    </div>
                    <div
                        v-if="
                            debt.progress_percentage !== null &&
                            debt.progress_percentage !== undefined
                        "
                        class="space-y-1"
                    >
                        <div class="flex justify-between text-xs text-muted-foreground">
                            <span>Paid off</span>
                            <span>{{ debt.progress_percentage }}%</span>
                        </div>
                        <div class="h-2 w-full rounded-full bg-muted">
                            <div
                                class="h-full rounded-full bg-primary"
                                :style="{ width: `${debt.progress_percentage}%` }"
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <Dialog v-model:open="dialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {{ editing ? 'Edit debt' : 'Add debt' }}
                    </DialogTitle>
                    <DialogDescription>
                        {{
                            editing
                                ? 'Update this balance or payment details.'
                                : 'Track a balance you owe.'
                        }}
                    </DialogDescription>
                </DialogHeader>

                <DebtForm
                    v-if="dialogOpen"
                    :key="editing?.id ?? 'new'"
                    :action="
                        editing
                            ? DebtController.update({ debt: editing.id })
                            : DebtController.store()
                    "
                    :debt="editing ?? undefined"
                    :submit-label="editing ? 'Save changes' : 'Save debt'"
                    :deletable="!!editing"
                    @success="handleSuccess"
                    @cancel="dialogOpen = false"
                    @delete="handleDelete"
                />
            </DialogContent>
        </Dialog>
    </div>
</template>
