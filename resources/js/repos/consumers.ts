import { db, withTempId } from '@/db';
import type { Consumer, Outbox } from '@/types/models';

export async function fetchConsumersLocal(): Promise<Consumer[]> {
    return await db.consumers.toArray();
}

export async function fetchConsumerLocal(id: string | number): Promise<Consumer | undefined> {
    return await db.consumers.get(id);
}

export async function fetchConsumersByEventLocal(eventId: string | number): Promise<Consumer[]> {
    return await db.consumers.where('event_id').equals(eventId).toArray();
}

export async function createConsumerLocal(data: Omit<Consumer, 'id'>): Promise<Consumer> {
    const rec = withTempId<Consumer>({
        ...data,
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString()
    });
    await db.consumers.put(rec);
    await db.outbox.add({
        id: crypto.randomUUID(),
        table: 'consumers',
        action: 'create',
        payload: rec,
        created_at: Date.now()
    } as Outbox);
    return rec;
}

export async function updateConsumerLocal(id: string | number, patch: Partial<Consumer>): Promise<void> {
    const now = new Date().toISOString();
    await db.consumers.update(id, { ...patch, updated_at: now });
    const current = await db.consumers.get(id);
    await db.outbox.add({
        id: crypto.randomUUID(),
        table: 'consumers',
        action: 'update',
        payload: current,
        created_at: Date.now()
    } as Outbox);
}

export async function deleteConsumerLocal(id: string | number): Promise<void> {
    await db.consumers.delete(id);
    await db.outbox.add({
        id: crypto.randomUUID(),
        table: 'consumers',
        action: 'delete',
        payload: { id },
        created_at: Date.now()
    } as Outbox);
}
