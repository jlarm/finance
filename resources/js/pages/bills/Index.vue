<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import BillController from '@/actions/App/Http/Controllers/BillController';
import BillForm from '@/components/finance/forms/BillForm.vue';
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
import { categoryLabel  } from '@/lib/categories';
import type {ExpenseCategory} from '@/lib/categories';
import { formatDate } from '@/lib/utils';
import { index } from '@/routes/bills';


type Bill = {
    id: number;
    name: string;
    amount: number;
    category: ExpenseCategory | null;
    debt_id: number | null;
    frequency: 'monthly' | 'weekly' | 'biweekly' | 'quarterly' | 'annual' | 'custom';
    interval_days: number | null;
    next_due_on: string;
    autopay_reminder: boolean;
    notes: string | null;
    status?: string;
    debt: { id: number; name: string } | null;
};

type Debt = { id: number; name: string; balance: number | string };

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
    bills: Paginated<Bill>;
    debts: Debt[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Bills', href: index() }],
    },
});

const money = (v: number) =>
    new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: 'USD',
    }).format(v ?? 0);

const dialogOpen = ref(false);
const editing = ref<Bill | null>(null);

const openCreate = () => {
    editing.value = null;
    dialogOpen.value = true;
};

const openEdit = (bill: Bill) => {
    editing.value = bill;
    dialogOpen.value = true;
};

const handleSuccess = () => {
    dialogOpen.value = false;
    editing.value = null;
};

const markPaid = (bill: Bill) => {
    const msg = bill.debt
        ? `Record payment of ${money(bill.amount)} for ${bill.name}? This will reduce ${bill.debt.name}'s balance.`
        : `Record payment of ${money(bill.amount)} for ${bill.name}?`;

    if (!confirm(msg)) {
return;
}

    router.post(
        BillController.pay({ bill: bill.id }).url,
        {},
        { preserveScroll: true },
    );
};

const handleDelete = () => {
    if (!editing.value) {
return;
}

    if (!confirm('Delete this bill? This cannot be undone.')) {
return;
}

    router.delete(BillController.destroy({ bill: editing.value.id }).url, {
        preserveScroll: true,
        onSuccess: () => {
            dialogOpen.value = false;
            editing.value = null;
        },
    });
};

const goToPage = (url: string | null) => {
    if (!url) {
return;
}

    router.get(url, {}, { preserveState: true, preserveScroll: true });
};
</script>

<template>
    <Head title="Bills" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                title="Bills"
                description="Recurring obligations at a glance."
            />
            <Button @click="openCreate">Add bill</Button>
        </div>

        <Card>
            <CardContent class="p-0">
                <div
                    v-if="!bills.data.length"
                    class="flex flex-col items-center gap-2 p-10 text-center"
                >
                    <p class="font-medium">No bills set up</p>
                    <p class="text-sm text-muted-foreground">
                        Add your recurring bills so we can remind you when
                        they're due.
                    </p>
                    <Button class="mt-2" @click="openCreate">Add bill</Button>
                </div>

                <table v-else class="w-full text-sm">
                    <thead class="border-b text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">Name</th>
                            <th class="px-4 py-3 font-medium">Frequency</th>
                            <th class="px-4 py-3 font-medium">Next due</th>
                            <th class="px-4 py-3 text-right font-medium">
                                Amount
                            </th>
                            <th class="w-24"></th>
                            <th class="w-16"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="bill in bills.data"
                            :key="bill.id"
                            class="border-b last:border-0"
                        >
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ bill.name }}</div>
                                <div class="text-xs text-muted-foreground">
                                    {{ categoryLabel(bill.category) }}
                                    <span v-if="bill.debt">
                                        · pays down {{ bill.debt.name }}
                                    </span>
                                </div>
                            </td>
                            <td
                                class="px-4 py-3 text-muted-foreground capitalize"
                            >
                                {{ bill.frequency }}
                            </td>
                            <td class="px-4 py-3">{{ formatDate(bill.next_due_on) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">
                                {{ money(bill.amount) }}
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <button
                                    type="button"
                                    class="text-sm font-medium text-primary underline-offset-4 hover:underline"
                                    @click="markPaid(bill)"
                                >
                                    Mark paid
                                </button>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button
                                    type="button"
                                    class="text-sm text-muted-foreground underline-offset-4 hover:underline"
                                    @click="openEdit(bill)"
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
            v-if="bills.data.length && bills.last_page > 1"
            class="flex items-center justify-between gap-2 text-sm"
        >
            <p class="text-muted-foreground">
                Showing {{ bills.from }}–{{ bills.to }} of {{ bills.total }}
            </p>
            <div class="flex items-center gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="!bills.prev_page_url"
                    @click="goToPage(bills.prev_page_url)"
                >
                    Previous
                </Button>
                <span class="text-muted-foreground">
                    Page {{ bills.current_page }} of {{ bills.last_page }}
                </span>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="!bills.next_page_url"
                    @click="goToPage(bills.next_page_url)"
                >
                    Next
                </Button>
            </div>
        </div>

        <Dialog v-model:open="dialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {{ editing ? 'Edit bill' : 'Add bill' }}
                    </DialogTitle>
                    <DialogDescription>
                        {{
                            editing
                                ? 'Update this recurring bill.'
                                : 'Track a recurring payment.'
                        }}
                    </DialogDescription>
                </DialogHeader>

                <BillForm
                    v-if="dialogOpen"
                    :key="editing?.id ?? 'new'"
                    :action="
                        editing
                            ? BillController.update({ bill: editing.id })
                            : BillController.store()
                    "
                    :debts="debts"
                    :bill="editing ?? undefined"
                    :submit-label="editing ? 'Save changes' : 'Save bill'"
                    :deletable="!!editing"
                    @success="handleSuccess"
                    @cancel="dialogOpen = false"
                    @delete="handleDelete"
                />
            </DialogContent>
        </Dialog>
    </div>
</template>
