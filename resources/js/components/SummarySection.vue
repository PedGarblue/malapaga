<script setup lang="ts">
import { computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { Item, Consumer, Rate, Participation } from '@/types/models';

const props = defineProps<{
    consumers: Consumer[];
    items: Item[];
    rates: Rate[];
    participationsByItem: Map<string | number, Participation[]>;
}>();

const totalConsumers = computed(() => props.consumers.length);
const totalItems = computed(() => props.items.length);

const totalCostUsd = computed(() => {
    return props.items.reduce((sum, item) => sum + (item.price_usd || 0), 0);
});

const bcvUsdRate = computed(() => {
    return props.rates.find(r => r.source === 'BCV' && r.currency_to === 'USD');
});

const totalCostVes = computed(() => {
    if (!bcvUsdRate.value) return 0;
    return totalCostUsd.value * bcvUsdRate.value.value;
});

const consumerTotals = computed(() => {
    const totals = new Map<string | number, number>();

    // Initialize all consumers with 0
    props.consumers.forEach(consumer => {
        totals.set(consumer.id!, 0);
    });

    // Calculate each consumer's share
    props.items.forEach(item => {
        const participations = props.participationsByItem.get(item.id!) || [];

        if (participations.length === 0) return;

        const splitType = item.split_type || 'shared';

        if (splitType === 'shared') {
            // Shared split: divide price equally among all participants
            const sharePerPerson = item.price_usd / participations.length;

            participations.forEach(participation => {
                const currentTotal = totals.get(participation.paid_by_id || participation.consumer_id) || 0;
                totals.set(participation.paid_by_id || participation.consumer_id, currentTotal + sharePerPerson);
            });
        } else {
            participations.forEach(participation => {
                const consumerShare = item.price_usd * participation.qty;
                const currentTotal = totals.get(participation.paid_by_id || participation.consumer_id) || 0;
                totals.set(participation.paid_by_id || participation.consumer_id, currentTotal + consumerShare);
            });
        }
    });

    return totals;
});

const consumerTotalsArray = computed(() => {
    return props.consumers
        .map(consumer => ({
            consumer,
            totalUsd: consumerTotals.value.get(consumer.id!) || 0,
            totalVes: bcvUsdRate.value
                ? (consumerTotals.value.get(consumer.id!) || 0) * bcvUsdRate.value.value
                : 0
        }))
        .filter(item => item.totalUsd > 0) // Only show consumers with amounts to pay
        .sort((a, b) => b.totalUsd - a.totalUsd); // Sort by amount descending
});
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="text-base">Summary</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
            <!-- Overall Totals -->
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-[#706f6c] dark:text-[#A1A09A]">Total Cost (USD):</span>
                    <span class="font-medium">${{ totalCostUsd.toFixed(2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-[#706f6c] dark:text-[#A1A09A]">Total Cost (VES):</span>
                    <span class="font-medium">{{ totalCostVes.toFixed(2) }} Bs</span>
                </div>
            </div>

            <!-- Consumer Breakdown -->
            <div v-if="consumerTotalsArray.length > 0" class="space-y-2">
                <div class="border-t border-[#e3e3e0] pt-3 dark:border-[#3E3E3A]">
                    <div class="text-xs font-medium mb-2 text-[#706f6c] dark:text-[#A1A09A]">
                        Amount to Pay by Consumer
                    </div>
                </div>
                <div v-for="item in consumerTotalsArray" :key="item.consumer.id"
                    class="flex items-start justify-between rounded-md border border-[#e3e3e0] bg-[#FDFDFC] p-2 text-sm dark:border-[#3E3E3A] dark:bg-[#1C1C1A]">
                    <span class="font-medium">{{ item.consumer.name }}</span>
                    <div class="text-right">
                        <div class="font-semibold">${{ item.totalUsd.toFixed(2) }}</div>
                        <div class="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                            {{ item.totalVes.toFixed(2) }} Bs
                        </div>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
