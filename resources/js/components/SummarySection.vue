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

const consumerItems = computed(() => {
    const itemsByConsumer = new Map<string | number, Array<{ item: Item; amountUsd: number; amountVes: number }>>();

    // Initialize all consumers with empty arrays
    props.consumers.forEach(consumer => {
        itemsByConsumer.set(consumer.id!, []);
    });

    // Find items for each consumer based on participations and calculate their share
    props.items.forEach(item => {
        const participations = props.participationsByItem.get(item.id!) || [];

        if (participations.length === 0) return;

        const splitType = item.split_type || 'shared';

        participations.forEach(participation => {
            const consumerId = participation.consumer_id;
            const currentItems = itemsByConsumer.get(consumerId) || [];

            // Check if item already exists for this consumer
            const existingItemIndex = currentItems.findIndex(i => i.item.id === item.id);

            let consumerShareUsd = 0;

            if (splitType === 'shared') {
                // Shared split: divide price equally among all participants
                consumerShareUsd = item.price_usd / participations.length;
            } else {
                // Per-unit split: multiply price by quantity
                consumerShareUsd = item.price_usd * participation.qty;
            }

            const consumerShareVes = bcvUsdRate.value
                ? consumerShareUsd * bcvUsdRate.value.value
                : 0;

            if (existingItemIndex >= 0) {
                // If item already exists, add to the existing amount
                currentItems[existingItemIndex].amountUsd += consumerShareUsd;
                currentItems[existingItemIndex].amountVes += consumerShareVes;
            } else {
                // Add new item with calculated share
                currentItems.push({
                    item,
                    amountUsd: consumerShareUsd,
                    amountVes: consumerShareVes
                });
            }

            itemsByConsumer.set(consumerId, currentItems);
        });
    });

    return itemsByConsumer;
});

const consumerTotalsArray = computed(() => {
    return props.consumers
        .map(consumer => ({
            consumer,
            totalUsd: consumerTotals.value.get(consumer.id!) || 0,
            totalVes: bcvUsdRate.value
                ? (consumerTotals.value.get(consumer.id!) || 0) * bcvUsdRate.value.value
                : 0,
            items: consumerItems.value.get(consumer.id!) || []
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
                    <span class="font-medium font-mono tabular-nums">${{ totalCostUsd.toFixed(2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-[#706f6c] dark:text-[#A1A09A]">Total Cost (VES):</span>
                    <span class="font-medium font-mono tabular-nums">{{ totalCostVes.toFixed(2) }} Bs</span>
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
                    class="rounded-md border border-[#e3e3e0] bg-[#FDFDFC] p-2 text-sm dark:border-[#3E3E3A] dark:bg-[#1C1C1A]">
                    <div class="flex items-start justify-between mb-1">
                        <span class="font-medium">{{ item.consumer.name }}</span>
                        <div class="text-right">
                            <div class="font-semibold font-mono tabular-nums">${{ item.totalUsd.toFixed(2) }}</div>
                            <div class="text-xs text-[#706f6c] dark:text-[#A1A09A] font-mono tabular-nums">
                                {{ item.totalVes.toFixed(2) }} Bs
                            </div>
                        </div>
                    </div>
                    <div v-if="item.items.length > 0" class="mt-2 space-y-1">
                        <div v-for="consumedItem in item.items" :key="consumedItem.item.id"
                            class="flex justify-between text-xs text-[#706f6c] dark:text-[#A1A09A]">
                            <span class="flex-1 pr-4">{{ consumedItem.item.name }}</span>
                            <span class="font-medium text-right min-w-[70px] font-mono tabular-nums">${{ consumedItem.amountUsd.toFixed(2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
