<script setup lang="ts">
import { ref, computed } from 'vue';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import CurrencyConverter from '@/components/CurrencyConverter.vue';
import ItemCard from '@/components/ItemCard.vue';
import { createItemLocal } from '@/repos/items';
import type { Item, Consumer, Participation, Rate } from '@/types/models';

const props = defineProps<{
    eventId: string;
    items: Item[];
    consumers: Consumer[];
    participationsByItem: Map<string | number, Participation[]>;
    rates: Rate[];
}>();

const emit = defineEmits<{
    'update:items': [items: Item[]];
    'update:participationsByItem': [participationsByItem: Map<string | number, Participation[]>];
}>();

const newItemName = ref('');
const newItemPriceVes = ref<number | null>(null);

const totalItems = computed(() => props.items.length);

const bcvUsdRate = computed(() => {
    return props.rates.find(r => r.source === 'BCV' && r.currency_to === 'USD');
});

const addItem = async () => {
    if (!newItemName.value.trim() || !newItemPriceVes.value || !bcvUsdRate.value) return;

    const priceUsd = newItemPriceVes.value / bcvUsdRate.value.value;
    const item = await createItemLocal({
        event_id: props.eventId,
        name: newItemName.value.trim(),
        price_usd: priceUsd,
        rate_id: bcvUsdRate.value.id
    });

    emit('update:items', [...props.items, item]);

    const newMap = new Map(props.participationsByItem);
    newMap.set(item.id!, []);
    emit('update:participationsByItem', newMap);

    newItemName.value = '';
    newItemPriceVes.value = null;
};

const updateItem = (index: number, updatedItem: Item) => {
    const updated = [...props.items];
    updated[index] = updatedItem;
    emit('update:items', updated);
};

const updateParticipations = (itemId: string | number, participations: Participation[]) => {
    const newMap = new Map(props.participationsByItem);
    newMap.set(itemId, participations);
    emit('update:participationsByItem', newMap);
};

const removeItem = (index: number, itemId: string | number) => {
    const updated = props.items.filter((_, i) => i !== index);
    emit('update:items', updated);

    const newMap = new Map(props.participationsByItem);
    newMap.delete(itemId);
    emit('update:participationsByItem', newMap);
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="text-base">Items ({{ totalItems }})</CardTitle>
        </CardHeader>
        <CardContent class="space-y-3">
            <!-- Add Item Form -->
            <div
                class="space-y-3 rounded-md border border-[#e3e3e0] bg-[#FDFDFC] p-3 dark:border-[#3E3E3A] dark:bg-[#1C1C1A]">
                <Input v-model="newItemName" placeholder="Item name" class="w-full" />
                <Input v-model.number="newItemPriceVes" type="number" step="0.01" placeholder="Price in VES"
                    class="w-full" />
                <CurrencyConverter v-if="newItemPriceVes" :ves-amount="newItemPriceVes" :rates="rates" />
                <Button @click="addItem" :disabled="!newItemName || !newItemPriceVes" class="w-full">
                    Add Item
                </Button>
            </div>

            <!-- Item List -->
            <div v-if="items.length > 0" class="space-y-2">
                <ItemCard v-for="(item, index) in items" :key="item.id" :item="item" :consumers="consumers"
                    :participations="participationsByItem.get(item.id!) || []" :bcv-usd-rate="bcvUsdRate"
                    @update:item="updateItem(index, $event)"
                    @update:participations="updateParticipations(item.id!, $event)"
                    @delete="removeItem(index, item.id!)" />
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-6 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                Add your first item
            </div>
        </CardContent>
    </Card>
</template>
