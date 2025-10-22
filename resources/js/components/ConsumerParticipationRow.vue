<script setup lang="ts">
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { X } from 'lucide-vue-next';

interface Consumer {
    name: string;
    tempId: string;
}

const props = withDefaults(defineProps<{
    consumer: Consumer;
    allConsumers: Consumer[];
    quantity: number;
    paidById: string;
    splitType?: 'shared' | 'per-unit';
}>(), {
    splitType: 'shared'
});

const emit = defineEmits<{
    'update:quantity': [value: number];
    'update:paidById': [value: string];
    'remove': [];
}>();

const handleQuantityChange = (event: Event) => {
    const value = Number((event.target as HTMLInputElement).value);
    emit('update:quantity', value);
};

const handlePaidByChange = (event: Event) => {
    emit('update:paidById', (event.target as HTMLSelectElement).value);
};
</script>

<template>
    <div class="space-y-2 rounded-md border border-sidebar-border/50 bg-background p-3">
        <div class="flex items-center gap-3">
            <Label class="flex-1 font-medium">
                {{ consumer.name }}
            </Label>

            <Input v-if="splitType === 'per-unit'" :value="quantity" @input="handleQuantityChange" type="number" min="1"
                step="1" placeholder="Qty" class="w-20 h-8" />

            <Button variant="ghost" size="icon" class="h-8 w-8 text-destructive hover:text-destructive"
                @click="emit('remove')" title="Remove participation">
                <X :size="16" />
            </Button>
        </div>

        <div class="flex items-center gap-2">
            <Label :for="`paidBy-${consumer.tempId}`" class="text-xs text-muted-foreground whitespace-nowrap">
                Paid by:
            </Label>
            <select :id="`paidBy-${consumer.tempId}`" :value="paidById" @change="handlePaidByChange"
                class="flex h-8 flex-1 rounded-md border border-input bg-transparent px-2 py-1 text-xs shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                <option v-for="payer in allConsumers" :key="payer.tempId" :value="payer.tempId">
                    {{ payer.name }}
                </option>
            </select>
        </div>
    </div>
</template>
