<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = computed(() => usePage().props.auth.user);
const isMahasiswa = computed(() => user.value?.role === 'mahasiswa');
</script>

<template>
    <Head title="Pengaturan Profil" />

    <AuthenticatedLayout>
        <div class="py-8">
            <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- Section 1: Update Profile & Academic Biodata -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 sm:p-8 shadow-xs border border-slate-100 dark:border-slate-700/60">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                    />
                </div>

                <!-- Section 2: Update Password -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 sm:p-8 shadow-xs border border-slate-100 dark:border-slate-700/60">
                    <UpdatePasswordForm />
                </div>

                <!-- Section 3: Danger Zone / Delete Account -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 sm:p-8 shadow-xs border border-rose-100 dark:border-rose-950/40">
                    <DeleteUserForm />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
