<script setup lang="ts">
import { ref, onMounted, provide, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import EventInfoSection from '@/components/EventInfoSection.vue';
import ConsumersSection from '@/components/ConsumersSection.vue';
import ItemsSection from '@/components/ItemsSection.vue';
import SummarySection from '@/components/SummarySection.vue';
import { fetchEventLocal } from '@/repos/events';
import { fetchConsumersByEventLocal } from '@/repos/consumers';
import { fetchItemsByEventLocal } from '@/repos/items';
import { fetchParticipationsByItemLocal } from '@/repos/participation';
import { useRates } from '@/composables/useRates';
import type { Event, Consumer, Item, Participation } from '@/types/models';

const props = defineProps<{
    eventId: string;
}>();

// State
const event = ref<Event | null>(null);
const eventConsumers = ref<Consumer[]>([]);
const items = ref<Item[]>([]);
const participationsByItem = ref<Map<string | number, Participation[]>>(new Map());
const isLoading = ref(true);
const error = ref<string | null>(null);

// Rates
const { latestRates, loadLatestRates } = useRates();

// Computed for BCV USD rate
const bcvUsdRate = computed(() => {
    return latestRates.value.find(r => r.source === 'BCV' && r.currency_to === 'USD');
});

// Provide shared data to child components
provide('eventConsumers', eventConsumers);
provide('rates', latestRates);
provide('bcvUsdRate', bcvUsdRate);
provide('participationsByItem', participationsByItem);

// Provide update function for participations
provide('updateParticipationsByItem', (updatedMap: Map<string | number, Participation[]>) => {
    participationsByItem.value = updatedMap;
});

// Load data
const loadEventData = async () => {
    isLoading.value = true;
    error.value = null;

    try {
        // Load event
        const loadedEvent = await fetchEventLocal(props.eventId);
        if (!loadedEvent) {
            error.value = 'Event not found';
            return;
        }
        event.value = loadedEvent;

        // Load items for this event
        const loadedItems = await fetchItemsByEventLocal(props.eventId);
        items.value = loadedItems;

        // Load consumers for this event
        const loadedConsumers = await fetchConsumersByEventLocal(props.eventId);
        eventConsumers.value = loadedConsumers;

        // Load participations for each item
        for (const item of loadedItems) {
            const participations = await fetchParticipationsByItemLocal(item.id!);
            participationsByItem.value.set(item.id!, participations);
        }

        // Load rates
        await loadLatestRates();
    } catch (e) {
        error.value = e instanceof Error ? e.message : 'Failed to load event data';
        console.error('Error loading event:', e);
    } finally {
        isLoading.value = false;
    }
};

// Event handlers
const handleEventUpdate = (updatedEvent: Event) => {
    event.value = updatedEvent;
};

const handleConsumersUpdate = (updatedConsumers: Consumer[]) => {
    eventConsumers.value = updatedConsumers;
};

const handleItemsUpdate = (updatedItems: Item[]) => {
    items.value = updatedItems;
};

const handleParticipationsUpdate = (updatedMap: Map<string | number, Participation[]>) => {
    participationsByItem.value = updatedMap;
};

onMounted(() => {
    loadEventData();
});
</script>

<template>

    <Head :title="event?.title || 'Event'" />

    <div class="min-h-screen bg-[#FDFDFC] dark:bg-[#0a0a0a]">
        <!-- Loading State -->
        <div v-if="isLoading" class="flex min-h-screen items-center justify-center">
            <div class="text-[#706f6c] dark:text-[#A1A09A]">Loading event...</div>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="flex min-h-screen items-center justify-center p-6">
            <Card class="w-full max-w-md">
                <CardHeader>
                    <CardTitle class="text-red-600">Error</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">{{ error }}</p>
                    <Button @click="router.visit('/')" class="mt-4">Go Home</Button>
                </CardContent>
            </Card>
        </div>

        <!-- Event Builder -->
        <div v-else class="mx-auto max-w-3xl p-4 pb-20 space-y-4">
            <EventInfoSection v-if="event" :event="event" @update:event="handleEventUpdate" />

            <ConsumersSection :consumers="eventConsumers" :event-id="eventId"
                @update:consumers="handleConsumersUpdate" />

            <ItemsSection :event-id="eventId" :items="items" @update:items="handleItemsUpdate"
                @update:participations-by-item="handleParticipationsUpdate" />

            <SummarySection :consumers="eventConsumers" :items="items" :rates="latestRates"
                :participations-by-item="participationsByItem" />
        </div>
    </div>
</template>
