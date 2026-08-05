import { apiClient } from './apiClient';

export const roleService = {
    async list() {
        const { data } = await apiClient.get('/roles');

        return data.data.roles;
    },

    async get(id) {
        const { data } = await apiClient.get(`/roles/${id}`);

        return data.data.role;
    },

    async permissions() {
        const { data } = await apiClient.get('/permissions');

        return data.data.permissions;
    },

    async create(payload) {
        const { data } = await apiClient.post('/roles', payload);

        return data.data.role;
    },

    async update(id, payload) {
        const { data } = await apiClient.put(`/roles/${id}`, payload);

        return data.data.role;
    },

    async remove(id) {
        await apiClient.delete(`/roles/${id}`);
    },

    async assignPermissions(id, permissionIds) {
        const { data } = await apiClient.put(`/roles/${id}/permissions`, {
            permission_ids: permissionIds,
        });

        return data.data.role;
    },

    async auditLog(roleId) {
        const { data } = await apiClient.get('/roles/audit-log', {
            params: roleId ? { role_id: roleId } : {},
        });

        return data.data.log;
    },
};
