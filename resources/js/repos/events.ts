import { db, withTempId } from '@/db';
import type { Event, Outbox } from '@/types/models';

export async function createEventLocal(data: Omit<Event, 'id'>) {
    const rec = withTempId<Event>({ ...data, created_at: new Date().toISOString(), updated_at: new Date().toISOString() });
    await db.events.put(rec);
    await db.outbox.add({
        id: crypto.randomUUID(),
        table: 'events',
        action: 'create',
        payload: rec,
        created_at: Date.now()
    } as Outbox);
    return rec;
}

export async function updateEventLocal(id: string | number, patch: Partial<Event>) {
    const now = new Date().toISOString();
    await db.events.update(id, { ...patch, updated_at: now });
    const current = await db.events.get(id);
    await db.outbox.add({
        id: crypto.randomUUID(),
        table: 'events',
        action: 'update',
        payload: current,
        created_at: Date.now()
    });
}

export async function deleteEventLocal(id: string | number) {
    await db.events.delete(id);
    await db.outbox.add({
        id: crypto.randomUUID(),
        table: 'events',
        action: 'delete',
        payload: { id },
        created_at: Date.now()
    });
}
