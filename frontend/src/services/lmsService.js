import { apiClient } from './apiClient';

function sectionParams(sectionId) {
    return sectionId ? { section_id: sectionId } : {};
}

export const lmsService = {
    async materials(sectionId) {
        const { data } = await apiClient.get('/lms/materials', { params: sectionParams(sectionId) });

        return data.data.materials;
    },

    async uploadMaterial({ sectionId, title, description, visibility, file }) {
        const form = new FormData();

        form.append('section_id', sectionId);
        form.append('title', title);
        form.append('visibility', visibility);

        if (description) {
            form.append('description', description);
        }

        form.append('file', file);

        const { data } = await apiClient.post('/lms/materials', form, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        return data.data.material;
    },

    async downloadMaterial(id, fileName) {
        const response = await apiClient.get(`/lms/materials/${id}/download`, {
            responseType: 'blob',
        });

        const url = URL.createObjectURL(response.data);
        const link = document.createElement('a');

        link.href = url;
        link.download = fileName ?? 'material';
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
    },

    async assignments(sectionId) {
        const { data } = await apiClient.get('/lms/assignments', {
            params: sectionParams(sectionId),
        });

        return data.data.assignments;
    },

    async assignment(id) {
        const { data } = await apiClient.get(`/lms/assignments/${id}`);

        return data.data.assignment;
    },

    async createAssignment(fields) {
        const { data } = await apiClient.post('/lms/assignments', fields);

        return data.data.assignment;
    },

    async submitAssignment(id, { file, comment }) {
        const form = new FormData();

        if (file) {
            form.append('file', file);
        }

        if (comment) {
            form.append('comment', comment);
        }

        const { data } = await apiClient.post(`/lms/assignments/${id}/submit`, form, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        return data.data.submission;
    },

    async gradeSubmission(id, marks, feedback) {
        const { data } = await apiClient.put(`/lms/submissions/${id}/grade`, {
            marks,
            feedback,
        });

        return data.data.submission;
    },

    async quizzes(sectionId) {
        const { data } = await apiClient.get('/lms/quizzes', { params: sectionParams(sectionId) });

        return data.data.quizzes;
    },

    async quiz(id) {
        const { data } = await apiClient.get(`/lms/quizzes/${id}`);

        return data.data.quiz;
    },

    async submitQuiz(id, answers) {
        const { data } = await apiClient.post(`/lms/quizzes/${id}/submit`, { answers });

        return data.data.submission;
    },

    async announcements(sectionId) {
        const { data } = await apiClient.get('/lms/announcements', {
            params: sectionParams(sectionId),
        });

        return data.data.announcements;
    },

    async createAnnouncement(fields) {
        const { data } = await apiClient.post('/lms/announcements', fields);

        return data.data.announcement;
    },

    async resources(sectionId) {
        const { data } = await apiClient.get('/lms/resources', { params: sectionParams(sectionId) });

        return data.data.resources;
    },

    async grades(sectionId) {
        const { data } = await apiClient.get('/lms/grades', { params: sectionParams(sectionId) });

        return data.data.grades;
    },

    async publishGrades(sectionId) {
        const { data } = await apiClient.post('/lms/grades/publish', { section_id: sectionId });

        return data.data;
    },
};
