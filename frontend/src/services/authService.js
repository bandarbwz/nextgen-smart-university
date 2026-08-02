import { apiClient } from './apiClient';

export const authService = {
    async login(email, password) {
        const { data } = await apiClient.post('/auth/login', { email, password });

        return data.data;
    },

    async logout() {
        await apiClient.post('/auth/logout');
    },

    async profile() {
        const { data } = await apiClient.get('/auth/profile');

        return data.data.user;
    },

    async updateProfile(fields) {
        const { data } = await apiClient.put('/auth/profile', fields);

        return data.data.user;
    },

    async changePassword(fields) {
        const { data } = await apiClient.put('/auth/change-password', fields);

        return data;
    },

    async forgotPassword(email) {
        const { data } = await apiClient.post('/auth/forgot-password', { email });

        return data;
    },

    async resetPassword(fields) {
        const { data } = await apiClient.post('/auth/reset-password', fields);

        return data;
    },

    async sessions() {
        const { data } = await apiClient.get('/auth/sessions');

        return data.data.sessions;
    },

    async revokeSession(id) {
        await apiClient.delete(`/auth/sessions/${id}`);
    },
};
