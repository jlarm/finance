<?php

namespace App\Enums;

enum ExpenseCategory: string
{
    case Groceries = 'groceries';
    case DiningOut = 'dining_out';
    case Transportation = 'transportation';
    case Utilities = 'utilities';
    case Housing = 'housing';
    case Entertainment = 'entertainment';
    case Health = 'health';
    case Shopping = 'shopping';
    case Subscriptions = 'subscriptions';
    case PersonalCare = 'personal_care';

    public function label(): string
    {
        return match ($this) {
            self::Groceries => 'Groceries',
            self::DiningOut => 'Dining Out',
            self::Transportation => 'Transportation',
            self::Utilities => 'Utilities',
            self::Housing => 'Housing',
            self::Entertainment => 'Entertainment',
            self::Health => 'Health',
            self::Shopping => 'Shopping',
            self::Subscriptions => 'Subscriptions',
            self::PersonalCare => 'Personal Care',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Groceries => '#16a34a',
            self::DiningOut => '#f97316',
            self::Transportation => '#0ea5e9',
            self::Utilities => '#6366f1',
            self::Housing => '#7c3aed',
            self::Entertainment => '#ec4899',
            self::Health => '#14b8a6',
            self::Shopping => '#f59e0b',
            self::Subscriptions => '#a855f7',
            self::PersonalCare => '#e11d48',
        };
    }

    /**
     * @return array<int, array{value: string, label: string, color: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $c): array => [
                'value' => $c->value,
                'label' => $c->label(),
                'color' => $c->color(),
            ],
            self::cases(),
        );
    }
}
