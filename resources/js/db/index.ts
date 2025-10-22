import Dexie, { Table } from 'dexie';
import { v4 as uuid } from 'uuid';
import type { Event, Consumer, Item, Participation, Settlement, Rate, Outbox, ID } from '@/types/models';

export class AppDB extends Dexie {
    events!: Table<Event, ID>;
    consumers!: Table<Consumer, ID>;
    items!: Table<Item, ID>;
    participations!: Table<Participation, ID>;
    settlements!: Table<Settlement, ID>;
    rates!: Table<Rate, ID>;
    outbox!: Table<Outbox, string>;

    constructor() {
        super('malapaga-db');

        // Version 1: Initial schema
        this.version(1).stores({
            events: 'id, title, date, created_at, updated_at',
            consumers: 'id, name, created_at, updated_at',
            items: 'id, event_id, rate_id, created_at, updated_at',
            participations: 'id, item_id, consumer_id, paid_by_id, created_at, updated_at',
            settlements: 'id, event_id, payer_id, payee_id, paid, created_at, updated_at',
            rates: 'id, source, effective_at, created_at, updated_at',
            outbox: 'id, table, action, created_at'
        });

        // Version 2: Add event_id index to consumers
        this.version(2).stores({
            consumers: 'id, event_id, name, created_at, updated_at'
        });

        // Version 3: Add split_type to items (no index changes needed)
        this.version(3).stores({
            items: 'id, event_id, rate_id, created_at, updated_at'
        });
    }
}

export const db = new AppDB();

/** Utility: assign a temp id if missing (uuid string so it won’t collide with numeric API ids) */
export function withTempId<T extends { id?: ID }>(obj: T): T {
    if (!obj.id) obj.id = uuid();
    return obj;
}
