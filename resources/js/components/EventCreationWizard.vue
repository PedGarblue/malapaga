<script setup lang="ts">
import { ref, computed } from 'vue';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import CurrencyConverter from '@/components/CurrencyConverter.vue';
import { useRates } from '@/composables/useRates';
import { createEventLocal } from '@/repos/events';
import { createConsumerLocal } from '@/repos/consumers';
import { createItemLocal } from '@/repos/items';
import type { Event, Consumer, Item, Rate } from '@/types/models';

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
const items = ref<Array<{ name: string; priceVes: number; tempId: string }>>([]);
const newItemName = ref('');
const newItemPrice = ref<number | null>(null);

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
        items.value.push({
            name: newItemName.value.trim(),
            priceVes: newItemPrice.value,
            tempId: crypto.randomUUID()
        });
        newItemName.value = '';
        newItemPrice.value = null;
    }
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
        // Create all consumers
        const createdConsumers: Consumer[] = [];
        for (const consumer of consumers.value) {
            const created = await createConsumerLocal({ name: consumer.name });
            createdConsumers.push(created);
        }

        // Create all items
        // Convert VES to USD using BCV rate
        if (!bcvUsdRate.value) {
            throw new Error('BCV USD rate not available. Please try again later.');
        }

        for (const item of items.value) {
            const priceUsd = item.priceVes / bcvUsdRate.value.value;
            await createItemLocal({
                event_id: createdEvent.value.id!,
                name: item.name,
                price_usd: priceUsd,
                rate_id: bcvUsdRate.value.id
            });
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
                        <div class="space-y-3">
                            <div>
                                <Label for="itemName">Item Name</Label>
                                <Input id="itemName" v-model="newItemName" placeholder="Enter item name" />
                            </div>
                            <div>
                                <Label for="itemPrice">Price (VES)</Label>
                                <Input id="itemPrice" v-model.number="newItemPrice" type="number" step="0.01" min="0"
                                    placeholder="Enter price in VES" />
                            </div>

                            <!-- Currency Conversion Display -->
                            <CurrencyConverter :ves-amount="newItemPrice || 0" :rates="latestRates" />

                            <Button @click="addItem"
                                :disabled="!newItemName.trim() || !newItemPrice || newItemPrice <= 0" class="w-full">
                                Add Item
                            </Button>
                        </div>

                        <!-- Items List -->
                        <div v-if="items.length > 0" class="space-y-2">
                            <div class="text-sm font-medium">Items ({{ items.length }}):</div>
                            <div class="space-y-1">
                                <div v-for="item in items" :key="item.tempId"
                                    class="flex items-center justify-between rounded-md border border-sidebar-border/70 bg-muted/50 px-3 py-2 text-sm dark:border-sidebar-border">
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
