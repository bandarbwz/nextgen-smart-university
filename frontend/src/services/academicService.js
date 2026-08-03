import { apiClient } from './apiClient';

export const academicService = {
    async studentProfile() {
        const { data } = await apiClient.get('/students/me');

        return data.data.student;
    },

    async currentSemester() {
        const { data } = await apiClient.get('/semesters/current');

        return data.data.semester;
    },

    async courses(params = {}) {
        const { data } = await apiClient.get('/courses', { params });

        return data.data.courses;
    },

    async coursePrerequisites(courseId) {
        const { data } = await apiClient.get(`/courses/${courseId}/prerequisites`);

        return data.data.prerequisites;
    },

    async sections(params = {}) {
        const { data } = await apiClient.get('/sections', { params });

        return data.data.sections;
    },

    async section(id) {
        const { data } = await apiClient.get(`/sections/${id}`);

        return data.data.section;
    },

    async register(sectionId) {
        const { data } = await apiClient.post('/enrollments/register', { section_id: sectionId });

        return data.data.enrollment;
    },

    async drop(enrollmentId) {
        const { data } = await apiClient.post('/enrollments/drop', { enrollment_id: enrollmentId });

        return data;
    },

    async currentEnrollments() {
        const { data } = await apiClient.get('/enrollments/current');

        return data.data.enrollments;
    },

    async enrollmentHistory() {
        const { data } = await apiClient.get('/enrollments/history');

        return data.data.enrollments;
    },

    async schedule() {
        const { data } = await apiClient.get('/schedule');

        return data.data.schedule;
    },

    async transcript() {
        const { data } = await apiClient.get('/transcript');

        return data.data.transcript;
    },

    async faculties() {
        const { data } = await apiClient.get('/faculties');

        return data.data.faculties;
    },

    async departments() {
        const { data } = await apiClient.get('/departments');

        return data.data.departments;
    },
};
