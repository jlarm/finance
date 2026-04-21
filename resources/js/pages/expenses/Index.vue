<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { Search, X } from 'lucide-vue-next';
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
import { Input } from '@/components/ui/input';
import ExpenseForm from '@/components/finance/forms/ExpenseForm.vue';
import FormSelect from '@/components/finance/FormSelect.vue';
import ExpenseController from '@/actions/App/Http/Controllers/ExpenseController';
import { index } from '@/routes/expenses';
import { formatDate } from '@/lib/utils';

type Expense = {
    id: number;
    amount: number;
    occurred_on: string;
    description: string;
    notes: string | null;
    expense_category_id: number;
    category: { id: number; name: string } | null;
};

type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    from: number | null;
    to: number | null;
    total: number;
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

const props = defineProps<{
    expenses: Paginated<Expense>;
    categories: { id: number; name: string }[];
    filters: { from?: string; to?: string; category?: number; search?: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Expenses', href: index() }],
    },
});

const money = (v: number) =>
    new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: 'USD',
    }).format(v ?? 0);

const search = ref(props.filters.search ?? '');
const categoryId = ref<number | null>(props.filters.category ?? null);

const categoryOptions = computed(() =>
    props.categories.map((cat) => ({ value: cat.id, label: cat.name })),
);

const hasFilters = computed(
    () => !!(search.value || categoryId.value !== null),
);

const applyFilters = () => {
    router.get(
        index().url,
        {
            search: search.value || undefined,
            category: categoryId.value ?? undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

let searchTimer: ReturnType<typeof setTimeout> | null = null;
watch(search, () => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 300);
});

watch(categoryId, applyFilters);

const clearFilters = () => {
    search.value = '';
    categoryId.value = null;
};

const goToPage = (url: string | null) => {
    if (!url) return;
    router.get(url, {}, { preserveState: true, preserveScroll: true });
};

const dialogOpen = ref(false);
const editing = ref<Expense | null>(null);

const openCreate = () => {
    editing.value = null;
    dialogOpen.value = true;
};

const openEdit = (expense: Expense) => {
    editing.value = expense;
    dialogOpen.value = true;
};

const handleSuccess = () => {
    dialogOpen.value = false;
    editing.value = null;
};

const handleDelete = () => {
    if (!editing.value) return;
    if (!confirm('Delete this expense? This cannot be undone.')) return;
    router.delete(ExpenseController.destroy({ expense: editing.value.id }).url, {
        preserveScroll: true,
        onSuccess: () => {
            dialogOpen.value = false;
            editing.value = null;
        },
    });
};
</script>

<template>
    <Head title="Expenses" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                title="Expenses"
                description="Everything you've spent, in one place."
            />
            <Button @click="openCreate">Add expense</Button>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <div class="relative flex-1">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    type="search"
                    placeholder="Search expenses..."
                    class="pl-9"
                />
            </div>
            <FormSelect
                v-model="categoryId"
                name="category_filter"
                :options="categoryOptions"
                placeholder="All categories"
                class="sm:w-56"
            />
            <Button
                v-if="hasFilters"
                variant="ghost"
                size="sm"
                @click="clearFilters"
            >
                <X class="size-4" />
                Clear
            </Button>
        </div>

        <Card>
            <CardContent class="p-0">
                <div
                    v-if="!expenses.data.length"
                    class="flex flex-col items-center gap-2 p-10 text-center"
                >
                    <p class="font-medium">
                        {{ hasFilters ? 'No matching expenses' : 'No expenses yet' }}
                    </p>
                    <p class="text-sm text-muted-foreground">
                        {{
                            hasFilters
                                ? 'Try a different search or category.'
                                : 'Log your first expense to start seeing trends.'
                        }}
                    </p>
                    <Button
                        v-if="hasFilters"
                        variant="ghost"
                        class="mt-2"
                        @click="clearFilters"
                    >
                        Clear filters
                    </Button>
                    <Button v-else class="mt-2" @click="openCreate">Add expense</Button>
                </div>

                <table v-else class="w-full text-sm">
                    <thead class="border-b text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">Date</th>
                            <th class="px-4 py-3 font-medium">Description</th>
                            <th class="px-4 py-3 font-medium">Category</th>
                            <th class="px-4 py-3 text-right font-medium">
                                Amount
                            </th>
                            <th class="w-10"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="expense in expenses.data"
                            :key="expense.id"
                            class="border-b last:border-0"
                        >
                            <td class="px-4 py-3">{{ formatDate(expense.occurred_on) }}</td>
                            <td class="px-4 py-3">{{ expense.description }}</td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ expense.category?.name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">
                                {{ money(expense.amount) }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button
                                    type="button"
                                    class="text-sm text-muted-foreground underline-offset-4 hover:underline"
                                    @click="openEdit(expense)"
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
            v-if="expenses.data.length && expenses.last_page > 1"
            class="flex items-center justify-between gap-2 text-sm"
        >
            <p class="text-muted-foreground">
                Showing {{ expenses.from }}–{{ expenses.to }} of
                {{ expenses.total }}
            </p>
            <div class="flex items-center gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="!expenses.prev_page_url"
                    @click="goToPage(expenses.prev_page_url)"
                >
                    Previous
                </Button>
                <span class="text-muted-foreground">
                    Page {{ expenses.current_page }} of {{ expenses.last_page }}
                </span>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="!expenses.next_page_url"
                    @click="goToPage(expenses.next_page_url)"
                >
                    Next
                </Button>
            </div>
        </div>

        <Dialog v-model:open="dialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {{ editing ? 'Edit expense' : 'Add expense' }}
                    </DialogTitle>
                    <DialogDescription>
                        {{
                            editing
                                ? 'Update what you logged.'
                                : 'Log something you spent.'
                        }}
                    </DialogDescription>
                </DialogHeader>

                <ExpenseForm
                    v-if="dialogOpen"
                    :key="editing?.id ?? 'new'"
                    :action="
                        editing
                            ? ExpenseController.update({ expense: editing.id })
                            : ExpenseController.store()
                    "
                    :categories="categories"
                    :expense="editing ?? undefined"
                    :submit-label="editing ? 'Save changes' : 'Save expense'"
                    :deletable="!!editing"
                    @success="handleSuccess"
                    @cancel="dialogOpen = false"
                    @delete="handleDelete"
                />
            </DialogContent>
        </Dialog>
    </div>
</template>
