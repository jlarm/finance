export type ExpenseCategory =
    | 'groceries'
    | 'dining_out'
    | 'transportation'
    | 'utilities'
    | 'housing'
    | 'entertainment'
    | 'health'
    | 'shopping'
    | 'subscriptions'
    | 'personal_care';

export type ExpenseCategoryOption = {
    value: ExpenseCategory;
    label: string;
    color: string;
};

export const EXPENSE_CATEGORIES: ExpenseCategoryOption[] = [
    { value: 'groceries', label: 'Groceries', color: '#16a34a' },
    { value: 'dining_out', label: 'Dining Out', color: '#f97316' },
    { value: 'transportation', label: 'Transportation', color: '#0ea5e9' },
    { value: 'utilities', label: 'Utilities', color: '#6366f1' },
    { value: 'housing', label: 'Housing', color: '#7c3aed' },
    { value: 'entertainment', label: 'Entertainment', color: '#ec4899' },
    { value: 'health', label: 'Health', color: '#14b8a6' },
    { value: 'shopping', label: 'Shopping', color: '#f59e0b' },
    { value: 'subscriptions', label: 'Subscriptions', color: '#a855f7' },
    { value: 'personal_care', label: 'Personal Care', color: '#e11d48' },
];

const LABEL_BY_VALUE: Record<string, string> = Object.fromEntries(
    EXPENSE_CATEGORIES.map((c) => [c.value, c.label]),
);

export const categoryLabel = (value: string | null | undefined): string =>
    value ? (LABEL_BY_VALUE[value] ?? value) : '—';
