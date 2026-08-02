import { createContext, useCallback, useEffect, useMemo, useState } from 'react';
import { authService } from '../services/authService';
import { setSessionExpiredHandler } from '../services/apiClient';
import { tokenStorage } from '../services/tokenStorage';

export const AuthContext = createContext(null);

export function AuthProvider({ children }) {
    const [user, setUser] = useState(null);
    const [isLoading, setIsLoading] = useState(true);

    const clearSession = useCallback(() => {
        tokenStorage.clear();
        setUser(null);
    }, []);

    useEffect(() => {
        setSessionExpiredHandler(clearSession);
    }, [clearSession]);

    useEffect(() => {
        if (!tokenStorage.getAccessToken()) {
            setIsLoading(false);

            return;
        }

        authService
            .profile()
            .then(setUser)
            .catch(clearSession)
            .finally(() => setIsLoading(false));
    }, [clearSession]);

    const login = useCallback(async (email, password) => {
        const session = await authService.login(email, password);

        tokenStorage.save(session);
        setUser(session.user);

        return session.user;
    }, []);

    const logout = useCallback(async () => {
        try {
            await authService.logout();
        } finally {
            clearSession();
        }
    }, [clearSession]);

    const value = useMemo(
        () => ({
            user,
            isLoading,
            isAuthenticated: user !== null,
            login,
            logout,
            setUser,
        }),
        [user, isLoading, login, logout],
    );

    return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}
