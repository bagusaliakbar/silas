<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
    nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section>
        <div class="pb-6 border-b border-rose-100 dark:border-rose-950/60">
            <h2 class="text-lg font-bold text-rose-700 dark:text-rose-400 flex items-center gap-2">
                <span>Zona Bahaya: Hapus Akun</span>
            </h2>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                Setelah akun Anda dihapus, semua riwayat pengajuan, data arsip, dan sumber daya terkait akan dihapus secara permanen.
            </p>
        </div>

        <div class="mt-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="text-xs text-slate-500 dark:text-slate-400">
                Pastikan Anda telah mengunduh berkas surat atau dokumen penting sebelum melanjutkan.
            </div>
            <button
                type="button"
                @click="confirmUserDeletion"
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 dark:hover:bg-rose-900/60 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800/60 rounded-xl text-xs sm:text-sm font-semibold transition shrink-0"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                <span>Hapus Akun Saya</span>
            </button>
        </div>

        <Modal :show="confirmingUserDeletion" @close="closeModal" max-width="md">
            <div class="p-6">
                <div class="w-12 h-12 rounded-full bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>

                <h3 class="text-base font-bold text-center text-slate-900 dark:text-white">
                    Konfirmasi Penghapusan Akun
                </h3>

                <p class="mt-2 text-xs text-center text-slate-500 dark:text-slate-400">
                    Tindakan ini tidak dapat dibatalkan. Masukkan kata sandi akun Anda untuk memverifikasi bahwa Anda benar-benar ingin menghapus akun ini secara permanen.
                </p>

                <div class="mt-5">
                    <label for="delete_password" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Kata Sandi Anda
                    </label>
                    <input
                        id="delete_password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        placeholder="Masukkan kata sandi saat ini"
                        @keyup.enter="deleteUser"
                        class="w-full px-3.5 py-2.5 text-sm bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition text-slate-900 dark:text-white"
                    />
                    <InputError :message="form.errors.password" class="mt-1.5" />
                </div>

                <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button
                        type="button"
                        @click="closeModal"
                        class="px-4 py-2 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        :disabled="form.processing"
                        @click="deleteUser"
                        class="px-4 py-2 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-xl shadow-xs transition-colors disabled:opacity-50"
                    >
                        {{ form.processing ? 'Menghapus...' : 'Ya, Hapus Akun' }}
                    </button>
                </div>
            </div>
        </Modal>
    </section>
</template>
