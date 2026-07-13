import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '../lib/api';

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null);
    const token = ref(localStorage.getItem('token'));

    const isAuthenticated = computed(() => !!token.value);

    function setToken(value) {
        token.value = value;
        localStorage.setItem('token', value);
    }

    function clearSession() {
        user.value = null;
        token.value = null;
        localStorage.removeItem('token');
    }

    async function login(credentials) {
        const { data } = await api.post('/login', credentials);

        setToken(data.token);
        user.value = data.user;

        return data;
    }

    async function fetchUser() {
        const { data } = await api.get('/me');
        user.value = data;

        return data;
    }

    async function logout() {
        try {
            await api.post('/logout');
        } finally {
            clearSession();
        }
    }

    return {
        user,
        token,
        isAuthenticated,
        login,
        fetchUser,
        logout,
        clearSession,
    };
});
