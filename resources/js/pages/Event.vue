<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import EventInfoSection from '@/components/EventInfoSection.vue';
import ConsumersSection from '@/components/ConsumersSection.vue';
import ItemsSection from '@/components/ItemsSection.vue';
import SummarySection from '@/components/SummarySection.vue';
import { fetchEventLocal } from '@/repos/events';
import { fetchConsumersLocal } from '@/repos/consumers';
import { fetchItemsByEventLocal } from '@/repos/items';
import { fetchParticipationsByItemLocal } from '@/repos/participation';
import { useRates } from '@/composables/useRates';
import type { Event, Consumer, Item, Participation } from '@/types/models';

const props = defineProps<{
    eventId: string;
}>();

// State
const event = ref<Event | null>(null);
const allConsumers = ref<Consumer[]>([]);
const eventConsumers = ref<Consumer[]>([]);
const items = ref<Item[]>([]);
const participationsByItem = ref<Map<string | number, Participation[]>>(new Map());
const isLoading = ref(true);
const error = ref<string | null>(null);

// Rates
const { latestRates, loadLatestRates } = useRates();

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

        // Load all consumers
        const loadedConsumers = await fetchConsumersLocal();
        allConsumers.value = loadedConsumers;

        // Load participations for each item and determine event consumers
        const consumerIds = new Set<string | number>();
        for (const item of loadedItems) {
            const participations = await fetchParticipationsByItemLocal(item.id!);
            participationsByItem.value.set(item.id!, participations);
            participations.forEach(p => consumerIds.add(p.consumer_id));
        }

        // Filter consumers that are part of this event
        eventConsumers.value = loadedConsumers.filter(c => consumerIds.has(c.id!));

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
    // Also update allConsumers to keep them in sync
    const consumerIds = new Set(updatedConsumers.map(c => c.id));
    const existingIds = new Set(allConsumers.value.map(c => c.id));

    // Add new consumers to allConsumers
    updatedConsumers.forEach(consumer => {
        if (!existingIds.has(consumer.id)) {
            allConsumers.value.push(consumer);
        }
    });

    // Update existing consumers in allConsumers
    allConsumers.value = allConsumers.value.map(consumer => {
        const updated = updatedConsumers.find(c => c.id === consumer.id);
        return updated || consumer;
    });
};

const handleItemsUpdate = (updatedItems: Item[]) => {
    items.value = updatedItems;
};

const handleParticipationsUpdate = (updatedMap: Map<string | number, Participation[]>) => {
    participationsByItem.value = updatedMap;

    // Update event consumers based on participations
    const consumerIds = new Set<string | number>();
    updatedMap.forEach(participations => {
        participations.forEach(p => consumerIds.add(p.consumer_id));
    });
    eventConsumers.value = allConsumers.value.filter(c => consumerIds.has(c.id!));
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

            <ConsumersSection :consumers="eventConsumers" @update:consumers="handleConsumersUpdate" />

            <ItemsSection :event-id="eventId" :items="items" :consumers="eventConsumers"
                :participations-by-item="participationsByItem" :rates="latestRates" @update:items="handleItemsUpdate"
                @update:participations-by-item="handleParticipationsUpdate" />

            <SummarySection :consumers="eventConsumers" :items="items" :rates="latestRates"
                :participations-by-item="participationsByItem" />
        </div>
    </div>
</template>
