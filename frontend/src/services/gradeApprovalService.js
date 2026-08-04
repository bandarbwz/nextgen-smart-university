import { apiClient } from './apiClient';

export const gradeApprovalService = {
    async list(status) {
        const { data } = await apiClient.get('/grade-approvals', {
            params: status ? { status } : {},
        });

        return data.data.approvals;
    },

    async get(id) {
        const { data } = await apiClient.get(`/grade-approvals/${id}`);

        return data.data.approval;
    },

    async submit(sectionId) {
        const { data } = await apiClient.post('/grade-approvals', { section_id: sectionId });

        return data.data.approval;
    },

    async approve(id, remarks) {
        const { data } = await apiClient.put(`/grade-approvals/${id}/approve`, { remarks });

        return data.data.approval;
    },

    async reject(id, remarks) {
        const { data } = await apiClient.put(`/grade-approvals/${id}/reject`, { remarks });

        return data.data.approval;
    },

    async returnForRevision(id, remarks) {
        const { data } = await apiClient.put(`/grade-approvals/${id}/return`, { remarks });

        return data.data.approval;
    },

    async history() {
        const { data } = await apiClient.get('/grade-approvals/history');

        return data.data.history;
    },
};
