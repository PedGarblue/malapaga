import { db, withTempId } from '@/db';
import type { Item, Outbox } from '@/types/models';

export async function createItemLocal(data: Omit<Item, 'id'>) {
    const rec = withTempId<Item>({ ...data, created_at: new Date().toISOString(), updated_at: new Date().toISOString() });
    await db.items.put(rec);
    await db.outbox.add({
        id: crypto.randomUUID(),
        table: 'items',
        action: 'create',
        payload: rec,
        created_at: Date.now()
    } as Outbox);
}

export async function updateItemLocal(id: string | number, patch: Partial<Item>) {
    const now = new Date().toISOString();
    await db.items.update(id, { ...patch, updated_at: now });
    const current = await db.items.get(id);
    await db.outbox.add({
        id: crypto.randomUUID(),
        table: 'items',
        action: 'update',
        payload: current,
        created_at: Date.now()
    });
}

export async function deleteItemLocal(id: string | number) {
    await db.items.delete(id);
    await db.outbox.add({
        id: crypto.randomUUID(),
        table: 'items',
        action: 'delete',
        payload: { id },
        created_at: Date.now()
    });
}
