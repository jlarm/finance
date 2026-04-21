<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { create, edit, index } from '@/routes/bills';
import { formatDate } from '@/lib/utils';

type Bill = {
    id: number;
    name: string;
    amount: number;
    frequency: string;
    next_due_on: string;
    status?: string;
    category: { id: number; name: string } | null;
};

defineProps<{ bills: Bill[] }>();

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
</script>

<template>
    <Head title="Bills" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                title="Bills"
                description="Recurring obligations at a glance."
            />
            <Button as-child>
                <Link :href="create()">Add bill</Link>
            </Button>
        </div>

        <Card>
            <CardContent class="p-0">
                <div
                    v-if="!bills.length"
                    class="flex flex-col items-center gap-2 p-10 text-center"
                >
                    <p class="font-medium">No bills set up</p>
                    <p class="text-sm text-muted-foreground">
                        Add your recurring bills so we can remind you when
                        they're due.
                    </p>
                    <Button as-child class="mt-2">
                        <Link :href="create()">Add bill</Link>
                    </Button>
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
                            <th class="w-10"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="bill in bills"
                            :key="bill.id"
                            class="border-b last:border-0"
                        >
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ bill.name }}</div>
                                <div class="text-xs text-muted-foreground">
                                    {{ bill.category?.name ?? '—' }}
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
                            <td class="px-4 py-3 text-right">
                                <Link
                                    :href="edit({ bill: bill.id })"
                                    class="text-sm text-muted-foreground underline-offset-4 hover:underline"
                                >
                                    Edit
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </CardContent>
        </Card>
    </div>
</template>
