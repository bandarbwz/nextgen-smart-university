import { apiClient } from './apiClient';

export const financeService = {
    async invoices() {
        const { data } = await apiClient.get('/finance/invoices');

        return data.data.invoices;
    },

    async invoice(id) {
        const { data } = await apiClient.get(`/finance/invoices/${id}`);

        return data.data.invoice;
    },

    async payments() {
        const { data } = await apiClient.get('/finance/payments');

        return data.data.payments;
    },

    async scholarships() {
        const { data } = await apiClient.get('/finance/scholarships');

        return data.data.scholarships;
    },

    async standing() {
        const { data } = await apiClient.get('/finance/standing');

        return data.data;
    },
};
