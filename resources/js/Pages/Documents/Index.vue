<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    documents: Object,
    filters: Object,
    stats: Object,
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'all');

const handleFilter = () => {
    router.get(
        route('documents.index'),
        {
            search: search.value || undefined,
            status: status.value !== 'all' ? status.value : undefined,
        },
        { preserveState: true, replace: true }
    );
};

const copyHash = (hash) => {
    navigator.clipboard.writeText(hash);
    alert('Hash SHA-256 berhasil disalin ke papan klip!');
};

const deleteDoc = (doc) => {
    if (!confirm(`Hapus dokumen "${doc.title}"?`)) return;
    router.delete(route('documents.destroy', doc.uuid));
};

const formatBytes = (bytes) => {
    if (!bytes || bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
};
</script>

<template>
    <Head title="Dokumen Saya" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">
                        Dokumen Saya
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Kelola dan pantau seluruh dokumen tanda tangan elektronik Anda
                    </p>
                </div>
                <Link
                    :href="route('documents.create')"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-600/30 transition-all hover:scale-[1.02] active:scale-[0.98]"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Unggah Dokumen Baru
                </Link>
            </div>
        </template>

        <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- STATS CARDS -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Documents -->
                <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-900 dark:text-white">{{ stats.total }}</div>
                        <div class="text-xs font-medium text-slate-500">Total Dokumen</div>
                    </div>
                </div>

                <!-- Signed Documents -->
                <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-900 dark:text-white">{{ stats.signed }}</div>
                        <div class="text-xs font-medium text-slate-500">Sudah Ditandatangani</div>
                    </div>
                </div>

                <!-- Drafts -->
                <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-900 dark:text-white">{{ stats.draft }}</div>
                        <div class="text-xs font-medium text-slate-500">Draft / Menunggu</div>
                    </div>
                </div>

                <!-- Certificates -->
                <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-900 dark:text-white">{{ stats.certificates_count }}</div>
                        <div class="text-xs font-medium text-slate-500">Sertifikat Digital (PKI)</div>
                    </div>
                </div>
            </div>

            <!-- FILTER & SEARCH BAR -->
            <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-2 flex-1 max-w-md">
                    <div class="relative w-full">
                        <svg class="w-4 h-4 absolute left-3.5 top-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            v-model="search"
                            @keyup.enter="handleFilter"
                            type="text"
                            placeholder="Cari berdasarkan judul dokumen..."
                            class="w-full pl-9 pr-4 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>
                    <button
                        @click="handleFilter"
                        class="px-3 py-2 text-xs font-semibold rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 transition"
                    >
                        Cari
                    </button>
                </div>

                <div class="flex items-center gap-1.5 p-1 bg-slate-100 dark:bg-slate-800 rounded-xl">
                    <button
                        @click="status = 'all'; handleFilter()"
                        :class="[
                            'px-3 py-1.5 text-xs font-semibold rounded-lg transition-all',
                            status === 'all'
                                ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-300 shadow-xs'
                                : 'text-slate-500 hover:text-slate-800'
                        ]"
                    >
                        Semua
                    </button>
                    <button
                        @click="status = 'signed'; handleFilter()"
                        :class="[
                            'px-3 py-1.5 text-xs font-semibold rounded-lg transition-all',
                            status === 'signed'
                                ? 'bg-white dark:bg-slate-700 text-emerald-600 dark:text-emerald-300 shadow-xs'
                                : 'text-slate-500 hover:text-slate-800'
                        ]"
                    >
                        Ditandatangani
                    </button>
                    <button
                        @click="status = 'draft'; handleFilter()"
                        :class="[
                            'px-3 py-1.5 text-xs font-semibold rounded-lg transition-all',
                            status === 'draft'
                                ? 'bg-white dark:bg-slate-700 text-amber-600 dark:text-amber-300 shadow-xs'
                                : 'text-slate-500 hover:text-slate-800'
                        ]"
                    >
                        Draft
                    </button>
                </div>
            </div>

            <!-- DOCUMENTS TABLE / CARDS -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
                <div v-if="documents.data.length === 0" class="py-16 px-4 text-center">
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 flex items-center justify-center mb-3">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-800 dark:text-slate-200">Belum ada dokumen</h3>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1 mb-4">
                        Unggah berkas PDF pertama Anda untuk mulai menaruh tanda tangan elektronik dan menyegelnya dengan sertifikat digital.
                    </p>
                    <Link
                        :href="route('documents.create')"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition"
                    >
                        Unggah Dokumen PDF
                    </Link>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-slate-800 text-[11px] font-bold uppercase tracking-wider text-slate-400 bg-slate-50/50 dark:bg-slate-950/50">
                                <th class="py-3 px-4">Dokumen</th>
                                <th class="py-3 px-4">Status & Mesin</th>
                                <th class="py-3 px-4">Sidik Jari SHA-256</th>
                                <th class="py-3 px-4">Tanggal Unggah</th>
                                <th class="py-3 px-4 text-right">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                            <tr
                                v-for="doc in documents.data"
                                :key="doc.id"
                                class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition group"
                            >
                                <!-- Title & Details -->
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-red-50 dark:bg-red-950/60 text-red-600 dark:text-red-400 flex items-center justify-center shrink-0 font-bold text-[10px]">
                                            PDF
                                        </div>
                                        <div>
                                            <Link
                                                :href="route('documents.studio', doc.uuid)"
                                                class="font-bold text-slate-900 dark:text-white hover:text-indigo-600 transition truncate block max-w-xs"
                                            >
                                                {{ doc.title }}
                                            </Link>
                                            <span class="text-[11px] text-slate-400">
                                                {{ doc.original_filename }} • {{ doc.total_pages }} Hal • {{ formatBytes(doc.file_size) }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Status & Engine -->
                                <td class="py-3.5 px-4">
                                    <div class="space-y-1">
                                        <div>
                                            <span
                                                :class="[
                                                    'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold capitalize',
                                                    doc.status === 'signed'
                                                        ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800'
                                                        : 'bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border border-amber-300 dark:border-amber-800'
                                                ]"
                                            >
                                                <span class="w-1.5 h-1.5 rounded-full" :class="doc.status === 'signed' ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                                                {{ doc.status === 'signed' ? 'Selesai TTE' : 'Draft' }}
                                            </span>
                                        </div>
                                        <div class="text-[10px] text-slate-500">
                                            <span v-if="doc.psre_provider && doc.psre_provider !== 'local'" class="font-semibold text-indigo-600 uppercase">
                                                {{ doc.psre_provider }}
                                            </span>
                                            <span v-else-if="doc.digital_certificate_id" class="font-semibold text-blue-600">
                                                PKI X.509
                                            </span>
                                            <span v-else class="text-slate-400">
                                                Visual & QR
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- SHA-256 Hash -->
                                <td class="py-3.5 px-4 font-mono text-[11px]">
                                    <div class="flex items-center gap-1.5 group/hash">
                                        <span class="text-slate-600 dark:text-slate-300 truncate max-w-[120px]">
                                            {{ (doc.sha256_signed || doc.sha256_original).substring(0, 16) }}...
                                        </span>
                                        <button
                                            @click="copyHash(doc.sha256_signed || doc.sha256_original)"
                                            class="text-slate-400 hover:text-indigo-600 p-0.5"
                                            title="Salin Hash SHA-256 Lengkap"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>

                                <!-- Date -->
                                <td class="py-3.5 px-4 text-[11px] text-slate-500">
                                    {{ new Date(doc.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}
                                </td>

                                <!-- Actions -->
                                <td class="py-3.5 px-4 text-right">
                                    <div class="inline-flex items-center gap-1">
                                        <Link
                                            :href="route('documents.studio', doc.uuid)"
                                            class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-indigo-50 dark:bg-indigo-950 text-indigo-600 hover:bg-indigo-100 transition"
                                        >
                                            Studio
                                        </Link>

                                        <a
                                            v-if="doc.status === 'signed'"
                                            :href="route('documents.download', doc.uuid)"
                                            class="p-1 rounded-lg text-slate-400 hover:text-emerald-600 transition"
                                            title="Unduh PDF"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                        </a>

                                        <Link
                                            v-if="doc.status === 'signed'"
                                            :href="route('documents.verify', doc.verification_token)"
                                            class="p-1 rounded-lg text-slate-400 hover:text-blue-600 transition"
                                            title="Verifikasi"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </Link>

                                        <button
                                            @click="deleteDoc(doc)"
                                            class="p-1 rounded-lg text-slate-400 hover:text-rose-600 transition"
                                            title="Hapus"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="documents.links && documents.links.length > 3" class="px-4 py-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div class="text-xs text-slate-500">
                        Menampilkan {{ documents.from }} sampai {{ documents.to }} dari {{ documents.total }} dokumen
                    </div>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, i) in documents.links"
                            :key="i"
                            :href="link.url || '#'"
                            v-html="link.label"
                            :class="[
                                'px-2.5 py-1 text-xs rounded-lg transition',
                                link.active ? 'bg-indigo-600 text-white font-bold' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800',
                                !link.url ? 'opacity-40 pointer-events-none' : ''
                            ]"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
