<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const form = useForm({
    title: '',
    pdf_file: null,
    signing_method: 'visual_and_qr',
    psre_provider: 'local',
});

const isDragging = ref(false);
const fileInput = ref(null);
const selectedFileName = ref('');
const selectedFileSize = ref('');

const onFileSelect = (event) => {
    const file = event.target.files[0];
    handleFile(file);
};

const onDrop = (event) => {
    isDragging.value = false;
    const file = event.dataTransfer.files[0];
    handleFile(file);
};

const handleFile = (file) => {
    if (!file) return;

    if (file.type !== 'application/pdf' && !file.name.endsWith('.pdf')) {
        alert('File harus berformat PDF (.pdf)');
        return;
    }

    if (file.size > 20 * 1024 * 1024) {
        alert('Ukuran file maksimal adalah 20MB');
        return;
    }

    form.pdf_file = file;
    selectedFileName.value = file.name;
    selectedFileSize.value = (file.size / (1024 * 1024)).toFixed(2) + ' MB';

    if (!form.title) {
        form.title = file.name.replace(/\.pdf$/i, '');
    }
};

const submit = () => {
    form.post(route('documents.store'), {
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="Unggah Dokumen PDF" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link
                    :href="route('documents.index')"
                    class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <div>
                    <h2 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">
                        Unggah Dokumen PDF
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Unggah berkas untuk mulai membubuhkan tanda tangan elektronik dan verifikasi integritas
                    </p>
                </div>
            </div>
        </template>

        <div class="w-full px-4 sm:px-6 lg:px-8">
            <form @submit.prevent="submit" class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 sm:p-8 shadow-xs space-y-6">
                <!-- Title Field -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">
                        Judul Dokumen <span class="text-rose-500">*</span>
                    </label>
                    <input
                        v-model="form.title"
                        type="text"
                        placeholder="Perjanjian Kerja Sama Vendor 2026"
                        required
                        class="w-full px-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                    />
                    <div v-if="form.errors.title" class="text-xs text-rose-500 mt-1">{{ form.errors.title }}</div>
                </div>

                <!-- Drag and Drop PDF Zone -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">
                        Berkas Dokumen PDF <span class="text-rose-500">*</span>
                    </label>
                    <div
                        @dragover.prevent="isDragging = true"
                        @dragleave.prevent="isDragging = false"
                        @drop.prevent="onDrop"
                        @click="fileInput.click()"
                        :class="[
                            'border-2 border-dashed rounded-2xl p-8 text-center cursor-pointer transition flex flex-col items-center justify-center',
                            isDragging
                                ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-950/50'
                                : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50 hover:border-slate-300 dark:hover:border-slate-700'
                        ]"
                    >
                        <input
                            ref="fileInput"
                            type="file"
                            accept="application/pdf"
                            class="hidden"
                            @change="onFileSelect"
                        />

                        <!-- Selected File Preview -->
                        <div v-if="selectedFileName" class="flex flex-col items-center">
                            <div class="w-14 h-14 rounded-2xl bg-indigo-100 dark:bg-indigo-950 text-indigo-600 flex items-center justify-center font-bold text-lg mb-2">
                                PDF
                            </div>
                            <span class="font-bold text-sm text-slate-900 dark:text-white">{{ selectedFileName }}</span>
                            <span class="text-xs text-slate-400 mt-0.5">{{ selectedFileSize }}</span>
                            <span class="mt-3 text-xs text-indigo-600 font-semibold hover:underline">Klik untuk mengganti berkas</span>
                        </div>

                        <!-- Empty State Drag Drop -->
                        <div v-else class="flex flex-col items-center">
                            <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mb-3">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                            </div>
                            <div class="text-sm font-bold text-slate-800 dark:text-slate-200">
                                Tarik & lepaskan file PDF di sini, atau <span class="text-indigo-600 underline">pilih dari komputer</span>
                            </div>
                            <p class="text-xs text-slate-400 mt-1">Maksimal ukuran file: 20MB (Hanya format PDF)</p>
                        </div>
                    </div>
                    <div v-if="form.errors.pdf_file" class="text-xs text-rose-500 mt-1">{{ form.errors.pdf_file }}</div>
                </div>

                <!-- Signing Mode / Category -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">
                        Metode Penandatanganan Utama
                    </label>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <label
                            :class="[
                                'p-4 rounded-xl border cursor-pointer transition flex flex-col justify-between',
                                form.signing_method === 'visual_and_qr'
                                    ? 'border-indigo-600 ring-2 ring-indigo-500/20 bg-indigo-50/20'
                                    : 'border-slate-200 dark:border-slate-800 hover:border-slate-300'
                            ]"
                        >
                            <div class="flex items-center gap-2">
                                <input
                                    type="radio"
                                    v-model="form.signing_method"
                                    value="visual_and_qr"
                                    class="text-indigo-600 focus:ring-indigo-500"
                                />
                                <span class="text-xs font-bold text-slate-900 dark:text-white">Visual & QR Verifikasi</span>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-2 leading-relaxed">
                                Coretan canvas, stempel, dan QR Code verifikasi publik otomatis.
                            </p>
                        </label>

                        <label
                            :class="[
                                'p-4 rounded-xl border cursor-pointer transition flex flex-col justify-between',
                                form.signing_method === 'cryptographic_pki'
                                    ? 'border-indigo-600 ring-2 ring-indigo-500/20 bg-indigo-50/20'
                                    : 'border-slate-200 dark:border-slate-800 hover:border-slate-300'
                            ]"
                        >
                            <div class="flex items-center gap-2">
                                <input
                                    type="radio"
                                    v-model="form.signing_method"
                                    value="cryptographic_pki"
                                    class="text-indigo-600 focus:ring-indigo-500"
                                />
                                <span class="text-xs font-bold text-slate-900 dark:text-white">Kriptografi PKI X.509</span>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-2 leading-relaxed">
                                Standar PAdES / Adobe Acrobat dengan sertifikat digital RSA & SHA-256.
                            </p>
                        </label>

                        <label
                            :class="[
                                'p-4 rounded-xl border cursor-pointer transition flex flex-col justify-between',
                                form.signing_method === 'third_party_psre'
                                    ? 'border-indigo-600 ring-2 ring-indigo-500/20 bg-indigo-50/20'
                                    : 'border-slate-200 dark:border-slate-800 hover:border-slate-300'
                            ]"
                        >
                            <div class="flex items-center gap-2">
                                <input
                                    type="radio"
                                    v-model="form.signing_method"
                                    value="third_party_psre"
                                    class="text-indigo-600 focus:ring-indigo-500"
                                />
                                <span class="text-xs font-bold text-slate-900 dark:text-white">Eksklusif PSrE Vendor</span>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-2 leading-relaxed">
                                Integrasi ekosistem resmi (Privy, Peruri BUMN, atau DocuSign).
                            </p>
                        </label>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <Link
                        :href="route('documents.index')"
                        class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition"
                    >
                        Batal
                    </Link>

                    <button
                        type="submit"
                        :disabled="form.processing || !form.pdf_file"
                        class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm shadow-indigo-600/30 transition hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50"
                    >
                        <span v-if="form.processing">Mengunggah & Memproses...</span>
                        <span v-else>Lanjutkan ke Studio E-Sign →</span>
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
