import { onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { useAuthStore } from '../stores/auth';

export function useAuthGuard() {
    const auth = useAuthStore();

    onMounted(async () => {
        if (!auth.isAuthenticated) {
            router.visit('/login');
            return;
        }

        if (!auth.user) {
            try {
                await auth.fetchUser();
            } catch {
                auth.clearSession();
                router.visit('/login');
            }
        }
    });

    return auth;
}
