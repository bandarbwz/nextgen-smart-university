import { apiClient } from './apiClient';

export const calendarService = {
    async monthly(year, month) {
        const { data } = await apiClient.get('/calendar/events/monthly', {
            params: { year, month },
        });

        return data.data.events;
    },

    async overview() {
        const { data } = await apiClient.get('/calendar');

        return data.data;
    },

    async create(fields) {
        const { data } = await apiClient.post('/calendar/events', fields);

        return data.data.event;
    },

    async remove(id) {
        await apiClient.delete(`/calendar/events/${id}`);
    },

    async sync() {
        const { data } = await apiClient.post('/calendar/sync');

        return data.data;
    },

    async exportCalendar() {
        const response = await apiClient.get('/calendar/export', { responseType: 'blob' });

        const url = URL.createObjectURL(response.data);
        const link = document.createElement('a');

        link.href = url;
        link.download = 'nextgen-calendar.ics';
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
    },
};
