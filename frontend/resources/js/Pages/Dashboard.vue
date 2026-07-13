<script setup>
import { onMounted } from 'vue';
import AppLayout from '../Layouts/AppLayout.vue';
import { useAuthGuard } from '../composables/useAuthGuard';
import { useTaskStore } from '../stores/tasks';

useAuthGuard();

const store = useTaskStore();

const cards = [
    { key: 'total', label: 'Total de tareas' },
    { key: 'pending', label: 'Pendientes' },
    { key: 'in_progress', label: 'En progreso' },
    { key: 'done', label: 'Completadas' },
];

onMounted(() => {
    store.fetchMetrics();
});
</script>

<template>
    <AppLayout>
        <h1 class="mb-6 text-2xl font-semibold text-slate-900">Dashboard</h1>

        <p v-if="store.error" class="mb-4 rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">
            {{ store.error }}
        </p>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div
                v-for="card in cards"
                :key="card.key"
                class="rounded-lg border border-slate-200 bg-white p-5"
            >
                <p class="text-sm font-medium text-slate-500">{{ card.label }}</p>
                <p class="mt-2 text-3xl font-semibold text-slate-900">
                    {{ store.loading ? '—' : store.metrics[card.key] }}
                </p>
            </div>
        </div>
    </AppLayout>
</template>
