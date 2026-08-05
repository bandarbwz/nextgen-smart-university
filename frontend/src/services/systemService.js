import { apiClient } from './apiClient';

export const systemService = {
    async mySettings() {
        const { data } = await apiClient.get('/settings');

        return data.data.settings;
    },

    async updateMySettings(payload) {
        const { data } = await apiClient.put('/settings', payload);

        return data.data.settings;
    },

    async systemSettings() {
        const { data } = await apiClient.get('/system/settings');

        return data.data.settings;
    },

    async updateSystemSettings(settings) {
        const { data } = await apiClient.put('/system/settings', { settings });

        return data.data.settings;
    },

    async health() {
        const { data } = await apiClient.get('/system/health');

        return data.data;
    },

    async logs(params = {}) {
        const { data } = await apiClient.get('/system/logs', { params });

        return data.data;
    },

    async setMaintenance(enabled, message) {
        const { data } = await apiClient.put('/system/maintenance', { enabled, message });

        return data.data;
    },
};
