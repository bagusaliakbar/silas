<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import LetterPdfViewer from '@/Components/LetterPdfViewer.vue';

const props = defineProps({
    placeholders: {
        type: Array,
        required: true,
    },
    predefinedFields: {
        type: Array,
        default: () => [],
    },
    signers: {
        type: Array,
        default: () => [],
    },
    classifications: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    name: 'Surat Rekomendasi Prestasi & Beasiswa',
    letter_code: 'PD.1.2/FIK-US',
    description: 'Surat rekomendasi resmi dari pimpinan fakultas untuk pengajuan beasiswa atau keikutsertaan kompetisi/lomba tingkat nasional.',
    opening_template: 'Ketua Program Studi {program_studi} Fakultas Ilmu Komputer Universitas Subang, menerangkan bahwa :',
    content_template: 'Nama tersebut di atas adalah benar mahasiswa aktif Fakultas Ilmu Komputer Universitas Subang Program Studi {program_studi} Semester {semester} ({semester_huruf}) Tahun Akademik {tahun_akademik}. Yang bersangkutan memiliki rekam jejak akademik yang baik serta aktif berprestasi, dan surat rekomendasi ini diterbitkan untuk keperluan {keperluan} pada {tujuan_instansi}.',
    closing_template: 'Demikian surat rekomendasi ini dibuat dengan sebenarnya untuk dapat dipergunakan sebagaimana mestinya.',
    required_fields: ['target_agency', 'activity_name'],
    is_active: true,
});

// Custom fields created on-the-fly by admin
const customFields = ref([]);
const newCustomLabel = ref('');
const newCustomKey = ref('');

const addCustomField = () => {
    if (!newCustomLabel.value.trim()) return;
    const cleanKey = newCustomKey.value.trim()
        ? newCustomKey.value.trim().toLowerCase().replace(/[^a-z0-9_]/g, '_')
        : newCustomLabel.value.trim().toLowerCase().replace(/[^a-z0-9_]/g, '_');

    if (!form.required_fields.includes(cleanKey)) {
        form.required_fields.push(cleanKey);
    }

    customFields.value.push({
        key: cleanKey,
        label: newCustomLabel.value.trim(),
        tag: `{${cleanKey}}`,
        default: `Contoh ${newCustomLabel.value.trim()}`,
    });

    newCustomLabel.value = '';
    newCustomKey.value = '';
    triggerDebouncedSync();
};

const removeCustomField = (index) => {
    const field = customFields.value[index];
    if (field) {
        const idx = form.required_fields.indexOf(field.key);
        if (idx !== -1) {
            form.required_fields.splice(idx, 1);
        }
    }
    customFields.value.splice(index, 1);
    triggerDebouncedSync();
};

const toggleField = (key) => {
    const idx = form.required_fields.indexOf(key);
    if (idx !== -1) {
        form.required_fields.splice(idx, 1);
    } else {
        form.required_fields.push(key);
    }
};

// Quick Insert Tag
const insertTag = (tag) => {
    form.content_template += (form.content_template.length > 0 ? ' ' : '') + tag;
};

// LIVE PDF PREVIEW SYNC LOGIC
let syncTimeout = null;
const isLiveSyncing = ref(false);
const previewPdfBase64 = ref(null);
const previewErrorMessage = ref(null);
const lastSyncedAt = ref(null);

const fetchLivePreview = async () => {
    isLiveSyncing.value = true;
    previewErrorMessage.value = null;

    try {
        const res = await window.axios.post(route('letter-templates.preview-new'), {
            name: form.name,
            letter_code: form.letter_code,
            opening_template: form.opening_template,
            content_template: form.content_template,
            closing_template: form.closing_template,
            required_fields: form.required_fields,
            custom_fields: customFields.value,
        });

        if (res.data?.success && res.data?.pdfBase64) {
            previewPdfBase64.value = res.data.pdfBase64;
            lastSyncedAt.value = new Date().toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
            });
        } else {
            previewErrorMessage.value = res.data?.message || 'Format pratinjau tidak valid.';
        }
    } catch (e) {
        console.error('Error fetching preview:', e);
        previewErrorMessage.value = e.response?.data?.message || 'Terjadi kesalahan sistem saat memproses pratinjau surat.';
    } finally {
        isLiveSyncing.value = false;
    }
};

const triggerDebouncedSync = () => {
    clearTimeout(syncTimeout);
    syncTimeout = setTimeout(() => {
        fetchLivePreview();
    }, 500);
};

watch(
    [
        () => form.name,
        () => form.letter_code,
        () => form.opening_template,
        () => form.content_template,
        () => form.closing_template,
        () => form.required_fields,
    ],
    () => {
        triggerDebouncedSync();
    },
    { deep: true }
);

onMounted(() => {
    fetchLivePreview();
});

// Group predefined fields by category
const fieldCategories = computed(() => {
    const cats = {};
    props.predefinedFields.forEach((field) => {
        if (!cats[field.category]) {
            cats[field.category] = [];
        }
        cats[field.category].push(field);
    });
    return cats;
});

const submit = () => {
    form.post(route('letter-templates.store'));
};
</script>

<template>
    <Head title="Tambah Template Surat" />

    <AuthenticatedLayout>
        <div class="py-4 sm:py-6">
            <div class="w-full px-3 sm:px-6 lg:px-8 space-y-4">
                <!-- TOP HEADER BAR -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 p-4 shadow-xs flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <Link
                            :href="route('letter-templates.index')"
                            class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 flex items-center justify-center text-slate-600 dark:text-slate-300 transition"
                            title="Kembali"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </Link>
                        <div>
                            <div class="flex items-center gap-2">
                                <h1 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">
                                    Rancang Format Surat Baru
                                </h1>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-100 text-blue-700 dark:bg-blue-900/60 dark:text-blue-300">
                                    Custom Template Builder
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Buat format surat baru dengan penyesuaian kolom isian, kalimat narasi, dan live PDF preview.
                            </p>
                        </div>
                    </div>

                    <!-- Sync Status & Action Buttons -->
                    <div class="flex items-center gap-2.5">
                        <div class="px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 flex items-center gap-2 text-xs">
                            <span :class="['w-2 h-2 rounded-full', isLiveSyncing ? 'bg-amber-400 animate-ping' : 'bg-emerald-500']"></span>
                            <span class="text-slate-700 dark:text-slate-300 font-medium">
                                {{ isLiveSyncing ? 'Menyinkronkan...' : (lastSyncedAt ? `Tersinkron: ${lastSyncedAt}` : 'Siap') }}
                            </span>
                        </div>

                        <button
                            type="button"
                            @click="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-white bg-blue-600 hover:bg-blue-500 shadow-md shadow-blue-950/20 active:scale-95 transition"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Simpan Format Surat</span>
                        </button>
                    </div>
                </div>

                <!-- MAIN SPLIT WORKSPACE -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
                    <!-- LEFT COLUMN: BUILDER FORM (5/12) -->
                    <div class="lg:col-span-5 space-y-4">
                        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 p-5 shadow-xs space-y-5">
                            <!-- STEP 1: IDENTITAS FORMAT SURAT -->
                            <div class="space-y-3">
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300 flex items-center justify-center text-[11px] font-bold">1</span>
                                    <span>Identitas & Judul Surat Resmi</span>
                                </h3>

                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                        Nama / Judul Format Surat *
                                    </label>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        required
                                        placeholder="Surat Rekomendasi Beasiswa Prestasi"
                                        class="w-full text-xs sm:text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-semibold text-slate-900 dark:text-white"
                                    />
                                    <p v-if="form.errors.name" class="text-rose-500 text-[11px] mt-1">{{ form.errors.name }}</p>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                            Kode Klasifikasi Surat *
                                        </label>
                                        <select
                                            v-if="classifications && classifications.length"
                                            v-model="form.letter_code"
                                            required
                                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-mono text-slate-900 dark:text-white"
                                        >
                                            <option v-for="c in classifications" :key="c.id" :value="c.code">
                                                {{ c.code }} - {{ c.name }}
                                            </option>
                                        </select>
                                        <input
                                            v-else
                                            v-model="form.letter_code"
                                            type="text"
                                            required
                                            placeholder="PD.1.2/FIK-US"
                                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-mono text-slate-900 dark:text-white"
                                        />
                                    </div>
                                    <div class="flex items-center gap-2 pt-6">
                                        <label class="inline-flex items-center gap-2 cursor-pointer">
                                            <input
                                                v-model="form.is_active"
                                                type="checkbox"
                                                class="rounded-md border-slate-300 text-blue-600 focus:ring-blue-500"
                                            />
                                            <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Format Aktif Siap Pakai</span>
                                        </label>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                        Keterangan / Fungsi Surat
                                    </label>
                                    <textarea
                                        v-model="form.description"
                                        rows="2"
                                        placeholder="Penjelasan singkat mengenai peruntukan surat ini..."
                                        class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 text-slate-900 dark:text-white"
                                    ></textarea>
                                </div>
                            </div>

                            <!-- STEP 2: PENYESUAIAN KOLOM ISIAN (CUSTOM FIELDS SELECTOR) -->
                            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 space-y-3">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-2">
                                        <span class="w-5 h-5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300 flex items-center justify-center text-[11px] font-bold">2</span>
                                        <span>Penyesuaian Kolom Isian Dokumen</span>
                                    </h3>
                                    <span class="text-[10px] text-blue-600 dark:text-blue-400 font-bold">
                                        {{ form.required_fields.length }} Kolom Aktif
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500 leading-relaxed">
                                    Centang kolom isian yang perlu diisi pada saat surat ini dibuat. Tag variabel yang dicentang dapat Anda sisipkan pada kalimat narasi di Langkah 3.
                                </p>

                                <!-- Categories of fields -->
                                <div class="space-y-3">
                                    <div
                                        v-for="(fields, catName) in fieldCategories"
                                        :key="catName"
                                        class="p-3 rounded-xl bg-slate-50/70 dark:bg-slate-800/40 border border-slate-200/70 dark:border-slate-700/60 space-y-2"
                                    >
                                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            {{ catName }}
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                            <div
                                                v-for="field in fields"
                                                :key="field.key"
                                                @click="toggleField(field.key)"
                                                :class="[
                                                    'p-2 rounded-lg border text-xs cursor-pointer transition select-none flex items-start gap-2',
                                                    form.required_fields.includes(field.key)
                                                        ? 'bg-blue-50 dark:bg-blue-950/60 border-blue-400 text-blue-900 dark:text-blue-200 font-semibold shadow-2xs'
                                                        : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-100'
                                                ]"
                                            >
                                                <input
                                                    type="checkbox"
                                                    :checked="form.required_fields.includes(field.key)"
                                                    class="rounded-sm border-slate-300 text-blue-600 mt-0.5"
                                                    @click.stop="toggleField(field.key)"
                                                />
                                                <div class="flex-1 min-w-0">
                                                    <span class="block truncate leading-tight">{{ field.label }}</span>
                                                    <code class="text-[10px] font-mono text-blue-600 dark:text-blue-400">{{ field.tag }}</code>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Added Custom Fields -->
                                    <div v-if="customFields.length > 0" class="p-3 rounded-xl bg-purple-50/70 dark:bg-purple-950/40 border border-purple-200/70 dark:border-purple-800/60 space-y-2">
                                        <div class="text-[10px] font-bold uppercase tracking-wider text-purple-700 dark:text-purple-300">
                                            Kolom Tambahan Kustom Buatan Anda:
                                        </div>
                                        <div class="space-y-1.5">
                                            <div
                                                v-for="(cf, idx) in customFields"
                                                :key="cf.key"
                                                class="flex items-center justify-between p-2 rounded-lg bg-white dark:bg-slate-800 border border-purple-200 dark:border-purple-700 text-xs"
                                            >
                                                <div class="flex items-center gap-2">
                                                    <span class="font-bold text-slate-800 dark:text-slate-200">{{ cf.label }}</span>
                                                    <code class="px-1.5 py-0.2 rounded font-mono text-[10px] bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300">{{ cf.tag }}</code>
                                                </div>
                                                <button
                                                    type="button"
                                                    @click="removeCustomField(idx)"
                                                    class="text-rose-500 hover:text-rose-700 text-xs font-bold"
                                                >
                                                    Hapus
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Add New Custom Field Form -->
                                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-dashed border-slate-300 dark:border-slate-700 space-y-2">
                                        <span class="text-[11px] font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                            + Buat Kolom Isian Tambahan Baru:
                                        </span>
                                        <div class="flex flex-col sm:flex-row gap-2">
                                            <input
                                                v-model="newCustomLabel"
                                                type="text"
                                                placeholder="Label kolom (misal: Lokasi KKN)"
                                                class="flex-1 text-xs rounded-lg border border-slate-200 dark:border-slate-700 p-2 bg-white dark:bg-slate-900"
                                            />
                                            <input
                                                v-model="newCustomKey"
                                                type="text"
                                                placeholder="Tag (misal: lokasi_kkn)"
                                                class="w-36 text-xs font-mono rounded-lg border border-slate-200 dark:border-slate-700 p-2 bg-white dark:bg-slate-900"
                                            />
                                            <button
                                                type="button"
                                                @click="addCustomField"
                                                class="px-3 py-2 rounded-lg text-xs font-bold text-white bg-slate-800 hover:bg-slate-700 dark:bg-slate-700 dark:hover:bg-slate-600 transition shrink-0"
                                            >
                                                Tambah
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- STEP 3: REDAKSI NARASI TEMPLATE -->
                            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 space-y-3">
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300 flex items-center justify-center text-[11px] font-bold">3</span>
                                    <span>Redaksi Kalimat & Paragraf Inti</span>
                                </h3>

                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                        Kalimat Pembuka
                                    </label>
                                    <input
                                        v-model="form.opening_template"
                                        type="text"
                                        class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 text-slate-900 dark:text-white"
                                    />
                                </div>

                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="text-[11px] font-semibold text-slate-600 dark:text-slate-400">
                                            Paragraf Pernyataan Inti *
                                        </label>
                                        <span class="text-[10px] text-slate-400">Klik tag di bawah untuk menyisipkan</span>
                                    </div>

                                    <!-- Quick Insert Tags Buttons -->
                                    <div class="flex flex-wrap gap-1 mb-2">
                                        <button
                                            v-for="p in placeholders.slice(0, 10)"
                                            :key="p.tag"
                                            type="button"
                                            @click="insertTag(p.tag)"
                                            class="px-2 py-0.5 rounded-md font-mono text-[10px] font-semibold bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300 border border-blue-200 dark:border-blue-800 hover:bg-blue-100 transition"
                                            :title="`Sisipkan ${p.tag}`"
                                        >
                                            + {{ p.tag }}
                                        </button>
                                    </div>

                                    <textarea
                                        v-model="form.content_template"
                                        rows="5"
                                        required
                                        class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-sans leading-relaxed text-slate-900 dark:text-white"
                                    ></textarea>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                        Kalimat Penutup
                                    </label>
                                    <input
                                        v-model="form.closing_template"
                                        type="text"
                                        class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 text-slate-900 dark:text-white"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN: LIVE PDF CANVAS PREVIEW (7/12) -->
                    <div class="lg:col-span-7 sticky top-4 space-y-3">
                        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 p-4 shadow-xs flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                <h3 class="font-bold text-sm text-slate-900 dark:text-white">
                                    Simulasi Pratinjau Cetak PDF Format Baru
                                </h3>
                            </div>
                            <div class="text-xs text-slate-500">
                                Ber-kop Resmi Universitas Subang
                            </div>
                        </div>

                        <!-- Embedded PDF Viewer Container -->
                        <div class="rounded-2xl border border-slate-200/90 dark:border-slate-800 bg-white dark:bg-slate-900 p-2 shadow-xs h-[78vh] overflow-hidden flex flex-col">
                            <LetterPdfViewer
                                :pdfBase64="previewPdfBase64"
                                :isLoading="isLiveSyncing"
                                :errorMessage="previewErrorMessage"
                                :initialScale="1.0"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

