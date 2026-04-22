<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    BarChart3,
    BookOpen,
    CalendarDays,
    CreditCard,
    FolderGit2,
    LayoutGrid,
    Lightbulb,
    PiggyBank,
    Receipt,
    ReceiptText,
    Sparkles,
    Target,
    Wallet,
} from 'lucide-vue-next';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard, planner } from '@/routes';
import { index as assistantIndex } from '@/routes/assistant';
import { index as billsIndex } from '@/routes/bills';
import { index as budgetTargetsIndex } from '@/routes/budget-targets';
import { index as debtsIndex } from '@/routes/debts';
import { index as expensesIndex } from '@/routes/expenses';
import { index as incomeSourcesIndex } from '@/routes/income-sources';
import { index as insightsIndex } from '@/routes/insights';
import { index as reportsIndex } from '@/routes/reports';
import { index as savingsGoalsIndex } from '@/routes/savings-goals';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Expenses',
        href: expensesIndex(),
        icon: Receipt,
    },
    {
        title: 'Bills',
        href: billsIndex(),
        icon: ReceiptText,
    },
    {
        title: 'Debts',
        href: debtsIndex(),
        icon: CreditCard,
    },
    {
        title: 'Income',
        href: incomeSourcesIndex(),
        icon: Wallet,
    },
    {
        title: 'Savings Goals',
        href: savingsGoalsIndex(),
        icon: PiggyBank,
    },
    {
        title: 'Budgets',
        href: budgetTargetsIndex(),
        icon: Target,
    },
    {
        title: 'Planner',
        href: planner(),
        icon: CalendarDays,
    },
    {
        title: 'Reports',
        href: reportsIndex(),
        icon: BarChart3,
    },
    {
        title: 'Insights',
        href: insightsIndex(),
        icon: Lightbulb,
    },
    {
        title: 'Assistant',
        href: assistantIndex(),
        icon: Sparkles,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <div
                                class="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground"
                            >
                                <BookOpen class="size-5" />
                            </div>
                            <div class="ml-1 grid flex-1 text-left text-sm">
                                <span
                                    class="mb-0.5 truncate leading-tight font-semibold"
                                    >Ledger</span
                                >
                            </div>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
