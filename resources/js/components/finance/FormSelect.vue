<script setup lang="ts" generic="T extends string | number">
import { ref, watch } from 'vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type Option = { value: T; label: string };

const props = defineProps<{
    id?: string;
    name: string;
    options: Option[];
    modelValue?: T | null;
    defaultValue?: T | null;
    placeholder?: string;
    required?: boolean;
    disabled?: boolean;
    class?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: T | null];
}>();

const initial =
    props.modelValue !== undefined && props.modelValue !== null
        ? String(props.modelValue)
        : props.defaultValue !== undefined && props.defaultValue !== null
          ? String(props.defaultValue)
          : '';

const inner = ref<string>(initial);

watch(
    () => props.modelValue,
    (value) => {
        if (value === undefined || value === null) {
            inner.value = '';
        } else {
            inner.value = String(value);
        }
    },
);

const handleChange = (value: unknown) => {
    const stringValue = value === null || value === undefined ? '' : String(value);
    inner.value = stringValue;
    const match = props.options.find((opt) => String(opt.value) === stringValue);
    emit('update:modelValue', (match?.value ?? null) as T | null);
};
</script>

<template>
    <div :class="props.class">
        <input type="hidden" :name="name" :value="inner" />
        <Select :model-value="inner" :disabled="disabled" @update:model-value="handleChange">
            <SelectTrigger :id="id" class="w-full">
                <SelectValue :placeholder="placeholder ?? 'Select an option'" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem
                    v-for="opt in options"
                    :key="String(opt.value)"
                    :value="String(opt.value)"
                >
                    {{ opt.label }}
                </SelectItem>
            </SelectContent>
        </Select>
    </div>
</template>
