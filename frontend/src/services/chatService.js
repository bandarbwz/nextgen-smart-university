import { apiClient } from './apiClient';

export const chatService = {
    async rooms() {
        const { data } = await apiClient.get('/chat/rooms');

        return data.data.rooms;
    },

    async room(id) {
        const { data } = await apiClient.get(`/chat/rooms/${id}`);

        return data.data.room;
    },

    async messages(roomId, { afterId, beforeId, limit } = {}) {
        const params = {};

        if (afterId) {
            params.after_id = afterId;
        }

        if (beforeId) {
            params.before_id = beforeId;
        }

        if (limit) {
            params.limit = limit;
        }

        const { data } = await apiClient.get(`/chat/rooms/${roomId}/messages`, { params });

        return data.data;
    },

    async send(roomId, message, replyTo = null) {
        const { data } = await apiClient.post('/chat/messages', {
            room_id: roomId,
            message,
            reply_to: replyTo,
        });

        return data.data.message;
    },

    async remove(id) {
        await apiClient.delete(`/chat/messages/${id}`);
    },

    async pin(id, pinned) {
        const { data } = await apiClient.put(`/chat/messages/${id}/pin`, { pinned });

        return data.data.message;
    },

    async react(id, reaction) {
        const { data } = await apiClient.post(`/chat/messages/${id}/reaction`, { reaction });

        return data.data;
    },

    async search(keyword) {
        const { data } = await apiClient.get('/chat/search', { params: { keyword } });

        return data.data.results;
    },
};
