<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { cashFlow, index, spending } from '@/routes/reports';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Reports', href: index() }],
    },
});
</script>

<template>
    <Head title="Reports" />

    <div class="flex flex-col gap-6 p-4">
        <Heading
            title="Reports"
            description="Zoom out on where your money went."
        />

        <div class="grid gap-4 md:grid-cols-2">
            <Link :href="spending()" class="block">
                <Card class="transition hover:bg-accent/40">
                    <CardHeader>
                        <CardTitle>Spending</CardTitle>
                        <CardDescription>
                            See your expenses by category and by month.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <p class="text-sm text-muted-foreground">
                            Open spending report →
                        </p>
                    </CardContent>
                </Card>
            </Link>

            <Link :href="cashFlow()" class="block">
                <Card class="transition hover:bg-accent/40">
                    <CardHeader>
                        <CardTitle>Cash flow</CardTitle>
                        <CardDescription>
                            Income vs. expenses over the last several months.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <p class="text-sm text-muted-foreground">
                            Open cash-flow report →
                        </p>
                    </CardContent>
                </Card>
            </Link>
        </div>
    </div>
</template>
