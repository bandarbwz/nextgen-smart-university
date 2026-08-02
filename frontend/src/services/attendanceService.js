import { apiClient } from './apiClient';

export const attendanceService = {
    async myAttendance() {
        const { data } = await apiClient.get('/attendance/me');

        return data.data;
    },

    async scan({ qrToken, latitude, longitude }) {
        const { data } = await apiClient.post('/attendance/scan', {
            qr_token: qrToken,
            latitude,
            longitude,
        });

        return data.data.attendance;
    },

    async openSession({ sectionId, latitude, longitude, allowedRadius }) {
        const { data } = await apiClient.post('/attendance/qr-session', {
            section_id: sectionId,
            latitude,
            longitude,
            allowed_radius: allowedRadius,
        });

        return data.data.session;
    },

    async activeSession(sectionId) {
        const { data } = await apiClient.get(`/attendance/qr-session/${sectionId}`);

        return data.data.session;
    },

    async closeSession(id) {
        const { data } = await apiClient.put(`/attendance/qr-session/${id}/close`);

        return data.data.session;
    },

    async sectionAttendance(sectionId, date) {
        const { data } = await apiClient.get(`/attendance/section/${sectionId}`, {
            params: date ? { date } : {},
        });

        return data.data.attendance;
    },

    async recordManually(fields) {
        const { data } = await apiClient.put('/attendance/manual', fields);

        return data.data.attendance;
    },

    async excuses(status) {
        const { data } = await apiClient.get('/attendance/excuse', {
            params: status ? { status } : {},
        });

        return data.data.excuses;
    },

    async submitExcuse({ attendanceId, excuseType, reason, document }) {
        const form = new FormData();

        form.append('attendance_id', attendanceId);
        form.append('excuse_type', excuseType);
        form.append('reason', reason);

        if (document) {
            form.append('document', document);
        }

        const { data } = await apiClient.post('/attendance/excuse', form, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        return data.data.excuse;
    },

    async reviewExcuse(id, decision, note) {
        const { data } = await apiClient.put(`/attendance/excuse/${id}/${decision}`, {
            review_note: note,
        });

        return data.data.excuse;
    },
};
