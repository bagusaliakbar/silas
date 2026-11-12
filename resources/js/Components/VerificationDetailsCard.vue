<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    verification: {
        type: Object,
        required: true,
    },
    token: {
        type: String,
        default: '',
    },
    qrDataUri: {
        type: String,
        default: '',
    },
});

const doc = computed(() => props.verification?.document || {});
const certificate = computed(() => props.verification?.certificate_info);
const auditLogs = computed(() => props.verification?.audit_trail || []);

const signer = computed(() => {
    if (props.verification?.signer_info) return props.verification.signer_info;
    const lr = doc.value?.letter_request;
    const meta = doc.value?.metadata || {};
    const sigField = doc.value?.signature_fields?.[0];

    const name = lr?.signer_name || meta?.signer_name || sigField?.signer_name || (doc.value?.status === 'signed' ? 'Pejabat Penandatangan FASILKOM UNSUB' : null);
    const title = lr?.signer_title || meta?.signer_title || 'Pejabat Penandatangan';

    if (!name && doc.value?.status !== 'signed') return null;

    return {
        name: name || 'Pejabat Penandatangan FASILKOM UNSUB',
        title: title,
        signature_title: title,
        nip: '-',
        email: sigField?.signer_email || 'fasilkom@unsub.ac.id',
        institution: 'Fakultas Ilmu Komputer, Universitas Subang',
        signed_at: doc.value?.signed_at ? formatIndonesianDateTime(doc.value.signed_at, true) : null,
        status: doc.value?.status,
        is_valid: props.verification?.is_intact,
    };
});

const letter = computed(() => {
    if (props.verification?.letter_info) return props.verification.letter_info;
    const lr = doc.value?.letter_request;
    const meta = doc.value?.metadata || {};

    if (!lr && !meta?.letter_number) return null;

    return {
        letter_number: lr?.letter_number || meta?.letter_number || '-',
        letter_type: lr?.letter_type?.name || meta?.letter_type || 'Surat Akademik',
        student_name: lr?.student_name || meta?.student_name || doc.value?.user?.name || '-',
        npm: lr?.npm || meta?.npm || '-',
        study_program: lr?.study_program || 'Sistem Informasi',
        faculty: 'Fakultas Ilmu Komputer',
        institution: 'Universitas Subang',
        purpose: lr?.purpose || 'Kelengkapan Berkas Administrasi Akademik',
        extra_data: lr?.extra_data,
        created_at: lr?.created_at,
        signed_at: lr?.signed_at,
    };
});

const copiedKey = ref('');

const copyToClipboard = async (text, key) => {
    if (!text) return;
    try {
        if (navigator?.clipboard?.writeText) {
            await navigator.clipboard.writeText(text);
        } else {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
        }
        copiedKey.value = key;
        setTimeout(() => {
            copiedKey.value = '';
        }, 2000);
    } catch {
        // Fallback silently if clipboard blocked
    }
};

const indonesianMonths = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
];

const formatIndonesianDateTime = (val, withSeconds = true) => {
    if (!val) return null;

    if (typeof val === 'string') {
        const monthMap = {
            Jan: 'Januari',
            Feb: 'Februari',
            Mar: 'Maret',
            Apr: 'April',
            May: 'Mei',
            Jun: 'Juni',
            Jul: 'Juli',
            Aug: 'Agustus',
            Sep: 'September',
            Oct: 'Oktober',
            Okt: 'Oktober',
            Nov: 'November',
            Dec: 'Desember',
            Des: 'Desember',
        };

        let updated = val;
        for (const [abbr, full] of Object.entries(monthMap)) {
            const regex = new RegExp(`\\b${abbr}\\b`, 'g');
            if (regex.test(updated)) {
                updated = updated.replace(regex, full);
            }
        }
        if (updated !== val) {
            return updated;
        }

        const d = new Date(val);
        if (!isNaN(d.getTime())) {
            const day = String(d.getDate()).padStart(2, '0');
            const month = indonesianMonths[d.getMonth()];
            const year = d.getFullYear();
            const hours = String(d.getHours()).padStart(2, '0');
            const minutes = String(d.getMinutes()).padStart(2, '0');
            const seconds = String(d.getSeconds()).padStart(2, '0');
            const timeStr = withSeconds ? `${hours}:${minutes}:${seconds}` : `${hours}:${minutes}`;
            return `${day} ${month} ${year}, ${timeStr} WIB`;
        }
        return val;
    }

    if (val instanceof Date && !isNaN(val.getTime())) {
        const day = String(val.getDate()).padStart(2, '0');
        const month = indonesianMonths[val.getMonth()];
        const year = val.getFullYear();
        const hours = String(val.getHours()).padStart(2, '0');
        const minutes = String(val.getMinutes()).padStart(2, '0');
        const seconds = String(val.getSeconds()).padStart(2, '0');
        const timeStr = withSeconds ? `${hours}:${minutes}:${seconds}` : `${hours}:${minutes}`;
        return `${day} ${month} ${year}, ${timeStr} WIB`;
    }

    return String(val);
};

const formatFileSize = (bytes) => {
    if (!bytes || bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const getInitials = (name) => {
    if (!name) return 'UN';
    const clean = name.replace(/Dr\.|Ir\.|Prof\.|H\.|Drs\.|M\.Si|S\.Si|M\.Kom|S\.Kom|MT|M\.T\.|S\.T\.|,/gi, '').trim();
    const parts = clean.split(/\s+/).filter(Boolean);
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return (parts[0] ? parts[0].substring(0, 2) : 'TT').toUpperCase();
};

const hasExtraData = (extra) => {
    if (!extra || typeof extra !== 'object') return false;
    return Object.keys(extra).length > 0;
};

const formatExtraKey = (key) => {
    if (!key) return '';
    return key.replace(/_/g, ' ');
};

const filterExtraData = (extra) => {
    if (!extra || typeof extra !== 'object') return {};
    const filtered = {};
    for (const [k, v] of Object.entries(extra)) {
        if (v && typeof v !== 'object') {
            filtered[k] = v;
        }
    }
    return filtered;
};
</script>

<template>
    <div class="space-y-6">
        <!-- TIER 1: CORE VERIFICATION PAIR (PEJABAT PENANDATANGAN & KODE QR) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
            <!-- 1. KARTU PEJABAT PENANDATANGAN RESMI (Lg: Col Span 7) -->
            <div class="lg:col-span-7 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-6 shadow-xs flex flex-col justify-between relative overflow-hidden">
                <!-- Subtle decorative background gradient -->
                <div class="absolute -top-12 -right-12 w-44 h-44 bg-blue-500/5 dark:bg-blue-400/5 rounded-full blur-2xl pointer-events-none"></div>

                <div>
                    <!-- Header -->
                    <div class="flex items-center justify-between gap-3 pb-4 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white leading-tight">
                                    Pejabat Penandatangan Resmi (TTE)
                                </h3>
                                <p class="text-[11px] text-slate-400">
                                    Fakultas Ilmu Komputer • Universitas Subang
                                </p>
                            </div>
                        </div>

                        <span
                            v-if="verification.is_intact && doc?.status === 'signed'"
                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 shrink-0"
                        >
                            <svg class="w-3.5 h-3.5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span>Terverifikasi</span>
                        </span>
                        <span
                            v-else-if="!verification.is_intact"
                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 shrink-0"
                        >
                            ⚠️ Tidak Terverifikasi
                        </span>
                    </div>

                    <!-- Profile & Details -->
                    <div class="pt-5 flex flex-col sm:flex-row items-start sm:items-center gap-4">
                        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-700 text-white flex items-center justify-center font-black text-xl shadow-md shadow-blue-500/20 shrink-0">
                            {{ getInitials(signer?.name || 'Pejabat') }}
                        </div>

                        <div class="flex-1 min-w-0">
                            <h4 class="text-base sm:text-lg font-extrabold text-slate-900 dark:text-white tracking-tight leading-snug">
                                {{ signer?.name || 'Pejabat Penandatangan FASILKOM UNSUB' }}
                            </h4>
                            <p class="text-xs font-semibold text-blue-700 dark:text-blue-400 mt-0.5">
                                {{ signer?.title || 'Wakil Dekan / Pejabat Struktural' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Detailed metadata grid inside signer card -->
                <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800 grid grid-cols-1 sm:grid-cols-2 gap-3.5 text-xs">
                    <div>
                        <span class="text-slate-400 block text-[11px] font-medium">NIP / NIDN:</span>
                        <span class="font-mono font-bold text-slate-800 dark:text-slate-200 mt-0.5 block">
                            {{ signer?.nip || '-' }}
                        </span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px] font-medium">Email Resmi:</span>
                        <span class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5 block truncate" :title="signer?.email">
                            {{ signer?.email || 'fasilkom@unsub.ac.id' }}
                        </span>
                    </div>
                    <div class="sm:col-span-2">
                        <span class="text-slate-400 block text-[11px] font-medium">Waktu Pengesahan (Stempel TTE):</span>
                        <span class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5 block">
                            {{ formatIndonesianDateTime(signer?.signed_at || doc?.signed_at, true) || 'Belum Ditandatangani' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- 2. KARTU KODE QR & VERIFIKASI (Lg: Col Span 5) -->
            <div class="lg:col-span-5 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-6 shadow-xs flex flex-col justify-between items-center text-center">
                <!-- Header -->
                <div class="w-full flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 text-left">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                            </svg>
                        </div>
                        <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">
                            Kode QR
                        </h4>
                    </div>
                </div>

                <!-- QR Image in Frame -->
                <div class="my-3 p-3 bg-white rounded-2xl shadow-inner border border-slate-100 dark:border-slate-200 inline-block">
                    <img :src="qrDataUri" alt="QR Code Verifikasi" class="w-36 h-36 mx-auto object-contain" />
                </div>

                <!-- Token & Copy Button -->
                <div class="w-full">
                    <div class="flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 max-w-full">
                        <span class="text-[11px] font-mono text-slate-600 dark:text-slate-400 truncate max-w-[190px] select-all">
                            ID: {{ token }}
                        </span>
                        <button
                            type="button"
                            @click="copyToClipboard(token, 'token')"
                            class="p-1 rounded text-slate-400 hover:text-blue-600 transition"
                            title="Salin ID Token"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </button>
                    </div>
                    <p v-if="copiedKey === 'token'" class="text-[10px] text-emerald-600 font-bold mt-1">ID Token Tersalin!</p>
                </div>
            </div>
        </div>

        <!-- TIER 2: RINCIAN SURAT DINAS / AKADEMIK & MAHASISWA PEMOHON (FULL WIDTH) -->
        <div v-if="letter || doc?.letter_request || doc?.metadata?.letter_number" class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-6 sm:p-7 shadow-xs space-y-5">
            <!-- Header -->
            <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white leading-tight">
                            Rincian Surat & Mahasiswa Pemohon
                        </h3>
                        <p class="text-[11px] text-slate-400">
                            Data permohonan surat dinas / akademik mahasiswa
                        </p>
                    </div>
                </div>

                <div v-if="letter?.letter_number || doc?.letter_request?.letter_number" class="flex items-center gap-2">
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 font-mono text-xs font-bold border border-amber-200/70 dark:border-amber-800/70">
                        <span>No: {{ letter?.letter_number || doc?.letter_request?.letter_number }}</span>
                        <button
                            type="button"
                            @click="copyToClipboard(letter?.letter_number || doc?.letter_request?.letter_number, 'no_surat')"
                            class="p-0.5 rounded text-amber-700 dark:text-amber-300 hover:text-amber-900 transition"
                            title="Salin Nomor Surat"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </button>
                    </div>
                    <span v-if="copiedKey === 'no_surat'" class="text-[10px] text-emerald-600 font-bold">Tersalin!</span>
                </div>
            </div>

            <!-- Content Grid -->
            <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 text-xs">
                <div>
                    <dt class="text-slate-400 font-medium">Nomor Registrasi Surat:</dt>
                    <dd class="font-mono font-bold text-slate-900 dark:text-white mt-1 select-all">
                        {{ letter?.letter_number || doc?.letter_request?.letter_number || '-' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-slate-400 font-medium">Kategori / Jenis Surat:</dt>
                    <dd class="font-bold text-slate-800 dark:text-slate-200 mt-1">
                        {{ letter?.letter_type || doc?.letter_request?.letter_type?.name || doc?.metadata?.letter_type || 'Surat Akademik' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-slate-400 font-medium">Mahasiswa Pemohon:</dt>
                    <dd class="font-bold text-slate-900 dark:text-white mt-1">
                        {{ letter?.student_name || doc?.letter_request?.student_name || doc?.user?.name || '-' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-slate-400 font-medium">NPM & Program Studi:</dt>
                    <dd class="font-semibold text-slate-800 dark:text-slate-200 mt-1">
                        <span class="font-mono font-bold">{{ letter?.npm || doc?.letter_request?.npm || '-' }}</span>
                        <span class="text-slate-400 mx-1">•</span>
                        <span>{{ letter?.study_program || doc?.letter_request?.study_program || 'Sistem Informasi' }}</span>
                    </dd>
                </div>

                <div v-if="letter?.purpose || doc?.letter_request?.purpose" class="sm:col-span-2 lg:col-span-4">
                    <dt class="text-slate-400 font-medium">Perihal / Keperluan Surat:</dt>
                    <dd class="font-medium text-slate-800 dark:text-slate-200 mt-1 bg-slate-50 dark:bg-slate-950/70 p-3 rounded-2xl border border-slate-100 dark:border-slate-800/80 leading-relaxed">
                        {{ letter?.purpose || doc?.letter_request?.purpose }}
                    </dd>
                </div>

                <!-- Extra Data Pills -->
                <div v-if="hasExtraData(letter?.extra_data || doc?.letter_request?.extra_data)" class="sm:col-span-2 lg:col-span-4">
                    <dt class="text-slate-400 font-medium mb-1.5">Informasi Tambahan Pengajuan:</dt>
                    <div class="flex flex-wrap gap-2">
                        <div
                            v-for="(val, key) in filterExtraData(letter?.extra_data || doc?.letter_request?.extra_data)"
                            :key="key"
                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-slate-100 dark:bg-slate-800/80 text-[11px] text-slate-700 dark:text-slate-300"
                        >
                            <span class="text-slate-400 capitalize">{{ formatExtraKey(key) }}:</span>
                            <span class="font-semibold text-slate-900 dark:text-white">{{ val }}</span>
                        </div>
                    </div>
                </div>
            </dl>
        </div>

        <!-- TIER 3: DUAL BALANCED CARDS (SPESIFIKASI BERKAS & BUKTI KRIPTOGRAFIS) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-stretch">
            <!-- 3. KARTU SPESIFIKASI BERKAS & OTORITAS TTE -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-6 shadow-xs space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h4 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            Spesifikasi Berkas & Otoritas TTE
                        </h4>
                    </div>
                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300 font-mono font-bold">
                        PAdES-B-B
                    </span>
                </div>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-3 text-xs">
                    <div class="sm:col-span-2">
                        <dt class="text-slate-400 font-medium">Judul Dokumen:</dt>
                        <dd class="font-bold text-slate-800 dark:text-slate-200 mt-0.5">{{ doc?.title }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-slate-400 font-medium">Nama Berkas Asli:</dt>
                        <dd class="font-mono text-slate-800 dark:text-slate-200 mt-0.5 truncate select-all" :title="doc?.original_filename">
                            {{ doc?.original_filename }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 font-medium">Ukuran & Halaman:</dt>
                        <dd class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5">
                            {{ doc?.total_pages || 1 }} Halaman • {{ formatFileSize(doc?.file_size) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 font-medium">Pengunggah / Pemohon:</dt>
                        <dd class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5 truncate">
                            {{ doc?.user?.name || '-' }} <span class="text-slate-400 font-normal">({{ doc?.user?.email || '-' }})</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 font-medium">Mesin / Provider TTE:</dt>
                        <dd class="font-bold text-blue-600 dark:text-blue-400 mt-0.5 uppercase">
                            {{ doc?.psre_provider || 'Lokal PKI X.509' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 font-medium">Kepatuhan Regulasi:</dt>
                        <dd class="font-semibold text-slate-700 dark:text-slate-300 mt-0.5">
                            UU ITE & PP No. 71/2019
                        </dd>
                    </div>
                </dl>
            </div>

            <!-- 4. KARTU BUKTI KRIPTOGRAFIS & SIDIK JARI (HASH) -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-6 shadow-xs space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <h4 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            Bukti Kriptografis & Sidik Jari (Hash)
                        </h4>
                    </div>
                    <span class="text-[11px] font-mono text-slate-400">SHA-256</span>
                </div>

                <div class="space-y-3 text-xs">
                    <div>
                        <div class="flex items-center justify-between text-slate-500 font-medium mb-1">
                            <span>Sidik Jari SHA-256 Dokumen Asli:</span>
                            <button
                                v-if="doc?.sha256_original"
                                type="button"
                                @click="copyToClipboard(doc.sha256_original, 'sha_original')"
                                class="text-[11px] text-blue-600 hover:text-blue-700 transition"
                            >
                                <span v-if="copiedKey === 'sha_original'" class="text-emerald-600 font-bold">✓ Tersalin!</span>
                                <span v-else>Salin Hash</span>
                            </button>
                        </div>
                        <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 font-mono text-[11px] text-slate-700 dark:text-slate-300 break-all select-all border border-slate-100 dark:border-slate-800">
                            {{ doc?.sha256_original || '-' }}
                        </div>
                    </div>

                    <div v-if="doc?.sha256_signed">
                        <div class="flex items-center justify-between text-slate-500 font-medium mb-1">
                            <span>Sidik Jari SHA-256 Dokumen Bertanda Tangan:</span>
                            <button
                                type="button"
                                @click="copyToClipboard(doc.sha256_signed, 'sha_signed')"
                                class="text-[11px] text-blue-600 hover:text-blue-700 transition"
                            >
                                <span v-if="copiedKey === 'sha_signed'" class="text-emerald-600 font-bold">✓ Tersalin!</span>
                                <span v-else>Salin Hash</span>
                            </button>
                        </div>
                        <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 font-mono text-[11px] text-emerald-700 dark:text-emerald-300 break-all select-all border border-slate-100 dark:border-slate-800 font-bold">
                            {{ doc.sha256_signed }}
                        </div>
                    </div>

                    <div v-if="certificate?.signature_hash">
                        <div class="flex items-center justify-between text-slate-500 font-medium mb-1">
                            <span>Hash Tanda Tangan Kriptografis (Digital Signature):</span>
                            <button
                                type="button"
                                @click="copyToClipboard(certificate.signature_hash, 'sig_hash')"
                                class="text-[11px] text-blue-600 hover:text-blue-700 transition"
                            >
                                <span v-if="copiedKey === 'sig_hash'" class="text-emerald-600 font-bold">✓ Tersalin!</span>
                                <span v-else>Salin Hash</span>
                            </button>
                        </div>
                        <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 font-mono text-[11px] text-indigo-700 dark:text-indigo-300 break-all select-all border border-slate-100 dark:border-slate-800">
                            {{ certificate.signature_hash }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
