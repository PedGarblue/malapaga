import { db, withTempId } from '@/db';
import type { Item, Outbox } from '@/types/models';

export async function fetchItemsLocal(): Promise<Item[]> {
    return await db.items.toArray();
}

export async function fetchItemsByEventLocal(eventId: string | number): Promise<Item[]> {
    return await db.items.where('event_id').equals(eventId).toArray();
}

export async function fetchItemLocal(id: string | number): Promise<Item | undefined> {
    return await db.items.get(id);
}

export async function createItemLocal(data: Omit<Item, 'id'>): Promise<Item> {
    const rec = withTempId<Item>({
        ...data,
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString()
    });
    await db.items.put(rec);
    await db.outbox.add({
        id: crypto.randomUUID(),
        table: 'items',
        action: 'create',
        payload: rec,
        created_at: Date.now()
    } as Outbox);
    return rec;
}

export async function updateItemLocal(id: string | number, patch: Partial<Item>): Promise<void> {
    const now = new Date().toISOString();
    await db.items.update(id, { ...patch, updated_at: now });
    const current = await db.items.get(id);
    await db.outbox.add({
        id: crypto.randomUUID(),
        table: 'items',
        action: 'update',
        payload: current,
        created_at: Date.now()
    } as Outbox);
}

export async function deleteItemLocal(id: string | number): Promise<void> {
    await db.items.delete(id);
    await db.outbox.add({
        id: crypto.randomUUID(),
        table: 'items',
        action: 'delete',
        payload: { id },
        created_at: Date.now()
    } as Outbox);
}
