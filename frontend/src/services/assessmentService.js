import { apiClient } from './apiClient';

export const assessmentService = {
    async list(sectionId) {
        const { data } = await apiClient.get('/assessments', {
            params: sectionId ? { section_id: sectionId } : {},
        });

        return data.data.assessments;
    },

    async get(id) {
        const { data } = await apiClient.get(`/assessments/${id}`);

        return data.data.assessment;
    },

    async create(payload) {
        const { data } = await apiClient.post('/assessments', payload);

        return data.data.assessment;
    },

    async update(id, payload) {
        const { data } = await apiClient.put(`/assessments/${id}`, payload);

        return data.data.assessment;
    },

    async remove(id) {
        await apiClient.delete(`/assessments/${id}`);
    },

    async weights(sectionId) {
        const { data } = await apiClient.get(`/assessments/sections/${sectionId}/weights`);

        return data.data;
    },

    async recordResult(assessmentId, payload) {
        const { data } = await apiClient.post(`/assessments/${assessmentId}/results`, payload);

        return data.data.result;
    },

    async publish(assessmentId) {
        const { data } = await apiClient.put(`/assessments/${assessmentId}/publish`);

        return data.data;
    },

    async myResults() {
        const { data } = await apiClient.get('/assessments/results');

        return data.data.results;
    },

    async courseResult(sectionId, studentId) {
        const { data } = await apiClient.get(`/assessments/sections/${sectionId}/course-result`, {
            params: studentId ? { student_id: studentId } : {},
        });

        return data.data.result;
    },
};
