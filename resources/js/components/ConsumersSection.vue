<script setup lang="ts">
import { ref, computed } from 'vue';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import ConsumerListItem from '@/components/ConsumerListItem.vue';
import { createConsumerLocal } from '@/repos/consumers';
import type { Consumer } from '@/types/models';

const props = defineProps<{
    consumers: Consumer[];
    eventId: string | number;
}>();

const emit = defineEmits<{
    'update:consumers': [consumers: Consumer[]];
}>();

const newConsumerName = ref('');

const totalConsumers = computed(() => props.consumers.length);

const addConsumer = async () => {
    if (newConsumerName.value.trim()) {
        const consumer = await createConsumerLocal({
            name: newConsumerName.value.trim(),
            event_id: props.eventId
        });
        emit('update:consumers', [...props.consumers, consumer]);
        newConsumerName.value = '';
    }
};

const updateConsumer = (index: number, updatedConsumer: Consumer) => {
    const updated = [...props.consumers];
    updated[index] = updatedConsumer;
    emit('update:consumers', updated);
};

const removeConsumer = (index: number) => {
    const updated = props.consumers.filter((_, i) => i !== index);
    emit('update:consumers', updated);
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="text-base">Consumers ({{ totalConsumers }})</CardTitle>
        </CardHeader>
        <CardContent class="space-y-3">
            <!-- Add Consumer Form -->
            <div class="flex gap-2">
                <Input v-model="newConsumerName" placeholder="Consumer name" @keydown.enter="addConsumer"
                    class="flex-1" />
                <Button @click="addConsumer" size="sm">Add</Button>
            </div>

            <!-- Consumer List -->
            <div v-if="consumers.length > 0" class="space-y-2">
                <ConsumerListItem v-for="(consumer, index) in consumers" :key="consumer.id" :consumer="consumer"
                    @update:consumer="updateConsumer(index, $event)" @delete="removeConsumer(index)" />
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-6 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                Add your first consumer
            </div>
        </CardContent>
    </Card>
</template>
