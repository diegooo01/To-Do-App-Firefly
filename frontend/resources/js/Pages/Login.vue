<script setup>
import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();

const form = ref({ email: '', password: '' });
const errors = ref({});
const message = ref('');
const loading = ref(false);

onMounted(() => {
    if (auth.isAuthenticated) {
        router.visit('/dashboard');
    }
});

async function handleSubmit() {
    loading.value = true;
    errors.value = {};
    message.value = '';

    try {
        await auth.login(form.value);
        router.visit('/dashboard');
    } catch (error) {
        const response = error.response;

        if (response?.status === 422) {
            errors.value = response.data.errors ?? {};
        } else if (response?.status === 401) {
            message.value = response.data.message;
        } else {
            message.value = 'No se pudo conectar con el servidor.';
        }
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="flex min-h-screen items-center justify-center px-4">
        <div class="w-full max-w-sm">
            <h1 class="mb-8 text-center text-2xl font-semibold text-slate-900">
                Iniciar sesión
            </h1>

            <form class="space-y-4" @submit.prevent="handleSubmit">
                <div>
                    <label for="email" class="mb-1 block text-sm font-medium text-slate-700">
                        Email
                    </label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none"
                    >
                    <p v-if="errors.email" class="mt-1 text-sm text-red-600">
                        {{ errors.email[0] }}
                    </p>
                </div>

                <div>
                    <label for="password" class="mb-1 block text-sm font-medium text-slate-700">
                        Contraseña
                    </label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        required
                        class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none"
                    >
                    <p v-if="errors.password" class="mt-1 text-sm text-red-600">
                        {{ errors.password[0] }}
                    </p>
                </div>

                <p v-if="message" class="rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">
                    {{ message }}
                </p>

                <button
                    type="submit"
                    :disabled="loading"
                    class="w-full rounded-md bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-700 disabled:opacity-50"
                >
                    {{ loading ? 'Ingresando...' : 'Ingresar' }}
                </button>
            </form>
        </div>
    </div>
</template>
