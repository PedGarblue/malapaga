export type ID = number | string; // server IDs are numbers; local temp IDs can be uuid strings

export interface Rate {
    id?: ID;
    source: 'BCV' | 'Paralelo' | 'Custom';
    value: number;              // decimal(12,4) in backend
    currency_from: 'VES' | 'USD';
    currency_to: 'USD' | 'EUR';
    effective_at: string;       // ISO string
    created_at?: string;
    updated_at?: string;
}

export interface Event {
    id?: ID;
    title: string;
    date?: string | null;
    user_id?: number | null;
    created_at?: string;
    updated_at?: string;
}

export interface Consumer {
    id?: ID;
    name: string;
    user_id?: number | null;
    created_at?: string;
    updated_at?: string;
}

export interface Item {
    id?: ID;
    event_id: ID;
    name: string;
    price_usd: number;          // store normalized
    rate_id?: ID | null;
    created_at?: string;
    updated_at?: string;
}

export interface Participation {
    id?: ID;
    item_id: ID;
    consumer_id: ID;
    qty: number;                // default 1
    paid_by_id?: ID | null;
    created_at?: string;
    updated_at?: string;
}

export interface Settlement {
    id?: ID;
    event_id: ID;
    payer_id: ID;
    payee_id: ID;
    amount: number;
    paid: boolean;
    created_at?: string;
    updated_at?: string;
}

/** Outbox entries capture actions while offline */
export type OutboxAction = 'create' | 'update' | 'delete';

export interface Outbox {
    id: string;                 // uuid
    table: 'events' | 'consumers' | 'items' | 'participations' | 'settlements' | 'rates';
    action: OutboxAction;
    payload: any;               // the record (may include temp IDs)
    created_at: number;         // Date.now()
}
