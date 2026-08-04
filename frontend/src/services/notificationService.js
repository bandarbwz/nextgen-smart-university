import { apiClient } from './apiClient';

export const NOTIFICATIONS_CHANGED = 'nsu:notifications-changed';

function announceChange() {
    window.dispatchEvent(new Event(NOTIFICATIONS_CHANGED));
}

export const notificationService = {
    async list(params = {}) {
        const { data } = await apiClient.get('/notifications', { params });

        return data.data;
    },

    async unreadCount() {
        const { data } = await apiClient.get('/notifications/unread');

        return data.data.unread_count;
    },

    async markRead(id) {
        const { data } = await apiClient.put(`/notifications/${id}/read`);

        announceChange();

        return data.data.notification;
    },

    async markAllRead() {
        const { data } = await apiClient.put('/notifications/read-all');

        announceChange();

        return data.data.updated;
    },

    async archive(id) {
        const { data } = await apiClient.put(`/notifications/${id}/archive`);

        announceChange();

        return data.data.notification;
    },

    async remove(id) {
        await apiClient.delete(`/notifications/${id}`);

        announceChange();
    },

    async preferences() {
        const { data } = await apiClient.get('/notifications/preferences');

        return data.data.preferences;
    },

    async updatePreferences(payload) {
        const { data } = await apiClient.put('/notifications/preferences', payload);

        return data.data.preferences;
    },

    async announcements() {
        const { data } = await apiClient.get('/notifications/announcements');

        return data.data.announcements;
    },

    async broadcast(payload) {
        const { data } = await apiClient.post('/notifications/broadcast', payload);

        return data.data.recipients;
    },
};
