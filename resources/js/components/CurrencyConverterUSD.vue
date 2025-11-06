<script setup lang="ts">
import { computed } from 'vue';
import type { Rate } from '@/types/models';

const props = defineProps<{
    usdAmount: number;
    rates: Rate[];
}>();

const bcvUsdRate = computed(() => {
    return props.rates.find(r => r.source === 'BCV' && r.currency_to === 'USD');
});

const paraleloUsdRate = computed(() => {
    return props.rates.find(r => r.source === 'Paralelo' && r.currency_to === 'USD');
});

const usdToVesBcv = computed(() => {
    if (!bcvUsdRate.value || !props.usdAmount) return 0;
    return props.usdAmount * bcvUsdRate.value.value;
});

const usdToVesParalelo = computed(() => {
    if (!paraleloUsdRate.value || !props.usdAmount) return 0;
    return props.usdAmount * paraleloUsdRate.value.value;
});
</script>

<template>
    <div v-if="usdAmount > 0"
        class="mt-3 space-y-2 rounded-lg border border-sidebar-border/70 bg-muted/50 p-3 text-sm dark:border-sidebar-border">
        <div class="font-medium text-muted-foreground">Currency Conversions:</div>

        <div v-if="bcvUsdRate" class="flex items-center justify-between">
            <span class="text-muted-foreground">USD → VES (BCV):</span>
            <span class="font-semibold">Bs. {{ usdToVesBcv.toFixed(2) }}</span>
        </div>
        <div v-else class="flex items-center justify-between text-muted-foreground">
            <span>USD → VES (BCV):</span>
            <span class="text-xs italic">Rate not available</span>
        </div>

        <div v-if="paraleloUsdRate" class="flex items-center justify-between">
            <span class="text-muted-foreground">USD → VES (Paralelo):</span>
            <span class="font-semibold">Bs. {{ usdToVesParalelo.toFixed(2) }}</span>
        </div>
        <div v-else class="flex items-center justify-between text-muted-foreground">
            <span>USD → VES (Paralelo):</span>
            <span class="text-xs italic">Rate not available</span>
        </div>
    </div>
</template>
