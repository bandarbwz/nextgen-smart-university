import { apiClient } from './apiClient';

export const examResetService = {
    async list(status) {
        const { data } = await apiClient.get('/exam-reset', {
            params: status ? { status } : {},
        });

        return data.data.requests;
    },

    async get(id) {
        const { data } = await apiClient.get(`/exam-reset/${id}`);

        return data.data.request;
    },

    async request(examId, reason) {
        const { data } = await apiClient.post('/exam-reset', {
            exam_id: examId,
            request_reason: reason,
        });

        return data.data.request;
    },

    async recommend(id, remarks) {
        const { data } = await apiClient.put(`/exam-reset/${id}/recommend`, { remarks });

        return data.data.request;
    },

    async approve(id, remarks) {
        const { data } = await apiClient.put(`/exam-reset/${id}/approve`, { remarks });

        return data.data.request;
    },

    async reject(id, remarks) {
        const { data } = await apiClient.put(`/exam-reset/${id}/reject`, { remarks });

        return data.data.request;
    },
};
