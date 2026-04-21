<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { CalendarIcon } from 'lucide-vue-next';
import type { DateValue } from '@internationalized/date';
import {
    CalendarDate,
    DateFormatter,
    getLocalTimeZone,
    parseDate,
    today,
} from '@internationalized/date';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';

const props = defineProps<{
    id?: string;
    name: string;
    modelValue?: string | null;
    defaultValue?: string | null;
    placeholder?: string;
    required?: boolean;
    disabled?: boolean;
    class?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string | null];
}>();

const formatter = new DateFormatter('en-US', {
    month: '2-digit',
    day: '2-digit',
    year: 'numeric',
});

const toCalendarDate = (value: string | null | undefined): CalendarDate | undefined => {
    if (!value) return undefined;
    try {
        return parseDate(value);
    } catch {
        return undefined;
    }
};

const initial = toCalendarDate(props.modelValue ?? props.defaultValue ?? null);
const selected = ref<CalendarDate | undefined>(initial);
const open = ref(false);

watch(
    () => props.modelValue,
    (value) => {
        if (value === undefined) return;
        selected.value = toCalendarDate(value);
    },
);

const hiddenValue = computed(() =>
    selected.value ? selected.value.toString() : '',
);

const displayLabel = computed(() => {
    if (!selected.value) return props.placeholder ?? 'Pick a date';
    return formatter.format(selected.value.toDate(getLocalTimeZone()));
});

const handleUpdate = (value: unknown) => {
    if (value && typeof value === 'object' && 'toString' in value) {
        selected.value = value as CalendarDate;
        emit('update:modelValue', (value as CalendarDate).toString());
    } else {
        selected.value = undefined;
        emit('update:modelValue', null);
    }
    open.value = false;
};

const placeholderDate = today(getLocalTimeZone());
</script>

<template>
    <div :class="props.class">
        <input type="hidden" :name="name" :value="hiddenValue" />
        <Popover v-model:open="open">
            <PopoverTrigger as-child>
                <Button
                    :id="id"
                    type="button"
                    variant="outline"
                    :disabled="disabled"
                    :class="cn(
                        'w-full justify-start text-left font-normal h-9',
                        !selected && 'text-muted-foreground',
                    )"
                >
                    <CalendarIcon class="mr-2 h-4 w-4" />
                    {{ displayLabel }}
                </Button>
            </PopoverTrigger>
            <PopoverContent class="w-auto p-0" align="start">
                <Calendar
                    :model-value="(selected as DateValue | undefined)"
                    :placeholder="(placeholderDate as DateValue)"
                    initial-focus
                    @update:model-value="handleUpdate"
                />
            </PopoverContent>
        </Popover>
    </div>
</template>
