<script setup lang="ts">
import { ref } from 'vue';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Check, Trash2 } from 'lucide-vue-next';
import { updateConsumerLocal, deleteConsumerLocal } from '@/repos/consumers';
import type { Consumer } from '@/types/models';

const props = defineProps<{
    consumer: Consumer;
}>();

const emit = defineEmits<{
    'update:consumer': [consumer: Consumer];
    'delete': [];
}>();

const isEditing = ref(false);
const tempName = ref('');

const startEdit = () => {
    isEditing.value = true;
    tempName.value = props.consumer.name;
};

const save = async () => {
    if (tempName.value.trim()) {
        await updateConsumerLocal(props.consumer.id!, { name: tempName.value.trim() });
        emit('update:consumer', { ...props.consumer, name: tempName.value.trim() });
        isEditing.value = false;
    }
};

const remove = async () => {
    if (confirm('Are you sure you want to remove this consumer?')) {
        await deleteConsumerLocal(props.consumer.id!);
        emit('delete');
    }
};
</script>

<template>
    <div
        class="flex items-center gap-2 rounded-md border border-[#e3e3e0] bg-white p-3 dark:border-[#3E3E3A] dark:bg-[#161615]">
        <!-- Edit mode -->
        <div v-if="isEditing" class="flex flex-1 gap-2">
            <Input v-model="tempName" @keydown.enter="save" @keydown.esc="isEditing = false" class="flex-1" autofocus />
            <Button @click="save" size="sm">
                <Check class="h-4 w-4" />
            </Button>
        </div>

        <!-- View mode -->
        <div v-else @click="startEdit" class="flex-1 cursor-pointer">
            {{ consumer.name }}
        </div>

        <Button @click="remove" variant="ghost" size="sm" class="h-8 w-8 p-0">
            <Trash2 class="h-4 w-4 text-red-600" />
        </Button>
    </div>
</template>
