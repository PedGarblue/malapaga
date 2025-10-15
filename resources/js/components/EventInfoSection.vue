<script setup lang="ts">
import { ref } from 'vue';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Check } from 'lucide-vue-next';
import { updateEventLocal } from '@/repos/events';
import type { Event } from '@/types/models';

const props = defineProps<{
    event: Event;
}>();

const emit = defineEmits<{
    'update:event': [event: Event];
}>();

const editingTitle = ref(false);
const editingDate = ref(false);
const tempTitle = ref('');
const tempDate = ref('');

const startEditTitle = () => {
    editingTitle.value = true;
    tempTitle.value = props.event.title || '';
};

const saveTitle = async () => {
    if (tempTitle.value.trim()) {
        await updateEventLocal(props.event.id!, { title: tempTitle.value.trim() });
        emit('update:event', { ...props.event, title: tempTitle.value.trim() });
        editingTitle.value = false;
    }
};

const startEditDate = () => {
    editingDate.value = true;
    tempDate.value = props.event.date || '';
};

const saveDate = async () => {
    await updateEventLocal(props.event.id!, { date: tempDate.value });
    emit('update:event', { ...props.event, date: tempDate.value });
    editingDate.value = false;
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="text-base">Event Info</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
            <!-- Title -->
            <div>
                <label class="text-xs text-[#706f6c] dark:text-[#A1A09A]">Title</label>
                <div v-if="editingTitle" class="flex gap-2 mt-1">
                    <Input v-model="tempTitle" @keydown.enter="saveTitle" @keydown.esc="editingTitle = false"
                        class="flex-1" autofocus />
                    <Button @click="saveTitle" size="sm">
                        <Check class="h-4 w-4" />
                    </Button>
                </div>
                <div v-else @click="startEditTitle"
                    class="mt-1 cursor-pointer rounded-md border border-transparent px-3 py-2 hover:border-[#e3e3e0] dark:hover:border-[#3E3E3A]">
                    {{ event.title || 'Untitled Event' }}
                </div>
            </div>

            <!-- Date -->
            <div>
                <label class="text-xs text-[#706f6c] dark:text-[#A1A09A]">Date</label>
                <div v-if="editingDate" class="flex gap-2 mt-1">
                    <Input v-model="tempDate" type="date" @keydown.enter="saveDate" @keydown.esc="editingDate = false"
                        class="flex-1" autofocus />
                    <Button @click="saveDate" size="sm">
                        <Check class="h-4 w-4" />
                    </Button>
                </div>
                <div v-else @click="startEditDate"
                    class="mt-1 cursor-pointer rounded-md border border-transparent px-3 py-2 hover:border-[#e3e3e0] dark:hover:border-[#3E3E3A]">
                    {{ event.date ? new Date(event.date).toLocaleDateString() : 'No date set' }}
                </div>
            </div>
        </CardContent>
    </Card>
</template>
