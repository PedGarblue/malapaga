import { db, withTempId } from '@/db';
import type { Rate, Outbox } from '@/types/models';

export async function createRateLocal(data: Omit<Rate, 'id'>) {
    const rec = withTempId<Rate>({ ...data, created_at: new Date().toISOString(), updated_at: new Date().toISOString() });
    await db.rates.put(rec);
    await db.outbox.add({
        id: crypto.randomUUID(),
        table: 'rates',
        action: 'create',
        payload: rec,
        created_at: Date.now()
    } as Outbox);
    return rec;
}

export async function updateRateLocal(id: string | number, patch: Partial<Rate>) {
    const now = new Date().toISOString();
    await db.rates.update(id, { ...patch, updated_at: now });
    const current = await db.rates.get(id);
    await db.outbox.add({
        id: crypto.randomUUID(),
        table: 'rates',
        action: 'update',
        payload: current,
        created_at: Date.now()
    });
}

export async function deleteRateLocal(id: string | number) {
    await db.rates.delete(id);
    await db.outbox.add({
        id: crypto.randomUUID(),
        table: 'rates',
        action: 'delete',
        payload: { id },
        created_at: Date.now()
    });
}

export async function fetchRatesFromAPI(): Promise<Rate[]> {
    try {
        const response = await fetch('/api/rates', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error(`Failed to fetch rates: ${response.statusText}`);
        }

        const rates = await response.json();

        // Store rates in local database
        await db.rates.clear();
        await db.rates.bulkPut(rates);

        return rates;
    } catch (error) {
        console.error('Error fetching rates:', error);
        // Return rates from local database if API fails
        return await db.rates.toArray();
    }
}

export async function fetchLatestRatesFromAPI(): Promise<Rate[]> {
    try {
        const response = await fetch('/api/rates/latest', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error(`Failed to fetch latest rates: ${response.statusText}`);
        }

        const rates = await response.json();

        // Store rates in local database
        await db.rates.bulkPut(rates);

        return rates;
    } catch (error) {
        console.error('Error fetching latest rates:', error);
        // Return rates from local database if API fails
        return await db.rates.toArray();
    }
}

