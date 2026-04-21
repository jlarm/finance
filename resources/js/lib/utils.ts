import type { InertiaLinkProps } from '@inertiajs/vue3';
import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(href: NonNullable<InertiaLinkProps['href']>) {
    return typeof href === 'string' ? href : href?.url;
}

export function formatDate(value: string | null | undefined): string {
    if (!value) return '';
    const iso = value.length >= 10 ? value.slice(0, 10) : value;
    const [year, month, day] = iso.split('-');
    if (!year || !month || !day) return value;
    return `${month}/${day}/${year}`;
}
