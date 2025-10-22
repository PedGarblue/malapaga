<script setup lang="ts">
import { ref, computed, reactive } from 'vue';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import CurrencyConverter from '@/components/CurrencyConverter.vue';
import ConsumerParticipationRow from '@/components/ConsumerParticipationRow.vue';
import { useRates } from '@/composables/useRates';
import { createEventLocal } from '@/repos/events';
import { createConsumerLocal } from '@/repos/consumers';
import { createItemLocal } from '@/repos/items';
import { createParticipationLocal } from '@/repos/participation';
import type { Event, Consumer, Item, Rate } from '@/types/models';

interface ItemParticipation {
    consumerId: string;
    qty: number;
    paidById: string;  // ID of consumer who paid for this participation
}

interface ItemWithParticipations {
    name: string;
    priceVes: number;
    tempId: string;
    participations: ItemParticipation[];
}

const props = defineProps<{
    open: boolean;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    'eventCreated': [event: Event];
}>();

// Wizard state
const currentStep = ref(1);
const isLoading = ref(false);
const error = ref<string | null>(null);

// Event data
const createdEvent = ref<Event | null>(null);

// Consumer data
const consumers = ref<Array<{ name: string; tempId: string }>>([]);
const newConsumerName = ref('');

// Item data
const items = ref<ItemWithParticipations[]>([]);
const newItemName = ref('');
const newItemPrice = ref<number | null>(null);

// Participation state
const showParticipationUI = ref(false);
const participationQuantities = reactive<Record<string, number>>({});
const participationPaidBy = reactive<Record<string, string>>({});  // consumerId -> paidByConsumerId

// Rates
const { latestRates, loadLatestRates } = useRates();

// Load rates when dialog opens
const handleOpenChange = async (value: boolean) => {
    emit('update:open', value);
    if (value) {
        await loadLatestRates();
    } else {
        // Reset wizard state when closed
        resetWizard();
    }
};

const resetWizard = () => {
    currentStep.value = 1;
    createdEvent.value = null;
    consumers.value = [];
    newConsumerName.value = '';
    items.value = [];
    newItemName.value = '';
    newItemPrice.value = null;
    showParticipationUI.value = false;
    // Clear reactive objects
    Object.keys(participationQuantities).forEach(key => delete participationQuantities[key]);
    Object.keys(participationPaidBy).forEach(key => delete participationPaidBy[key]);
    error.value = null;
};

// Step 1: Create Event
const createEvent = async () => {
    isLoading.value = true;
    error.value = null;

    try {
        const today = new Date().toISOString().split('T')[0];
        const event = await createEventLocal({
            title: 'New Event',
            date: today
        });

        createdEvent.value = event;
        currentStep.value = 2;
    } catch (e) {
        error.value = e instanceof Error ? e.message : 'Failed to create event';
        console.error('Error creating event:', e);
    } finally {
        isLoading.value = false;
    }
};

// Step 2: Add Consumers
const addConsumer = () => {
    if (newConsumerName.value.trim()) {
        consumers.value.push({
            name: newConsumerName.value.trim(),
            tempId: crypto.randomUUID()
        });
        newConsumerName.value = '';
    }
};

const removeConsumer = (tempId: string) => {
    consumers.value = consumers.value.filter(c => c.tempId !== tempId);
};

const canProceedFromConsumers = computed(() => consumers.value.length > 0);

const proceedToItems = () => {
    if (canProceedFromConsumers.value) {
        currentStep.value = 3;
    }
};

// Step 3: Add Items
const addItem = () => {
    if (newItemName.value.trim() && newItemPrice.value && newItemPrice.value > 0) {
        // Clear existing participation data
        Object.keys(participationQuantities).forEach(key => delete participationQuantities[key]);
        Object.keys(participationPaidBy).forEach(key => delete participationPaidBy[key]);

        // Initialize quantities and paidBy for all consumers (all checked by default)
        consumers.value.forEach(consumer => {
            participationQuantities[consumer.tempId] = 1; // All consumers participate by default
            participationPaidBy[consumer.tempId] = consumer.tempId; // Each pays for themselves by default
        });

        // Show participation UI
        showParticipationUI.value = true;
    }
};

const cancelParticipation = () => {
    showParticipationUI.value = false;
    // Clear reactive objects
    Object.keys(participationQuantities).forEach(key => delete participationQuantities[key]);
    Object.keys(participationPaidBy).forEach(key => delete participationPaidBy[key]);
};

const confirmItem = () => {
    // Build participations array
    const participations: ItemParticipation[] = [];

    for (const consumer of consumers.value) {
        const qty = participationQuantities[consumer.tempId] || 0;
        if (qty > 0) {
            participations.push({
                consumerId: consumer.tempId,
                qty: qty,
                paidById: participationPaidBy[consumer.tempId] || consumer.tempId
            });
        }
    }

    // Validate: at least one consumer must participate
    if (participations.length === 0) {
        error.value = 'At least one consumer must participate with qty > 0';
        return;
    }

    // Add item with participations
    items.value.push({
        name: newItemName.value.trim(),
        priceVes: newItemPrice.value!,
        tempId: crypto.randomUUID(),
        participations: participations
    });

    // Reset form
    newItemName.value = '';
    newItemPrice.value = null;
    showParticipationUI.value = false;
    // Clear reactive objects
    Object.keys(participationQuantities).forEach(key => delete participationQuantities[key]);
    Object.keys(participationPaidBy).forEach(key => delete participationPaidBy[key]);
    error.value = null;
};

const removeItem = (tempId: string) => {
    items.value = items.value.filter(i => i.tempId !== tempId);
};

const canComplete = computed(() => items.value.length > 0);

// Get the BCV USD rate for conversion
const bcvUsdRate = computed(() => {
    return latestRates.value.find(r => r.source === 'BCV' && r.currency_to === 'USD');
});

// Complete the wizard
const completeWizard = async () => {
    if (!createdEvent.value || !canComplete.value) return;

    isLoading.value = true;
    error.value = null;

    try {
        // Create all consumers and map temp IDs to actual IDs
        const consumerIdMap = new Map<string, string>();
        for (const consumer of consumers.value) {
            const created = await createConsumerLocal({
                name: consumer.name,
                event_id: createdEvent.value.id!
            });
            consumerIdMap.set(consumer.tempId, created.id as string);
        }

        // Create all items and their participations
        // Convert VES to USD using BCV rate
        if (!bcvUsdRate.value) {
            throw new Error('BCV USD rate not available. Please try again later.');
        }

        for (const item of items.value) {
            const priceUsd = item.priceVes / bcvUsdRate.value.value;
            const createdItem = await createItemLocal({
                event_id: createdEvent.value.id!,
                name: item.name,
                price_usd: priceUsd,
                rate_id: bcvUsdRate.value.id
            });

            // Create participations for this item
            for (const participation of item.participations) {
                const actualConsumerId = consumerIdMap.get(participation.consumerId);
                if (!actualConsumerId) continue;

                // Get the actual payer ID
                const actualPayerId = consumerIdMap.get(participation.paidById) || actualConsumerId;

                await createParticipationLocal({
                    item_id: createdItem.id!,
                    consumer_id: actualConsumerId,
                    qty: participation.qty,
                    paid_by_id: actualPayerId
                });
            }
        }

        // Emit success event and close dialog
        emit('eventCreated', createdEvent.value);
        emit('update:open', false);
    } catch (e) {
        error.value = e instanceof Error ? e.message : 'Failed to complete wizard';
        console.error('Error completing wizard:', e);
    } finally {
        isLoading.value = false;
    }
};

// Back navigation
const goBack = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};
</script>

<template>
    <Dialog :open="props.open" @update:open="handleOpenChange">
        <DialogContent class="max-w-2xl">
            <DialogHeader>
                <DialogTitle>Create New Event</DialogTitle>
                <DialogDescription>
                    Step {{ currentStep }} of 3:
                    {{ currentStep === 1 ? 'Create Event' : currentStep === 2 ? 'Add Consumers' : 'Add Items' }}
                </DialogDescription>
            </DialogHeader>

            <!-- Error Display -->
            <div v-if="error"
                class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200">
                {{ error }}
            </div>

            <!-- Step 1: Create Event -->
            <div v-if="currentStep === 1" class="space-y-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Create Event</CardTitle>
                        <CardDescription>
                            Click the button below to create a new event with default values.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Title:</span>
                                <span class="font-medium">New Event</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Date:</span>
                                <span class="font-medium">{{ new Date().toLocaleDateString() }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <DialogFooter>
                    <Button @click="createEvent" :disabled="isLoading">
                        {{ isLoading ? 'Creating...' : 'Create Event' }}
                    </Button>
                </DialogFooter>
            </div>

            <!-- Step 2: Add Consumers -->
            <div v-if="currentStep === 2" class="space-y-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Add Consumers</CardTitle>
                        <CardDescription>
                            Add people who will participate in this event.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Add Consumer Form -->
                        <div class="flex gap-2">
                            <div class="flex-1">
                                <Label for="consumerName" class="sr-only">Consumer Name</Label>
                                <Input id="consumerName" v-model="newConsumerName" placeholder="Enter consumer name"
                                    @keyup.enter="addConsumer" />
                            </div>
                            <Button @click="addConsumer" :disabled="!newConsumerName.trim()">
                                Add
                            </Button>
                        </div>

                        <!-- Consumer List -->
                        <div v-if="consumers.length > 0" class="space-y-2">
                            <div class="text-sm font-medium">Consumers ({{ consumers.length }}):</div>
                            <div class="space-y-1">
                                <div v-for="consumer in consumers" :key="consumer.tempId"
                                    class="flex items-center justify-between rounded-md border border-sidebar-border/70 bg-muted/50 px-3 py-2 text-sm dark:border-sidebar-border">
                                    <span>{{ consumer.name }}</span>
                                    <Button variant="ghost" size="sm" @click="removeConsumer(consumer.tempId)"
                                        class="h-6 px-2 text-xs">
                                        Remove
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-center text-sm text-muted-foreground">
                            No consumers added yet. Add at least one to continue.
                        </div>
                    </CardContent>
                </Card>

                <DialogFooter class="gap-2">
                    <Button variant="outline" @click="goBack">
                        Back
                    </Button>
                    <Button @click="proceedToItems" :disabled="!canProceedFromConsumers">
                        Continue to Items
                    </Button>
                </DialogFooter>
            </div>

            <!-- Step 3: Add Items -->
            <div v-if="currentStep === 3" class="space-y-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Add Items</CardTitle>
                        <CardDescription>
                            Add items with their prices in VES (Venezuelan Bolívares).
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Add Item Form -->
                        <div v-if="!showParticipationUI" class="space-y-3">
                            <div>
                                <Label for="itemName">Item Name</Label>
                                <Input id="itemName" v-model="newItemName" placeholder="Enter item name" />
                            </div>
                            <div>
                                <Label for="itemPrice">Price (VES)</Label>
                                <Input id="itemPrice" :model-value="newItemPrice ?? undefined"
                                    @update:model-value="(val) => newItemPrice = val ? Number(val) : null" type="number"
                                    step="0.01" min="0" placeholder="Enter price in VES" />
                            </div>

                            <!-- Currency Conversion Display -->
                            <CurrencyConverter :ves-amount="newItemPrice || 0" :rates="latestRates" />

                            <Button @click="addItem"
                                :disabled="!newItemName.trim() || !newItemPrice || newItemPrice <= 0" class="w-full">
                                Add Item
                            </Button>
                        </div>

                        <!-- Participation Assignment UI -->
                        <div v-if="showParticipationUI"
                            class="space-y-4 rounded-lg border border-sidebar-border/70 bg-muted/30 p-4 dark:border-sidebar-border">
                            <div>
                                <h4 class="font-semibold text-sm mb-1">{{ newItemName }}</h4>
                                <p class="text-xs text-muted-foreground">{{ newItemPrice?.toLocaleString() }} VES</p>
                            </div>

                            <div class="space-y-3">
                                <Label class="text-sm font-medium">Participants:</Label>
                                <div class="space-y-2">
                                    <ConsumerParticipationRow
                                        v-for="consumer in consumers.filter(c => (participationQuantities[c.tempId] || 0) > 0)"
                                        :key="consumer.tempId" :consumer="consumer" :all-consumers="consumers"
                                        :quantity="participationQuantities[consumer.tempId] || 0"
                                        :paid-by-id="participationPaidBy[consumer.tempId] || consumer.tempId"
                                        @update:quantity="(qty) => participationQuantities[consumer.tempId] = qty"
                                        @update:paid-by-id="(id) => participationPaidBy[consumer.tempId] = id"
                                        @remove="participationQuantities[consumer.tempId] = 0" />
                                </div>

                                <!-- Add removed consumers back -->
                                <div v-if="consumers.filter(c => (participationQuantities[c.tempId] || 0) === 0).length > 0"
                                    class="pt-2">
                                    <Label class="text-xs text-muted-foreground mb-2 block">Add participant:</Label>
                                    <div class="flex flex-wrap gap-2">
                                        <Button
                                            v-for="consumer in consumers.filter(c => (participationQuantities[c.tempId] || 0) === 0)"
                                            :key="consumer.tempId" variant="outline" size="sm" class="h-7 text-xs"
                                            @click="participationQuantities[consumer.tempId] = 1; participationPaidBy[consumer.tempId] = consumer.tempId">
                                            + {{ consumer.name }}
                                        </Button>
                                    </div>
                                </div>
                            </div>

                            <div class="flex gap-2">
                                <Button variant="outline" @click="cancelParticipation" class="flex-1">
                                    Cancel
                                </Button>
                                <Button @click="confirmItem" class="flex-1">
                                    Confirm Item
                                </Button>
                            </div>
                        </div>

                        <!-- Items List -->
                        <div v-if="items.length > 0" class="space-y-2">
                            <div class="text-sm font-medium">Items ({{ items.length }}):</div>
                            <div class="space-y-1">
                                <div v-for="item in items" :key="item.tempId"
                                    class="rounded-md border border-sidebar-border/70 bg-muted/50 px-3 py-2 text-sm dark:border-sidebar-border">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex-1">
                                            <div class="font-medium">{{ item.name }}</div>
                                            <div class="text-xs text-muted-foreground">
                                                {{ item.priceVes.toLocaleString() }} VES
                                            </div>
                                        </div>
                                        <Button variant="ghost" size="sm" @click="removeItem(item.tempId)"
                                            class="h-6 px-2 text-xs">
                                            Remove
                                        </Button>
                                    </div>
                                    <!-- Participation Summary -->
                                    <div
                                        class="text-xs text-muted-foreground space-y-1 pt-2 border-t border-sidebar-border/50">
                                        <div v-for="participation in item.participations"
                                            :key="participation.consumerId" class="flex items-center justify-between">
                                            <span>
                                                {{consumers.find(c => c.tempId === participation.consumerId)?.name}}
                                                <span class="ml-1 text-muted-foreground/70">
                                                    (paid by: {{consumers.find(c => c.tempId ===
                                                        participation.paidById)?.name}})
                                                </span>
                                            </span>
                                            <span>qty: {{ participation.qty }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-center text-sm text-muted-foreground">
                            No items added yet. Add at least one to complete.
                        </div>
                    </CardContent>
                </Card>

                <DialogFooter class="gap-2">
                    <Button variant="outline" @click="goBack">
                        Back
                    </Button>
                    <Button @click="completeWizard" :disabled="!canComplete || isLoading">
                        {{ isLoading ? 'Creating...' : 'Complete' }}
                    </Button>
                </DialogFooter>
            </div>
        </DialogContent>
    </Dialog>
</template>
