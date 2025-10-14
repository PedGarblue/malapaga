<script setup lang="ts">
import { computed } from 'vue';
import type { Rate } from '@/types/models';

const props = defineProps<{
    vesAmount: number;
    rates: Rate[];
}>();

const bcvEurRate = computed(() => {
    return props.rates.find(r => r.source === 'BCV' && r.currency_to === 'EUR');
});

const bcvUsdRate = computed(() => {
    return props.rates.find(r => r.source === 'BCV' && r.currency_to === 'USD');
});

const paraleloUsdRate = computed(() => {
    return props.rates.find(r => r.source === 'Paralelo' && r.currency_to === 'USD');
});

const vesToEur = computed(() => {
    if (!bcvEurRate.value || !props.vesAmount) return 0;
    return props.vesAmount / bcvEurRate.value.value;
});

const vesToUsdBcv = computed(() => {
    if (!bcvUsdRate.value || !props.vesAmount) return 0;
    return props.vesAmount / bcvUsdRate.value.value;
});

const vesToUsdParalelo = computed(() => {
    if (!paraleloUsdRate.value || !props.vesAmount) return 0;
    return props.vesAmount / paraleloUsdRate.value.value;
});
</script>

<template>
    <div v-if="vesAmount > 0"
        class="mt-3 space-y-2 rounded-lg border border-sidebar-border/70 bg-muted/50 p-3 text-sm dark:border-sidebar-border">
        <div class="font-medium text-muted-foreground">Currency Conversions:</div>

        <div v-if="bcvEurRate" class="flex items-center justify-between">
            <span class="text-muted-foreground">VES → EUR (BCV):</span>
            <span class="font-semibold">€{{ vesToEur.toFixed(2) }}</span>
        </div>
        <div v-else class="flex items-center justify-between text-muted-foreground">
            <span>VES → EUR (BCV):</span>
            <span class="text-xs italic">Rate not available</span>
        </div>

        <div v-if="bcvUsdRate" class="flex items-center justify-between">
            <span class="text-muted-foreground">VES → USD (BCV):</span>
            <span class="font-semibold">${{ vesToUsdBcv.toFixed(2) }}</span>
        </div>
        <div v-else class="flex items-center justify-between text-muted-foreground">
            <span>VES → USD (BCV):</span>
            <span class="text-xs italic">Rate not available</span>
        </div>

        <div v-if="paraleloUsdRate" class="flex items-center justify-between">
            <span class="text-muted-foreground">VES → USD (Paralelo):</span>
            <span class="font-semibold">${{ vesToUsdParalelo.toFixed(2) }}</span>
        </div>
        <div v-else class="flex items-center justify-between text-muted-foreground">
            <span>VES → USD (Paralelo):</span>
            <span class="text-xs italic">Rate not available</span>
        </div>
    </div>
</template>
