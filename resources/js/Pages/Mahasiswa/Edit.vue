<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps({
    letter: Object,
    letterTypes: Array,
    signers: {
        type: Array,
        default: () => [],
    },
    user: Object,
});

const form = useForm({
    letter_type_id: props.letter?.letter_type_id || '',
    student_name: props.letter?.student_name || props.user.name || '',
    npm: props.letter?.npm || props.user.npm || '',
    study_program: props.letter?.study_program || props.user.study_program || 'Sistem Informasi',
    birth_place: props.letter?.birth_place || '',
    birth_date: props.letter?.birth_date || '',
    address: props.letter?.address || '',
    semester: props.letter?.semester || 1,
    academic_year: props.letter?.academic_year || '2026/2027',
    purpose: props.letter?.purpose || '',
    signer_id: props.letter?.signer_id || null,
    extra_data: {
        company_name: props.letter?.extra_data?.company_name || '',
        company_address: props.letter?.extra_data?.company_address || '',
        internship_period: props.letter?.extra_data?.internship_period || '',
        course_name: props.letter?.extra_data?.course_name || '',
        lecturer_name: props.letter?.extra_data?.lecturer_name || '',
        target_recipient: props.letter?.extra_data?.target_recipient || '',
        target_agency: props.letter?.extra_data?.target_agency || '',
        target_city: props.letter?.extra_data?.target_city || '',
        activity_name: props.letter?.extra_data?.activity_name || '',
        event_day: props.letter?.extra_data?.event_day || '',
        thesis_title: props.letter?.extra_data?.thesis_title || '',
        exam_courses: props.letter?.extra_data?.exam_courses || '',
        exam_type: props.letter?.extra_data?.exam_type || 'UTS',
        original_score: props.letter?.extra_data?.original_score || '',
        exam_reason: props.letter?.extra_data?.exam_reason || '',
        reason: props.letter?.extra_data?.reason || '',
        parent_name: props.letter?.extra_data?.parent_name || '',
        parent_nip: props.letter?.extra_data?.parent_nip || '',
        parent_rank: props.letter?.extra_data?.parent_rank || '',
        parent_institution: props.letter?.extra_data?.parent_institution || '',
        group_members: Array.isArray(props.letter?.extra_data?.group_members)
            ? JSON.parse(JSON.stringify(props.letter.extra_data.group_members))
            : [],
        internship_duration: props.letter?.extra_data?.internship_duration || '',
        start_date: props.letter?.extra_data?.start_date || '',
        end_date: props.letter?.extra_data?.end_date || '',
        exam_schedules: Array.isArray(props.letter?.extra_data?.exam_schedules)
            ? JSON.parse(JSON.stringify(props.letter.extra_data.exam_schedules))
            : [],
    },
});

const selectedType = computed(() => {
    return props.letterTypes.find((t) => t.id === form.letter_type_id);
});

watch(
    () => selectedType.value,
    (type) => {
        if (!form.extra_data) {
            form.extra_data = {};
        }
    },
    { immediate: true }
);

const addGroupMember = () => {
    if (!Array.isArray(form.extra_data.group_members)) {
        form.extra_data.group_members = [];
    }
    form.extra_data.group_members.push({
        name: '',
        npm: '',
        address: 'SUBANG',
        activity: form.extra_data.activity_name || (selectedType.value?.code === 'izin_sidang_ta' ? 'Sidang Tugas Akhir' : 'Seminar Usulan Penelitian'),
        day: form.extra_data.event_day || 'Kamis',
    });
};

const removeGroupMember = (index) => {
    form.extra_data.group_members.splice(index, 1);
};

const addExamSchedule = () => {
    if (!Array.isArray(form.extra_data.exam_schedules)) {
        form.extra_data.exam_schedules = [];
    }
    form.extra_data.exam_schedules.push({
        course_name: '',
        class_name: '',
        sks: 3,
        date: '',
        time: '',
        room: '',
    });
};

const removeExamSchedule = (index) => {
    form.extra_data.exam_schedules.splice(index, 1);
};

const submit = () => {
    form.put(route('mahasiswa.letters.update', props.letter.uuid));
};
</script>

<template>
    <Head title="Edit Pengajuan Surat" />

    <AuthenticatedLayout>
        <div class="py-8">
            <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- TOP HEADER -->
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('mahasiswa.letters.index')"
                        class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-xl font-bold text-slate-900 dark:text-white">
                                Edit Permohonan Surat
                            </h1>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800/80">
                                Mode Perbaikan Data
                            </span>
                        </div>
                        <p class="text-xs text-slate-500">
                            Perbaiki detail permohonan surat Anda. Dokumen PDF akan diperbarui secara otomatis.
                        </p>
                    </div>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- 1. PILIH JENIS SURAT -->
                    <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-4">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                            <h2 class="text-sm font-bold text-slate-900 dark:text-white">1. Kategori / Jenis Surat</h2>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                Kategori Permohonan Surat:
                            </label>
                            <select
                                v-model="form.letter_type_id"
                                class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600"
                            >
                                <option v-for="type in letterTypes" :key="type.id" :value="type.id">
                                    {{ type.name }} ({{ type.letter_code }})
                                </option>
                            </select>
                            <p class="text-[11px] text-slate-500 mt-1.5 italic">
                                {{ selectedType?.description }}
                            </p>
                        </div>
                    </div>

                    <!-- 2. DATA MAHASISWA -->
                    <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-4">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                            <h2 class="text-sm font-bold text-slate-900 dark:text-white">2. Data Pemohon (Mahasiswa)</h2>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    Nama Lengkap Mahasiswa <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    :value="form.student_name"
                                    type="text"
                                    disabled
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-900/60 text-slate-500 dark:text-slate-400 cursor-not-allowed uppercase"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    Nomor Pokok Mahasiswa (NPM) <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    :value="form.npm"
                                    type="text"
                                    disabled
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-900/60 text-slate-500 dark:text-slate-400 cursor-not-allowed uppercase font-mono"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Program Studi</label>
                                <input
                                    :value="form.study_program"
                                    type="text"
                                    disabled
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-900/60 text-slate-500 dark:text-slate-400 cursor-not-allowed"
                                />
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Tempat Lahir</label>
                                    <input
                                        :value="form.birth_place"
                                        type="text"
                                        disabled
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-900/60 text-slate-500 dark:text-slate-400 cursor-not-allowed"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Tanggal Lahir</label>
                                    <input
                                        :value="form.birth_date"
                                        type="date"
                                        disabled
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-900/60 text-slate-500 dark:text-slate-400 cursor-not-allowed"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Semester Aktif</label>
                                    <select
                                        v-model="form.semester"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600"
                                    >
                                        <option v-for="s in 8" :key="s" :value="s">Semester {{ s }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Tahun Akademik</label>
                                    <input
                                        v-model="form.academic_year"
                                        type="text"
                                        placeholder="2026/2027"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Keperluan Pembuatan Surat</label>
                                <input
                                    v-model="form.purpose"
                                    type="text"
                                    placeholder="Contoh: Persyaratan Beasiswa / Keperluan Administrasi"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-600"
                                />
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Alamat Domisili / Tempat Tinggal</label>
                                <textarea
                                    :value="form.address"
                                    rows="2"
                                    disabled
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-900/60 text-slate-500 dark:text-slate-400 cursor-not-allowed"
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- 3. INFORMASI TAMBAHAN KHUSUS SURAT -->
                    <div
                        v-if="['keterangan_kuliah_pns', 'pengantar_magang', 'pengantar_pkl', 'observasi_matkul', 'izin_penelitian_skripsi', 'izin_seminar_up', 'izin_sidang_ta', 'ujian_susulan', 'izin_mengikuti_ujian', 'perbaikan_nilai', 'keterangan_yudisium', 'rekomendasi_beasiswa', 'rekomendasi_luar_prodi'].includes(selectedType?.code)"
                        class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-4"
                    >
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                            <h2 class="text-sm font-bold text-slate-900 dark:text-white">
                                3. Informasi Tambahan Khusus Surat ({{ selectedType?.name }})
                            </h2>
                        </div>

                        <!-- KHUSUS SURAT KETERANGAN KULIAH PNS -->
                        <div v-if="selectedType?.code === 'keterangan_kuliah_pns'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Orang Tua / Wali (PNS)</label>
                                <input
                                    v-model="form.extra_data.parent_name"
                                    type="text"
                                    placeholder="Masukkan nama lengkap orang tua / wali"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white uppercase placeholder-slate-400"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">NIP / NIK Orang Tua</label>
                                <input
                                    v-model="form.extra_data.parent_nip"
                                    type="text"
                                    placeholder="Contoh: 197501012000031001"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white font-mono placeholder-slate-400"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Pangkat / Golongan</label>
                                <input
                                    v-model="form.extra_data.parent_rank"
                                    type="text"
                                    placeholder="Contoh: Pembina Tk. I, IV/b"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Instansi / Unit Kerja Orang Tua</label>
                                <input
                                    v-model="form.extra_data.parent_institution"
                                    type="text"
                                    placeholder="Contoh: Dinas Pendidikan Kabupaten Subang"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400"
                                />
                            </div>
                        </div>

                        <!-- KHUSUS REKOMENDASI BEASISWA -->
                        <div v-else-if="selectedType?.code === 'rekomendasi_beasiswa'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Program Beasiswa <span class="text-rose-500">*</span></label>
                                <input
                                    v-model="form.extra_data.scholarship_name"
                                    type="text"
                                    placeholder="Contoh: Beasiswa Pendidikan Pemimpin Indonesia"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Penyelenggara / Lembaga Beasiswa <span class="text-rose-500">*</span></label>
                                <input
                                    v-model="form.extra_data.scholarship_provider"
                                    type="text"
                                    placeholder="Contoh: LEADERS.ID"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400"
                                />
                            </div>
                        </div>

                        <!-- KHUSUS REKOMENDASI PEMBELAJARAN LUAR PRODI -->
                        <div v-else-if="selectedType?.code === 'rekomendasi_luar_prodi'" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Program Kegiatan / Pelatihan <span class="text-rose-500">*</span></label>
                                    <input
                                        v-model="form.extra_data.program_name"
                                        type="text"
                                        placeholder="Contoh: Program Coding Camp 2025 powered by DBS Foundation"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400"
                                    />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Periode Pelaksanaan Kegiatan <span class="text-rose-500">*</span></label>
                                    <input
                                        v-model="form.extra_data.activity_period"
                                        type="text"
                                        placeholder="Contoh: periode Februari - Juli 2025"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- KHUSUS PENGANTAR MAGANG (MBKM) -->
                        <div v-else-if="selectedType?.code === 'pengantar_magang'" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Ditujukan Kepada Yth. (Pimpinan / Jabatan / Instansi) <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.target_recipient"
                                        type="text"
                                        placeholder="Kepala Kantor Urusan Agama (KUA) Kecamatan Ciater Kabupaten Subang"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                        required
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Nama Instansi / Kantor Tempat Magang <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.target_agency"
                                        type="text"
                                        placeholder="Kantor Urusan Agama (KUA) Kecamatan Ciater Kabupaten Subang"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                        required
                                    />
                                    <p class="text-[10px] text-slate-400 mt-1">
                                        Nama instansi ini juga akan dicantumkan sebagai judul Lampiran Daftar Mahasiswa.
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Kota / Tempat Tujuan</label>
                                    <input
                                        v-model="form.extra_data.target_city"
                                        type="text"
                                        placeholder="Tempat atau Subang"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Durasi Pelaksanaan Magang <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.internship_duration"
                                        type="text"
                                        placeholder="5 bulan"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                        required
                                    />
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Tanggal Mulai</label>
                                        <input
                                            v-model="form.extra_data.start_date"
                                            type="text"
                                            placeholder="14 September 2026"
                                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Tanggal Selesai</label>
                                        <input
                                            v-model="form.extra_data.end_date"
                                            type="text"
                                            placeholder="09 Januari 2027"
                                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- LAMPIRAN DAFTAR MAHASISWA PROGRAM MAGANG MBKM -->
                            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 space-y-3">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                            Lampiran: Daftar Mahasiswa Program Magang MBKM
                                        </h4>
                                        <p class="text-[11px] text-slate-400">
                                            Pemohon utama di atas otomatis menjadi <strong>No. 1</strong> pada Lampiran Surat. Tambahkan rekan jika magang kelompok.
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="addGroupMember"
                                        class="inline-flex items-center gap-1.5 min-h-[38px] px-3.5 py-2 rounded-xl text-xs font-bold text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-950/60 hover:bg-blue-100 dark:hover:bg-blue-900/60 border border-blue-200/80 dark:border-blue-800 transition"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        <span>Tambah Mahasiswa</span>
                                    </button>
                                </div>

                                <!-- Member 1 (Ketua / Main Applicant) Card -->
                                <div class="p-3.5 sm:p-4 rounded-2xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-3">
                                        <span class="w-7 h-7 rounded-lg bg-blue-700 text-white font-bold flex items-center justify-center text-xs shrink-0">1</span>
                                        <div>
                                            <span class="font-bold text-slate-900 dark:text-white uppercase">{{ form.student_name || 'Nama Mahasiswa' }}</span>
                                            <span class="text-slate-400 mx-1.5">•</span>
                                            <span class="font-mono text-slate-500">{{ form.npm || 'NPM' }}</span>
                                        </div>
                                    </div>
                                    <span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                        Pemohon Utama
                                    </span>
                                </div>

                                <!-- Dynamic Additional Members List (Mobile-First Cards) -->
                                <div v-if="form.extra_data.group_members && form.extra_data.group_members.length > 0" class="space-y-3">
                                    <div
                                        v-for="(member, idx) in form.extra_data.group_members"
                                        :key="idx"
                                        class="p-3.5 sm:p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-xs space-y-3"
                                    >
                                        <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                                            <div class="flex items-center gap-2">
                                                <span class="w-6 h-6 rounded-lg bg-blue-100 dark:bg-blue-950 text-blue-800 dark:text-blue-300 font-bold flex items-center justify-center text-xs shrink-0">
                                                    {{ idx + 2 }}
                                                </span>
                                                <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                                    Anggota Mahasiswa #{{ idx + 2 }}
                                                </span>
                                            </div>
                                            <button
                                                type="button"
                                                @click="removeGroupMember(idx)"
                                                class="inline-flex items-center gap-1 min-h-[36px] px-2.5 py-1 text-xs font-bold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/60 rounded-xl transition"
                                                title="Hapus Mahasiswa Ini"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                <span>Hapus</span>
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 text-xs">
                                            <div class="sm:col-span-7">
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    Nama Lengkap Mahasiswa <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="member.name"
                                                    type="text"
                                                    placeholder="Nama Lengkap Mahasiswa (SYAHRUL MUHAIMIN)"
                                                    class="w-full min-h-[42px] px-3.5 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white uppercase focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                                                    required
                                                />
                                            </div>
                                            <div class="sm:col-span-5">
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    NPM <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="member.npm"
                                                    type="text"
                                                    placeholder="NPM (D1A230080)"
                                                    class="w-full min-h-[42px] px-3.5 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white uppercase font-mono focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                                                    required
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- KHUSUS PENGANTAR IZIN PKL -->
                        <div v-else-if="selectedType?.code === 'pengantar_pkl'" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Ditujukan Kepada Yth. (Jabatan / Pimpinan & Nama Instansi) <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.target_recipient"
                                        type="text"
                                        placeholder="Kepala Sekolah SMP Negeri 1 Binong Kabupaten Subang"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                        required
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Nama Instansi / Perusahaan Tempat PKL <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.target_agency"
                                        type="text"
                                        placeholder="SMP Negeri 1 Binong Kabupaten Subang"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                        required
                                    />
                                    <p class="text-[10px] text-slate-400 mt-1">
                                        Nama instansi ini dicetak tebal (bold) pada narasi isi surat.
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Kota / Tempat Tujuan</label>
                                    <input
                                        v-model="form.extra_data.target_city"
                                        type="text"
                                        placeholder="Tempat atau Subang"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Durasi Pelaksanaan PKL <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.internship_duration"
                                        type="text"
                                        placeholder="40 hari"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                        required
                                    />
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Tanggal Mulai</label>
                                        <input
                                            v-model="form.extra_data.start_date"
                                            type="text"
                                            placeholder="06 Oktober 2025"
                                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Tanggal Selesai</label>
                                        <input
                                            v-model="form.extra_data.end_date"
                                            type="text"
                                            placeholder="28 November 2025"
                                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- DAFTAR MAHASISWA PRAKTEK KERJA LAPANGAN (PKL) -->
                            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 space-y-3">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                            Daftar Mahasiswa Peserta PKL
                                        </h4>
                                        <p class="text-[11px] text-slate-400">
                                            Pemohon utama di atas otomatis menjadi <strong>No. 1</strong> pada tabel surat. Tambahkan rekan jika PKL dilakukan bersama tim.
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="addGroupMember"
                                        class="inline-flex items-center gap-1.5 min-h-[38px] px-3.5 py-2 rounded-xl text-xs font-bold text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-950/60 hover:bg-blue-100 dark:hover:bg-blue-900/60 border border-blue-200/80 dark:border-blue-800 transition"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        <span>Tambah Mahasiswa</span>
                                    </button>
                                </div>

                                <!-- Member 1 (Ketua / Main Applicant) Card -->
                                <div class="p-3.5 sm:p-4 rounded-2xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-3">
                                        <span class="w-7 h-7 rounded-lg bg-blue-700 text-white font-bold flex items-center justify-center text-xs shrink-0">1</span>
                                        <div>
                                            <span class="font-bold text-slate-900 dark:text-white uppercase">{{ form.student_name || 'Nama Mahasiswa' }}</span>
                                            <span class="text-slate-400 mx-1.5">•</span>
                                            <span class="font-mono text-slate-500">{{ form.npm || 'NPM' }}</span>
                                            <span class="text-slate-400 mx-1.5">•</span>
                                            <span class="text-slate-500 uppercase">{{ form.address || (form.extra_data.target_city || 'SUBANG') }}</span>
                                        </div>
                                    </div>
                                    <span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                        Pemohon Utama
                                    </span>
                                </div>

                                <!-- Dynamic Additional Members List (Mobile-First Cards) -->
                                <div v-if="form.extra_data.group_members && form.extra_data.group_members.length > 0" class="space-y-3">
                                    <div
                                        v-for="(member, idx) in form.extra_data.group_members"
                                        :key="idx"
                                        class="p-3.5 sm:p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-xs space-y-3"
                                    >
                                        <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                                            <div class="flex items-center gap-2">
                                                <span class="w-6 h-6 rounded-lg bg-blue-100 dark:bg-blue-950 text-blue-800 dark:text-blue-300 font-bold flex items-center justify-center text-xs shrink-0">
                                                    {{ idx + 2 }}
                                                </span>
                                                <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                                    Anggota Mahasiswa #{{ idx + 2 }}
                                                </span>
                                            </div>
                                            <button
                                                type="button"
                                                @click="removeGroupMember(idx)"
                                                class="inline-flex items-center gap-1 min-h-[36px] px-2.5 py-1 text-xs font-bold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/60 rounded-xl transition"
                                                title="Hapus Mahasiswa Ini"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                <span>Hapus</span>
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 text-xs">
                                            <div class="sm:col-span-5">
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    Nama Lengkap Mahasiswa <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="member.name"
                                                    type="text"
                                                    placeholder="Nama Lengkap Mahasiswa"
                                                    class="w-full min-h-[42px] px-3.5 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white uppercase focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                                                    required
                                                />
                                            </div>
                                            <div class="sm:col-span-3">
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    NPM <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="member.npm"
                                                    type="text"
                                                    placeholder="NPM (D1A220013)"
                                                    class="w-full min-h-[42px] px-3.5 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white uppercase font-mono focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                                                    required
                                                />
                                            </div>
                                            <div class="sm:col-span-4">
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    Alamat / Domisili <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="member.address"
                                                    type="text"
                                                    placeholder="Alamat (SUBANG)"
                                                    class="w-full min-h-[42px] px-3.5 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white uppercase focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                                                    required
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- KHUSUS OBSERVASI MATA KULIAH -->
                        <div v-else-if="selectedType?.code === 'observasi_matkul'" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Ditujukan Kepada Yth. (Jabatan / Pimpinan) <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.target_recipient"
                                        type="text"
                                        placeholder="Kepala Kantor Samsat Kabupaten Subang"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Kota / Tempat Tujuan</label>
                                    <input
                                        v-model="form.extra_data.target_city"
                                        type="text"
                                        placeholder="Tempat atau Subang"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Nama Mata Kuliah <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.course_name"
                                        type="text"
                                        placeholder="Konsep Sistem Informasi"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Dosen Pengampu (Opsional)</label>
                                    <input
                                        v-model="form.extra_data.lecturer_name"
                                        type="text"
                                        placeholder="Bambang Tjahjo Utomo, MT"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                            </div>

                            <!-- DAFTAR ANGGOTA KELOMPOK OBSERVASI -->
                            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 space-y-3">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                            Daftar Mahasiswa / Kelompok Observasi
                                        </h4>
                                        <p class="text-[11px] text-slate-400">
                                            Pemohon utama otomatis menjadi <strong>No. 1 (Ketua Kelompok)</strong>. Tambahkan anggota jika tugas observasi dikerjakan berkelompok.
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="addGroupMember"
                                        class="inline-flex items-center gap-1.5 min-h-[38px] px-3.5 py-2 rounded-xl text-xs font-bold text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-950/60 hover:bg-blue-100 dark:hover:bg-blue-900/60 border border-blue-200/80 dark:border-blue-800 transition"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        <span>+ Tambah Anggota</span>
                                    </button>
                                </div>

                                <!-- Member 1 (Ketua / Main Applicant) Card -->
                                <div class="p-3.5 sm:p-4 rounded-2xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-3">
                                        <span class="w-7 h-7 rounded-lg bg-blue-700 text-white font-bold flex items-center justify-center text-xs shrink-0">1</span>
                                        <div>
                                            <span class="font-bold text-slate-900 dark:text-white uppercase">{{ form.student_name || 'Nama Mahasiswa' }}</span>
                                            <span class="text-slate-400 mx-1.5">•</span>
                                            <span class="font-mono text-slate-500">{{ form.npm || 'NPM' }}</span>
                                            <span class="text-slate-400 mx-1.5">•</span>
                                            <span class="text-slate-500 uppercase">{{ form.address || 'SUBANG' }}</span>
                                        </div>
                                    </div>
                                    <span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                        Ketua / Pemohon
                                    </span>
                                </div>

                                <!-- Dynamic Additional Members List (Mobile-First Cards) -->
                                <div v-if="form.extra_data.group_members && form.extra_data.group_members.length > 0" class="space-y-3">
                                    <div
                                        v-for="(member, idx) in form.extra_data.group_members"
                                        :key="idx"
                                        class="p-3.5 sm:p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-xs space-y-3"
                                    >
                                        <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                                            <div class="flex items-center gap-2">
                                                <span class="w-6 h-6 rounded-lg bg-blue-100 dark:bg-blue-950 text-blue-800 dark:text-blue-300 font-bold flex items-center justify-center text-xs shrink-0">
                                                    {{ idx + 2 }}
                                                </span>
                                                <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                                    Anggota Kelompok #{{ idx + 2 }}
                                                </span>
                                            </div>
                                            <button
                                                type="button"
                                                @click="removeGroupMember(idx)"
                                                class="inline-flex items-center gap-1 min-h-[36px] px-2.5 py-1 text-xs font-bold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/60 rounded-xl transition"
                                                title="Hapus Anggota Ini"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                <span>Hapus</span>
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 text-xs">
                                            <div class="sm:col-span-5">
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    Nama Lengkap Anggota <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="member.name"
                                                    type="text"
                                                    placeholder="Nama Lengkap Anggota (ALVITO BAGASWARA)"
                                                    class="w-full min-h-[42px] px-3.5 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white uppercase focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                                                    required
                                                />
                                            </div>
                                            <div class="sm:col-span-3">
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    NPM <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="member.npm"
                                                    type="text"
                                                    placeholder="NPM (D1A260009)"
                                                    class="w-full min-h-[42px] px-3.5 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white uppercase font-mono focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                                                    required
                                                />
                                            </div>
                                            <div class="sm:col-span-4">
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    Kota / Asal
                                                </label>
                                                <input
                                                    v-model="member.address"
                                                    type="text"
                                                    placeholder="Kota / Asal (SUBANG)"
                                                    class="w-full min-h-[42px] px-3.5 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white uppercase focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- KHUSUS PENELITIAN SKRIPSI -->
                        <div v-else-if="selectedType?.code === 'izin_penelitian_skripsi'" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Ditujukan Kepada Yth. (Jabatan / Pimpinan) <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.target_recipient"
                                        type="text"
                                        placeholder="Kepala Kantor Desa Panyingkiran Kecamatan Purwadadi Kabupaten Subang"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                        required
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Instansi / Objek Lokasi Penelitian
                                    </label>
                                    <input
                                        v-model="form.extra_data.target_agency"
                                        type="text"
                                        placeholder="Kantor Desa Panyingkiran Kecamatan Purwadadi Kabupaten Subang"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                    <p class="text-[10px] text-slate-400 mt-1">
                                        Jika dikosongkan, otomatis mengambil nama instansi dari tujuan surat.
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Kota / Tempat Tujuan</label>
                                    <input
                                        v-model="form.extra_data.target_city"
                                        type="text"
                                        placeholder="Tempat atau Subang"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Judul Skripsi / Topik Penelitian (Opsional)</label>
                                    <input
                                        v-model="form.extra_data.thesis_title"
                                        type="text"
                                        placeholder="Tuliskan judul lengkap skripsi jika ingin dicantumkan..."
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                            </div>

                            <!-- DAFTAR MAHASISWA PENELITI SKRIPSI -->
                            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 space-y-3">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                            Daftar Mahasiswa / Peneliti
                                        </h4>
                                        <p class="text-[11px] text-slate-400">
                                            Pemohon utama di atas otomatis menjadi <strong>No. 1</strong>. Tambahkan anggota jika penelitian dilakukan bersama rekan/tim.
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="addGroupMember"
                                        class="inline-flex items-center gap-1.5 min-h-[38px] px-3.5 py-2 rounded-xl text-xs font-bold text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-950/60 hover:bg-blue-100 dark:hover:bg-blue-900/60 border border-blue-200/80 dark:border-blue-800 transition"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        <span>Tambah Anggota</span>
                                    </button>
                                </div>

                                <!-- Member 1 (Ketua / Main Applicant) Card -->
                                <div class="p-3.5 sm:p-4 rounded-2xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-3">
                                        <span class="w-7 h-7 rounded-lg bg-blue-700 text-white font-bold flex items-center justify-center text-xs shrink-0">1</span>
                                        <div>
                                            <span class="font-bold text-slate-900 dark:text-white uppercase">{{ form.student_name || 'Nama Mahasiswa' }}</span>
                                            <span class="text-slate-400 mx-1.5">•</span>
                                            <span class="font-mono text-slate-500">{{ form.npm || 'NPM' }}</span>
                                            <span class="text-slate-400 mx-1.5">•</span>
                                            <span class="text-slate-500 uppercase">{{ form.address || 'SUBANG' }}</span>
                                        </div>
                                    </div>
                                    <span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                        Pemohon Utama
                                    </span>
                                </div>

                                <!-- Dynamic Additional Members List (Mobile-First Cards) -->
                                <div v-if="form.extra_data.group_members && form.extra_data.group_members.length > 0" class="space-y-3">
                                    <div
                                        v-for="(member, idx) in form.extra_data.group_members"
                                        :key="idx"
                                        class="p-3.5 sm:p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-xs space-y-3"
                                    >
                                        <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                                            <div class="flex items-center gap-2">
                                                <span class="w-6 h-6 rounded-lg bg-blue-100 dark:bg-blue-950 text-blue-800 dark:text-blue-300 font-bold flex items-center justify-center text-xs shrink-0">
                                                    {{ idx + 2 }}
                                                </span>
                                                <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                                    Anggota Peneliti #{{ idx + 2 }}
                                                </span>
                                            </div>
                                            <button
                                                type="button"
                                                @click="removeGroupMember(idx)"
                                                class="inline-flex items-center gap-1 min-h-[36px] px-2.5 py-1 text-xs font-bold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/60 rounded-xl transition"
                                                title="Hapus Anggota Ini"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                <span>Hapus</span>
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 text-xs">
                                            <div class="sm:col-span-5">
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    Nama Lengkap <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="member.name"
                                                    type="text"
                                                    placeholder="Nama Lengkap (AGUS CAKRA DINATA)"
                                                    class="w-full min-h-[42px] px-3.5 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white uppercase focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                                                    required
                                                />
                                            </div>
                                            <div class="sm:col-span-3">
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    NPM <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="member.npm"
                                                    type="text"
                                                    placeholder="NPM (D1A2300036)"
                                                    class="w-full min-h-[42px] px-3.5 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white uppercase font-mono focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                                                    required
                                                />
                                            </div>
                                            <div class="sm:col-span-4">
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    Kota / Asal
                                                </label>
                                                <input
                                                    v-model="member.address"
                                                    type="text"
                                                    placeholder="Kota / Asal (SUBANG)"
                                                    class="w-full min-h-[42px] px-3.5 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white uppercase focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- KHUSUS SURAT KETERANGAN MENGIKUTI SEMINAR UP / SIDANG TA -->
                        <div v-else-if="selectedType?.code === 'izin_seminar_up' || selectedType?.code === 'izin_sidang_ta'" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Ditujukan Kepada Yth. (Pimpinan / HRD / Instansi) <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.target_recipient"
                                        type="text"
                                        placeholder="HRD PT. Uwu Jump Indonesia"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                        required
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Kota / Tempat Tujuan</label>
                                    <input
                                        v-model="form.extra_data.target_city"
                                        type="text"
                                        placeholder="Tempat atau Subang"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Nama Kegiatan <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.activity_name"
                                        type="text"
                                        :placeholder="selectedType?.code === 'izin_sidang_ta' ? 'Contoh: Sidang Tugas Akhir' : 'Contoh: Seminar Usulan Penelitian'"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                        required
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Hari Pelaksanaan <span class="text-rose-500">*</span>
                                    </label>
                                    <select
                                        v-model="form.extra_data.event_day"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    >
                                        <option value="Senin">Senin</option>
                                        <option value="Selasa">Selasa</option>
                                        <option value="Rabu">Rabu</option>
                                        <option value="Kamis">Kamis</option>
                                        <option value="Jum'at">Jum'at</option>
                                        <option value="Sabtu">Sabtu</option>
                                    </select>
                                </div>
                            </div>

                            <!-- DAFTAR PESERTA -->
                            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 space-y-3">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                            {{ selectedType?.code === 'izin_sidang_ta' ? 'Daftar Mahasiswa Sidang Tugas Akhir' : 'Daftar Mahasiswa Peserta Seminar' }}
                                        </h4>
                                        <p class="text-[11px] text-slate-400">
                                            Pemohon utama di atas otomatis menjadi <strong>No. 1</strong>. Tambahkan peserta jika ada mahasiswa lain dalam surat izin yang sama.
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="addGroupMember"
                                        class="inline-flex items-center gap-1.5 min-h-[38px] px-3.5 py-2 rounded-xl text-xs font-bold text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-950/60 hover:bg-blue-100 dark:hover:bg-blue-900/60 border border-blue-200/80 dark:border-blue-800 transition"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        <span>Tambah Peserta</span>
                                    </button>
                                </div>

                                <!-- Peserta 1 (Main Applicant) Card -->
                                <div class="p-3.5 sm:p-4 rounded-2xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-3">
                                        <span class="w-7 h-7 rounded-lg bg-blue-700 text-white font-bold flex items-center justify-center text-xs shrink-0">1</span>
                                        <div>
                                            <span class="font-bold text-slate-900 dark:text-white uppercase">{{ form.student_name || 'Nama Mahasiswa' }}</span>
                                            <span class="text-slate-400 mx-1.5">•</span>
                                            <span class="font-mono text-slate-500">{{ form.npm || 'NPM' }}</span>
                                            <span class="text-slate-400 mx-1.5">•</span>
                                            <span class="text-slate-500">{{ form.extra_data.activity_name || (selectedType?.code === 'izin_sidang_ta' ? 'Sidang Tugas Akhir' : 'Seminar Usulan Penelitian') }}</span>
                                            <span class="text-slate-400 mx-1.5">•</span>
                                            <span class="text-slate-500">{{ form.extra_data.event_day || 'Kamis' }}</span>
                                        </div>
                                    </div>
                                    <span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                        Peserta Utama
                                    </span>
                                </div>

                                <!-- Dynamic Additional Participants List (Mobile-First Cards) -->
                                <div v-if="form.extra_data.group_members && form.extra_data.group_members.length > 0" class="space-y-3">
                                    <div
                                        v-for="(member, idx) in form.extra_data.group_members"
                                        :key="idx"
                                        class="p-3.5 sm:p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-xs space-y-3"
                                    >
                                        <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                                            <div class="flex items-center gap-2">
                                                <span class="w-6 h-6 rounded-lg bg-blue-100 dark:bg-blue-950 text-blue-800 dark:text-blue-300 font-bold flex items-center justify-center text-xs shrink-0">
                                                    {{ idx + 2 }}
                                                </span>
                                                <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                                    Peserta #{{ idx + 2 }}
                                                </span>
                                            </div>
                                            <button
                                                type="button"
                                                @click="removeGroupMember(idx)"
                                                class="inline-flex items-center gap-1 min-h-[36px] px-2.5 py-1 text-xs font-bold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/60 rounded-xl transition"
                                                title="Hapus Peserta Ini"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                <span>Hapus</span>
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 text-xs">
                                            <div class="sm:col-span-4">
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    Nama Mahasiswa <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="member.name"
                                                    type="text"
                                                    placeholder="Nama Mahasiswa"
                                                    class="w-full min-h-[42px] px-3.5 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white uppercase focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                                                    required
                                                />
                                            </div>
                                            <div class="sm:col-span-3">
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    NPM <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="member.npm"
                                                    type="text"
                                                    placeholder="NPM"
                                                    class="w-full min-h-[42px] px-3.5 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white uppercase font-mono focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                                                    required
                                                />
                                            </div>
                                            <div class="sm:col-span-3">
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    Kegiatan
                                                </label>
                                                <input
                                                    v-model="member.activity"
                                                    type="text"
                                                    :placeholder="form.extra_data.activity_name || 'Seminar UP'"
                                                    class="w-full min-h-[42px] px-3.5 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                                                />
                                            </div>
                                            <div class="sm:col-span-2">
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    Hari / Tgl
                                                </label>
                                                <input v-model="member.day" type="text" :placeholder="form.extra_data.event_day || 'Kamis'" class="w-full min-h-[42px] px-3.5 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600 focus:border-blue-600" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- KHUSUS SURAT IZIN MENGIKUTI UJIAN (UTS/UAS) -->
                        <div v-else-if="selectedType?.code === 'izin_mengikuti_ujian'" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Jenis Ujian <span class="text-rose-500">*</span>
                                    </label>
                                    <select
                                        v-model="form.extra_data.exam_type"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600"
                                    >
                                        <option value="UAS">Ujian Akhir Semester (UAS)</option>
                                        <option value="UTS">Ujian Tengah Semester (UTS)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Semester Perkuliahan <span class="text-rose-500">*</span>
                                    </label>
                                    <select
                                        v-model="form.extra_data.semester_type"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600"
                                    >
                                        <option value="Ganjil">Semester Ganjil</option>
                                        <option value="Genap">Semester Genap</option>
                                    </select>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Ditujukan Kepada Yth. (Jabatan / Pimpinan & Nama Instansi) <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.target_recipient"
                                        type="text"
                                        placeholder="HRD Klinik Pratama Rawat Inap Larasati"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                        required
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Kota / Tempat Tujuan</label>
                                    <input
                                        v-model="form.extra_data.target_city"
                                        type="text"
                                        placeholder="Tempat atau Subang"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Rentang Tanggal Pelaksanaan Ujian <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.exam_dates"
                                        type="text"
                                        placeholder="12-19 Januari 2026"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                        required
                                    />
                                </div>
                            </div>

                            <!-- TABEL JADWAL UJIAN PADA LAMPIRAN (HALAMAN 2) -->
                            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 space-y-3">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                            Lampiran: Jadwal {{ form.extra_data.exam_type === 'UTS' ? 'Ujian Tengah Semester (UTS)' : 'Ujian Akhir Semester (UAS)' }}
                                        </h4>
                                        <p class="text-[11px] text-slate-400">
                                            Jadwal ujian ini akan dicantumkan secara rapi pada Lampiran (Halaman 2).
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="addExamSchedule"
                                        class="inline-flex items-center gap-1.5 min-h-[38px] px-3.5 py-2 rounded-xl text-xs font-bold text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-950/60 hover:bg-blue-100 dark:hover:bg-blue-900/60 border border-blue-200/80 dark:border-blue-800 transition"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        <span>Tambah Jadwal Ujian</span>
                                    </button>
                                </div>

                                <!-- Schedule Items (Mobile-First Cards) -->
                                <div class="space-y-3">
                                    <div
                                        v-for="(item, idx) in form.extra_data.exam_schedules"
                                        :key="idx"
                                        class="p-3.5 sm:p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-xs space-y-3"
                                    >
                                        <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                                            <div class="flex items-center gap-2">
                                                <span class="w-6 h-6 rounded-lg bg-blue-100 dark:bg-blue-950 text-blue-800 dark:text-blue-300 font-bold flex items-center justify-center text-xs shrink-0">
                                                    #{{ idx + 1 }}
                                                </span>
                                                <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                                    Jadwal Mata Kuliah #{{ idx + 1 }}
                                                </span>
                                            </div>
                                            <button
                                                v-if="form.extra_data.exam_schedules.length > 1"
                                                type="button"
                                                @click="removeExamSchedule(idx)"
                                                class="inline-flex items-center gap-1 min-h-[36px] px-2.5 py-1 text-xs font-bold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/60 rounded-xl transition"
                                                title="Hapus baris jadwal ini"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                <span>Hapus</span>
                                            </button>
                                        </div>

                                        <!-- Row 1: Course Name & Class & SKS -->
                                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 text-xs">
                                            <div class="sm:col-span-7">
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    Nama Mata Kuliah <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.course_name"
                                                    type="text"
                                                    placeholder="Audit Sistem Informasi"
                                                    class="w-full min-h-[42px] px-3.5 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                                                    required
                                                />
                                            </div>
                                            <div class="sm:col-span-3">
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    Kelas <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.class_name"
                                                    type="text"
                                                    placeholder="4A"
                                                    class="w-full min-h-[42px] px-3.5 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white uppercase focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                                                    required
                                                />
                                            </div>
                                            <div class="sm:col-span-2">
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    SKS
                                                </label>
                                                <input v-model.number="item.sks" type="number" min="1" max="6" class="w-full min-h-[42px] px-3.5 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600 focus:border-blue-600" />
                                            </div>
                                        </div>

                                        <!-- Row 2: Tanggal, Waktu, Ruang -->
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                                            <div>
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    Hari / Tanggal Ujian <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.date"
                                                    type="text"
                                                    placeholder="Senin, 21-03-2025"
                                                    class="w-full min-h-[42px] px-3.5 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                                                    required
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    Waktu / Jam <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.time"
                                                    type="text"
                                                    placeholder="08.00 - 09.30 WIB"
                                                    class="w-full min-h-[42px] px-3.5 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                                                    required
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    Ruang Ujian
                                                </label>
                                                <input
                                                    v-model="item.room"
                                                    type="text"
                                                    placeholder="Lab Komputer 2"
                                                    class="w-full min-h-[42px] px-3.5 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- KHUSUS SURAT PENGANTAR UJIAN SUSULAN (UTS/UAS) -->
                        <div v-else-if="selectedType?.code === 'ujian_susulan'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    Jenis Ujian <span class="text-rose-500">*</span>
                                </label>
                                <select
                                    v-model="form.extra_data.exam_type"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600"
                                >
                                    <option value="UTS">Ujian Tengah Semester (UTS)</option>
                                    <option value="UAS">Ujian Akhir Semester (UAS)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    Nilai Asal <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="form.extra_data.original_score"
                                    type="text"
                                    placeholder="- atau E / D"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    Bapak / Ibu (Dosen Pengampu) <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="form.extra_data.lecturer_name"
                                    type="text"
                                    placeholder="ACHMAD SYAFA'AT, S.Kom., MT"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    Dosen Mata Kuliah <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="form.extra_data.course_name"
                                    type="text"
                                    placeholder="Jaringan Komputer"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    required
                                />
                            </div>
                        </div>

                        <!-- KHUSUS SURAT PENGANTAR PERBAIKAN NILAI -->
                        <div v-else-if="selectedType?.code === 'perbaikan_nilai'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    Bapak / Ibu (Dosen Pengampu) <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="form.extra_data.lecturer_name"
                                    type="text"
                                    placeholder="Ir. Syarif Hidayat, MT"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    Dosen Mata Kuliah <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="form.extra_data.course_name"
                                    type="text"
                                    placeholder="APSI I"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    required
                                />
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    Nilai Asal <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="form.extra_data.original_score"
                                    type="text"
                                    placeholder="T atau E / D"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    required
                                />
                            </div>
                        </div>

                        <!-- KHUSUS SURAT KETERANGAN YUDISIUM -->
                        <div v-else-if="selectedType?.code === 'keterangan_yudisium'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    Hari & Tanggal Sidang Tugas Akhir <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="form.extra_data.defense_date_text"
                                    type="text"
                                    placeholder="Kamis, 27 Agustus 2026"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    IPK (Indeks Prestasi Kumulatif) <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="form.extra_data.ipk"
                                    type="text"
                                    placeholder="3.73"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    Masa Studi <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="form.extra_data.study_duration"
                                    type="text"
                                    placeholder="4 (Empat) Tahun"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    Nilai Sidang Tugas Akhir <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="form.extra_data.defense_grade"
                                    type="text"
                                    placeholder="A"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    Hasil Sidang Tugas Akhir <span class="text-rose-500">*</span>
                                </label>
                                <select
                                    v-model="form.extra_data.defense_result"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    required
                                >
                                    <option value="Lulus">Lulus</option>
                                    <option value="Gagal">Gagal</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    Hari & Tanggal Sidang Yudisium <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="form.extra_data.yudisium_date_text"
                                    type="text"
                                    placeholder="Kamis, 27 Agustus 2026"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    required
                                />
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    Hasil Yudisium (Predikat Kelulusan) <span class="text-rose-500">*</span>
                                </label>
                                <select
                                    v-model="form.extra_data.yudisium_predicate"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    required
                                >
                                    <option value="Dengan Pujian">Dengan Pujian</option>
                                    <option value="Sangat Memuaskan">Sangat Memuaskan</option>
                                    <option value="Memuaskan">Memuaskan</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- BUTTON ACTIONS -->
                    <div class="flex items-center justify-end gap-3 pt-2">
                        <Link
                            :href="route('mahasiswa.letters.index')"
                            class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                        >
                            Batal
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-900/20 transition hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50"
                        >
                            <svg v-if="form.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span>{{ form.processing ? 'Menyimpan Perubahan...' : 'Simpan Perubahan' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

