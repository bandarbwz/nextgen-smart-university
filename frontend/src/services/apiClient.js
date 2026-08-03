import axios from 'axios';
import { tokenStorage } from './tokenStorage';

const baseURL = import.meta.env.VITE_API_URL ?? 'http://127.0.0.1:8000/api/v1';

export const apiClient = axios.create({
    baseURL,
    headers: { 'Content-Type': 'application/json' },
});

let onSessionExpired = null;

export function setSessionExpiredHandler(handler) {
    onSessionExpired = handler;
}

apiClient.interceptors.request.use((config) => {
    const token = tokenStorage.getAccessToken();

    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }

    return config;
});

let refreshPromise = null;

function refreshSession() {
    if (refreshPromise) {
        return refreshPromise;
    }

    const refreshToken = tokenStorage.getRefreshToken();

    if (!refreshToken) {
        return Promise.reject(new Error('No refresh token available.'));
    }

    refreshPromise = axios
        .post(`${baseURL}/auth/refresh`, { refresh_token: refreshToken })
        .then((response) => {
            tokenStorage.save(response.data.data);

            return response.data.data.access_token;
        })
        .finally(() => {
            refreshPromise = null;
        });

    return refreshPromise;
}

apiClient.interceptors.response.use(
    (response) => response,
    async (error) => {
        const request = error.config;
        const isAuthEndpoint = request?.url?.includes('/auth/login')
            || request?.url?.includes('/auth/refresh');

        if (error.response?.status !== 401 || request?._retried || isAuthEndpoint) {
            return Promise.reject(error);
        }

        request._retried = true;

        try {
            const accessToken = await refreshSession();

            request.headers.Authorization = `Bearer ${accessToken}`;

            return apiClient(request);
        } catch (refreshError) {
            tokenStorage.clear();

            if (onSessionExpired) {
                onSessionExpired();
            }

            return Promise.reject(refreshError);
        }
    },
);

export function readApiError(error, fallback = 'Something went wrong. Please try again.') {
    return {
        message: error?.response?.data?.message ?? fallback,
        fieldErrors: error?.response?.data?.errors ?? {},
        status: error?.response?.status ?? 0,
    };
}
