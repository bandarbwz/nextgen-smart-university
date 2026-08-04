import { apiClient } from './apiClient';

export const examService = {
    async list(sectionId) {
        const { data } = await apiClient.get('/ai-exam/examinations', {
            params: sectionId ? { section_id: sectionId } : {},
        });

        return data.data.examinations;
    },

    async get(id) {
        const { data } = await apiClient.get(`/ai-exam/examinations/${id}`);

        return data.data.examination;
    },

    async create(payload) {
        const { data } = await apiClient.post('/ai-exam/examinations', payload);

        return data.data.examination;
    },

    async update(id, payload) {
        const { data } = await apiClient.put(`/ai-exam/examinations/${id}`, payload);

        return data.data.examination;
    },

    async remove(id) {
        await apiClient.delete(`/ai-exam/examinations/${id}`);
    },

    async submissions(id) {
        const { data } = await apiClient.get(`/ai-exam/examinations/${id}/submissions`);

        return data.data.submissions;
    },

    async grade(submissionId, score) {
        const { data } = await apiClient.put(`/ai-exam/submissions/${submissionId}/grade`, { score });

        return data.data.submission;
    },

    async startSession(examId, context = {}) {
        const { data } = await apiClient.post('/ai-exam/session/start', {
            exam_id: examId,
            ...context,
        });

        return data.data.session;
    },

    async endSession(sessionId, answers) {
        const { data } = await apiClient.post('/ai-exam/session/end', {
            session_id: sessionId,
            answers,
        });

        return data.data.submission;
    },

    async pauseSession(sessionId) {
        const { data } = await apiClient.put('/ai-exam/session/pause', { session_id: sessionId });

        return data.data.session;
    },

    async resumeSession(sessionId) {
        const { data } = await apiClient.put('/ai-exam/session/resume', { session_id: sessionId });

        return data.data.session;
    },

    async mySessions() {
        const { data } = await apiClient.get('/ai-exam/sessions');

        return data.data.sessions;
    },

    async sessionsForExam(examId) {
        const { data } = await apiClient.get(`/ai-exam/examinations/${examId}/sessions`);

        return data.data.sessions;
    },

    async reportBrowserActivity(sessionId, activityType, detail = null) {
        const { data } = await apiClient.post('/ai-exam/browser-monitor', {
            session_id: sessionId,
            activity_type: activityType,
            detail,
        });

        return data.data.session;
    },

    async reportDevice(sessionId, payload) {
        const { data } = await apiClient.post('/ai-exam/device-monitor', {
            session_id: sessionId,
            ...payload,
        });

        return data.data.session;
    },

    async violationsForExam(examId) {
        const { data } = await apiClient.get('/ai-exam/violations', {
            params: { exam_id: examId },
        });

        return data.data.violations;
    },

    async violationsForSession(sessionId) {
        const { data } = await apiClient.get('/ai-exam/violations', {
            params: { session_id: sessionId },
        });

        return data.data.violations;
    },

    async generateReport(sessionId) {
        const { data } = await apiClient.post('/ai-exam/reports/generate', {
            session_id: sessionId,
        });

        return data.data.report;
    },

    async report(id) {
        const { data } = await apiClient.get(`/ai-exam/reports/${id}`);

        return data.data.report;
    },
};
