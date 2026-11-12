<script setup>
import InputError from '@/Components/InputError.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

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

const isProfileIncomplete = computed(() => {
    if (!isMahasiswa.value) return false;
    return !user.value?.npm || !user.value?.phone || !user.value?.birth_place || !user.value?.birth_date || !user.value?.address;
});

const avatarInput = ref(null);
const avatarPreview = ref(null);

const form = useForm({
    _method: 'PATCH',
    avatar: null,
    remove_avatar: false,
    name: user.value?.name || '',
    email: user.value?.email || '',
    npm: user.value?.npm || '',
    study_program: user.value?.study_program || 'Sistem Informasi',
    phone: user.value?.phone || '',
    birth_place: user.value?.birth_place || '',
    birth_date: user.value?.birth_date || '',
    address: user.value?.address || '',
    semester: user.value?.semester || 1,
});

const onAvatarSelected = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.avatar = file;
        form.remove_avatar = false;
        const reader = new FileReader();
        reader.onload = (e) => {
            avatarPreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const removeAvatar = () => {
    form.avatar = null;
    form.remove_avatar = true;
    avatarPreview.value = null;
    if (avatarInput.value) {
        avatarInput.value.value = '';
    }
};

const triggerFileInput = () => {
    if (avatarInput.value) {
        avatarInput.value.click();
    }
};

const submitForm = () => {
    form.post(route('profile.update'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            avatarPreview.value = null;
            form.avatar = null;
            form.remove_avatar = false;
            if (avatarInput.value) {
                avatarInput.value.value = '';
            }
        },
    });
};
</script>

<template>
    <section>
        <!-- Notice jika profil belum lengkap -->
        <div v-if="isProfileIncomplete" class="mb-6 p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 flex items-start gap-3">
            <div class="w-8 h-8 rounded-xl bg-amber-100 dark:bg-amber-900/50 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0 mt-0.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div>
                <h4 class="text-sm font-bold text-amber-900 dark:text-amber-200">
                    Biodata Akademik Belum Lengkap!
                </h4>
                <p class="text-xs text-amber-700 dark:text-amber-300/90 mt-0.5">
                    Sebagai mahasiswa baru terdaftar, Anda wajib melengkapi data (No. WhatsApp, Tempat/Tanggal Lahir, dan Alamat Lengkap) di bawah ini sebelum dapat mengajukan surat akademik.
                </p>
            </div>
        </div>

        <div class="flex items-start justify-between pb-6 border-b border-slate-100 dark:border-slate-700/60">
            <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <span>Informasi Akun & Data Diri</span>
                </h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Perbarui foto profil, informasi akun, dan biodata akademik Anda.
                </p>
            </div>
            <div class="shrink-0">
                <span
                    :class="[
                        'inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold uppercase tracking-wider',
                        isMahasiswa
                            ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300 border border-blue-200/60'
                            : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200/60'
                    ]"
                >
                    {{ isMahasiswa ? 'Akun Mahasiswa' : 'Admin / Pejabat' }}
                </span>
            </div>
        </div>

        <form @submit.prevent="submitForm" class="mt-6 space-y-6">
            <!-- Upload Foto Profil Section -->
            <div class="p-4 sm:p-5 rounded-2xl bg-slate-50/70 dark:bg-slate-900/40 border border-slate-200/70 dark:border-slate-700/60">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-3">
                    Foto Profil Pengguna
                </label>
                <div class="flex flex-col sm:flex-row items-center gap-5">
                    <!-- Avatar Preview -->
                    <div class="relative shrink-0">
                        <div class="w-20 h-20 rounded-2xl overflow-hidden ring-4 ring-white dark:ring-slate-700 shadow-md bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center text-white font-bold text-2xl">
                            <img
                                v-if="avatarPreview"
                                :src="avatarPreview"
                                alt="Preview Foto"
                                class="w-full h-full object-cover"
                            />
                            <img
                                v-else-if="user.avatar_url && !form.remove_avatar"
                                :src="user.avatar_url"
                                alt="Foto Profil"
                                class="w-full h-full object-cover"
                            />
                            <span v-else>
                                {{ user.name?.charAt(0) || 'U' }}
                            </span>
                        </div>
                    </div>

                    <div class="space-y-2 text-center sm:text-left flex-1 min-w-0">
                        <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2.5">
                            <input
                                ref="avatarInput"
                                type="file"
                                accept="image/jpeg,image/png,image/jpg,image/webp"
                                @change="onAvatarSelected"
                                class="hidden"
                            />
                            <button
                                type="button"
                                @click="triggerFileInput"
                                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-750 text-xs font-semibold shadow-2xs transition"
                            >
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span>{{ user.avatar_url || avatarPreview ? 'Ganti Foto' : 'Unggah Foto' }}</span>
                            </button>

                            <button
                                v-if="(user.avatar_url && !form.remove_avatar) || avatarPreview"
                                type="button"
                                @click="removeAvatar"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 text-xs font-semibold transition"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                <span>Hapus Foto</span>
                            </button>
                        </div>
                        <p class="text-[11px] text-slate-400 dark:text-slate-500">
                            Format yang didukung: JPG, PNG, atau WEBP. Ukuran maksimal 2 MB.
                        </p>
                        <InputError class="mt-1" :message="form.errors.avatar" />
                    </div>
                </div>
            </div>

            <!-- Data Dasar -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label for="name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Nama Lengkap <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </span>
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            required
                            class="w-full pl-10 pr-4 py-2.5 text-sm bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition text-slate-900 dark:text-white"
                            placeholder="Nama Lengkap"
                        />
                    </div>
                    <InputError class="mt-1.5" :message="form.errors.name" />
                </div>

                <div>
                    <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Alamat Email <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </span>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            required
                            class="w-full pl-10 pr-4 py-2.5 text-sm bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition text-slate-900 dark:text-white"
                            placeholder="nama@mail.com"
                        />
                    </div>
                    <InputError class="mt-1.5" :message="form.errors.email" />
                </div>
            </div>

            <!-- Bagian Khusus Biodata Akademik Mahasiswa -->
            <div class="pt-6 border-t border-slate-100 dark:border-slate-700/60">
                <div class="flex items-center gap-2 mb-1">
                    <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                        Biodata Akademik Mahasiswa
                    </h3>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-5">
                    Data akademik ini digunakan sebagai data otomatis saat Anda membuat pengajuan surat permohonan.
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label for="npm" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Nomor Pokok Mahasiswa (NPM)
                        </label>
                        <input
                            id="npm"
                            v-model="form.npm"
                            type="text"
                            placeholder="D1A23xxxx"
                            class="w-full px-3.5 py-2 text-sm bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition font-mono uppercase text-slate-900 dark:text-white"
                        />
                        <InputError class="mt-1" :message="form.errors.npm" />
                    </div>

                    <div>
                        <label for="study_program" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Program Studi
                        </label>
                        <select
                            id="study_program"
                            v-model="form.study_program"
                            class="w-full px-3.5 py-2 text-sm bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition text-slate-900 dark:text-white"
                        >
                            <option value="Sistem Informasi">S1 - Sistem Informasi</option>
                        </select>
                        <InputError class="mt-1" :message="form.errors.study_program" />
                    </div>

                    <div>
                        <label for="phone" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Nomor WhatsApp / HP
                        </label>
                        <input
                            id="phone"
                            v-model="form.phone"
                            type="text"
                            placeholder="081234567890"
                            class="w-full px-3.5 py-2 text-sm bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition font-mono text-slate-900 dark:text-white"
                        />
                        <InputError class="mt-1" :message="form.errors.phone" />
                    </div>

                    <div>
                        <label for="birth_place" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Tempat Lahir
                        </label>
                        <input
                            id="birth_place"
                            v-model="form.birth_place"
                            type="text"
                            placeholder="SUBANG"
                            class="w-full px-3.5 py-2 text-sm bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition uppercase text-slate-900 dark:text-white"
                        />
                        <InputError class="mt-1" :message="form.errors.birth_place" />
                    </div>

                    <div>
                        <label for="birth_date" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Tanggal Lahir
                        </label>
                        <input
                            id="birth_date"
                            v-model="form.birth_date"
                            type="date"
                            class="w-full px-3.5 py-2 text-sm bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition text-slate-900 dark:text-white"
                        />
                        <InputError class="mt-1" :message="form.errors.birth_date" />
                    </div>

                    <div>
                        <label for="semester" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Semester Saat Ini
                        </label>
                        <input
                            id="semester"
                            v-model.number="form.semester"
                            type="number"
                            min="1"
                            max="14"
                            placeholder="5"
                            class="w-full px-3.5 py-2 text-sm bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition text-slate-900 dark:text-white"
                        />
                        <InputError class="mt-1" :message="form.errors.semester" />
                    </div>

                    <div class="sm:col-span-2 lg:col-span-3">
                        <label for="address" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Alamat Domisili Lengkap
                        </label>
                        <textarea
                            id="address"
                            v-model="form.address"
                            rows="2"
                            placeholder="Jl. RA Kartini KM3 Subang, Jawa Barat"
                            class="w-full px-3.5 py-2 text-sm bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition text-slate-900 dark:text-white"
                        ></textarea>
                        <InputError class="mt-1" :message="form.errors.address" />
                    </div>
                </div>
            </div>

            <!-- Verification Alert jika belum verifikasi email -->
            <div v-if="mustVerifyEmail && user.email_verified_at === null" class="p-4 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 text-xs text-amber-800 dark:text-amber-200">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span>Email Anda belum terverifikasi.</span>
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="font-bold underline hover:text-amber-900 dark:hover:text-amber-100 ms-auto"
                    >
                        Kirim Ulang Email Verifikasi
                    </Link>
                </div>
                <div v-show="status === 'verification-link-sent'" class="mt-2 text-xs text-emerald-600 font-semibold">
                    Link verifikasi baru telah dikirimkan ke email Anda.
                </div>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-slate-700/60">
                <Transition
                    enter-active-class="transition ease-out duration-300"
                    enter-from-class="opacity-0 translate-y-1"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition ease-in duration-200"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div v-if="form.recentlySuccessful" class="flex items-center gap-1.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Perubahan profil berhasil disimpan.</span>
                    </div>
                    <div v-else></div>
                </Transition>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs sm:text-sm font-semibold shadow-xs transition-colors disabled:opacity-50"
                >
                    <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Profil' }}</span>
                </button>
            </div>
        </form>
    </section>
</template>
