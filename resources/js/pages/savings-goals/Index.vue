<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
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
import SavingsGoalForm from '@/components/finance/forms/SavingsGoalForm.vue';
import SavingsGoalController from '@/actions/App/Http/Controllers/SavingsGoalController';
import { index } from '@/routes/savings-goals';
import { formatDate } from '@/lib/utils';

type Goal = {
    id: number;
    name: string;
    target_amount: number;
    current_amount: number;
    target_date: string | null;
    is_achieved: boolean;
    progress_percentage: number;
    notes: string | null;
};

defineProps<{ goals: Goal[] }>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Savings goals', href: index() }],
    },
});

const money = (v: number) =>
    new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: 'USD',
    }).format(v ?? 0);

const dialogOpen = ref(false);
const editing = ref<Goal | null>(null);

const openCreate = () => {
    editing.value = null;
    dialogOpen.value = true;
};

const openEdit = (goal: Goal) => {
    editing.value = goal;
    dialogOpen.value = true;
};

const handleSuccess = () => {
    dialogOpen.value = false;
    editing.value = null;
};

const handleDelete = () => {
    if (!editing.value) return;
    if (!confirm('Delete this goal? This cannot be undone.')) return;
    router.delete(
        SavingsGoalController.destroy({ savings_goal: editing.value.id }).url,
        {
            preserveScroll: true,
            onSuccess: () => {
                dialogOpen.value = false;
                editing.value = null;
            },
        },
    );
};
</script>

<template>
    <Head title="Savings goals" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                title="Savings goals"
                description="Something you're working toward."
            />
            <Button @click="openCreate">New goal</Button>
        </div>

        <div v-if="!goals.length">
            <Card>
                <CardContent class="flex flex-col items-center gap-2 p-10 text-center">
                    <p class="font-medium">No goals yet</p>
                    <p class="text-sm text-muted-foreground">
                        Name something worth saving for — a trip, a cushion, a
                        move. We'll track your pace.
                    </p>
                    <Button class="mt-2" @click="openCreate">New goal</Button>
                </CardContent>
            </Card>
        </div>

        <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <Card v-for="goal in goals" :key="goal.id">
                <CardHeader>
                    <div class="flex items-start justify-between">
                        <div>
                            <CardTitle>{{ goal.name }}</CardTitle>
                            <CardDescription v-if="goal.target_date">
                                By {{ formatDate(goal.target_date) }}
                            </CardDescription>
                        </div>
                        <span
                            v-if="goal.is_achieved"
                            class="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary"
                        >
                            Achieved
                        </span>
                    </div>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div class="flex items-end justify-between">
                        <span class="text-sm text-muted-foreground">
                            {{ money(goal.current_amount) }}
                        </span>
                        <span class="text-sm text-muted-foreground">
                            of {{ money(goal.target_amount) }}
                        </span>
                    </div>
                    <div class="h-2 w-full rounded-full bg-muted">
                        <div
                            class="h-full rounded-full bg-primary"
                            :style="{ width: `${goal.progress_percentage}%` }"
                        />
                    </div>
                    <div class="flex justify-end text-sm">
                        <button
                            type="button"
                            class="text-muted-foreground underline-offset-4 hover:underline"
                            @click="openEdit(goal)"
                        >
                            Edit
                        </button>
                    </div>
                </CardContent>
            </Card>
        </div>

        <Dialog v-model:open="dialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {{ editing ? 'Edit goal' : 'New goal' }}
                    </DialogTitle>
                    <DialogDescription>
                        {{
                            editing
                                ? 'Update this savings goal.'
                                : 'Name something worth saving for.'
                        }}
                    </DialogDescription>
                </DialogHeader>

                <SavingsGoalForm
                    v-if="dialogOpen"
                    :key="editing?.id ?? 'new'"
                    :action="
                        editing
                            ? SavingsGoalController.update({
                                  savings_goal: editing.id,
                              })
                            : SavingsGoalController.store()
                    "
                    :goal="editing ?? undefined"
                    :submit-label="editing ? 'Save changes' : 'Save goal'"
                    :deletable="!!editing"
                    @success="handleSuccess"
                    @cancel="dialogOpen = false"
                    @delete="handleDelete"
                />
            </DialogContent>
        </Dialog>
    </div>
</template>
