<script setup>
import { ref, onMounted, watch } from 'vue';
import AppLayout from '../Layouts/AppLayout.vue';
import { useAuthGuard } from '../composables/useAuthGuard';
import { useTaskStore } from '../stores/tasks';

useAuthGuard();

const store = useTaskStore();

const filters = ref({ status: '', priority: '', search: '' });

const statusLabels = {
    pending: 'Pendiente',
    in_progress: 'En progreso',
    done: 'Completada',
};

const priorityLabels = {
    low: 'Baja',
    medium: 'Media',
    high: 'Alta',
};

const statusStyles = {
    pending: 'bg-slate-100 text-slate-700',
    in_progress: 'bg-blue-100 text-blue-700',
    done: 'bg-green-100 text-green-700',
};

const priorityStyles = {
    low: 'bg-slate-100 text-slate-700',
    medium: 'bg-amber-100 text-amber-700',
    high: 'bg-red-100 text-red-700',
};

let timer = null;

watch(filters, (value) => {
    clearTimeout(timer);
    timer = setTimeout(() => store.fetchTasks(value), 300);
}, { deep: true });

onMounted(() => {
    store.fetchTasks();
});

function formatDate(value) {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleDateString('es-PE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        timeZone: 'UTC',
    });
}
</script>

<template>
    <AppLayout>
        <h1 class="mb-6 text-2xl font-semibold text-slate-900">Tareas</h1>

        <div class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
            <input
                v-model="filters.search"
                type="search"
                placeholder="Buscar por título"
                class="rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none"
            >

            <select
                v-model="filters.status"
                class="rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none"
            >
                <option value="">Todos los estados</option>
                <option value="pending">Pendiente</option>
                <option value="in_progress">En progreso</option>
                <option value="done">Completada</option>
            </select>

            <select
                v-model="filters.priority"
                class="rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none"
            >
                <option value="">Todas las prioridades</option>
                <option value="low">Baja</option>
                <option value="medium">Media</option>
                <option value="high">Alta</option>
            </select>
        </div>

        <p v-if="store.error" class="mb-4 rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">
            {{ store.error }}
        </p>

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50">
                <tr>
                    <th class="px-4 py-3 font-medium text-slate-600">Título</th>
                    <th class="px-4 py-3 font-medium text-slate-600">Estado</th>
                    <th class="px-4 py-3 font-medium text-slate-600">Prioridad</th>
                    <th class="px-4 py-3 font-medium text-slate-600">Fecha límite</th>
                </tr>
                </thead>

                <tbody>
                <tr v-if="store.loading">
                    <td colspan="4" class="px-4 py-8 text-center text-slate-500">
                        Cargando...
                    </td>
                </tr>

                <tr v-else-if="store.tasks.length === 0">
                    <td colspan="4" class="px-4 py-8 text-center text-slate-500">
                        No hay tareas que coincidan con los filtros.
                    </td>
                </tr>

                <tr
                    v-for="task in store.tasks"
                    v-else
                    :key="task.id"
                    class="border-b border-slate-100 last:border-0"
                >
                    <td class="px-4 py-3 font-medium text-slate-900">
                        {{ task.title }}
                    </td>
                    <td class="px-4 py-3">
                            <span
                                class="rounded-full px-2 py-1 text-xs font-medium"
                                :class="statusStyles[task.status]"
                            >
                                {{ statusLabels[task.status] }}
                            </span>
                    </td>
                    <td class="px-4 py-3">
                            <span
                                class="rounded-full px-2 py-1 text-xs font-medium"
                                :class="priorityStyles[task.priority]"
                            >
                                {{ priorityLabels[task.priority] }}
                            </span>
                    </td>
                    <td class="px-4 py-3 text-slate-600">
                        {{ formatDate(task.due_date) }}
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
