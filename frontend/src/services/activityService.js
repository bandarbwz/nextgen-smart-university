import { apiClient } from './apiClient';

export const activityService = {
    async clubs(params = {}) {
        const { data } = await apiClient.get('/activities/clubs', { params });

        return data.data.clubs;
    },

    async club(id) {
        const { data } = await apiClient.get(`/activities/clubs/${id}`);

        return data.data.club;
    },

    async createClub(payload) {
        const { data } = await apiClient.post('/activities/clubs', payload);

        return data.data.club;
    },

    async events(params = {}) {
        const { data } = await apiClient.get('/activities/events', { params });

        return data.data.events;
    },

    async event(id) {
        const { data } = await apiClient.get(`/activities/events/${id}`);

        return data.data.event;
    },

    async createEvent(payload) {
        const { data } = await apiClient.post('/activities/events', payload);

        return data.data.event;
    },

    async updateEvent(id, payload) {
        const { data } = await apiClient.put(`/activities/events/${id}`, payload);

        return data.data.event;
    },

    async cancelEvent(id) {
        const { data } = await apiClient.put(`/activities/events/${id}/cancel`);

        return data.data.event;
    },

    async register(eventId) {
        const { data } = await apiClient.post('/activities/register', { event_id: eventId });

        return data.data.registration;
    },

    async myRegistrations() {
        const { data } = await apiClient.get('/activities/registrations');

        return data.data.registrations;
    },

    async cancelRegistration(id) {
        const { data } = await apiClient.put(`/activities/registrations/${id}/cancel`);

        return data.data.registration;
    },

    async eventRegistrations(eventId, status) {
        const { data } = await apiClient.get(`/activities/events/${eventId}/registrations`, {
            params: status ? { status } : {},
        });

        return data.data.registrations;
    },

    async approve(id) {
        const { data } = await apiClient.put(`/activities/registrations/${id}/approve`);

        return data.data.registration;
    },

    async reject(id, reason) {
        const { data } = await apiClient.put(`/activities/registrations/${id}/reject`, { reason });

        return data.data.registration;
    },

    async openQr(eventId) {
        const { data } = await apiClient.post(`/activities/events/${eventId}/qr`);

        return data.data.session;
    },

    async scan(token) {
        const { data } = await apiClient.post('/activities/attendance', { token });

        return data.data.attendance;
    },

    async recordAttendance(registrationId) {
        const { data } = await apiClient.post('/activities/attendance/manual', {
            registration_id: registrationId,
        });

        return data.data.attendance;
    },

    async myPoints() {
        const { data } = await apiClient.get('/activities/points');

        return data.data;
    },

    async awardPoints(payload) {
        const { data } = await apiClient.post('/activities/points', payload);

        return data.data.point;
    },
};
