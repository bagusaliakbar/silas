<script setup>
import { ref, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    signers: {
        type: Array,
        required: true,
    },
    stats: {
        type: Object,
        default: () => ({
            total: 0,
            active: 0,
            inactive: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
        }),
    },
});

const search = ref(props.filters.search || '');
const activeFilter = ref('all'); // 'all', 'active', 'inactive'

const filteredSigners = computed(() => {
    let result = props.signers;

    if (activeFilter.value === 'active') {
        result = result.filter((s) => s.is_active);
    } else if (activeFilter.value === 'inactive') {
        result = result.filter((s) => !s.is_active);
    }

    if (search.value.trim()) {
        const query = search.value.toLowerCase();
        result = result.filter((s) =>
            (s.name || '').toLowerCase().includes(query) ||
            (s.role_title || '').toLowerCase().includes(query) ||
            (s.signature_title || '').toLowerCase().includes(query) ||
            (s.nip || '').toLowerCase().includes(query) ||
            (s.code || '').toLowerCase().includes(query)
        );
    }

    return result;
});

const resetFilters = () => {
    search.value = '';
    activeFilter.value = 'all';
};

// Modal State
const showFormModal = ref(false);
const isEditing = ref(false);
const editingSignerId = ref(null);
const currentSignatureUrl = ref(null);
const signatureFileInput = ref(null);
const signaturePreviewUrl = ref(null);

const form = useForm({
    _method: 'post',
    code: '',
    role_title: '',
    signature_title: '',
    name: '',
    display_name: '',
    nip: '',
    email: '',
    signature_image: null,
    remove_signature: false,
    description: '',
    order: 1,
    is_active: true,
});

const showDeleteModal = ref(false);
const signerToDelete = ref(null);
const isDeleting = ref(false);

const handleSignatureFileChange = (e) => {
    const file = e.target.files?.[0];
    if (file) {
        form.signature_image = file;
        form.remove_signature = false;
        signaturePreviewUrl.value = URL.createObjectURL(file);
    }
};

const removeExistingSignature = () => {
    form.signature_image = null;
    form.remove_signature = true;
    currentSignatureUrl.value = null;
    signaturePreviewUrl.value = null;
    if (signatureFileInput.value) {
        signatureFileInput.value.value = '';
    }
};

const openCreateModal = () => {
    isEditing.value = false;
    editingSignerId.value = null;
    currentSignatureUrl.value = null;
    signaturePreviewUrl.value = null;
    form.reset();
    form.clearErrors();
    form._method = 'post';
    form.order = (props.signers.length > 0 ? Math.max(...props.signers.map(s => s.order || 0)) + 1 : 1);
    form.is_active = true;
    showFormModal.value = true;
};

const openEditModal = (signer) => {
    isEditing.value = true;
    editingSignerId.value = signer.id;
    currentSignatureUrl.value = signer.signature_image_url || null;
    signaturePreviewUrl.value = null;
    form.clearErrors();
    form._method = 'put';
    form.code = signer.code || '';
    form.role_title = signer.role_title || '';
    form.signature_title = signer.signature_title || '';
    form.name = signer.name || '';
    form.display_name = signer.display_name || signer.name || '';
    form.nip = signer.nip || '';
    form.email = signer.email || '';
    form.signature_image = null;
    form.remove_signature = false;
    form.description = signer.description || '';
    form.order = signer.order ?? 1;
    form.is_active = Boolean(signer.is_active);
    showFormModal.value = true;
};

const handleRoleTitleChange = () => {
    if (!isEditing.value && !form.code && form.role_title) {
        form.code = form.role_title
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_+|_+$/g, '');
    }
};

const handleNameChange = () => {
    if (!isEditing.value && !form.display_name && form.name) {
        form.display_name = form.name;
    }
};

const saveSigner = () => {
    if (isEditing.value) {
        form._method = 'put';
        form.post(route('settings.signers.update', editingSignerId.value), {
            preserveScroll: true,
            onSuccess: () => {
                showFormModal.value = false;
                form.reset();
            },
        });
    } else {
        form._method = 'post';
        form.post(route('settings.signers.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showFormModal.value = false;
                form.reset();
            },
        });
    }
};

const toggleStatus = (signer) => {
    router.patch(route('settings.signers.toggle', signer.id), {}, {
        preserveScroll: true,
    });
};

const confirmDelete = (signer) => {
    signerToDelete.value = signer;
    showDeleteModal.value = true;
};

const handleDelete = () => {
    if (!signerToDelete.value) return;

    isDeleting.value = true;
    router.delete(route('settings.signers.destroy', signerToDelete.value.id), {
        preserveScroll: true,
        onFinish: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
            signerToDelete.value = null;
        },
    });
};
</script>

<template>
    <Head title="Daftar Pejabat Penandatangan" />

    <AuthenticatedLayout>
        <div class="py-6 sm:py-8">
            <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- CONTROLS & FILTER TOOLBAR (MATCHING TEMPLATE & KLASIFIKASI SURAT) -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-xs space-y-4">
                    <!-- Section Title -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div>
                            <h3 class="font-bold text-slate-900 dark:text-white text-base">Daftar Pejabat Penandatangan</h3>
                        </div>

                        <button
                            type="button"
                            @click="openCreateModal"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-white bg-blue-600 hover:bg-blue-500 shadow-md shadow-blue-950/20 active:scale-95 transition shrink-0"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Tambah Pejabat Penandatangan</span>
                        </button>
                    </div>

                    <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
                        <!-- Search Bar -->
                        <div class="relative flex-1">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Cari nama pejabat, NIP, atau jabatan..."
                                class="w-full pl-10 pr-10 py-2.5 text-xs sm:text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/80 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-hidden focus:ring-2 focus:ring-blue-600/30 focus:border-blue-600 transition"
                            />
                            <button
                                v-if="search"
                                type="button"
                                @click="search = ''"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                                title="Hapus pencarian"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Status Filter Toggle -->
                        <div class="flex items-center gap-2.5 shrink-0 self-end md:self-auto">
                            <div class="inline-flex rounded-xl p-1 bg-slate-100 dark:bg-slate-800 text-xs">
                                <button
                                    type="button"
                                    @click="activeFilter = 'all'"
                                    :class="[
                                        'px-3 py-1.5 rounded-lg font-semibold transition',
                                        activeFilter === 'all'
                                            ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-2xs'
                                            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                                    ]"
                                >
                                    Semua ({{ signers.length }})
                                </button>
                                <button
                                    type="button"
                                    @click="activeFilter = 'active'"
                                    :class="[
                                        'px-3 py-1.5 rounded-lg font-semibold transition',
                                        activeFilter === 'active'
                                            ? 'bg-white dark:bg-slate-900 text-emerald-600 dark:text-emerald-400 shadow-2xs'
                                            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                                    ]"
                                >
                                    Aktif ({{ stats.active }})
                                </button>
                                <button
                                    type="button"
                                    @click="activeFilter = 'inactive'"
                                    :class="[
                                        'px-3 py-1.5 rounded-lg font-semibold transition',
                                        activeFilter === 'inactive'
                                            ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-2xs'
                                            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                                    ]"
                                >
                                    Non-Aktif ({{ stats.inactive }})
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RESULTS COUNTER -->
                <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 px-1">
                    <div>
                        Menampilkan
                        <span class="font-bold text-slate-900 dark:text-white">{{ filteredSigners.length }}</span>
                        dari
                        <span class="font-bold text-slate-900 dark:text-white">{{ signers.length }}</span>
                        pejabat penandatangan
                    </div>
                    <div v-if="search || activeFilter !== 'all'">
                        <button
                            type="button"
                            @click="resetFilters"
                            class="text-blue-600 dark:text-blue-400 hover:underline font-semibold"
                        >
                            Reset Filter
                        </button>
                    </div>
                </div>

                <!-- SIGNERS TABLE / CARDS -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-xs">
                    <!-- DESKTOP TABLE VIEW -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-slate-950/60 border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                                    <th class="py-3.5 px-4 w-12 text-center">Urutan</th>
                                    <th class="py-3.5 px-4">Nama Pejabat & NIP</th>
                                    <th class="py-3.5 px-4">Jabatan Struktural & Surat</th>
                                    <th class="py-3.5 px-4">Email / Kontak</th>
                                    <th class="py-3.5 px-4 w-28 text-center">TTD Visual</th>
                                    <th class="py-3.5 px-4 w-24 text-center">Status</th>
                                    <th class="py-3.5 px-4 w-28 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr
                                    v-for="signer in filteredSigners"
                                    :key="signer.id"
                                    class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition"
                                >
                                    <td class="py-3.5 px-4 text-center font-mono font-bold text-slate-400">
                                        #{{ signer.order }}
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-xl bg-blue-600 text-white font-bold flex items-center justify-center shrink-0 uppercase shadow-2xs">
                                                {{ (signer.display_name || signer.name || 'P').charAt(0) }}
                                            </div>
                                            <div>
                                                <div class="font-bold text-slate-900 dark:text-white">
                                                    {{ signer.name }}
                                                </div>
                                                <div class="text-[11px] text-slate-500 flex items-center gap-2 mt-0.5">
                                                    <span v-if="signer.nip" class="font-mono">NIP/NIDN: {{ signer.nip }}</span>
                                                    <span v-else class="text-slate-400 italic">Tanpa NIP</span>
                                                    <span>•</span>
                                                    <span class="px-1.5 py-0.2 rounded bg-slate-100 dark:bg-slate-800 font-mono text-[10px] text-slate-600 dark:text-slate-400">
                                                        {{ signer.code }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div>
                                            <span class="inline-block px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 mb-1">
                                                {{ signer.role_title }}
                                            </span>
                                            <div class="font-medium text-slate-700 dark:text-slate-300 text-[11px]">
                                                Teks Surat: <strong>{{ signer.signature_title }}</strong>
                                            </div>
                                            <div v-if="signer.description" class="text-[10px] text-slate-400 mt-0.5 max-w-xs truncate" :title="signer.description">
                                                {{ signer.description }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 font-mono text-slate-600 dark:text-slate-400">
                                        <span v-if="signer.email">{{ signer.email }}</span>
                                        <span v-else class="text-slate-400 italic font-sans">-</span>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <div v-if="signer.signature_image_url" class="inline-flex items-center justify-center p-1 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 shadow-2xs group relative">
                                            <img
                                                :src="signer.signature_image_url"
                                                alt="TTD"
                                                class="h-9 max-w-[70px] object-contain"
                                            />
                                        </div>
                                        <span v-else class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-400 italic">
                                            Belum Ada
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <button
                                            type="button"
                                            @click="toggleStatus(signer)"
                                            :class="[
                                                'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold transition',
                                                signer.is_active
                                                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 hover:bg-emerald-200'
                                                    : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 hover:bg-slate-200'
                                            ]"
                                            :title="signer.is_active ? 'Klik untuk menonaktifkan' : 'Klik untuk mengaktifkan'"
                                        >
                                            <span
                                                :class="[
                                                    'w-1.5 h-1.5 rounded-full',
                                                    signer.is_active ? 'bg-emerald-500' : 'bg-slate-400'
                                                ]"
                                            ></span>
                                            <span>{{ signer.is_active ? 'Aktif' : 'Non-Aktif' }}</span>
                                        </button>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <div class="inline-flex items-center gap-1">
                                            <button
                                                type="button"
                                                @click="openEditModal(signer)"
                                                class="w-8 h-8 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition inline-flex items-center justify-center"
                                                title="Edit Data Pejabat"
                                                aria-label="Edit Data Pejabat"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button
                                                type="button"
                                                @click="confirmDelete(signer)"
                                                class="w-8 h-8 rounded-lg text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition inline-flex items-center justify-center"
                                                title="Hapus Pejabat"
                                                aria-label="Hapus Pejabat"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="filteredSigners.length === 0">
                                    <td colspan="7" class="py-12 text-center text-slate-400">
                                        <svg class="w-10 h-10 mx-auto text-slate-300 dark:text-slate-700 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        <p class="font-medium text-xs">Tidak ada pejabat penandatangan yang sesuai kriteria pencarian.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- MOBILE CARD VIEW -->
                    <div class="md:hidden divide-y divide-slate-100 dark:divide-slate-800">
                        <div
                            v-for="signer in filteredSigners"
                            :key="signer.id"
                            class="p-4 space-y-3"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-10 h-10 rounded-xl bg-blue-600 text-white font-bold flex items-center justify-center shrink-0 uppercase shadow-2xs text-sm">
                                        {{ (signer.display_name || signer.name || 'P').charAt(0) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-bold text-slate-900 dark:text-white text-sm truncate">
                                            {{ signer.name }}
                                        </div>
                                        <div class="text-[11px] text-slate-500 flex items-center gap-1.5 mt-0.5 flex-wrap">
                                            <span v-if="signer.nip" class="font-mono">NIP: {{ signer.nip }}</span>
                                            <span v-else class="text-slate-400 italic">Tanpa NIP</span>
                                            <span>•</span>
                                            <span class="px-1.5 py-0.2 rounded bg-slate-100 dark:bg-slate-800 font-mono text-[10px] text-slate-600 dark:text-slate-400">
                                                {{ signer.code }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    @click="toggleStatus(signer)"
                                    :class="[
                                        'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold transition shrink-0',
                                        signer.is_active
                                            ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'
                                            : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'
                                    ]"
                                    :title="signer.is_active ? 'Klik untuk menonaktifkan' : 'Klik untuk mengaktifkan'"
                                >
                                    <span
                                        :class="[
                                            'w-1.5 h-1.5 rounded-full',
                                            signer.is_active ? 'bg-emerald-500' : 'bg-slate-400'
                                        ]"
                                    ></span>
                                    <span>{{ signer.is_active ? 'Aktif' : 'Non-Aktif' }}</span>
                                </button>
                            </div>

                            <div class="bg-slate-50 dark:bg-slate-800/60 rounded-xl p-2.5 space-y-1 text-xs">
                                <div class="flex items-center justify-between">
                                    <span class="inline-block px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300">
                                        {{ signer.role_title }}
                                    </span>
                                    <span class="text-[10px] font-mono text-slate-400">Urutan: #{{ signer.order }}</span>
                                </div>
                                <div class="text-slate-700 dark:text-slate-300 text-[11px]">
                                    Teks Surat: <strong>{{ signer.signature_title }}</strong>
                                </div>
                                <div v-if="signer.email" class="text-slate-500 font-mono text-[11px]">
                                    {{ signer.email }}
                                </div>
                            </div>

                            <div class="flex items-center justify-between pt-1 text-xs">
                                <div class="flex items-center gap-2">
                                    <div v-if="signer.signature_image_url" class="inline-flex items-center gap-1.5 text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>TTD Visual Ada</span>
                                    </div>
                                    <span v-else class="text-[11px] text-slate-400 italic">Tanpa TTD Visual</span>
                                </div>

                                <div class="flex items-center gap-1">
                                    <button
                                        type="button"
                                        @click="openEditModal(signer)"
                                        class="w-8 h-8 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition inline-flex items-center justify-center"
                                        title="Edit Data Pejabat"
                                        aria-label="Edit Data Pejabat"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button
                                        type="button"
                                        @click="confirmDelete(signer)"
                                        class="w-8 h-8 rounded-lg text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition inline-flex items-center justify-center"
                                        title="Hapus Pejabat"
                                        aria-label="Hapus Pejabat"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-if="filteredSigners.length === 0" class="py-10 text-center text-slate-400 p-4">
                            <p class="font-medium text-xs">Tidak ada pejabat penandatangan yang sesuai kriteria pencarian.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL FORM CREATE / EDIT -->
        <Modal :show="showFormModal" @close="showFormModal = false" max-width="lg">
            <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                        <span>{{ isEditing ? 'Edit Data Pejabat Penandatangan' : 'Tambah Pejabat Penandatangan Baru' }}</span>
                    </h3>
                    <button
                        type="button"
                        @click="showFormModal = false"
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="saveSigner" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Nama Lengkap (beserta Gelar Resmi) <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.name"
                                @input="handleNameChange"
                                type="text"
                                placeholder="BAGUS ALI AKBAR, S.Si., M.Kom"
                                class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600"
                                required
                            />
                            <p v-if="form.errors.name" class="text-rose-500 text-[10px] mt-1">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Nama Tampilan (Title Case)
                            </label>
                            <input
                                v-model="form.display_name"
                                type="text"
                                placeholder="Bagus Ali Akbar, S.Si., M.Kom"
                                class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                NIP / NIDN
                            </label>
                            <input
                                v-model="form.nip"
                                type="text"
                                placeholder="0410019202"
                                class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600 font-mono"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Jabatan Struktural (Badge Role) <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.role_title"
                                @input="handleRoleTitleChange"
                                type="text"
                                placeholder="Ketua Program Studi / Dekan"
                                class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600"
                                required
                            />
                            <p v-if="form.errors.role_title" class="text-rose-500 text-[10px] mt-1">{{ form.errors.role_title }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Teks Jabatan di Tanda Tangan <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.signature_title"
                                type="text"
                                placeholder="Ka. Program Studi / Dekan"
                                class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600"
                                required
                            />
                            <p v-if="form.errors.signature_title" class="text-rose-500 text-[10px] mt-1">{{ form.errors.signature_title }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Kode / ID Unik <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.code"
                                type="text"
                                placeholder="kaprodi / dekan / wadek"
                                class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600 font-mono"
                                required
                            />
                            <p class="text-[10px] text-slate-400 mt-1">Gunakan huruf kecil dan garis bawah tanpa spasi.</p>
                            <p v-if="form.errors.code" class="text-rose-500 text-[10px] mt-1">{{ form.errors.code }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Email Resmi Pejabat
                            </label>
                            <input
                                v-model="form.email"
                                type="email"
                                placeholder="kaprodi.si@unsub.ac.id"
                                class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600 font-mono"
                            />
                            <p v-if="form.errors.email" class="text-rose-500 text-[10px] mt-1">{{ form.errors.email }}</p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Gambar Tanda Tangan Basah (Visual TTD)
                            </label>
                            
                            <div class="p-3 rounded-xl border border-dashed border-slate-300 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-950/50 flex flex-col sm:flex-row items-center gap-4">
                                <div class="w-32 h-16 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-center justify-center overflow-hidden shrink-0 shadow-2xs">
                                    <img
                                        v-if="signaturePreviewUrl || (currentSignatureUrl && !form.remove_signature)"
                                        :src="signaturePreviewUrl || currentSignatureUrl"
                                        alt="Preview TTD"
                                        class="h-full w-full object-contain p-1"
                                    />
                                    <div v-else class="text-[10px] text-slate-400 text-center px-1">
                                        Belum Ada TTD
                                    </div>
                                </div>

                                <div class="space-y-1.5 flex-1 w-full">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <input
                                            ref="signatureFileInput"
                                            type="file"
                                            accept="image/png,image/jpeg,image/webp"
                                            @change="handleSignatureFileChange"
                                            class="block w-full sm:w-auto text-xs text-slate-500 file:mr-2 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 dark:file:bg-blue-950 dark:file:text-blue-300 hover:file:bg-blue-100"
                                        />
                                        <button
                                            v-if="signaturePreviewUrl || (currentSignatureUrl && !form.remove_signature)"
                                            type="button"
                                            @click="removeExistingSignature"
                                            class="text-[11px] text-rose-600 hover:text-rose-700 font-semibold underline px-1"
                                        >
                                            Hapus TTD
                                        </button>
                                    </div>
                                    <p class="text-[10px] text-slate-400">
                                        Format: PNG (transparan disarankan), JPG, atau WEBP. Maksimal 2MB. TTD ini dibubuhkan pada dokumen saat proses penandatanganan visual.
                                    </p>
                                </div>
                            </div>
                            <p v-if="form.errors.signature_image" class="text-rose-500 text-[10px] mt-1">{{ form.errors.signature_image }}</p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Keterangan / Deskripsi Lengkap
                            </label>
                            <input
                                v-model="form.description"
                                type="text"
                                placeholder="Ketua Program Studi Sistem Informasi Fakultas Ilmu Komputer"
                                class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Urutan Tampilan
                            </label>
                            <input
                                v-model.number="form.order"
                                type="number"
                                min="0"
                                class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600 font-mono"
                            />
                        </div>

                        <div class="flex items-center pt-5">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input
                                    v-model="form.is_active"
                                    type="checkbox"
                                    class="w-4 h-4 text-blue-600 rounded border-slate-300 dark:border-slate-700 focus:ring-blue-600"
                                />
                                <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">
                                    Aktifkan Pejabat untuk Penandatanganan
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <button
                            type="button"
                            @click="showFormModal = false"
                            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-1.5 px-5 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-sm transition disabled:opacity-50"
                        >
                            <svg v-if="form.processing" class="animate-spin w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span>{{ form.processing ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Tambahkan Pejabat') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- MODAL KONFIRMASI HAPUS -->
        <Modal :show="showDeleteModal" @close="showDeleteModal = false" max-width="md">
            <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl space-y-4">
                <div class="w-12 h-12 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>

                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">
                        Hapus Pejabat Penandatangan?
                    </h3>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                        Anda yakin ingin menghapus data pejabat <strong class="text-slate-900 dark:text-white">{{ signerToDelete?.name }}</strong> ({{ signerToDelete?.role_title }})? Pejabat ini tidak akan muncul lagi pada opsi penandatanganan surat.
                    </p>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button
                        type="button"
                        @click="showDeleteModal = false"
                        class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="handleDelete"
                        :disabled="isDeleting"
                        class="px-5 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl shadow-sm transition disabled:opacity-50"
                    >
                        {{ isDeleting ? 'Menghapus...' : 'Ya, Hapus Pejabat' }}
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

