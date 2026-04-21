<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import SettingsController from '@/actions/App/Http/Controllers/SettingsController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/finance-settings';

type Settings = {
    currency: string;
    locale: string;
    monthly_cycle_start_day: number;
    debt_strategy: 'snowball' | 'avalanche';
    ai_tone: 'supportive' | 'direct' | 'cheerful' | 'neutral';
    ai_enabled: boolean;
    timezone: string;
};

defineProps<{ settings: Settings }>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Finance settings', href: edit() }],
    },
});
</script>

<template>
    <Head title="Finance settings" />

    <div class="mx-auto flex w-full max-w-xl flex-col gap-6 p-4">
        <Heading
            title="Finance settings"
            description="Make the app feel like yours."
        />

        <Card>
            <CardContent class="p-6">
                <Form
                    :action="SettingsController.update()"
                    class="flex flex-col gap-4"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-2">
                        <Label for="currency">Currency</Label>
                        <Input
                            id="currency"
                            name="currency"
                            maxlength="3"
                            :value="settings.currency"
                            class="uppercase"
                        />
                        <InputError :message="errors.currency" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="locale">Locale</Label>
                        <Input
                            id="locale"
                            name="locale"
                            :value="settings.locale"
                        />
                        <InputError :message="errors.locale" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="monthly_cycle_start_day">
                            Monthly cycle start day
                        </Label>
                        <Input
                            id="monthly_cycle_start_day"
                            name="monthly_cycle_start_day"
                            type="number"
                            min="1"
                            max="28"
                            :value="settings.monthly_cycle_start_day"
                        />
                        <InputError :message="errors.monthly_cycle_start_day" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="debt_strategy">Debt payoff strategy</Label>
                        <select
                            id="debt_strategy"
                            name="debt_strategy"
                            class="h-10 rounded-md border bg-background px-3 text-sm"
                            :value="settings.debt_strategy"
                        >
                            <option value="avalanche">
                                Avalanche (highest APR first)
                            </option>
                            <option value="snowball">
                                Snowball (smallest balance first)
                            </option>
                        </select>
                        <InputError :message="errors.debt_strategy" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="ai_tone">AI tone</Label>
                        <select
                            id="ai_tone"
                            name="ai_tone"
                            class="h-10 rounded-md border bg-background px-3 text-sm"
                            :value="settings.ai_tone"
                        >
                            <option value="supportive">Supportive</option>
                            <option value="direct">Direct</option>
                            <option value="cheerful">Cheerful</option>
                            <option value="neutral">Neutral</option>
                        </select>
                        <InputError :message="errors.ai_tone" />
                    </div>

                    <div class="flex items-center gap-3">
                        <input
                            id="ai_enabled"
                            name="ai_enabled"
                            type="checkbox"
                            value="1"
                            :checked="settings.ai_enabled"
                            class="h-4 w-4"
                        />
                        <Label for="ai_enabled" class="m-0">
                            Enable AI features
                        </Label>
                    </div>
                    <InputError :message="errors.ai_enabled" />

                    <div class="grid gap-2">
                        <Label for="timezone">Timezone</Label>
                        <Input
                            id="timezone"
                            name="timezone"
                            :value="settings.timezone"
                        />
                        <InputError :message="errors.timezone" />
                    </div>

                    <div class="flex justify-end">
                        <Button type="submit" :disabled="processing">
                            Save settings
                        </Button>
                    </div>
                </Form>
            </CardContent>
        </Card>
    </div>
</template>
