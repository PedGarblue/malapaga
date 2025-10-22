<script setup lang="ts">
import { ref, computed, inject, type Ref, type ComputedRef } from 'vue';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import CurrencyConverter from '@/components/CurrencyConverter.vue';
import ItemCard from '@/components/ItemCard.vue';
import { createItemLocal } from '@/repos/items';
import { createParticipationLocal } from '@/repos/participation';
import type { Item, Consumer, Participation, Rate } from '@/types/models';

const props = defineProps<{
    eventId: string;
    items: Item[];
}>();

const emit = defineEmits<{
    'update:items': [items: Item[]];
    'update:participationsByItem': [participationsByItem: Map<string | number, Participation[]>];
}>();

// Inject shared data
const rates = inject<Ref<Rate[]>>('rates')!;
const bcvUsdRate = inject<ComputedRef<Rate | undefined>>('bcvUsdRate')!;
const participationsByItem = inject<Ref<Map<string | number, Participation[]>>>('participationsByItem')!;
const eventConsumers = inject<Ref<Consumer[]>>('eventConsumers')!;

const newItemName = ref('');
const newItemPriceVes = ref<number | null>(null);
const newItemSplitType = ref<'shared' | 'per-unit'>('shared');
const newIsPerUnit = ref(false);

const totalItems = computed(() => props.items.length);

const addItem = async () => {
    if (!newItemName.value.trim() || !newItemPriceVes.value || !bcvUsdRate.value) return;

    newItemSplitType.value = newIsPerUnit.value ? 'per-unit' : 'shared';

    const priceUsd = newItemPriceVes.value / bcvUsdRate.value.value;
    const item = await createItemLocal({
        event_id: props.eventId,
        name: newItemName.value.trim(),
        price_usd: priceUsd,
        rate_id: bcvUsdRate.value.id,
        split_type: newItemSplitType.value
    });

    emit('update:items', [...props.items, item]);

    const newMap = new Map(participationsByItem.value);

    for (const consumer of eventConsumers.value) {
        const participation = await createParticipationLocal({
            item_id: item.id!,
            consumer_id: consumer.id!,
            qty: 1,
            paid_by_id: consumer.id!
        });
        newMap.set(item.id!, [...(newMap.get(item.id!) || []), participation]);
    }

    emit('update:participationsByItem', newMap);

    newItemName.value = '';
    newItemPriceVes.value = null;
    newItemSplitType.value = 'shared';
};

const updateItem = (index: number, updatedItem: Item) => {
    const updated = [...props.items];
    updated[index] = updatedItem;
    emit('update:items', updated);
};

const removeItem = (index: number, itemId: string | number) => {
    const updated = props.items.filter((_, i) => i !== index);
    emit('update:items', updated);

    const newMap = new Map(participationsByItem.value);
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
                <Input :model-value="newItemPriceVes ?? undefined"
                    @update:model-value="(val) => newItemPriceVes = val ? Number(val) : null" type="number" step="0.01"
                    placeholder="Price in VES" class="w-full" />
                <CurrencyConverter v-if="newItemPriceVes" :ves-amount="newItemPriceVes ?? 0" :rates="rates" />

                <!-- Split Type Toggle -->
                <div class="flex items-center justify-between">
                    <div class="space-y-0.5">
                        <Label class="text-sm font-medium">Split Type</Label>
                        <div class="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                            {{ newItemSplitType === 'shared' ? 'Shared equally' : 'Per-unit split' }}
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs"
                            :class="newItemSplitType === 'shared' ? 'font-medium' : 'text-[#706f6c] dark:text-[#A1A09A]'">Shared</span>
                        <Switch :model-value="newIsPerUnit"
                            @update:model-value="(val: boolean) => newIsPerUnit = val" />
                        <span class="text-xs"
                            :class="newItemSplitType === 'per-unit' ? 'font-medium' : 'text-[#706f6c] dark:text-[#A1A09A]'">Per-Unit</span>
                    </div>
                </div>

                <Button @click="addItem" :disabled="!newItemName || !newItemPriceVes" class="w-full">
                    Add Item
                </Button>
            </div>

            <!-- Item List -->
            <div v-if="items.length > 0" class="space-y-2">
                <ItemCard v-for="(item, index) in items" :key="item.id" :item="item"
                    @update:item="updateItem(index, $event)" @delete="removeItem(index, item.id!)" />
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-6 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                Add your first item
            </div>
        </CardContent>
    </Card>
</template>
