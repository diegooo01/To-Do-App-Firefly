import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '../lib/api';

export const useTaskStore = defineStore('tasks', () => {
    const tasks = ref([]);
    const metrics = ref({ total: 0, pending: 0, in_progress: 0, done: 0 });
    const loading = ref(false);
    const error = ref('');

    async function fetchTasks(filters = {}) {
        loading.value = true;
        error.value = '';

        const params = Object.fromEntries(
            Object.entries(filters).filter(([, value]) => value !== '' && value !== null)
        );

        try {
            const { data } = await api.get('/tasks', { params });
            tasks.value = data;
        } catch {
            error.value = 'No se pudieron cargar las tareas.';
        } finally {
            loading.value = false;
        }
    }

    async function fetchMetrics() {
        loading.value = true;
        error.value = '';

        try {
            const { data } = await api.get('/dashboard');
            metrics.value = data;
        } catch {
            error.value = 'No se pudieron cargar las métricas.';
        } finally {
            loading.value = false;
        }
    }

    return { tasks, metrics, loading, error, fetchTasks, fetchMetrics };
});
