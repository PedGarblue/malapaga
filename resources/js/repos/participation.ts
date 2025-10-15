import { db, withTempId } from '@/db';
import type { Participation, Outbox } from '@/types/models';

export async function fetchParticipationsByItemLocal(itemId: string | number): Promise<Participation[]> {
    return await db.participations.where('item_id').equals(itemId).toArray();
}

export async function createParticipationLocal(data: Omit<Participation, 'id'>): Promise<Participation> {
    const rec = withTempId<Participation>({ ...data, created_at: new Date().toISOString(), updated_at: new Date().toISOString() });
    await db.participations.put(rec);
    await db.outbox.add({
        id: crypto.randomUUID(),
        table: 'participations',
        action: 'create',
        payload: rec,
        created_at: Date.now()
    } as Outbox);
    return rec;
}

export async function updateParticipationLocal(id: string | number, patch: Partial<Participation>) {
    const now = new Date().toISOString();
    await db.participations.update(id, { ...patch, updated_at: now });
    const current = await db.participations.get(id);
    await db.outbox.add({
        id: crypto.randomUUID(),
        table: 'participations',
        action: 'update',
        payload: current,
        created_at: Date.now()
    });
}

export async function deleteParticipationLocal(id: string | number) {
    await db.participations.delete(id);
    await db.outbox.add({
        id: crypto.randomUUID(),
        table: 'participations',
        action: 'delete',
        payload: { id },
        created_at: Date.now()
    });
}
