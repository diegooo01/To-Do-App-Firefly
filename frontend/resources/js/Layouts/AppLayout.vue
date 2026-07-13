<script setup>
import { Link, router } from '@inertiajs/vue3';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();

async function handleLogout() {
    await auth.logout();
    router.visit('/login');
}
</script>

<template>
    <div class="min-h-full">
        <nav class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4">
                <div class="flex items-center gap-8">
                    <span class="text-lg font-semibold text-slate-900">To-Do App</span>

                    <div class="flex gap-1">
                        <Link
                            href="/dashboard"
                            class="rounded-md px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900"
                        >
                            Dashboard
                        </Link>
                        <Link
                            href="/tasks"
                            class="rounded-md px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900"
                        >
                            Tareas
                        </Link>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <span class="text-sm text-slate-600">
                        {{ auth.user?.name }}
                    </span>
                    <button
                        type="button"
                        class="rounded-md bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-700"
                        @click="handleLogout"
                    >
                        Cerrar sesión
                    </button>
                </div>
            </div>
        </nav>

        <main class="mx-auto max-w-6xl px-4 py-8">
            <slot />
        </main>
    </div>
</template>
