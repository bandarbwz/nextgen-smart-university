import { apiClient } from './apiClient';

function saveBlob(blob, fallbackName, headers) {
    const disposition = headers?.['content-disposition'] ?? '';
    const match = disposition.match(/filename="([^"]+)"/);

    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');

    link.href = url;
    link.download = match ? match[1] : fallbackName;
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(url);
}

export const reportService = {
    async available() {
        const { data } = await apiClient.get('/reports');

        return data.data.reports;
    },

    async run(key, parameters = {}) {
        const [category, name] = key.split('.');

        const { data } = await apiClient.get(`/reports/${category}/${name}`, {
            params: parameters,
        });

        return data.data.report;
    },

    async export(key, format, parameters = {}) {
        const response = await apiClient.post(
            '/reports/export',
            { report: key, format, parameters },
            { responseType: 'blob' },
        );

        saveBlob(response.data, `${key}.${format}`, response.headers);
    },

    async files(category) {
        const { data } = await apiClient.get('/download-center/files', {
            params: category ? { category } : {},
        });

        return data.data.files;
    },

    async downloadFile(id, fileName) {
        const response = await apiClient.get(`/download-center/files/${id}/download`, {
            responseType: 'blob',
        });

        saveBlob(response.data, fileName, response.headers);
    },

    async downloadTranscript(format = 'pdf') {
        const response = await apiClient.get('/download-center/transcript', {
            params: { format },
            responseType: 'blob',
        });

        saveBlob(response.data, `transcript.${format}`, response.headers);
    },

    async downloadSchedule(format = 'pdf') {
        const response = await apiClient.get('/download-center/schedule', {
            params: { format },
            responseType: 'blob',
        });

        saveBlob(response.data, `schedule.${format}`, response.headers);
    },

    async history() {
        const { data } = await apiClient.get('/download-center/history');

        return data.data.history;
    },
};
