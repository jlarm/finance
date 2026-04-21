<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { planner } from '@/routes';

type Debt = {
    id: number;
    name: string;
    balance: number;
    apr: number | null;
    minimum_payment: number | null;
};

const props = defineProps<{
    strategy: 'snowball' | 'avalanche';
    extraPayment: number;
    debts: Debt[];
    totals: { balance: number; minimums: number };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Debt planner', href: planner() }],
    },
});

const strategy = ref<'snowball' | 'avalanche'>(props.strategy);
const extra = ref<number>(props.extraPayment);

const money = (v: number) =>
    new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: 'USD',
    }).format(v ?? 0);

let t: ReturnType<typeof setTimeout> | null = null;
const refresh = () => {
    if (t) clearTimeout(t);
    t = setTimeout(() => {
        router.get(
            planner().url,
            { strategy: strategy.value, extra_payment: extra.value },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }, 300);
};

watch([strategy, extra], refresh);
</script>

<template>
    <Head title="Debt planner" />

    <div class="flex flex-col gap-6 p-4">
        <Heading
            title="Debt planner"
            description="Plan your path to paid off. Try different strategies — no commitment."
        />

        <div class="grid gap-4 md:grid-cols-3">
            <Card class="md:col-span-2">
                <CardHeader>
                    <CardTitle>Strategy</CardTitle>
                    <CardDescription>
                        Snowball pays smallest first for momentum. Avalanche
                        targets highest APR to save the most money.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="flex gap-2">
                        <Button
                            :variant="strategy === 'snowball' ? 'default' : 'outline'"
                            @click="strategy = 'snowball'"
                        >
                            Snowball
                        </Button>
                        <Button
                            :variant="strategy === 'avalanche' ? 'default' : 'outline'"
                            @click="strategy = 'avalanche'"
                        >
                            Avalanche
                        </Button>
                    </div>

                    <div class="grid gap-2">
                        <Label for="extra">Extra monthly payment</Label>
                        <Input
                            id="extra"
                            type="number"
                            min="0"
                            step="10"
                            inputmode="decimal"
                            v-model.number="extra"
                        />
                    </div>
                </CardContent>
            </Card>

            <div class="grid gap-4">
                <Card>
                    <CardHeader>
                        <CardDescription>Total balance</CardDescription>
                        <CardTitle>{{ money(totals.balance) }}</CardTitle>
                    </CardHeader>
                </Card>
                <Card>
                    <CardHeader>
                        <CardDescription>Minimums / month</CardDescription>
                        <CardTitle>{{ money(totals.minimums) }}</CardTitle>
                    </CardHeader>
                </Card>
            </div>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Payoff order</CardTitle>
                <CardDescription>
                    We'll tackle debts in this order based on your strategy.
                </CardDescription>
            </CardHeader>
            <CardContent class="p-0">
                <p
                    v-if="!debts.length"
                    class="p-10 text-center text-sm text-muted-foreground"
                >
                    No active debts — nothing to plan.
                </p>
                <ol v-else class="divide-y">
                    <li
                        v-for="(debt, i) in debts"
                        :key="debt.id"
                        class="flex items-center justify-between gap-4 px-4 py-3 text-sm"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                class="flex h-7 w-7 items-center justify-center rounded-full bg-muted text-xs font-semibold"
                            >
                                {{ i + 1 }}
                            </span>
                            <div>
                                <p class="font-medium">{{ debt.name }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ debt.apr ? `${debt.apr}% APR` : 'No APR set' }}
                                    <span v-if="debt.minimum_payment">
                                        · min {{ money(debt.minimum_payment) }}
                                    </span>
                                </p>
                            </div>
                        </div>
                        <span class="tabular-nums">
                            {{ money(debt.balance) }}
                        </span>
                    </li>
                </ol>
            </CardContent>
        </Card>
    </div>
</template>
