<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    certificates: Array,
});

const showGenerateModal = ref(false);
const showImportModal = ref(false);

const generateForm = useForm({
    name: '',
    organization: 'Universitas Subang',
    organizational_unit: 'Fakultas Ilmu Komputer',
    email: '',
    country: 'ID',
    days_valid: 365,
    passphrase: '',
});

const importForm = useForm({
    name: '',
    p12_file: null,
    passphrase: '',
});

const submitGenerate = () => {
    generateForm.post(route('certificates.store'), {
        onSuccess: () => {
            showGenerateModal.value = false;
            generateForm.reset();
        },
    });
};

const submitImport = () => {
    importForm.post(route('certificates.import'), {
        forceFormData: true,
        onSuccess: () => {
            showImportModal.value = false;
            importForm.reset();
        },
    });
};

const setDefaultCert = (cert) => {
    router.post(route('certificates.default', cert.id));
};

const deleteCert = (cert) => {
    if (!confirm(`Hapus sertifikat "${cert.name}"? File sertifikat dan private key akan dihapus.`)) return;
    router.delete(route('certificates.destroy', cert.id));
};
</script>

<template>
    <Head title="Sertifikat Digital (TTE)" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">
                        Sertifikat Digital (PKI X.509)
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Kelola kunci kriptografi dan identitas digital resmi Anda untuk tanda tangan PAdES
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="showImportModal = true"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 transition"
                    >
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        Impor .P12 / .PFX
                    </button>
                    <button
                        type="button"
                        @click="showGenerateModal = true"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm shadow-indigo-600/30 transition hover:scale-[1.02] active:scale-[0.98]"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Terbitkan Sertifikat Baru
                    </button>
                </div>
            </div>
        </template>

        <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- INFO BANNER -->
            <div class="p-5 rounded-2xl bg-gradient-to-r from-blue-900/10 via-blue-800/10 to-transparent border border-blue-200/60 dark:border-blue-900/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-100 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white">Tentang Sertifikat X.509 & PAdES</h4>
                        <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">
                            Sertifikat digital menggunakan kunci asimetris RSA 2048-bit dan hash SHA-256. Dokumen PDF yang disegel dengan sertifikat ini memiliki sifat nir-penyangkalan (*non-repudiation*) dan terdeteksi rusak (*tamper-evident*) jika diubah setelah ditandatangani.
                        </p>
                    </div>
                </div>
            </div>

            <!-- CERTIFICATE CARDS GRID -->
            <div v-if="certificates.length === 0" class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-16 text-center">
                <div class="w-16 h-16 mx-auto rounded-2xl bg-indigo-50 dark:bg-indigo-950 text-indigo-600 flex items-center justify-center mb-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-800 dark:text-slate-200">Belum ada Sertifikat Digital</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1 mb-4">
                    Buat sertifikat digital X.509 gratis sekarang atau impor sertifikat .p12 milik instansi Anda.
                </p>
                <button
                    type="button"
                    @click="showGenerateModal = true"
                    class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition"
                >
                    Terbitkan Sertifikat Sekarang
                </button>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <div
                    v-for="cert in certificates"
                    :key="cert.id"
                    class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-5 shadow-xs flex flex-col justify-between hover:border-indigo-400 transition"
                >
                    <div>
                        <!-- Top Badges -->
                        <div class="flex items-center justify-between mb-3">
                            <span
                                :class="[
                                    'px-2.5 py-0.5 rounded-full text-[10px] font-bold',
                                    cert.is_active
                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                        : 'bg-rose-100 text-rose-700'
                                ]"
                            >
                                {{ cert.is_active ? '✓ Aktif' : 'Kedaluwarsa' }}
                            </span>
                            <span v-if="cert.is_default" class="text-[10px] font-bold text-indigo-600 bg-indigo-50 dark:bg-indigo-950 px-2 py-0.5 rounded-md">
                                ★ Default Signer
                            </span>
                        </div>

                        <!-- Certificate Info -->
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div class="overflow-hidden">
                                <h4 class="font-bold text-sm text-slate-900 dark:text-white truncate">{{ cert.name }}</h4>
                                <p class="text-xs text-slate-500 truncate">{{ cert.organization || 'Personal Signer' }}</p>
                            </div>
                        </div>

                        <!-- Technical Details -->
                        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 space-y-1.5 text-[11px]">
                            <div class="flex justify-between">
                                <span class="text-slate-400">Penerbit (CA):</span>
                                <span class="font-semibold text-slate-700 dark:text-slate-300 truncate max-w-[150px]">{{ cert.issuer }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400">Nomor Seri:</span>
                                <span class="font-mono text-slate-600 dark:text-slate-300 truncate max-w-[150px]">#{{ cert.serial_number }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400">Masa Berlaku:</span>
                                <span class="text-slate-600 dark:text-slate-300">
                                    s/d {{ new Date(cert.valid_to).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="mt-5 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            <a
                                :href="route('certificates.download', cert.id) + '?type=crt'"
                                class="px-2 py-1 text-[11px] font-semibold text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950 rounded-lg transition"
                                title="Unduh Sertifikat Publik .CRT"
                            >
                                Unduh .CRT
                            </a>
                            <a
                                v-if="cert.p12_path"
                                :href="route('certificates.download', cert.id) + '?type=p12'"
                                class="px-2 py-1 text-[11px] font-semibold text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950 rounded-lg transition"
                                title="Unduh Bundle Kriptografi .P12"
                            >
                                Unduh .P12
                            </a>
                        </div>

                        <div class="flex items-center gap-1">
                            <button
                                v-if="!cert.is_default"
                                @click="setDefaultCert(cert)"
                                class="px-2 py-1 text-[11px] font-semibold text-slate-500 hover:text-slate-800 transition"
                            >
                                Jadikan Default
                            </button>
                            <button
                                @click="deleteCert(cert)"
                                class="p-1 text-slate-400 hover:text-rose-600 transition"
                                title="Hapus Sertifikat"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL GENERATE CERTIFICATE -->
        <Modal :show="showGenerateModal" @close="showGenerateModal = false" max-width="md">
            <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl">
                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-1">Terbitkan Sertifikat X.509</h3>
                <p class="text-xs text-slate-500 mb-4">Buat pasangan kunci RSA 2048-bit dan sertifikat digital self-signed</p>

                <form @submit.prevent="submitGenerate" class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Lengkap / Common Name (CN) *</label>
                        <input
                            v-model="generateForm.name"
                            type="text"
                            required
                            placeholder="Program Studi Sistem Informasi"
                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Organisasi / Perusahaan</label>
                            <input
                                v-model="generateForm.organization"
                                type="text"
                                placeholder="Universitas Subang"
                                class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Divisi / Unit Kerja</label>
                            <input
                                v-model="generateForm.organizational_unit"
                                type="text"
                                placeholder="Fakultas Ilmu Komputer"
                                class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Email</label>
                            <input
                                v-model="generateForm.email"
                                type="email"
                                placeholder="kaprodi.si@unsub.ac.id"
                                class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Masa Berlaku (Hari)</label>
                            <input
                                v-model="generateForm.days_valid"
                                type="number"
                                min="30"
                                max="3650"
                                class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Kata Sandi / Passphrase Enkripsi (Opsional)</label>
                        <input
                            v-model="generateForm.passphrase"
                            type="password"
                            placeholder="Biarkan kosong jika tanpa kata sandi"
                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                        />
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3">
                        <button
                            type="button"
                            @click="showGenerateModal = false"
                            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="generateForm.processing"
                            class="px-5 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-sm transition disabled:opacity-50"
                        >
                            <span v-if="generateForm.processing">Menerbitkan...</span>
                            <span v-else>Terbitkan Sertifikat</span>
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- MODAL IMPORT CERTIFICATE -->
        <Modal :show="showImportModal" @close="showImportModal = false" max-width="md">
            <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl">
                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-1">Impor Sertifikat (.P12 / .PFX)</h3>
                <p class="text-xs text-slate-500 mb-4">Unggah sertifikat digital PKCS#12 yang telah Anda miliki</p>

                <form @submit.prevent="submitImport" class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Tampilan Sertifikat *</label>
                        <input
                            v-model="importForm.name"
                            type="text"
                            required
                            placeholder="Sertifikat Resmi PT XYZ"
                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">File .P12 atau .PFX *</label>
                        <input
                            type="file"
                            accept=".p12,.pfx"
                            required
                            @change="(e) => importForm.p12_file = e.target.files[0]"
                            class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Kata Sandi / Passphrase Sertifikat</label>
                        <input
                            v-model="importForm.passphrase"
                            type="password"
                            placeholder="Masukkan kata sandi jika file dienkripsi"
                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                        />
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3">
                        <button
                            type="button"
                            @click="showImportModal = false"
                            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="importForm.processing"
                            class="px-5 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-sm transition disabled:opacity-50"
                        >
                            <span v-if="importForm.processing">Mengimpor...</span>
                            <span v-else>Impor Sertifikat</span>
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
