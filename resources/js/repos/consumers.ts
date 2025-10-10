import { db, withTempId } from '@/db';
import type { Consumer, Outbox } from '@/types/models';

export async function createConsumerLocal(data: Omit<Consumer, 'id'>) {
    const rec = withTempId<Consumer>({ ...data, created_at: new Date().toISOString(), updated_at: new Date().toISOString() });
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

export async function updateConsumerLocal(id: string | number, patch: Partial<Consumer>) {
    const now = new Date().toISOString();
    await db.consumers.update(id, { ...patch, updated_at: now });
    const current = await db.consumers.get(id);
    await db.outbox.add({
        id: crypto.randomUUID(),
        table: 'consumers',
        action: 'update',
        payload: current,
        created_at: Date.now()
    });
}

export async function deleteConsumerLocal(id: string | number) {
    await db.consumers.delete(id);
    await db.outbox.add({
        id: crypto.randomUUID(),
        table: 'consumers',
        action: 'delete',
        payload: { id },
        created_at: Date.now()
    });
}