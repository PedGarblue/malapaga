<script setup lang="ts">
import { computed } from 'vue';
import type { Rate } from '@/types/models';

const props = defineProps<{
    eurAmount: number;
    rates: Rate[];
}>();

const bcvEurRate = computed(() => {
    return props.rates.find(r => r.source === 'BCV' && r.currency_to === 'EUR');
});

const eurToVesBcv = computed(() => {
    if (!bcvEurRate.value || !props.eurAmount) return 0;
    return props.eurAmount * bcvEurRate.value.value;
});

</script>

<template>
    <div v-if="eurAmount > 0"
        class="mt-3 space-y-2 rounded-lg border border-sidebar-border/70 bg-muted/50 p-3 text-sm dark:border-sidebar-border">
        <div class="font-medium text-muted-foreground">Currency Conversions:</div>

        <div v-if="bcvEurRate" class="flex items-center justify-between">
            <span class="text-muted-foreground">EUR → VES (BCV):</span>
            <span class="font-semibold">Bs. {{ eurToVesBcv.toFixed(2) }}</span>
        </div>
        <div v-else class="flex items-center justify-between text-muted-foreground">
            <span>EUR → VES (BCV):</span>
            <span class="text-xs italic">Rate not available</span>
        </div>
    </div>
</template>
