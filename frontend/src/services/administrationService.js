import { apiClient } from './apiClient';

export const administrationService = {
    async students() {
        const { data } = await apiClient.get('/students');

        return data.data.students;
    },

    async student(id) {
        const { data } = await apiClient.get(`/students/${id}`);

        return data.data.student;
    },

    async updateStudent(id, payload) {
        const { data } = await apiClient.put(`/students/${id}`, payload);

        return data.data.student;
    },

    async lecturers() {
        const { data } = await apiClient.get('/lecturers');

        return data.data.lecturers;
    },

    async updateLecturer(id, payload) {
        const { data } = await apiClient.put(`/lecturers/${id}`, payload);

        return data.data.lecturer;
    },

    async sections() {
        const { data } = await apiClient.get('/sections');

        return data.data.sections;
    },

    async sectionStudents(id) {
        const { data } = await apiClient.get(`/sections/${id}/students`);

        return data.data.students;
    },

    async updateCapacity(id, capacity) {
        const { data } = await apiClient.put(`/sections/${id}/capacity`, { capacity });

        return data.data.section;
    },

    async assignLecturer(id, lecturerId) {
        const { data } = await apiClient.put(`/sections/${id}/lecturer`, { lecturer_id: lecturerId });

        return data.data.section;
    },

    async openRegistration(id) {
        const { data } = await apiClient.post(`/sections/${id}/open-registration`);

        return data.data.section;
    },

    async closeRegistration(id) {
        const { data } = await apiClient.post(`/sections/${id}/close-registration`);

        return data.data.section;
    },
};
