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
import AppLogo from '@/components/AppLogo.vue';
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

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
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
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
