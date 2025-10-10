import { ref, computed } from 'vue';
import type { Rate } from '@/types/models';
import { fetchRatesFromAPI, fetchLatestRatesFromAPI } from '@/repos/rates';

const rates = ref<Rate[]>([]);
const isLoading = ref(false);
const error = ref<string | null>(null);

export function useRates() {
    const loadRates = async () => {
        isLoading.value = true;
        error.value = null;

        try {
            const fetchedRates = await fetchRatesFromAPI();
            rates.value = fetchedRates;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to load rates';
            console.error('Error loading rates:', e);
        } finally {
            isLoading.value = false;
        }
    };

    const loadLatestRates = async () => {
        isLoading.value = true;
        error.value = null;

        try {
            const fetchedRates = await fetchLatestRatesFromAPI();
            rates.value = fetchedRates;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to load latest rates';
            console.error('Error loading latest rates:', e);
        } finally {
            isLoading.value = false;
        }
    };

    const latestRates = computed(() => {
        // Group by currency pair and get the most recent one
        const grouped = new Map<string, Rate>();

        rates.value.forEach(rate => {
            const key = `${rate.currency_from}-${rate.currency_to}-${rate.source}`;
            const existing = grouped.get(key);

            if (!existing || new Date(rate.effective_at) > new Date(existing.effective_at)) {
                grouped.set(key, rate);
            }
        });

        return Array.from(grouped.values());
    });

    const getRateByPair = (currencyFrom: string, currencyTo: string) => {
        return computed(() => {
            return latestRates.value.find(
                rate => rate.currency_from === currencyFrom && rate.currency_to === currencyTo
            );
        });
    };

    return {
        rates,
        latestRates,
        isLoading,
        error,
        loadRates,
        loadLatestRates,
        getRateByPair
    };
}

