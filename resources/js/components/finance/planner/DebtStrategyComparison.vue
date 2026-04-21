<script setup lang="ts">
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

type StrategyResult = {
    label: string;
    payoffMonths: number;
    totalInterest: number;
    firstDebt?: string;
};

type Props = {
    snowball: StrategyResult;
    avalanche: StrategyResult;
    currency?: string;
    selected?: 'snowball' | 'avalanche';
};

const props = withDefaults(defineProps<Props>(), {
    currency: 'USD',
});

const money = (v: number) =>
    new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: props.currency,
        maximumFractionDigits: 0,
    }).format(v ?? 0);

const months = (m: number) => {
    if (m < 12) {
        return `${m} mo`;
    }
    const years = Math.floor(m / 12);
    const rem = m % 12;
    return rem ? `${years}y ${rem}mo` : `${years}y`;
};
</script>

<template>
    <div class="grid gap-4 md:grid-cols-2">
        <Card :class="selected === 'snowball' ? 'ring-2 ring-primary' : ''">
            <CardHeader>
                <CardDescription>Snowball</CardDescription>
                <CardTitle>{{ snowball.label }}</CardTitle>
            </CardHeader>
            <CardContent class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Payoff in</span>
                    <span class="tabular-nums font-medium">
                        {{ months(snowball.payoffMonths) }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Total interest</span>
                    <span class="tabular-nums font-medium">
                        {{ money(snowball.totalInterest) }}
                    </span>
                </div>
                <div v-if="snowball.firstDebt" class="flex justify-between">
                    <span class="text-muted-foreground">Tackle first</span>
                    <span class="font-medium">{{ snowball.firstDebt }}</span>
                </div>
            </CardContent>
        </Card>

        <Card :class="selected === 'avalanche' ? 'ring-2 ring-primary' : ''">
            <CardHeader>
                <CardDescription>Avalanche</CardDescription>
                <CardTitle>{{ avalanche.label }}</CardTitle>
            </CardHeader>
            <CardContent class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Payoff in</span>
                    <span class="tabular-nums font-medium">
                        {{ months(avalanche.payoffMonths) }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Total interest</span>
                    <span class="tabular-nums font-medium">
                        {{ money(avalanche.totalInterest) }}
                    </span>
                </div>
                <div v-if="avalanche.firstDebt" class="flex justify-between">
                    <span class="text-muted-foreground">Tackle first</span>
                    <span class="font-medium">{{ avalanche.firstDebt }}</span>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
