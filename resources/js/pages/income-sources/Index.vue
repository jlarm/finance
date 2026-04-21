<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import IncomeSourceForm from '@/components/finance/forms/IncomeSourceForm.vue';
import IncomeSourceController from '@/actions/App/Http/Controllers/IncomeSourceController';
import { index } from '@/routes/income-sources';
import { formatDate } from '@/lib/utils';

type Income = {
    id: number;
    name: string;
    amount: number;
    received_on: string;
    notes: string | null;
};

type Paginated<T> = {
    data: T[];
    from: number | null;
    to: number | null;
    total: number;
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

defineProps<{
    incomes: Paginated<Income>;
    filters: { from?: string; to?: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Income', href: index() }],
    },
});

const money = (v: number) =>
    new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: 'USD',
    }).format(v ?? 0);

const dialogOpen = ref(false);
const editing = ref<Income | null>(null);

const openCreate = () => {
    editing.value = null;
    dialogOpen.value = true;
};

const openEdit = (income: Income) => {
    editing.value = income;
    dialogOpen.value = true;
};

const handleSuccess = () => {
    dialogOpen.value = false;
    editing.value = null;
};

const handleDelete = () => {
    if (!editing.value) return;
    if (!confirm('Delete this income entry? This cannot be undone.')) return;
    router.delete(
        IncomeSourceController.destroy({ income_source: editing.value.id }).url,
        {
            preserveScroll: true,
            onSuccess: () => {
                dialogOpen.value = false;
                editing.value = null;
            },
        },
    );
};

const goToPage = (url: string | null) => {
    if (!url) return;
    router.get(url, {}, { preserveState: true, preserveScroll: true });
};
</script>

<template>
    <Head title="Income" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                title="Income"
                description="Everything that came in, logged by you."
            />
            <Button @click="openCreate">Add income</Button>
        </div>

        <Card>
            <CardContent class="p-0">
                <div
                    v-if="!incomes.data.length"
                    class="flex flex-col items-center gap-2 p-10 text-center"
                >
                    <p class="font-medium">No income logged</p>
                    <p class="text-sm text-muted-foreground">
                        Add a paycheck, side income, or anything else that
                        landed.
                    </p>
                    <Button class="mt-2" @click="openCreate">Add income</Button>
                </div>

                <table v-else class="w-full text-sm">
                    <thead class="border-b text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">Date</th>
                            <th class="px-4 py-3 font-medium">Source</th>
                            <th class="px-4 py-3 text-right font-medium">
                                Amount
                            </th>
                            <th class="w-10"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="income in incomes.data"
                            :key="income.id"
                            class="border-b last:border-0"
                        >
                            <td class="px-4 py-3">{{ formatDate(income.received_on) }}</td>
                            <td class="px-4 py-3">{{ income.name }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">
                                {{ money(income.amount) }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button
                                    type="button"
                                    class="text-sm text-muted-foreground underline-offset-4 hover:underline"
                                    @click="openEdit(income)"
                                >
                                    Edit
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </CardContent>
        </Card>

        <div
            v-if="incomes.data.length && incomes.last_page > 1"
            class="flex items-center justify-between gap-2 text-sm"
        >
            <p class="text-muted-foreground">
                Showing {{ incomes.from }}–{{ incomes.to }} of
                {{ incomes.total }}
            </p>
            <div class="flex items-center gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="!incomes.prev_page_url"
                    @click="goToPage(incomes.prev_page_url)"
                >
                    Previous
                </Button>
                <span class="text-muted-foreground">
                    Page {{ incomes.current_page }} of {{ incomes.last_page }}
                </span>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="!incomes.next_page_url"
                    @click="goToPage(incomes.next_page_url)"
                >
                    Next
                </Button>
            </div>
        </div>

        <Dialog v-model:open="dialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {{ editing ? 'Edit income' : 'Add income' }}
                    </DialogTitle>
                    <DialogDescription>
                        {{
                            editing
                                ? 'Update this income entry.'
                                : 'Log something that came in.'
                        }}
                    </DialogDescription>
                </DialogHeader>

                <IncomeSourceForm
                    v-if="dialogOpen"
                    :key="editing?.id ?? 'new'"
                    :action="
                        editing
                            ? IncomeSourceController.update({
                                  income_source: editing.id,
                              })
                            : IncomeSourceController.store()
                    "
                    :income-source="editing ?? undefined"
                    :submit-label="editing ? 'Save changes' : 'Save income'"
                    :deletable="!!editing"
                    @success="handleSuccess"
                    @cancel="dialogOpen = false"
                    @delete="handleDelete"
                />
            </DialogContent>
        </Dialog>
    </div>
</template>
