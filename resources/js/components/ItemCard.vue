<script setup lang="ts">
import { ref, computed, inject, type Ref, type ComputedRef } from 'vue';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { ChevronDown, ChevronUp, Trash2 } from 'lucide-vue-next';
import { updateItemLocal, deleteItemLocal } from '@/repos/items';
import { createParticipationLocal, updateParticipationLocal, deleteParticipationLocal } from '@/repos/participation';
import type { Item, Consumer, Participation, Rate } from '@/types/models';

const props = defineProps<{
    item: Item;
}>();

const emit = defineEmits<{
    'update:item': [item: Item];
    'delete': [];
}>();

// Inject shared data
const consumers = inject<Ref<Consumer[]>>('eventConsumers')!;
const bcvUsdRate = inject<ComputedRef<Rate | undefined>>('bcvUsdRate')!;
const participationsByItem = inject<Ref<Map<string | number, Participation[]>>>('participationsByItem')!;
const updateParticipationsByItem = inject<(map: Map<string | number, Participation[]>) => void>('updateParticipationsByItem')!;

// Local computed for this item's participations
const participations = computed(() => participationsByItem.value.get(props.item.id!) || []);

const isExpanded = ref(false);
const isEditingName = ref(false);
const isEditingPrice = ref(false);
const tempName = ref('');
const tempPrice = ref<number | null>(null);

const participationQty = ref<Record<string, number>>({});
const participationPaidBy = ref<Record<string, string>>({});

const vesPrice = computed(() => {
    if (!bcvUsdRate.value) return 'N/A';
    return (props.item.price_usd * bcvUsdRate.value.value).toFixed(2);
});

// Helper to update participations
const updateParticipations = (updated: Participation[]) => {
    const newMap = new Map(participationsByItem.value);
    newMap.set(props.item.id!, updated);
    updateParticipationsByItem(newMap);
};

const startEditName = () => {
    isEditingName.value = true;
    tempName.value = props.item.name;
};

const saveName = async () => {
    if (tempName.value.trim()) {
        await updateItemLocal(props.item.id!, { name: tempName.value.trim() });
        emit('update:item', { ...props.item, name: tempName.value.trim() });
        isEditingName.value = false;
    }
};

const startEditPrice = () => {
    isEditingPrice.value = true;
    tempPrice.value = bcvUsdRate.value ? props.item.price_usd * bcvUsdRate.value.value : null;
};

const savePrice = async () => {
    if (tempPrice.value && bcvUsdRate.value) {
        const priceUsd = tempPrice.value / bcvUsdRate.value.value;
        await updateItemLocal(props.item.id!, {
            price_usd: priceUsd,
            rate_id: bcvUsdRate.value.id
        });
        emit('update:item', {
            ...props.item,
            price_usd: priceUsd,
            rate_id: bcvUsdRate.value.id
        });
        isEditingPrice.value = false;
    }
};

const remove = async () => {
    if (confirm('Are you sure you want to remove this item?')) {
        // Delete all participations first
        for (const p of participations.value) {
            await deleteParticipationLocal(p.id!);
        }
        await deleteItemLocal(props.item.id!);
        emit('delete');
    }
};

const toggleExpand = () => {
    isExpanded.value = !isExpanded.value;

    // Initialize participation state
    if (isExpanded.value) {
        participations.value.forEach(p => {
            const key = `${props.item.id}-${p.consumer_id}`;
            participationQty.value[key] = p.qty;
            participationPaidBy.value[key] = String(p.paid_by_id);
        });
    }
};

const isConsumerInItem = (consumerId: string | number): boolean => {
    return participations.value.some(p => p.consumer_id === consumerId);
};

const toggleConsumerParticipation = async (consumer: Consumer) => {
    const existing = participations.value.find(p => p.consumer_id === consumer.id);

    if (existing) {
        // Remove participation
        await deleteParticipationLocal(existing.id!);
        const updated = participations.value.filter(p => p.id !== existing.id);
        updateParticipations(updated);

        const key = `${props.item.id}-${consumer.id}`;
        delete participationQty.value[key];
        delete participationPaidBy.value[key];
    } else {
        // Add participation
        const participation = await createParticipationLocal({
            item_id: props.item.id!,
            consumer_id: consumer.id!,
            qty: 1,
            paid_by_id: consumer.id!
        });
        updateParticipations([...participations.value, participation]);

        const key = `${props.item.id}-${consumer.id}`;
        participationQty.value[key] = 1;
        participationPaidBy.value[key] = String(consumer.id);
    }
};

const updateQty = async (consumerId: string | number, qty: number) => {
    const participation = participations.value.find(p => p.consumer_id === consumerId);

    if (participation && qty > 0) {
        await updateParticipationLocal(participation.id!, { qty });
        const updated = participations.value.map(p =>
            p.id === participation.id ? { ...p, qty } : p
        );
        updateParticipations(updated);

        const key = `${props.item.id}-${consumerId}`;
        participationQty.value[key] = qty;
    }
};

const updatePayer = async (consumerId: string | number, paidById: string | number) => {
    const participation = participations.value.find(p => p.consumer_id === consumerId);

    if (participation) {
        await updateParticipationLocal(participation.id!, { paid_by_id: paidById });
        const updated = participations.value.map(p =>
            p.id === participation.id ? { ...p, paid_by_id: paidById } : p
        );
        updateParticipations(updated);

        const key = `${props.item.id}-${consumerId}`;
        participationPaidBy.value[key] = String(paidById);
    }
};

const getConsumerName = (consumerId: string | number): string => {
    return consumers.value.find(c => c.id === consumerId)?.name || 'Unknown';
};
</script>

<template>
    <div class="rounded-md border border-[#e3e3e0] bg-white dark:border-[#3E3E3A] dark:bg-[#161615]">
        <!-- Item Header -->
        <div class="p-3">
            <div class="flex items-start justify-between gap-2">
                <div class="flex-1 space-y-2">
                    <!-- Item Name -->
                    <div v-if="isEditingName">
                        <Input v-model="tempName" @blur="saveName(); isEditingName = false"
                            @keydown.enter="saveName(); isEditingName = false" @keydown.esc="isEditingName = false"
                            class="text-sm" autofocus />
                    </div>
                    <div v-else @click="startEditName" class="cursor-pointer font-medium">
                        {{ item.name }}
                    </div>

                    <!-- Prices -->
                    <div class="space-y-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                        <div v-if="isEditingPrice" class="flex gap-2">
                            <Input :model-value="tempPrice ?? undefined"
                                @update:model-value="tempPrice = $event ? Number($event) : null" type="number"
                                step="0.01" @blur="savePrice(); isEditingPrice = false"
                                @keydown.enter="savePrice(); isEditingPrice = false"
                                @keydown.esc="isEditingPrice = false" placeholder="VES" class="text-xs" />
                        </div>
                        <div v-else @click="startEditPrice" class="cursor-pointer">
                            <div>VES: {{ vesPrice }}</div>
                            <div>USD: ${{ item.price_usd?.toFixed(2) ?? '0.00' }}</div>
                        </div>
                    </div>

                    <!-- Participation chips -->
                    <div v-if="participations.length" class="flex flex-wrap gap-1">
                        <span v-for="p in participations" :key="p.id"
                            class="inline-block rounded-full bg-[#f3f3f1] px-2 py-0.5 text-xs text-[#1b1b18] dark:bg-[#2C2C2A] dark:text-[#EDEDEC]">
                            {{ getConsumerName(p.consumer_id) }} ({{ p.qty }})
                        </span>
                    </div>
                </div>

                <div class="flex gap-1">
                    <Button @click="toggleExpand" variant="ghost" size="sm" class="h-8 w-8 p-0">
                        <ChevronDown v-if="!isExpanded" class="h-4 w-4" />
                        <ChevronUp v-else class="h-4 w-4" />
                    </Button>
                    <Button @click="remove" variant="ghost" size="sm" class="h-8 w-8 p-0">
                        <Trash2 class="h-4 w-4 text-red-600" />
                    </Button>
                </div>
            </div>
        </div>

        <!-- Participation Controls (Expanded) -->
        <div v-if="isExpanded"
            class="border-t border-[#e3e3e0] bg-[#FDFDFC] p-3 dark:border-[#3E3E3A] dark:bg-[#1C1C1A]">
            <div class="text-xs font-medium mb-2 text-[#706f6c] dark:text-[#A1A09A]">
                Assign Consumers
            </div>

            <div v-if="consumers.length > 0" class="space-y-2">
                <div v-for="consumer in consumers" :key="consumer.id" class="space-y-2">
                    <!-- Consumer checkbox -->
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" :checked="isConsumerInItem(consumer.id!)"
                            @change="toggleConsumerParticipation(consumer)"
                            class="h-4 w-4 rounded border-[#e3e3e0] dark:border-[#3E3E3A]" />
                        <span class="text-sm">{{ consumer.name }}</span>
                    </label>

                    <!-- Participation details (if checked) -->
                    <div v-if="isConsumerInItem(consumer.id!)" class="ml-6 flex gap-2">
                        <div class="flex-1">
                            <Input :model-value="participationQty[`${item.id}-${consumer.id}`] || 1"
                                @update:model-value="updateQty(consumer.id!, Number($event))" type="number" min="1"
                                placeholder="Qty" class="text-xs" />
                        </div>
                        <div class="flex-1">
                            <select :value="participationPaidBy[`${item.id}-${consumer.id}`] || consumer.id"
                                @change="updatePayer(consumer.id!, ($event.target as HTMLSelectElement).value)"
                                class="w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-xs dark:border-[#3E3E3A] dark:bg-[#161615]">
                                <option v-for="c in consumers" :key="c.id" :value="c.id">
                                    Paid by {{ c.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="text-xs text-center py-4 text-[#706f6c] dark:text-[#A1A09A]">
                Add consumers first
            </div>
        </div>
    </div>
</template>
