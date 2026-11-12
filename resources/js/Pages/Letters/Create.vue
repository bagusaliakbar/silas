<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    letterTypes: Array,
    courses: {
        type: Array,
        default: () => [],
    },
    signers: {
        type: Array,
        default: () => [
            {
                id: 'kaprodi',
                role_title: 'Ketua Program Studi',
                signature_title: 'Ka. Program Studi',
                name: 'BAGUS ALI AKBAR, S.Si., M.Kom',
                display_name: 'Bagus Ali Akbar, S.SI., M.Kom',
                email: 'bagusaliakbar@unsub.ac.id',
                description: 'Ketua Program Studi Sistem Informasi',
            },
            {
                id: 'dekan',
                role_title: 'Dekan',
                signature_title: 'Dekan',
                name: 'Dr. TEPI PEIRISAL, M.Si',
                display_name: 'Dr. Tepi Peirisal, M.Si',
                email: 'tepipeirisal@unsub.ac.id',
                description: 'Dekan Fakultas Ilmu Komputer',
            },
            {
                id: 'wadek',
                role_title: 'Wakil Dekan',
                signature_title: 'Wakil Dekan',
                name: 'BAMBANG TJAHJO UTOMO, MT',
                display_name: 'Bambang Tjahjo Utomo, MT',
                email: 'bambangtjahjoutomo@unsub.ac.id',
                description: 'Wakil Dekan Fakultas Ilmu Komputer',
            },
        ],
    },
    user: Object,
});

const form = useForm({
    letter_type_id: props.letterTypes[0]?.id || '',
    signer_id: 'kaprodi',
    student_name: props.user?.name || '',
    npm: props.user?.npm || '',
    study_program: props.user?.study_program || 'Sistem Informasi',
    birth_place: props.user?.birth_place || '',
    birth_date: props.user?.birth_date || '',
    address: props.user?.address || '',
    semester: props.user?.semester || 1,
    academic_year: props.user?.academic_year || '2026/2027',
    purpose: '',
    extra_data: {
        parent_name: '',
        parent_nip: '',
        parent_rank: '',
        parent_institution: '',
        company_name: '',
        company_address: '',
        internship_period: '',
        course_name: '',
        lecturer_name: '',
        target_recipient: '',
        target_agency: '',
        target_city: 'Tempat',
        target_address: '',
        activity_name: '',
        event_day: '',
        thesis_title: '',
        exam_type: '',
        original_score: '',
        reason: '',
        group_members: [],
        lecturer_status: '',
        nidn: '',
        semester_type: '',
        teaching_schedules: [],
        mbkm_students: [],
        ta_students: [],
        internship_duration: '',
        start_date: '',
        end_date: '',
        exam_schedules: [],
        lecturer_name: '',
        academic_rank: 'Lektor',
        expertise: '',
        teaching_courses: [
            {
                code: 'D1A12004',
                name: 'Desain WEB',
                teaching_type: '',
                class_name: '3',
                sks: 2,
                total_sks: 6,
            },
        ],
        total_sks_sum: '6',
    },
});

const addTeachingCourse = () => {
    if (!Array.isArray(form.extra_data.teaching_courses)) {
        form.extra_data.teaching_courses = [];
    }
    form.extra_data.teaching_courses.push({
        code: '',
        name: '',
        teaching_type: '',
        class_name: '1',
        sks: 2,
        total_sks: 2,
    });
    recalcTotalSks();
};

const removeTeachingCourse = (index) => {
    form.extra_data.teaching_courses.splice(index, 1);
    recalcTotalSks();
};

const recalcTotalSks = () => {
    if (Array.isArray(form.extra_data.teaching_courses)) {
        const sum = form.extra_data.teaching_courses.reduce((acc, c) => acc + (Number(c.total_sks) || 0), 0);
        form.extra_data.total_sks_sum = String(sum);
    }
};

const addGroupMember = () => {
    if (!Array.isArray(form.extra_data.group_members)) {
        form.extra_data.group_members = [];
    }
    form.extra_data.group_members.push({
        name: '',
        npm: '',
        address: 'SUBANG',
        activity: form.extra_data.activity_name || 'Seminar Usulan Penelitian',
        day: form.extra_data.event_day || 'Kamis',
    });
};

const removeGroupMember = (index) => {
    form.extra_data.group_members.splice(index, 1);
};

const addMbkmStudent = () => {
    if (!Array.isArray(form.extra_data.mbkm_students)) {
        form.extra_data.mbkm_students = [];
    }
    form.extra_data.mbkm_students.push({
        name: '',
        npm: '',
        mitra_project: '',
        role: 'Pembimbing I',
    });
};

const removeMbkmStudent = (index) => {
    form.extra_data.mbkm_students.splice(index, 1);
};

const addTaStudent = () => {
    if (!Array.isArray(form.extra_data.ta_students)) {
        form.extra_data.ta_students = [];
    }
    form.extra_data.ta_students.push({
        name: '',
        npm: '',
        role: 'Pembimbing I',
    });
};

const removeTaStudent = (index) => {
    form.extra_data.ta_students.splice(index, 1);
};

const addTeachingSchedule = () => {
    if (!Array.isArray(form.extra_data.teaching_schedules)) {
        form.extra_data.teaching_schedules = [];
    }
    form.extra_data.teaching_schedules.push({
        course_name: '',
        code: '',
        sks: 2,
        class_name: '',
        day: 'Kamis',
        time: '18.30-20.00',
        room: 'Lab II',
    });
};

const removeTeachingSchedule = (index) => {
    form.extra_data.teaching_schedules.splice(index, 1);
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

// Perbaikan Data PDDIKTI helpers
const addCorrectionItem = () => {
    if (!Array.isArray(form.extra_data.correction_items)) {
        form.extra_data.correction_items = [];
    }
    form.extra_data.correction_items.push({
        npm: '',
        name: '',
        initial_data: '',
        proposed_correction: '',
    });
};

const removeCorrectionItem = (index) => {
    form.extra_data.correction_items.splice(index, 1);
};

const selectedType = computed(() => {
    return props.letterTypes.find((t) => t.id === Number(form.letter_type_id)) || props.letterTypes[0];
});

watch(
    () => selectedType.value,
    (type) => {
        if (type?.code === 'tugas_mengajar') {
            form.signer_id = 'wadek';
            if (!form.extra_data.teaching_schedules) {
                form.extra_data.teaching_schedules = [];
            }
        } else if (type?.code === 'tugas_pembimbing_mbkm') {
            form.signer_id = 'wadek';
            if (!form.extra_data.mbkm_students) {
                form.extra_data.mbkm_students = [];
            }
        } else if (type?.code === 'tugas_pembimbing_ta') {
            form.signer_id = 'wadek';
            if (!form.extra_data.ta_students) {
                form.extra_data.ta_students = [];
            }
        } else if (type?.code === 'izin_seminar_up' || type?.code === 'izin_sidang_ta') {
            if (!form.extra_data.target_city) {
                form.extra_data.target_city = 'Tempat';
            }
        } else if (type?.code === 'pengantar_magang' || type?.code === 'pengantar_pkl') {
            if (!form.extra_data.target_city) {
                form.extra_data.target_city = 'Tempat';
            }
        } else if (type?.code === 'izin_mengikuti_ujian') {
            if (!form.extra_data.target_city) {
                form.extra_data.target_city = 'Tempat';
            }
            if (!form.extra_data.semester_type) {
                form.extra_data.semester_type = form.semester % 2 === 1 ? 'Ganjil' : 'Genap';
            }
            if (!form.extra_data.exam_schedules) {
                form.extra_data.exam_schedules = [];
            }
        } else if (type?.code === 'undangan_rapat_akademik') {
            if (!form.extra_data.meeting_type) {
                form.extra_data.meeting_type = 'offline';
            }
        } else if (type?.code === 'rekomendasi_pinjaman_bjb') {
            if (!form.extra_data.employee_name) form.extra_data.employee_name = '';
            if (!form.extra_data.employee_nik) form.extra_data.employee_nik = '';
            if (!form.extra_data.employee_position) form.extra_data.employee_position = '';
            if (!form.extra_data.loan_purpose) form.extra_data.loan_purpose = '';
            if (!form.extra_data.bank_name) form.extra_data.bank_name = 'Bank BJB';
            if (!form.extra_data.dekan_name) form.extra_data.dekan_name = 'Dr. Tepi Peirisal, S.Sos, M.Si';
            if (!form.extra_data.dekan_nik) form.extra_data.dekan_nik = '0410420142';
            if (!form.extra_data.dekan_position) form.extra_data.dekan_position = 'Dekan Fakultas Ilmu Komputer';
            const dekanSigner = props.signers.find((s) => s.code === 'dekan' || s.id === 'dekan');
            if (dekanSigner) {
                form.signer_id = dekanSigner.id;
            }
        } else if (type?.code === 'rekomendasi_beasiswa') {
            if (!form.extra_data.scholarship_name) form.extra_data.scholarship_name = '';
            if (!form.extra_data.scholarship_provider) form.extra_data.scholarship_provider = '';
            if (!form.extra_data.pejabat_fakultas) form.extra_data.pejabat_fakultas = 'Ilmu Komputer Universitas Subang';
            const kaprodiSigner = props.signers.find((s) => s.code === 'kaprodi' || s.id === 'kaprodi');
            if (kaprodiSigner) {
                form.signer_id = kaprodiSigner.id;
            }
        } else if (type?.code === 'rekomendasi_luar_prodi') {
            if (!form.extra_data.program_name) form.extra_data.program_name = '';
            if (!form.extra_data.activity_period) form.extra_data.activity_period = '';
            if (!form.extra_data.pejabat_prodi) form.extra_data.pejabat_prodi = 'Sistem Informasi';
            if (!form.extra_data.pejabat_fakultas) form.extra_data.pejabat_fakultas = 'Ilmu Komputer Universitas Subang';
            if (!form.extra_data.fakultas_mahasiswa) form.extra_data.fakultas_mahasiswa = 'Ilmu Komputer Universitas Subang';
            const wadekSigner = props.signers.find((s) => s.code === 'wadek' || s.id === 'wadek');
            if (wadekSigner) {
                form.signer_id = wadekSigner.id;
            }
        } else if (type?.code === 'pengajuan_blanko_ijazah_transkrip') {
            if (!form.extra_data.lampiran_count) {
                form.extra_data.lampiran_count = '1 (satu) berkas';
            }
            if (!form.extra_data.subject) {
                form.extra_data.subject = 'Pengajuan Blanko Ijazah dan Blanko Transkrip';
            }
            if (!form.extra_data.target_recipient) {
                form.extra_data.target_recipient = 'Rektor Universitas Subang';
            }
            if (!form.extra_data.target_city) {
                form.extra_data.target_city = 'Subang';
            }
            if (!form.extra_data.opening_text) {
                form.extra_data.opening_text = 'Bersama surat ini kami sampaikan bahwa Fakultas Ilmu Komputer Universitas Subang telah melaksanakan sidang komprehensif pada:';
            }
            if (!form.extra_data.body_text) {
                form.extra_data.body_text = 'Sehubungan dengan hal tersebut, kami mengajukan permohonan blanko ijazah dan blanko transkrip pendamping ijazah untuk mahasiswa sebagaimana data terlampir.';
            }
            if (!form.extra_data.closing_text) {
                form.extra_data.closing_text = 'Demikian surat pengajuan ini kami sampaikan untuk dapat dipergunakan sebagaimana mestinya. Atas perhatian dan kerja samanya kami ucapkan terima kasih.';
            }
            if (form.extra_data.show_total_row === undefined) {
                form.extra_data.show_total_row = false;
            }
            if (!form.extra_data.total_count_text) {
                form.extra_data.total_count_text = '12 ( Dua belas ) mahasiswa';
            }
            if (!form.extra_data.session_summary_table || form.extra_data.session_summary_table.length === 0) {
                form.extra_data.session_summary_table = [
                    {
                        session_date: '26 Juni 2026',
                        count_text: '12 ( Dua belas ) mahasiswa',
                    },
                ];
            }
        } else if (type?.code === 'pengajuan_ttd_ijazah') {
            if (!form.extra_data.lampiran_count) {
                form.extra_data.lampiran_count = '1 (satu) bendel';
            }
            if (!form.extra_data.subject) {
                form.extra_data.subject = 'Pengajuan Penandatanganan Ijazah';
            }
            if (!form.extra_data.target_recipient) {
                form.extra_data.target_recipient = 'Rektor Universitas Subang';
            }
            if (!form.extra_data.target_city) {
                form.extra_data.target_city = 'Subang';
            }
            if (!form.extra_data.opening_text) {
                form.extra_data.opening_text = 'Bersama surat ini kami sampaikan bahwa Fakultas Ilmu Komputer Universitas Subang telah melaksanakan sidang komprehensif pada:';
            }
            if (!form.extra_data.body_text) {
                form.extra_data.body_text = 'bersamaan dengan ini kami mengajukan penandatanganan ijazah untuk mahasiswa tersebut diatas, data suplemen pengajuan terlampir.';
            }
            if (!form.extra_data.total_count_text) {
                form.extra_data.total_count_text = '32 (Tiga Puluh Dua ) mahasiswa';
            }
            if (!form.extra_data.session_summary_table || form.extra_data.session_summary_table.length === 0) {
                form.extra_data.session_summary_table = [
                    {
                        session_date: '27 Agustus 2026',
                        count_text: '32 (Tiga Puluh Dua ) mahasiswa',
                    },
                ];
            }
        } else if (type?.code === 'pembukaan_periode') {
            if (!form.extra_data.lampiran_count) {
                form.extra_data.lampiran_count = '1 (satu) berkas';
            }
            if (!form.extra_data.subject) {
                form.extra_data.subject = 'Pengajuan Pembukaan Periode Tipe 2';
            }
            if (!form.extra_data.target_recipient) {
                form.extra_data.target_recipient = "Rektor Universitas Subang\nu.p. Wakil Rektor I Bid. Akademik\ndan kemahasiswaan";
            }
            if (!form.extra_data.target_city) {
                form.extra_data.target_city = 'Tempat';
            }
            if (!form.extra_data.purpose_description) {
                form.extra_data.purpose_description = 'Sehubungan dengan kebutuhan perbaikan data PIN mahasiswa yang akan lulus, dengan ini kami mengajukan permohonan Pembukaan Periode Tipe 2 melalui Feeder PDDIKTI untuk memperbaiki data pada Program Studi Sistem Informasi (57201). Adapun data yang kami ajukan sebagaimana data terlampir.';
            }
        } else if (type?.code === 'perbaikan_data_pddikti') {
            if (!form.extra_data.lampiran_count) {
                form.extra_data.lampiran_count = '1 Rangkap';
            }
            if (!form.extra_data.subject) {
                form.extra_data.subject = 'Pengajuan Perubahan Data Mahasiswa';
            }
            if (!form.extra_data.target_recipient) {
                form.extra_data.target_recipient = "Rektor Universitas Subang\nu.p. Wakil Rektor I Bid. Akademik\ndan kemahasiswaan";
            }
            if (!form.extra_data.target_city) {
                form.extra_data.target_city = 'Tempat';
            }
            if (!form.extra_data.evidence_text) {
                form.extra_data.evidence_text = 'Data yang diubah sesuai dengan fakta dan dibuktikan dengan dokumen terlampir.';
            }
            if (!form.extra_data.correction_items || form.extra_data.correction_items.length === 0) {
                form.extra_data.correction_items = [
                    {
                        npm: 'D1A210020',
                        name: 'FANNY APRILIANTI',
                        initial_data: 'Kesalahan Nama : Fany Aprilianti',
                        proposed_correction: 'FANNY APRILIANTI',
                    },
                ];
            }
        }
    },
    { immediate: true }
);

const submit = () => {
    form.post(route('letters.store'));
};

// Master Course helpers
const activeTeachingCourseIdx = ref(null);
const teachingCourseSearchQuery = ref('');
const activeExamCourseIdx = ref(null);
const examCourseSearchQuery = ref('');
const showSingleCourseSearch = ref(false);
const singleCourseSearchQuery = ref('');

const selectTeachingCourse = (idx, course) => {
    if (form.extra_data?.teaching_schedules?.[idx]) {
        const item = form.extra_data.teaching_schedules[idx];
        item.course_name = course.name;
        item.code = course.code;
        item.sks = course.sks;
    }
    activeTeachingCourseIdx.value = null;
    teachingCourseSearchQuery.value = '';
};

const filteredTeachingCourses = computed(() => {
    if (!props.courses) return [];
    if (!teachingCourseSearchQuery.value) return props.courses.slice(0, 10);
    const q = teachingCourseSearchQuery.value.toLowerCase();
    return props.courses.filter(
        (c) => c.name?.toLowerCase().includes(q) || c.code?.toLowerCase().includes(q)
    ).slice(0, 15);
});

const selectExamCourse = (idx, course) => {
    if (form.extra_data?.exam_schedules?.[idx]) {
        const item = form.extra_data.exam_schedules[idx];
        item.course_name = course.name;
        item.sks = course.sks;
    }
    activeExamCourseIdx.value = null;
    examCourseSearchQuery.value = '';
};

const filteredExamCourses = computed(() => {
    if (!props.courses) return [];
    if (!examCourseSearchQuery.value) return props.courses.slice(0, 10);
    const q = examCourseSearchQuery.value.toLowerCase();
    return props.courses.filter(
        (c) => c.name?.toLowerCase().includes(q) || c.code?.toLowerCase().includes(q)
    ).slice(0, 15);
});

const selectSingleCourse = (course) => {
    if (form.extra_data) {
        form.extra_data.course_name = course.name;
        form.extra_data.course_code = course.code;
        form.extra_data.sks = course.sks;
    }
    showSingleCourseSearch.value = false;
    singleCourseSearchQuery.value = '';
};

const filteredSingleCourses = computed(() => {
    if (!props.courses) return [];
    if (!singleCourseSearchQuery.value) return props.courses.slice(0, 10);
    const q = singleCourseSearchQuery.value.toLowerCase();
    return props.courses.filter(
        (c) => c.name?.toLowerCase().includes(q) || c.code?.toLowerCase().includes(q)
    ).slice(0, 15);
});
</script>

<template>
    <Head title="Buat Surat Keluar" />

    <AuthenticatedLayout>
        <div class="py-8">
            <div class="w-full px-4 sm:px-6 lg:px-8">
                <!-- TOP HEADER -->
                <div class="flex items-center gap-3 mb-6">
                    <Link
                        :href="route('letters.index')"
                        class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold text-slate-900 dark:text-white">
                            Formulir Permohonan Surat Mahasiswa
                        </h1>
                        <p class="text-xs text-slate-500">
                            Program Studi Sistem Informasi - Fakultas Ilmu Komputer Universitas Subang
                        </p>
                    </div>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- PILIH JENIS SURAT -->
                    <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs space-y-4">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
                            <h2 class="text-sm font-bold text-slate-900 dark:text-white">1. Pilih Jenis Surat</h2>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                Kategori / Jenis Surat yang Dibutuhkan:
                            </label>
                            <select
                                v-model="form.letter_type_id"
                                class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            >
                                <option v-for="type in letterTypes" :key="type.id" :value="type.id">
                                    {{ type.name }}
                                </option>
                            </select>
                            <p class="text-[11px] text-slate-500 mt-1.5 italic">
                                {{ selectedType?.description }}
                            </p>
                        </div>
                    </div>

                    <!-- PILIH PEJABAT PENANDATANGAN -->
                    <div
                        v-if="!['kesanggupan_mengajar', 'pakta_integritas', 'pengajuan_dana'].includes(selectedType?.code)"
                        class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs space-y-4"
                    >
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
                                <h2 class="text-sm font-bold text-slate-900 dark:text-white">2. Pilih Pejabat Penandatangan Surat</h2>
                            </div>
                            <span class="text-[11px] text-slate-400">Pilih salah satu dari 3 pejabat resmi Fasilkom UNSUB</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <label
                                v-for="signer in signers"
                                :key="signer.id"
                                :class="[
                                    'relative flex flex-col p-4 rounded-xl border-2 cursor-pointer transition text-left select-none',
                                    form.signer_id === signer.id
                                        ? 'border-indigo-600 bg-indigo-50/60 dark:bg-indigo-950/40 shadow-sm ring-2 ring-indigo-500/20'
                                        : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-white dark:bg-slate-900'
                                ]"
                            >
                                <div class="flex items-center justify-between mb-2">
                                    <span
                                        :class="[
                                            'px-2 py-0.5 text-[10px] font-bold rounded-md uppercase tracking-wider',
                                            form.signer_id === signer.id ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'
                                        ]"
                                    >
                                        {{ signer.role_title }}
                                    </span>
                                    <input
                                        type="radio"
                                        name="signer_id"
                                        :value="signer.id"
                                        v-model="form.signer_id"
                                        class="w-4 h-4 text-indigo-600 focus:ring-indigo-500 border-slate-300"
                                    />
                                </div>
                                <div class="font-bold text-xs text-slate-900 dark:text-white">
                                    {{ signer.display_name }}
                                </div>
                                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                                    {{ signer.description }}
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- DATA IDENTITAS MAHASISWA / DOSEN -->
                    <div
                        v-if="!['pengajuan_dana', 'pengadaan_sarpras', 'perbaikan_sarpras', 'pemberitahuan_ujian_dosen', 'pengumuman_ujian_mahasiswa', 'perbaikan_data_pddikti', 'pembukaan_periode'].includes(selectedType?.code)"
                        class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs space-y-4"
                    >
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
                            <h2 class="text-sm font-bold text-slate-900 dark:text-white">
                                3. {{ ['tugas_mengajar', 'tugas_pembimbing_mbkm', 'tugas_pembimbing_ta', 'undangan_rapat_akademik', 'kesanggupan_mengajar', 'pakta_integritas'].includes(selectedType?.code) ? (selectedType?.code === 'undangan_rapat_akademik' ? 'Data Penerima Undangan / Dosen yang Diundang' : (['kesanggupan_mengajar', 'pakta_integritas'].includes(selectedType?.code) ? 'Data Dosen Yang Bertandatangan (Dosen ybs)' : 'Data Dosen / Tenaga Pengajar')) : 'Data Pemohon (Mahasiswa)' }}
                            </h2>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    {{ selectedType?.code === 'undangan_rapat_akademik' ? 'Nama Lengkap Penerima Undangan / Dosen (dengan Gelar)' : (['tugas_mengajar', 'tugas_pembimbing_mbkm', 'tugas_pembimbing_ta', 'kesanggupan_mengajar', 'pakta_integritas'].includes(selectedType?.code) ? 'Nama Lengkap Dosen (dengan Gelar)' : 'Nama Lengkap Mahasiswa') }}
                                </label>
                                <input
                                    v-model="form.student_name"
                                    type="text"
                                    required
                                    :placeholder="selectedType?.code === 'undangan_rapat_akademik' ? 'Bagus Ali Akbar, S.Si., M.Kom' : (['tugas_mengajar', 'tugas_pembimbing_mbkm', 'tugas_pembimbing_ta', 'kesanggupan_mengajar', 'pakta_integritas'].includes(selectedType?.code) ? 'BAGUS ALI AKBAR, S.Si., M.Kom' : 'LUNA AMELLA WARDHANI')"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 uppercase"
                                    @input="form.extra_data.lecturer_name = form.student_name"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    {{ selectedType?.code === 'undangan_rapat_akademik' ? 'NIDN / NIP / -' : (['tugas_mengajar', 'tugas_pembimbing_mbkm', 'tugas_pembimbing_ta', 'kesanggupan_mengajar', 'pakta_integritas'].includes(selectedType?.code) ? 'NIDN / NIP Dosen' : 'Nomor Pokok Mahasiswa (NPM)') }}
                                </label>
                                <input
                                    v-model="form.npm"
                                    type="text"
                                    required
                                    :placeholder="selectedType?.code === 'undangan_rapat_akademik' ? '0410019202' : (['tugas_mengajar', 'tugas_pembimbing_mbkm', 'tugas_pembimbing_ta', 'kesanggupan_mengajar', 'pakta_integritas'].includes(selectedType?.code) ? '0410019202' : 'D1A260029')"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 font-mono"
                                    @input="form.extra_data.nidn = form.npm"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Program Studi</label>
                                <input
                                    v-model="form.study_program"
                                    type="text"
                                    required
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                />
                            </div>

                            <div v-if="!['tugas_mengajar', 'tugas_pembimbing_mbkm', 'tugas_pembimbing_ta', 'undangan_rapat_akademik', 'kesanggupan_mengajar', 'pakta_integritas'].includes(selectedType?.code)" class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Tempat Lahir</label>
                                    <input
                                        v-model="form.birth_place"
                                        type="text"
                                        placeholder="Wonogiri"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Tanggal Lahir</label>
                                    <input
                                        v-model="form.birth_date"
                                        type="date"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div v-if="['tugas_mengajar', 'tugas_pembimbing_mbkm', 'tugas_pembimbing_ta', 'undangan_rapat_akademik', 'kesanggupan_mengajar', 'pakta_integritas'].includes(selectedType?.code)">
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Semester Perkuliahan</label>
                                    <select
                                        v-model="form.extra_data.semester_type"
                                        @change="form.semester = form.extra_data.semester_type === 'Ganjil' ? 1 : 2"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                    >
                                        <option value="Ganjil">Semester Ganjil</option>
                                        <option value="Genap">Semester Genap</option>
                                    </select>
                                </div>
                                <div v-else>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Semester Aktif</label>
                                    <select
                                        v-model="form.semester"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
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
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                    />
                                </div>
                            </div>

                            <div v-if="!['tugas_mengajar', 'tugas_pembimbing_mbkm', 'tugas_pembimbing_ta', 'undangan_rapat_akademik', 'kesanggupan_mengajar', 'pakta_integritas'].includes(selectedType?.code)">
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Keperluan Pembuatan Surat</label>
                                <input
                                    v-model="form.purpose"
                                    type="text"
                                    placeholder="Lampiran Tunjangan Gaji / Beasiswa / Administrasi"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                />
                            </div>
                        </div>

                        <div v-if="['tugas_mengajar', 'tugas_pembimbing_mbkm', 'tugas_pembimbing_ta'].includes(selectedType?.code)" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Status Tenaga Pengajar</label>
                                <select
                                    v-model="form.extra_data.lecturer_status"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                >
                                    <option value="Dosen Tetap">Dosen Tetap</option>
                                    <option value="Dosen Luar Biasa">Dosen Luar Biasa</option>
                                    <option value="Dosen Praktisi">Dosen Praktisi / Mitra</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Semester (Ganjil/Genap)</label>
                                <select
                                    v-model="form.extra_data.semester_type"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 font-semibold"
                                >
                                    <option value="">Pilih Semester</option>
                                    <option value="Ganjil">Ganjil</option>
                                    <option value="Genap">Genap</option>
                                </select>
                            </div>
                        </div>
                        <div v-else-if="selectedType?.code !== 'pakta_integritas'">
                            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Alamat Lengkap (Domisili)</label>
                            <textarea
                                v-model="form.address"
                                rows="2"
                                placeholder="Alamat domisili saat ini..."
                                class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            ></textarea>
                        </div>
                    </div>

                    <!-- DATA KHUSUS (DOKUMEN SPESIFIK) -->
                    <div
                        v-if="['keterangan_kuliah_pns', 'pengantar_magang', 'pengantar_pkl', 'observasi_matkul', 'izin_penelitian_skripsi', 'izin_seminar_up', 'izin_sidang_ta', 'tugas_mengajar', 'tugas_pembimbing_mbkm', 'tugas_pembimbing_ta', 'ujian_susulan', 'izin_mengikuti_ujian', 'perbaikan_nilai', 'keterangan_yudisium', 'undangan_rapat_akademik', 'kesanggupan_mengajar', 'pakta_integritas', 'pengajuan_dana', 'pengadaan_sarpras', 'perbaikan_sarpras', 'pemberitahuan_ujian_dosen', 'pengumuman_ujian_mahasiswa', 'rekomendasi_beasiswa', 'rekomendasi_luar_prodi'].includes(selectedType?.code)"
                        class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs space-y-4"
                    >
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
                            <h2 class="text-sm font-bold text-slate-900 dark:text-white">
                                4. Informasi Tambahan Khusus Surat ({{ selectedType?.name }})
                            </h2>
                        </div>

                        <!-- KHUSUS SURAT PNS (DATA ORANG TUA) -->
                        <div v-if="selectedType?.code === 'keterangan_kuliah_pns'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Orang Tua / Wali (PNS)</label>
                                <input
                                    v-model="form.extra_data.parent_name"
                                    type="text"
                                    placeholder="Nama lengkap & gelar orang tua"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">NIP / NRP Orang Tua</label>
                                <input
                                    v-model="form.extra_data.parent_nip"
                                    type="text"
                                    placeholder="NIP orang tua"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Pangkat / Golongan Ruang</label>
                                <input
                                    v-model="form.extra_data.parent_rank"
                                    type="text"
                                    placeholder="Pembina / IV.a"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Instansi / Unit Kerja Orang Tua</label>
                                <input
                                    v-model="form.extra_data.parent_institution"
                                    type="text"
                                    placeholder="Dinas Pendidikan Kabupaten Subang"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                />
                            </div>
                        </div>

                        <!-- KHUSUS PENGANTAR MAGANG (MBKM) -->
                        <div v-else-if="selectedType?.code === 'pengantar_magang'" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Tujuan Penerima Surat (Kepada Yth.) <span class="text-rose-500">*</span>
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
                                        Nama Instansi / Perusahaan Tujuan <span class="text-rose-500">*</span>
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
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Alamat / Kota Instansi Tujuan</label>
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
                                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Tanggal Mulai Pelaksanaan</label>
                                        <input
                                            v-model="form.extra_data.start_date"
                                            type="text"
                                            placeholder="14 September 2026"
                                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Tanggal Selesai Pelaksanaan</label>
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
                                            Pemohon utama di atas otomatis menjadi <strong>No. 1</strong> pada Lampiran Surat. Tambahkan peserta jika magang dilakukan bersama rekan kelompok.
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
                                        <span>+ Tambah Mahasiswa</span>
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
                                                    Nomor Pokok Mahasiswa (NPM) <span class="text-rose-500">*</span>
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



                        <!-- 19. SURAT PENGANTAR PERBAIKAN DATA MAHASISWA PDDIKTI -->
                        <div
                            v-else-if="selectedType?.code === 'perbaikan_data_pddikti'"
                            class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs space-y-5"
                        >
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-teal-600"></span>
                                <h2 class="text-sm font-bold text-slate-900 dark:text-white">
                                    3. Data Permohonan Perbaikan Data Mahasiswa PDDIKTI
                                </h2>
                            </div>

                            <!-- Header Memo & Tujuan -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Lampiran Surat
                                    </label>
                                    <input
                                        v-model="form.extra_data.lampiran_count"
                                        type="text"
                                        placeholder="1 Rangkap"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Perihal Surat <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.subject"
                                        type="text"
                                        placeholder="Pengajuan Perubahan Data Mahasiswa"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white font-bold"
                                    />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Tujuan Surat (Kepada Yth.)
                                    </label>
                                    <textarea
                                        v-model="form.extra_data.target_recipient"
                                        rows="3"
                                        placeholder="Rektor Universitas Subang&#10;u.p. Wakil Rektor I Bid. Akademik&#10;dan kemahasiswaan"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    ></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Kota / Lokasi Tujuan
                                    </label>
                                    <input
                                        v-model="form.extra_data.target_city"
                                        type="text"
                                        placeholder="Tempat"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Pernyataan Bukti Dokumen
                                    </label>
                                    <input
                                        v-model="form.extra_data.evidence_text"
                                        type="text"
                                        placeholder="Data yang diubah sesuai dengan fakta dan dibuktikan dengan dokumen terlampir."
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white text-xs"
                                    />
                                </div>
                            </div>

                            <!-- Dynamic Correction Table -->
                            <div class="space-y-3 pt-3 border-t border-slate-200 dark:border-slate-700">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <label class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                            Tabel Usulan Perbaikan Data Mahasiswa
                                        </label>
                                        <p class="text-[11px] text-slate-400">Rincian data awal dan usulan perbaikan data mahasiswa</p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="addCorrectionItem"
                                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold text-teal-700 bg-teal-50 dark:bg-teal-900/40 hover:bg-teal-100 flex items-center gap-1"
                                    >
                                        + Tambah Mahasiswa
                                    </button>
                                </div>

                                <div class="space-y-3">
                                    <div
                                        v-for="(item, cIdx) in form.extra_data.correction_items"
                                        :key="cIdx"
                                        class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950/60 space-y-3"
                                    >
                                        <div class="flex items-center justify-between pb-1 border-b border-slate-200 dark:border-slate-800">
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                                Mahasiswa #{{ cIdx + 1 }}
                                            </span>
                                            <button
                                                v-if="form.extra_data.correction_items.length > 1"
                                                type="button"
                                                @click="removeCorrectionItem(cIdx)"
                                                class="text-xs font-semibold text-rose-500 hover:text-rose-700"
                                            >
                                                Hapus ✕
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                                            <div class="md:col-span-4">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    NPM <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.npm"
                                                    type="text"
                                                    placeholder="D1A210020"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-mono uppercase"
                                                />
                                            </div>
                                            <div class="md:col-span-8">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Nama Lengkap Mahasiswa <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.name"
                                                    type="text"
                                                    placeholder="FANNY APRILIANTI"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-bold uppercase"
                                                />
                                            </div>
                                            <div class="md:col-span-6">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Data Awal <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.initial_data"
                                                    type="text"
                                                    placeholder="Kesalahan Nama : Fany Aprilianti"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white italic"
                                                />
                                            </div>
                                            <div class="md:col-span-6">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Usulan Perbaikan <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.proposed_correction"
                                                    type="text"
                                                    placeholder="FANNY APRILIANTI"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-bold"
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
                                        Tujuan Penerima Surat (Kepada Yth.) <span class="text-rose-500">*</span>
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
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Alamat / Kota Instansi Tujuan</label>
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
                                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Tanggal Mulai Pelaksanaan</label>
                                        <input
                                            v-model="form.extra_data.start_date"
                                            type="text"
                                            placeholder="06 Oktober 2025"
                                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Tanggal Selesai Pelaksanaan</label>
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
                                        <span>+ Tambah Mahasiswa</span>
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
                                                    Nomor Pokok Mahasiswa (NPM) <span class="text-rose-500">*</span>
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
                                                    Alamat Lengkap (Domisili) <span class="text-rose-500">*</span>
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



                        <!-- 19. SURAT PENGANTAR PERBAIKAN DATA MAHASISWA PDDIKTI -->
                        <div
                            v-else-if="selectedType?.code === 'perbaikan_data_pddikti'"
                            class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs space-y-5"
                        >
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-teal-600"></span>
                                <h2 class="text-sm font-bold text-slate-900 dark:text-white">
                                    3. Data Permohonan Perbaikan Data Mahasiswa PDDIKTI
                                </h2>
                            </div>

                            <!-- Header Memo & Tujuan -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Lampiran Surat
                                    </label>
                                    <input
                                        v-model="form.extra_data.lampiran_count"
                                        type="text"
                                        placeholder="1 Rangkap"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Perihal Surat <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.subject"
                                        type="text"
                                        placeholder="Pengajuan Perubahan Data Mahasiswa"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white font-bold"
                                    />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Tujuan Surat (Kepada Yth.)
                                    </label>
                                    <textarea
                                        v-model="form.extra_data.target_recipient"
                                        rows="3"
                                        placeholder="Rektor Universitas Subang&#10;u.p. Wakil Rektor I Bid. Akademik&#10;dan kemahasiswaan"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    ></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Kota / Lokasi Tujuan
                                    </label>
                                    <input
                                        v-model="form.extra_data.target_city"
                                        type="text"
                                        placeholder="Tempat"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Pernyataan Bukti Dokumen
                                    </label>
                                    <input
                                        v-model="form.extra_data.evidence_text"
                                        type="text"
                                        placeholder="Data yang diubah sesuai dengan fakta dan dibuktikan dengan dokumen terlampir."
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white text-xs"
                                    />
                                </div>
                            </div>

                            <!-- Dynamic Correction Table -->
                            <div class="space-y-3 pt-3 border-t border-slate-200 dark:border-slate-700">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <label class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                            Tabel Usulan Perbaikan Data Mahasiswa
                                        </label>
                                        <p class="text-[11px] text-slate-400">Rincian data awal dan usulan perbaikan data mahasiswa</p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="addCorrectionItem"
                                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold text-teal-700 bg-teal-50 dark:bg-teal-900/40 hover:bg-teal-100 flex items-center gap-1"
                                    >
                                        + Tambah Mahasiswa
                                    </button>
                                </div>

                                <div class="space-y-3">
                                    <div
                                        v-for="(item, cIdx) in form.extra_data.correction_items"
                                        :key="cIdx"
                                        class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950/60 space-y-3"
                                    >
                                        <div class="flex items-center justify-between pb-1 border-b border-slate-200 dark:border-slate-800">
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                                Mahasiswa #{{ cIdx + 1 }}
                                            </span>
                                            <button
                                                v-if="form.extra_data.correction_items.length > 1"
                                                type="button"
                                                @click="removeCorrectionItem(cIdx)"
                                                class="text-xs font-semibold text-rose-500 hover:text-rose-700"
                                            >
                                                Hapus ✕
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                                            <div class="md:col-span-4">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    NPM <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.npm"
                                                    type="text"
                                                    placeholder="D1A210020"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-mono uppercase"
                                                />
                                            </div>
                                            <div class="md:col-span-8">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Nama Lengkap Mahasiswa <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.name"
                                                    type="text"
                                                    placeholder="FANNY APRILIANTI"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-bold uppercase"
                                                />
                                            </div>
                                            <div class="md:col-span-6">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Data Awal <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.initial_data"
                                                    type="text"
                                                    placeholder="Kesalahan Nama : Fany Aprilianti"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white italic"
                                                />
                                            </div>
                                            <div class="md:col-span-6">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Usulan Perbaikan <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.proposed_correction"
                                                    type="text"
                                                    placeholder="FANNY APRILIANTI"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-bold"
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
                                        Tujuan Penerima Surat (Kepada Yth.) <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.target_recipient"
                                        type="text"
                                        placeholder="Kepala Kantor Samsat Kabupaten Subang"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Alamat / Kota Instansi Tujuan</label>
                                    <input
                                        v-model="form.extra_data.target_city"
                                        type="text"
                                        placeholder="Tempat atau Subang"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div class="relative">
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300">
                                            Nama Mata Kuliah <span class="text-rose-500">*</span>
                                        </label>
                                        <button
                                            type="button"
                                            @click="showSingleCourseSearch = !showSingleCourseSearch"
                                            class="text-[10px] font-bold text-blue-600 hover:text-blue-700 dark:text-blue-400 inline-flex items-center gap-0.5"
                                        >
                                            <span>📚 Pilih Master MK</span>
                                        </button>
                                    </div>

                                    <!-- DROPDOWN QUICK PICKER MATA KULIAH -->
                                    <div
                                        v-if="showSingleCourseSearch"
                                        class="absolute left-0 top-full mt-1 w-80 sm:w-96 z-30 p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-blue-200 dark:border-blue-900 shadow-xl space-y-2"
                                    >
                                        <div class="flex items-center justify-between">
                                            <span class="text-[11px] font-bold text-blue-900 dark:text-blue-300">Pilih Mata Kuliah Kurikulum:</span>
                                            <button type="button" @click="showSingleCourseSearch = false" class="text-slate-400 hover:text-slate-600 text-xs">✕</button>
                                        </div>
                                        <input
                                            v-model="singleCourseSearchQuery"
                                            type="text"
                                            placeholder="Ketik kode / nama MK..."
                                            class="w-full text-xs rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 py-1.5 px-2.5"
                                            autofocus
                                        />
                                        <div class="max-h-48 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700">
                                            <button
                                                v-for="c in filteredSingleCourses"
                                                :key="c.id"
                                                type="button"
                                                @click="selectSingleCourse(c)"
                                                class="w-full text-left py-1.5 px-2 hover:bg-blue-50 dark:hover:bg-slate-700/60 rounded transition flex items-center justify-between text-xs"
                                            >
                                                <div>
                                                    <div class="font-bold text-slate-800 dark:text-white">{{ c.name }}</div>
                                                    <div class="text-[10px] text-slate-500 font-mono">{{ c.code }} • {{ c.sks }} SKS • Sem {{ c.semester || '-' }}</div>
                                                </div>
                                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-blue-100 text-blue-800 font-bold shrink-0">Pilih</span>
                                            </button>
                                            <div v-if="filteredSingleCourses.length === 0" class="p-3 text-center text-slate-400 text-xs">
                                                Mata kuliah tidak ditemukan.
                                            </div>
                                        </div>
                                    </div>

                                    <input
                                        v-model="form.extra_data.course_name"
                                        type="text"
                                        placeholder="Konsep Sistem Informasi"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Dosen Pengampu Mata Kuliah</label>
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
                                            Pemohon utama di atas otomatis menjadi <strong>No. 1</strong>. Tambahkan anggota kelompok jika observasi dilakukan bersama tim.
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
                                                    Nomor Pokok Mahasiswa (NPM) <span class="text-rose-500">*</span>
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
                                                    Alamat Lengkap (Domisili)
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



                        <!-- 19. SURAT PENGANTAR PERBAIKAN DATA MAHASISWA PDDIKTI -->
                        <div
                            v-else-if="selectedType?.code === 'perbaikan_data_pddikti'"
                            class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs space-y-5"
                        >
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-teal-600"></span>
                                <h2 class="text-sm font-bold text-slate-900 dark:text-white">
                                    3. Data Permohonan Perbaikan Data Mahasiswa PDDIKTI
                                </h2>
                            </div>

                            <!-- Header Memo & Tujuan -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Lampiran Surat
                                    </label>
                                    <input
                                        v-model="form.extra_data.lampiran_count"
                                        type="text"
                                        placeholder="1 Rangkap"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Perihal Surat <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.subject"
                                        type="text"
                                        placeholder="Pengajuan Perubahan Data Mahasiswa"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white font-bold"
                                    />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Tujuan Surat (Kepada Yth.)
                                    </label>
                                    <textarea
                                        v-model="form.extra_data.target_recipient"
                                        rows="3"
                                        placeholder="Rektor Universitas Subang&#10;u.p. Wakil Rektor I Bid. Akademik&#10;dan kemahasiswaan"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    ></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Kota / Lokasi Tujuan
                                    </label>
                                    <input
                                        v-model="form.extra_data.target_city"
                                        type="text"
                                        placeholder="Tempat"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Pernyataan Bukti Dokumen
                                    </label>
                                    <input
                                        v-model="form.extra_data.evidence_text"
                                        type="text"
                                        placeholder="Data yang diubah sesuai dengan fakta dan dibuktikan dengan dokumen terlampir."
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white text-xs"
                                    />
                                </div>
                            </div>

                            <!-- Dynamic Correction Table -->
                            <div class="space-y-3 pt-3 border-t border-slate-200 dark:border-slate-700">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <label class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                            Tabel Usulan Perbaikan Data Mahasiswa
                                        </label>
                                        <p class="text-[11px] text-slate-400">Rincian data awal dan usulan perbaikan data mahasiswa</p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="addCorrectionItem"
                                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold text-teal-700 bg-teal-50 dark:bg-teal-900/40 hover:bg-teal-100 flex items-center gap-1"
                                    >
                                        + Tambah Mahasiswa
                                    </button>
                                </div>

                                <div class="space-y-3">
                                    <div
                                        v-for="(item, cIdx) in form.extra_data.correction_items"
                                        :key="cIdx"
                                        class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950/60 space-y-3"
                                    >
                                        <div class="flex items-center justify-between pb-1 border-b border-slate-200 dark:border-slate-800">
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                                Mahasiswa #{{ cIdx + 1 }}
                                            </span>
                                            <button
                                                v-if="form.extra_data.correction_items.length > 1"
                                                type="button"
                                                @click="removeCorrectionItem(cIdx)"
                                                class="text-xs font-semibold text-rose-500 hover:text-rose-700"
                                            >
                                                Hapus ✕
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                                            <div class="md:col-span-4">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    NPM <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.npm"
                                                    type="text"
                                                    placeholder="D1A210020"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-mono uppercase"
                                                />
                                            </div>
                                            <div class="md:col-span-8">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Nama Lengkap Mahasiswa <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.name"
                                                    type="text"
                                                    placeholder="FANNY APRILIANTI"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-bold uppercase"
                                                />
                                            </div>
                                            <div class="md:col-span-6">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Data Awal <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.initial_data"
                                                    type="text"
                                                    placeholder="Kesalahan Nama : Fany Aprilianti"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white italic"
                                                />
                                            </div>
                                            <div class="md:col-span-6">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Usulan Perbaikan <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.proposed_correction"
                                                    type="text"
                                                    placeholder="FANNY APRILIANTI"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-bold"
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
                                        Tujuan Penerima Surat (Kepada Yth.) <span class="text-rose-500">*</span>
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
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Alamat / Kota Instansi Tujuan</label>
                                    <input
                                        v-model="form.extra_data.target_city"
                                        type="text"
                                        placeholder="Tempat atau Subang"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Judul Penelitian / Skripsi (Opsional)</label>
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
                                                    Nomor Pokok Mahasiswa (NPM) <span class="text-rose-500">*</span>
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
                                                    Alamat Lengkap (Domisili)
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



                        <!-- 19. SURAT PENGANTAR PERBAIKAN DATA MAHASISWA PDDIKTI -->
                        <div
                            v-else-if="selectedType?.code === 'perbaikan_data_pddikti'"
                            class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs space-y-5"
                        >
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-teal-600"></span>
                                <h2 class="text-sm font-bold text-slate-900 dark:text-white">
                                    3. Data Permohonan Perbaikan Data Mahasiswa PDDIKTI
                                </h2>
                            </div>

                            <!-- Header Memo & Tujuan -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Lampiran Surat
                                    </label>
                                    <input
                                        v-model="form.extra_data.lampiran_count"
                                        type="text"
                                        placeholder="1 Rangkap"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Perihal Surat <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.subject"
                                        type="text"
                                        placeholder="Pengajuan Perubahan Data Mahasiswa"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white font-bold"
                                    />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Tujuan Surat (Kepada Yth.)
                                    </label>
                                    <textarea
                                        v-model="form.extra_data.target_recipient"
                                        rows="3"
                                        placeholder="Rektor Universitas Subang&#10;u.p. Wakil Rektor I Bid. Akademik&#10;dan kemahasiswaan"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    ></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Kota / Lokasi Tujuan
                                    </label>
                                    <input
                                        v-model="form.extra_data.target_city"
                                        type="text"
                                        placeholder="Tempat"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Pernyataan Bukti Dokumen
                                    </label>
                                    <input
                                        v-model="form.extra_data.evidence_text"
                                        type="text"
                                        placeholder="Data yang diubah sesuai dengan fakta dan dibuktikan dengan dokumen terlampir."
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white text-xs"
                                    />
                                </div>
                            </div>

                            <!-- Dynamic Correction Table -->
                            <div class="space-y-3 pt-3 border-t border-slate-200 dark:border-slate-700">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <label class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                            Tabel Usulan Perbaikan Data Mahasiswa
                                        </label>
                                        <p class="text-[11px] text-slate-400">Rincian data awal dan usulan perbaikan data mahasiswa</p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="addCorrectionItem"
                                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold text-teal-700 bg-teal-50 dark:bg-teal-900/40 hover:bg-teal-100 flex items-center gap-1"
                                    >
                                        + Tambah Mahasiswa
                                    </button>
                                </div>

                                <div class="space-y-3">
                                    <div
                                        v-for="(item, cIdx) in form.extra_data.correction_items"
                                        :key="cIdx"
                                        class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950/60 space-y-3"
                                    >
                                        <div class="flex items-center justify-between pb-1 border-b border-slate-200 dark:border-slate-800">
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                                Mahasiswa #{{ cIdx + 1 }}
                                            </span>
                                            <button
                                                v-if="form.extra_data.correction_items.length > 1"
                                                type="button"
                                                @click="removeCorrectionItem(cIdx)"
                                                class="text-xs font-semibold text-rose-500 hover:text-rose-700"
                                            >
                                                Hapus ✕
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                                            <div class="md:col-span-4">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    NPM <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.npm"
                                                    type="text"
                                                    placeholder="D1A210020"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-mono uppercase"
                                                />
                                            </div>
                                            <div class="md:col-span-8">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Nama Lengkap Mahasiswa <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.name"
                                                    type="text"
                                                    placeholder="FANNY APRILIANTI"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-bold uppercase"
                                                />
                                            </div>
                                            <div class="md:col-span-6">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Data Awal <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.initial_data"
                                                    type="text"
                                                    placeholder="Kesalahan Nama : Fany Aprilianti"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white italic"
                                                />
                                            </div>
                                            <div class="md:col-span-6">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Usulan Perbaikan <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.proposed_correction"
                                                    type="text"
                                                    placeholder="FANNY APRILIANTI"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-bold"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- KHUSUS SURAT KETERANGAN MENGIKUTI SEMINAR UP / SIDANG TA -->
                        <div v-else-if="selectedType?.code === 'izin_seminar_up' || selectedType?.code === 'izin_sidang_ta'" class="space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    Perihal Surat <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="form.extra_data.subject"
                                    type="text"
                                    :placeholder="selectedType?.code === 'izin_sidang_ta' ? 'Permohonan Izin Mengikuti Sidang Tugas Akhir' : 'Permohonan Izin Mengikuti Seminar Usulan Penelitian'"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white font-bold"
                                />
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Tujuan Penerima Surat (Kepada Yth.) <span class="text-rose-500">*</span>
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
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Alamat / Kota Instansi Tujuan</label>
                                    <input
                                        v-model="form.extra_data.target_city"
                                        type="text"
                                        placeholder="Tempat atau Subang"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Nama Agenda / Kegiatan <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.activity_name"
                                        type="text"
                                        :placeholder="selectedType?.code === 'izin_sidang_ta' ? 'Sidang Tugas Akhir' : 'Seminar Usulan Penelitian'"
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
                                        <span>+ Tambah Peserta</span>
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
                                                    Nomor Pokok Mahasiswa (NPM) <span class="text-rose-500">*</span>
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



                        <!-- 19. SURAT PENGANTAR PERBAIKAN DATA MAHASISWA PDDIKTI -->
                        <div
                            v-else-if="selectedType?.code === 'perbaikan_data_pddikti'"
                            class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs space-y-5"
                        >
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-teal-600"></span>
                                <h2 class="text-sm font-bold text-slate-900 dark:text-white">
                                    3. Data Permohonan Perbaikan Data Mahasiswa PDDIKTI
                                </h2>
                            </div>

                            <!-- Header Memo & Tujuan -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Lampiran Surat
                                    </label>
                                    <input
                                        v-model="form.extra_data.lampiran_count"
                                        type="text"
                                        placeholder="1 Rangkap"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Perihal Surat <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.subject"
                                        type="text"
                                        placeholder="Pengajuan Perubahan Data Mahasiswa"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white font-bold"
                                    />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Tujuan Surat (Kepada Yth.)
                                    </label>
                                    <textarea
                                        v-model="form.extra_data.target_recipient"
                                        rows="3"
                                        placeholder="Rektor Universitas Subang&#10;u.p. Wakil Rektor I Bid. Akademik&#10;dan kemahasiswaan"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    ></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Kota / Lokasi Tujuan
                                    </label>
                                    <input
                                        v-model="form.extra_data.target_city"
                                        type="text"
                                        placeholder="Tempat"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Pernyataan Bukti Dokumen
                                    </label>
                                    <input
                                        v-model="form.extra_data.evidence_text"
                                        type="text"
                                        placeholder="Data yang diubah sesuai dengan fakta dan dibuktikan dengan dokumen terlampir."
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white text-xs"
                                    />
                                </div>
                            </div>

                            <!-- Dynamic Correction Table -->
                            <div class="space-y-3 pt-3 border-t border-slate-200 dark:border-slate-700">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <label class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                            Tabel Usulan Perbaikan Data Mahasiswa
                                        </label>
                                        <p class="text-[11px] text-slate-400">Rincian data awal dan usulan perbaikan data mahasiswa</p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="addCorrectionItem"
                                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold text-teal-700 bg-teal-50 dark:bg-teal-900/40 hover:bg-teal-100 flex items-center gap-1"
                                    >
                                        + Tambah Mahasiswa
                                    </button>
                                </div>

                                <div class="space-y-3">
                                    <div
                                        v-for="(item, cIdx) in form.extra_data.correction_items"
                                        :key="cIdx"
                                        class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950/60 space-y-3"
                                    >
                                        <div class="flex items-center justify-between pb-1 border-b border-slate-200 dark:border-slate-800">
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                                Mahasiswa #{{ cIdx + 1 }}
                                            </span>
                                            <button
                                                v-if="form.extra_data.correction_items.length > 1"
                                                type="button"
                                                @click="removeCorrectionItem(cIdx)"
                                                class="text-xs font-semibold text-rose-500 hover:text-rose-700"
                                            >
                                                Hapus ✕
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                                            <div class="md:col-span-4">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    NPM <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.npm"
                                                    type="text"
                                                    placeholder="D1A210020"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-mono uppercase"
                                                />
                                            </div>
                                            <div class="md:col-span-8">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Nama Lengkap Mahasiswa <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.name"
                                                    type="text"
                                                    placeholder="FANNY APRILIANTI"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-bold uppercase"
                                                />
                                            </div>
                                            <div class="md:col-span-6">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Data Awal <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.initial_data"
                                                    type="text"
                                                    placeholder="Kesalahan Nama : Fany Aprilianti"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white italic"
                                                />
                                            </div>
                                            <div class="md:col-span-6">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Usulan Perbaikan <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.proposed_correction"
                                                    type="text"
                                                    placeholder="FANNY APRILIANTI"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-bold"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- KHUSUS SURAT TUGAS MENGAJAR -->
                        <div v-else-if="selectedType?.code === 'tugas_mengajar'" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Status Tenaga Pengajar <span class="text-rose-500">*</span>
                                    </label>
                                    <select
                                        v-model="form.extra_data.lecturer_status"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                    >
                                        <option value="Dosen Tetap">Dosen Tetap</option>
                                        <option value="Dosen Luar Biasa">Dosen Luar Biasa</option>
                                        <option value="Dosen Praktisi">Dosen Praktisi / Mitra</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Semester Perkuliahan <span class="text-rose-500">*</span>
                                    </label>
                                    <select
                                        v-model="form.extra_data.semester_type"
                                        @change="form.semester = form.extra_data.semester_type === 'Ganjil' ? 1 : 2"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                    >
                                        <option value="Ganjil">Semester Ganjil</option>
                                        <option value="Genap">Semester Genap</option>
                                    </select>
                                </div>
                            </div>

                            <!-- TABEL JADWAL MENGAJAR / BEBAN MATA KULIAH -->
                            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 space-y-3">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                            Beban Mengajar & Jadwal Perkuliahan
                                        </h4>
                                        <p class="text-[11px] text-slate-400">
                                            Daftar mata kuliah, kode, SKS, hari, jam, dan ruang kuliah yang ditugaskan kepada Dosen.
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="addTeachingSchedule"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-blue-700 dark:text-blue-300 bg-blue-50 hover:bg-blue-100 dark:bg-blue-950/60 dark:hover:bg-blue-900/60 transition"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        <span>+ Tambah Mata Kuliah</span>
                                    </button>
                                </div>

                                <!-- Schedule Items -->
                                <div class="space-y-2">
                                    <div
                                        v-for="(item, idx) in form.extra_data.teaching_schedules"
                                        :key="idx"
                                        class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 space-y-2 text-xs"
                                    >
                                        <div class="flex items-center justify-between pb-1.5 border-b border-slate-200/60 dark:border-slate-800/60">
                                            <span class="font-bold text-blue-800 dark:text-blue-300">
                                                Mata Kuliah #{{ idx + 1 }}
                                            </span>
                                            <button
                                                v-if="form.extra_data.teaching_schedules.length > 1"
                                                type="button"
                                                @click="removeTeachingSchedule(idx)"
                                                class="p-1 text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded transition"
                                                title="Hapus Baris Ini"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-2">
                                            <div class="sm:col-span-3 relative">
                                                <div class="flex items-center justify-between mb-0.5">
                                                    <label class="block text-[10px] text-slate-500 dark:text-slate-400">Nama Mata Kuliah</label>
                                                    <button
                                                        type="button"
                                                        @click="activeTeachingCourseIdx = activeTeachingCourseIdx === idx ? null : idx"
                                                        class="text-[9px] font-bold text-blue-600 hover:text-blue-700 dark:text-blue-400 inline-flex items-center gap-0.5"
                                                    >
                                                        <span>📚 Pilih MK</span>
                                                    </button>
                                                </div>

                                                <!-- DROPDOWN QUICK PICKER MATA KULIAH UNTUK ROW INI -->
                                                <div
                                                    v-if="activeTeachingCourseIdx === idx"
                                                    class="absolute left-0 top-full mt-1 w-80 sm:w-96 z-30 p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-blue-200 dark:border-blue-900 shadow-xl space-y-2"
                                                >
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-[11px] font-bold text-blue-900 dark:text-blue-300">Pilih Mata Kuliah Kurikulum:</span>
                                                        <button type="button" @click="activeTeachingCourseIdx = null" class="text-slate-400 hover:text-slate-600 text-xs">✕</button>
                                                    </div>
                                                    <input
                                                        v-model="teachingCourseSearchQuery"
                                                        type="text"
                                                        placeholder="Ketik kode / nama MK..."
                                                        class="w-full text-xs rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 py-1.5 px-2.5"
                                                        autofocus
                                                    />
                                                    <div class="max-h-48 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700">
                                                        <button
                                                            v-for="c in filteredTeachingCourses"
                                                            :key="c.id"
                                                            type="button"
                                                            @click="selectTeachingCourse(idx, c)"
                                                            class="w-full text-left py-1.5 px-2 hover:bg-blue-50 dark:hover:bg-slate-700/60 rounded transition flex items-center justify-between text-xs"
                                                        >
                                                            <div>
                                                                <div class="font-bold text-slate-800 dark:text-white">{{ c.name }}</div>
                                                                <div class="text-[10px] text-slate-500 font-mono">{{ c.code }} • {{ c.sks }} SKS • Sem {{ c.semester || '-' }}</div>
                                                            </div>
                                                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-blue-100 text-blue-800 font-bold shrink-0">Pilih</span>
                                                        </button>
                                                        <div v-if="filteredTeachingCourses.length === 0" class="p-3 text-center text-slate-400 text-xs">
                                                            Mata kuliah tidak ditemukan.
                                                        </div>
                                                    </div>
                                                </div>

                                                <input
                                                    v-model="item.course_name"
                                                    type="text"
                                                    placeholder="Desain WEB 1C"
                                                    class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white"
                                                    required
                                                />
                                            </div>
                                            <div class="sm:col-span-2">
                                                <label class="block text-[10px] text-slate-500 dark:text-slate-400 mb-0.5">Kode</label>
                                                <input
                                                    v-model="item.code"
                                                    type="text"
                                                    placeholder="D1A12004"
                                                    class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-mono uppercase"
                                                    required
                                                />
                                            </div>
                                            <div class="sm:col-span-1">
                                                <label class="block text-[10px] text-slate-500 dark:text-slate-400 mb-0.5">SKS</label>
                                                <input v-model="item.sks" type="number" min="1" max="6" placeholder="2" class="w-full px-2 py-1.5 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white " required />
                                            </div>
                                            <div class="sm:col-span-1">
                                                <label class="block text-[10px] text-slate-500 dark:text-slate-400 mb-0.5">Kelas</label>
                                                <input v-model="item.class_name" type="text" placeholder="1C" class="w-full px-2 py-1.5 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-bold uppercase" />
                                            </div>
                                            <div class="sm:col-span-2">
                                                <label class="block text-[10px] text-slate-500 dark:text-slate-400 mb-0.5">Hari</label>
                                                <select
                                                    v-model="item.day"
                                                    class="w-full px-2 py-1.5 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white"
                                                >
                                                    <option value="Senin">Senin</option>
                                                    <option value="Selasa">Selasa</option>
                                                    <option value="Rabu">Rabu</option>
                                                    <option value="Kamis">Kamis</option>
                                                    <option value="Jum'at">Jum'at</option>
                                                    <option value="Sabtu">Sabtu</option>
                                                </select>
                                            </div>
                                            <div class="sm:col-span-2">
                                                <label class="block text-[10px] text-slate-500 dark:text-slate-400 mb-0.5">Jam</label>
                                                <input
                                                    v-model="item.time"
                                                    type="text"
                                                    placeholder="18.30-20.00"
                                                    class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white"
                                                />
                                            </div>
                                            <div class="sm:col-span-1">
                                                <label class="block text-[10px] text-slate-500 dark:text-slate-400 mb-0.5">Ruang</label>
                                                <input v-model="item.room" type="text" placeholder="Lab II" class="w-full px-2 py-1.5 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white " />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>



                        <!-- 19. SURAT PENGANTAR PERBAIKAN DATA MAHASISWA PDDIKTI -->
                        <div
                            v-else-if="selectedType?.code === 'perbaikan_data_pddikti'"
                            class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs space-y-5"
                        >
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-teal-600"></span>
                                <h2 class="text-sm font-bold text-slate-900 dark:text-white">
                                    3. Data Permohonan Perbaikan Data Mahasiswa PDDIKTI
                                </h2>
                            </div>

                            <!-- Header Memo & Tujuan -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Lampiran Surat
                                    </label>
                                    <input
                                        v-model="form.extra_data.lampiran_count"
                                        type="text"
                                        placeholder="1 Rangkap"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Perihal Surat <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.subject"
                                        type="text"
                                        placeholder="Pengajuan Perubahan Data Mahasiswa"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white font-bold"
                                    />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Tujuan Surat (Kepada Yth.)
                                    </label>
                                    <textarea
                                        v-model="form.extra_data.target_recipient"
                                        rows="3"
                                        placeholder="Rektor Universitas Subang&#10;u.p. Wakil Rektor I Bid. Akademik&#10;dan kemahasiswaan"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    ></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Kota / Lokasi Tujuan
                                    </label>
                                    <input
                                        v-model="form.extra_data.target_city"
                                        type="text"
                                        placeholder="Tempat"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Pernyataan Bukti Dokumen
                                    </label>
                                    <input
                                        v-model="form.extra_data.evidence_text"
                                        type="text"
                                        placeholder="Data yang diubah sesuai dengan fakta dan dibuktikan dengan dokumen terlampir."
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white text-xs"
                                    />
                                </div>
                            </div>

                            <!-- Dynamic Correction Table -->
                            <div class="space-y-3 pt-3 border-t border-slate-200 dark:border-slate-700">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <label class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                            Tabel Usulan Perbaikan Data Mahasiswa
                                        </label>
                                        <p class="text-[11px] text-slate-400">Rincian data awal dan usulan perbaikan data mahasiswa</p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="addCorrectionItem"
                                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold text-teal-700 bg-teal-50 dark:bg-teal-900/40 hover:bg-teal-100 flex items-center gap-1"
                                    >
                                        + Tambah Mahasiswa
                                    </button>
                                </div>

                                <div class="space-y-3">
                                    <div
                                        v-for="(item, cIdx) in form.extra_data.correction_items"
                                        :key="cIdx"
                                        class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950/60 space-y-3"
                                    >
                                        <div class="flex items-center justify-between pb-1 border-b border-slate-200 dark:border-slate-800">
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                                Mahasiswa #{{ cIdx + 1 }}
                                            </span>
                                            <button
                                                v-if="form.extra_data.correction_items.length > 1"
                                                type="button"
                                                @click="removeCorrectionItem(cIdx)"
                                                class="text-xs font-semibold text-rose-500 hover:text-rose-700"
                                            >
                                                Hapus ✕
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                                            <div class="md:col-span-4">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    NPM <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.npm"
                                                    type="text"
                                                    placeholder="D1A210020"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-mono uppercase"
                                                />
                                            </div>
                                            <div class="md:col-span-8">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Nama Lengkap Mahasiswa <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.name"
                                                    type="text"
                                                    placeholder="FANNY APRILIANTI"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-bold uppercase"
                                                />
                                            </div>
                                            <div class="md:col-span-6">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Data Awal <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.initial_data"
                                                    type="text"
                                                    placeholder="Kesalahan Nama : Fany Aprilianti"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white italic"
                                                />
                                            </div>
                                            <div class="md:col-span-6">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Usulan Perbaikan <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.proposed_correction"
                                                    type="text"
                                                    placeholder="FANNY APRILIANTI"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-bold"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- KHUSUS SURAT TUGAS PEMBIMBING MBKM -->
                        <div v-else-if="selectedType?.code === 'tugas_pembimbing_mbkm'" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Status Tenaga Pengajar <span class="text-rose-500">*</span>
                                    </label>
                                    <select
                                        v-model="form.extra_data.lecturer_status"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                    >
                                        <option value="Dosen Tetap">Dosen Tetap</option>
                                        <option value="Dosen Luar Biasa">Dosen Luar Biasa</option>
                                        <option value="Dosen Praktisi">Dosen Praktisi / Mitra</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Semester Pelaksanaan <span class="text-rose-500">*</span>
                                    </label>
                                    <select
                                        v-model="form.extra_data.semester_type"
                                        @change="form.semester = form.extra_data.semester_type === 'Ganjil' ? 1 : 2"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                    >
                                        <option value="Ganjil">Semester Ganjil</option>
                                        <option value="Genap">Semester Genap</option>
                                    </select>
                                </div>
                            </div>

                            <!-- TABEL MAHASISWA PROGRAM MBKM -->
                            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 space-y-3">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                            Daftar Mahasiswa MBKM yang Dibimbing
                                        </h4>
                                        <p class="text-[11px] text-slate-400">
                                            Nama mahasiswa, NPM, Mitra / Project MBKM, dan peran dosen pembimbing.
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="addMbkmStudent"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-blue-700 dark:text-blue-300 bg-blue-50 hover:bg-blue-100 dark:bg-blue-950/60 dark:hover:bg-blue-900/60 transition"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        <span>+ Tambah Mahasiswa MBKM</span>
                                    </button>
                                </div>

                                <!-- Student Items -->
                                <div class="space-y-2">
                                    <div
                                        v-for="(item, idx) in form.extra_data.mbkm_students"
                                        :key="idx"
                                        class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 space-y-2 text-xs"
                                    >
                                        <div class="flex items-center justify-between pb-1.5 border-b border-slate-200/60 dark:border-slate-800/60">
                                            <span class="font-bold text-blue-800 dark:text-blue-300">
                                                Mahasiswa #{{ idx + 1 }}
                                            </span>
                                            <button
                                                type="button"
                                                @click="removeMbkmStudent(idx)"
                                                class="text-rose-500 hover:text-rose-700 text-[11px] flex items-center gap-1 transition"
                                                v-if="form.extra_data.mbkm_students.length > 1"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                <span>Hapus</span>
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-2">
                                            <div class="sm:col-span-4">
                                                <label class="block text-[10px] text-slate-500 dark:text-slate-400 mb-0.5">Nama Lengkap Mahasiswa <span class="text-rose-500">*</span></label>
                                                <input
                                                    v-model="item.name"
                                                    type="text"
                                                    placeholder="LIA KARTIKA"
                                                    class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white uppercase"
                                                    required
                                                />
                                            </div>
                                            <div class="sm:col-span-3">
                                                <label class="block text-[10px] text-slate-500 dark:text-slate-400 mb-0.5">Nomor Pokok Mahasiswa (NPM) <span class="text-rose-500">*</span></label>
                                                <input
                                                    v-model="item.npm"
                                                    type="text"
                                                    placeholder="D1A230409"
                                                    class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white uppercase font-mono"
                                                    required
                                                />
                                            </div>
                                            <div class="sm:col-span-3">
                                                <label class="block text-[10px] text-slate-500 dark:text-slate-400 mb-0.5">Mitra / Project MBKM <span class="text-rose-500">*</span></label>
                                                <input
                                                    v-model="item.mitra_project"
                                                    type="text"
                                                    placeholder="Dicoding (Studi Independen)"
                                                    class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white"
                                                    required
                                                />
                                            </div>
                                            <div class="sm:col-span-2">
                                                <label class="block text-[10px] text-slate-500 dark:text-slate-400 mb-0.5">Peran Pembimbing</label>
                                                <select
                                                    v-model="item.role"
                                                    class="w-full px-2 py-1.5 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white"
                                                >
                                                    <option value="Pembimbing I">Pembimbing I</option>
                                                    <option value="Pembimbing II">Pembimbing II</option>
                                                    <option value="Dosen Pembimbing">Dosen Pembimbing</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>



                        <!-- 19. SURAT PENGANTAR PERBAIKAN DATA MAHASISWA PDDIKTI -->
                        <div
                            v-else-if="selectedType?.code === 'perbaikan_data_pddikti'"
                            class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs space-y-5"
                        >
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-teal-600"></span>
                                <h2 class="text-sm font-bold text-slate-900 dark:text-white">
                                    3. Data Permohonan Perbaikan Data Mahasiswa PDDIKTI
                                </h2>
                            </div>

                            <!-- Header Memo & Tujuan -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Lampiran Surat
                                    </label>
                                    <input
                                        v-model="form.extra_data.lampiran_count"
                                        type="text"
                                        placeholder="1 Rangkap"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Perihal Surat <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.subject"
                                        type="text"
                                        placeholder="Pengajuan Perubahan Data Mahasiswa"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white font-bold"
                                    />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Tujuan Surat (Kepada Yth.)
                                    </label>
                                    <textarea
                                        v-model="form.extra_data.target_recipient"
                                        rows="3"
                                        placeholder="Rektor Universitas Subang&#10;u.p. Wakil Rektor I Bid. Akademik&#10;dan kemahasiswaan"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    ></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Kota / Lokasi Tujuan
                                    </label>
                                    <input
                                        v-model="form.extra_data.target_city"
                                        type="text"
                                        placeholder="Tempat"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Pernyataan Bukti Dokumen
                                    </label>
                                    <input
                                        v-model="form.extra_data.evidence_text"
                                        type="text"
                                        placeholder="Data yang diubah sesuai dengan fakta dan dibuktikan dengan dokumen terlampir."
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white text-xs"
                                    />
                                </div>
                            </div>

                            <!-- Dynamic Correction Table -->
                            <div class="space-y-3 pt-3 border-t border-slate-200 dark:border-slate-700">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <label class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                            Tabel Usulan Perbaikan Data Mahasiswa
                                        </label>
                                        <p class="text-[11px] text-slate-400">Rincian data awal dan usulan perbaikan data mahasiswa</p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="addCorrectionItem"
                                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold text-teal-700 bg-teal-50 dark:bg-teal-900/40 hover:bg-teal-100 flex items-center gap-1"
                                    >
                                        + Tambah Mahasiswa
                                    </button>
                                </div>

                                <div class="space-y-3">
                                    <div
                                        v-for="(item, cIdx) in form.extra_data.correction_items"
                                        :key="cIdx"
                                        class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950/60 space-y-3"
                                    >
                                        <div class="flex items-center justify-between pb-1 border-b border-slate-200 dark:border-slate-800">
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                                Mahasiswa #{{ cIdx + 1 }}
                                            </span>
                                            <button
                                                v-if="form.extra_data.correction_items.length > 1"
                                                type="button"
                                                @click="removeCorrectionItem(cIdx)"
                                                class="text-xs font-semibold text-rose-500 hover:text-rose-700"
                                            >
                                                Hapus ✕
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                                            <div class="md:col-span-4">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    NPM <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.npm"
                                                    type="text"
                                                    placeholder="D1A210020"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-mono uppercase"
                                                />
                                            </div>
                                            <div class="md:col-span-8">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Nama Lengkap Mahasiswa <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.name"
                                                    type="text"
                                                    placeholder="FANNY APRILIANTI"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-bold uppercase"
                                                />
                                            </div>
                                            <div class="md:col-span-6">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Data Awal <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.initial_data"
                                                    type="text"
                                                    placeholder="Kesalahan Nama : Fany Aprilianti"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white italic"
                                                />
                                            </div>
                                            <div class="md:col-span-6">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Usulan Perbaikan <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.proposed_correction"
                                                    type="text"
                                                    placeholder="FANNY APRILIANTI"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-bold"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- KHUSUS SURAT TUGAS PEMBIMBING TUGAS AKHIR -->
                        <div v-else-if="selectedType?.code === 'tugas_pembimbing_ta'" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Status Tenaga Pengajar <span class="text-rose-500">*</span>
                                    </label>
                                    <select
                                        v-model="form.extra_data.lecturer_status"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                    >
                                        <option value="Dosen Tetap">Dosen Tetap</option>
                                        <option value="Dosen Luar Biasa">Dosen Luar Biasa</option>
                                        <option value="Dosen Praktisi">Dosen Praktisi / Mitra</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Semester Pelaksanaan <span class="text-rose-500">*</span>
                                    </label>
                                    <select
                                        v-model="form.extra_data.semester_type"
                                        @change="form.semester = form.extra_data.semester_type === 'Ganjil' ? 1 : 2"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                    >
                                        <option value="Ganjil">Semester Ganjil</option>
                                        <option value="Genap">Semester Genap</option>
                                    </select>
                                </div>
                            </div>

                            <!-- TABEL MAHASISWA BIMBINGAN TUGAS AKHIR -->
                            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 space-y-3">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                            Daftar Mahasiswa Bimbingan Tugas Akhir
                                        </h4>
                                        <p class="text-[11px] text-slate-400">
                                            Nama mahasiswa, NPM, dan peran pembimbing tugas akhir.
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="addTaStudent"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-blue-700 dark:text-blue-300 bg-blue-50 hover:bg-blue-100 dark:bg-blue-950/60 dark:hover:bg-blue-900/60 transition"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        <span>+ Tambah Mahasiswa TA</span>
                                    </button>
                                </div>

                                <!-- Student Items -->
                                <div class="space-y-2">
                                    <div
                                        v-for="(item, idx) in form.extra_data.ta_students"
                                        :key="idx"
                                        class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 space-y-2 text-xs"
                                    >
                                        <div class="flex items-center justify-between pb-1.5 border-b border-slate-200/60 dark:border-slate-800/60">
                                            <span class="font-bold text-blue-800 dark:text-blue-300">
                                                Mahasiswa #{{ idx + 1 }}
                                            </span>
                                            <button
                                                type="button"
                                                @click="removeTaStudent(idx)"
                                                class="text-rose-500 hover:text-rose-700 text-[11px] flex items-center gap-1 transition"
                                                v-if="form.extra_data.ta_students.length > 1"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                <span>Hapus</span>
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-2">
                                            <div class="sm:col-span-6">
                                                <label class="block text-[10px] text-slate-500 dark:text-slate-400 mb-0.5">Nama Lengkap Mahasiswa <span class="text-rose-500">*</span></label>
                                                <input
                                                    v-model="item.name"
                                                    type="text"
                                                    placeholder="Tiara Maharani Ar"
                                                    class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white"
                                                    required
                                                />
                                            </div>
                                            <div class="sm:col-span-3">
                                                <label class="block text-[10px] text-slate-500 dark:text-slate-400 mb-0.5">Nomor Pokok Mahasiswa (NPM) <span class="text-rose-500">*</span></label>
                                                <input
                                                    v-model="item.npm"
                                                    type="text"
                                                    placeholder="D1A200403"
                                                    class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white uppercase font-mono"
                                                    required
                                                />
                                            </div>
                                            <div class="sm:col-span-3">
                                                <label class="block text-[10px] text-slate-500 dark:text-slate-400 mb-0.5">Peran Pembimbing</label>
                                                <select
                                                    v-model="item.role"
                                                    class="w-full px-2 py-1.5 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white"
                                                >
                                                    <option value="Pembimbing I">Pembimbing I</option>
                                                    <option value="Pembimbing II">Pembimbing II</option>
                                                    <option value="Dosen Pembimbing">Dosen Pembimbing</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>



                        <!-- 19. SURAT PENGANTAR PERBAIKAN DATA MAHASISWA PDDIKTI -->
                        <div
                            v-else-if="selectedType?.code === 'perbaikan_data_pddikti'"
                            class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs space-y-5"
                        >
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-teal-600"></span>
                                <h2 class="text-sm font-bold text-slate-900 dark:text-white">
                                    3. Data Permohonan Perbaikan Data Mahasiswa PDDIKTI
                                </h2>
                            </div>

                            <!-- Header Memo & Tujuan -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Lampiran Surat
                                    </label>
                                    <input
                                        v-model="form.extra_data.lampiran_count"
                                        type="text"
                                        placeholder="1 Rangkap"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Perihal Surat <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.subject"
                                        type="text"
                                        placeholder="Pengajuan Perubahan Data Mahasiswa"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white font-bold"
                                    />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Tujuan Surat (Kepada Yth.)
                                    </label>
                                    <textarea
                                        v-model="form.extra_data.target_recipient"
                                        rows="3"
                                        placeholder="Rektor Universitas Subang&#10;u.p. Wakil Rektor I Bid. Akademik&#10;dan kemahasiswaan"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    ></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Kota / Lokasi Tujuan
                                    </label>
                                    <input
                                        v-model="form.extra_data.target_city"
                                        type="text"
                                        placeholder="Tempat"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Pernyataan Bukti Dokumen
                                    </label>
                                    <input
                                        v-model="form.extra_data.evidence_text"
                                        type="text"
                                        placeholder="Data yang diubah sesuai dengan fakta dan dibuktikan dengan dokumen terlampir."
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white text-xs"
                                    />
                                </div>
                            </div>

                            <!-- Dynamic Correction Table -->
                            <div class="space-y-3 pt-3 border-t border-slate-200 dark:border-slate-700">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <label class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                            Tabel Usulan Perbaikan Data Mahasiswa
                                        </label>
                                        <p class="text-[11px] text-slate-400">Rincian data awal dan usulan perbaikan data mahasiswa</p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="addCorrectionItem"
                                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold text-teal-700 bg-teal-50 dark:bg-teal-900/40 hover:bg-teal-100 flex items-center gap-1"
                                    >
                                        + Tambah Mahasiswa
                                    </button>
                                </div>

                                <div class="space-y-3">
                                    <div
                                        v-for="(item, cIdx) in form.extra_data.correction_items"
                                        :key="cIdx"
                                        class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950/60 space-y-3"
                                    >
                                        <div class="flex items-center justify-between pb-1 border-b border-slate-200 dark:border-slate-800">
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                                Mahasiswa #{{ cIdx + 1 }}
                                            </span>
                                            <button
                                                v-if="form.extra_data.correction_items.length > 1"
                                                type="button"
                                                @click="removeCorrectionItem(cIdx)"
                                                class="text-xs font-semibold text-rose-500 hover:text-rose-700"
                                            >
                                                Hapus ✕
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                                            <div class="md:col-span-4">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    NPM <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.npm"
                                                    type="text"
                                                    placeholder="D1A210020"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-mono uppercase"
                                                />
                                            </div>
                                            <div class="md:col-span-8">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Nama Lengkap Mahasiswa <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.name"
                                                    type="text"
                                                    placeholder="FANNY APRILIANTI"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-bold uppercase"
                                                />
                                            </div>
                                            <div class="md:col-span-6">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Data Awal <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.initial_data"
                                                    type="text"
                                                    placeholder="Kesalahan Nama : Fany Aprilianti"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white italic"
                                                />
                                            </div>
                                            <div class="md:col-span-6">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Usulan Perbaikan <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.proposed_correction"
                                                    type="text"
                                                    placeholder="FANNY APRILIANTI"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-bold"
                                                />
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
                                        Tujuan Penerima Surat (Kepada Yth.) <span class="text-rose-500">*</span>
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
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Alamat / Kota Instansi Tujuan</label>
                                    <input
                                        v-model="form.extra_data.target_city"
                                        type="text"
                                        placeholder="Tempat atau Subang"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Rentang Tanggal Ujian <span class="text-rose-500">*</span>
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
                                        <span>+ Tambah Jadwal Ujian</span>
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
                                            <div class="sm:col-span-7 relative">
                                                <div class="flex items-center justify-between mb-1">
                                                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400">
                                                        Nama Mata Kuliah <span class="text-rose-500">*</span>
                                                    </label>
                                                    <button
                                                        type="button"
                                                        @click="activeExamCourseIdx = activeExamCourseIdx === idx ? null : idx"
                                                        class="text-[10px] font-bold text-blue-600 hover:text-blue-700 dark:text-blue-400 inline-flex items-center gap-0.5"
                                                    >
                                                        <span>📚 Pilih MK</span>
                                                    </button>
                                                </div>

                                                <!-- DROPDOWN QUICK PICKER MATA KULIAH UNTUK ROW UJIAN -->
                                                <div
                                                    v-if="activeExamCourseIdx === idx"
                                                    class="absolute left-0 top-full mt-1 w-80 sm:w-96 z-30 p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-blue-200 dark:border-blue-900 shadow-xl space-y-2"
                                                >
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-[11px] font-bold text-blue-900 dark:text-blue-300">Pilih Mata Kuliah Kurikulum:</span>
                                                        <button type="button" @click="activeExamCourseIdx = null" class="text-slate-400 hover:text-slate-600 text-xs">✕</button>
                                                    </div>
                                                    <input
                                                        v-model="examCourseSearchQuery"
                                                        type="text"
                                                        placeholder="Ketik kode / nama MK..."
                                                        class="w-full text-xs rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 py-1.5 px-2.5"
                                                        autofocus
                                                    />
                                                    <div class="max-h-48 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700">
                                                        <button
                                                            v-for="c in filteredExamCourses"
                                                            :key="c.id"
                                                            type="button"
                                                            @click="selectExamCourse(idx, c)"
                                                            class="w-full text-left py-1.5 px-2 hover:bg-blue-50 dark:hover:bg-slate-700/60 rounded transition flex items-center justify-between text-xs"
                                                        >
                                                            <div>
                                                                <div class="font-bold text-slate-800 dark:text-white">{{ c.name }}</div>
                                                                <div class="text-[10px] text-slate-500 font-mono">{{ c.code }} • {{ c.sks }} SKS • Sem {{ c.semester || '-' }}</div>
                                                            </div>
                                                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-blue-100 text-blue-800 font-bold shrink-0">Pilih</span>
                                                        </button>
                                                        <div v-if="filteredExamCourses.length === 0" class="p-3 text-center text-slate-400 text-xs">
                                                            Mata kuliah tidak ditemukan.
                                                        </div>
                                                    </div>
                                                </div>

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



                        <!-- 19. SURAT PENGANTAR PERBAIKAN DATA MAHASISWA PDDIKTI -->
                        <div
                            v-else-if="selectedType?.code === 'perbaikan_data_pddikti'"
                            class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs space-y-5"
                        >
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-teal-600"></span>
                                <h2 class="text-sm font-bold text-slate-900 dark:text-white">
                                    3. Data Permohonan Perbaikan Data Mahasiswa PDDIKTI
                                </h2>
                            </div>

                            <!-- Header Memo & Tujuan -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Lampiran Surat
                                    </label>
                                    <input
                                        v-model="form.extra_data.lampiran_count"
                                        type="text"
                                        placeholder="1 Rangkap"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Perihal Surat <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.subject"
                                        type="text"
                                        placeholder="Pengajuan Perubahan Data Mahasiswa"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white font-bold"
                                    />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Tujuan Surat (Kepada Yth.)
                                    </label>
                                    <textarea
                                        v-model="form.extra_data.target_recipient"
                                        rows="3"
                                        placeholder="Rektor Universitas Subang&#10;u.p. Wakil Rektor I Bid. Akademik&#10;dan kemahasiswaan"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    ></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Kota / Lokasi Tujuan
                                    </label>
                                    <input
                                        v-model="form.extra_data.target_city"
                                        type="text"
                                        placeholder="Tempat"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Pernyataan Bukti Dokumen
                                    </label>
                                    <input
                                        v-model="form.extra_data.evidence_text"
                                        type="text"
                                        placeholder="Data yang diubah sesuai dengan fakta dan dibuktikan dengan dokumen terlampir."
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white text-xs"
                                    />
                                </div>
                            </div>

                            <!-- Dynamic Correction Table -->
                            <div class="space-y-3 pt-3 border-t border-slate-200 dark:border-slate-700">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <label class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                            Tabel Usulan Perbaikan Data Mahasiswa
                                        </label>
                                        <p class="text-[11px] text-slate-400">Rincian data awal dan usulan perbaikan data mahasiswa</p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="addCorrectionItem"
                                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold text-teal-700 bg-teal-50 dark:bg-teal-900/40 hover:bg-teal-100 flex items-center gap-1"
                                    >
                                        + Tambah Mahasiswa
                                    </button>
                                </div>

                                <div class="space-y-3">
                                    <div
                                        v-for="(item, cIdx) in form.extra_data.correction_items"
                                        :key="cIdx"
                                        class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950/60 space-y-3"
                                    >
                                        <div class="flex items-center justify-between pb-1 border-b border-slate-200 dark:border-slate-800">
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                                Mahasiswa #{{ cIdx + 1 }}
                                            </span>
                                            <button
                                                v-if="form.extra_data.correction_items.length > 1"
                                                type="button"
                                                @click="removeCorrectionItem(cIdx)"
                                                class="text-xs font-semibold text-rose-500 hover:text-rose-700"
                                            >
                                                Hapus ✕
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                                            <div class="md:col-span-4">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    NPM <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.npm"
                                                    type="text"
                                                    placeholder="D1A210020"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-mono uppercase"
                                                />
                                            </div>
                                            <div class="md:col-span-8">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Nama Lengkap Mahasiswa <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.name"
                                                    type="text"
                                                    placeholder="FANNY APRILIANTI"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-bold uppercase"
                                                />
                                            </div>
                                            <div class="md:col-span-6">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Data Awal <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.initial_data"
                                                    type="text"
                                                    placeholder="Kesalahan Nama : Fany Aprilianti"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white italic"
                                                />
                                            </div>
                                            <div class="md:col-span-6">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Usulan Perbaikan <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.proposed_correction"
                                                    type="text"
                                                    placeholder="FANNY APRILIANTI"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-bold"
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
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-600"
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
                                    Dosen Pengampu Mata Kuliah <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="form.extra_data.lecturer_name"
                                    type="text"
                                    placeholder="ACHMAD SYAFA'AT, S.Kom., MT"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    required
                                />
                            </div>
                            <div class="relative">
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300">
                                        Nama Mata Kuliah <span class="text-rose-500">*</span>
                                    </label>
                                    <button
                                        type="button"
                                        @click="showSingleCourseSearch = !showSingleCourseSearch"
                                        class="text-[10px] font-bold text-blue-600 hover:text-blue-700 dark:text-blue-400 inline-flex items-center gap-0.5"
                                    >
                                        <span>📚 Pilih Master MK</span>
                                    </button>
                                </div>

                                <!-- DROPDOWN QUICK PICKER MATA KULIAH -->
                                <div
                                    v-if="showSingleCourseSearch"
                                    class="absolute left-0 top-full mt-1 w-80 sm:w-96 z-30 p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-blue-200 dark:border-blue-900 shadow-xl space-y-2"
                                >
                                    <div class="flex items-center justify-between">
                                        <span class="text-[11px] font-bold text-blue-900 dark:text-blue-300">Pilih Mata Kuliah Kurikulum:</span>
                                        <button type="button" @click="showSingleCourseSearch = false" class="text-slate-400 hover:text-slate-600 text-xs">✕</button>
                                    </div>
                                    <input
                                        v-model="singleCourseSearchQuery"
                                        type="text"
                                        placeholder="Ketik kode / nama MK..."
                                        class="w-full text-xs rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 py-1.5 px-2.5"
                                        autofocus
                                    />
                                    <div class="max-h-48 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700">
                                        <button
                                            v-for="c in filteredSingleCourses"
                                            :key="c.id"
                                            type="button"
                                            @click="selectSingleCourse(c)"
                                            class="w-full text-left py-1.5 px-2 hover:bg-blue-50 dark:hover:bg-slate-700/60 rounded transition flex items-center justify-between text-xs"
                                        >
                                            <div>
                                                <div class="font-bold text-slate-800 dark:text-white">{{ c.name }}</div>
                                                <div class="text-[10px] text-slate-500 font-mono">{{ c.code }} • {{ c.sks }} SKS • Sem {{ c.semester || '-' }}</div>
                                            </div>
                                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-blue-100 text-blue-800 font-bold shrink-0">Pilih</span>
                                        </button>
                                        <div v-if="filteredSingleCourses.length === 0" class="p-3 text-center text-slate-400 text-xs">
                                            Mata kuliah tidak ditemukan.
                                        </div>
                                    </div>
                                </div>

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
                        <div v-else-if="selectedType?.code === 'perbaikan_nilai'" class="space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    Perihal Surat <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="form.extra_data.subject"
                                    type="text"
                                    placeholder="Perbaikan Nilai"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white font-bold"
                                />
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Dosen Pengampu Mata Kuliah <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.lecturer_name"
                                        type="text"
                                        placeholder="Ir. Syarif Hidayat, MT"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                        required
                                    />
                                </div>
                                <div class="relative">
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300">
                                            Nama Mata Kuliah <span class="text-rose-500">*</span>
                                        </label>
                                        <button
                                            type="button"
                                            @click="showSingleCourseSearch = !showSingleCourseSearch"
                                            class="text-[10px] font-bold text-blue-600 hover:text-blue-700 dark:text-blue-400 inline-flex items-center gap-0.5"
                                        >
                                            <span>📚 Pilih Master MK</span>
                                        </button>
                                    </div>

                                    <!-- DROPDOWN QUICK PICKER MATA KULIAH -->
                                    <div
                                        v-if="showSingleCourseSearch"
                                        class="absolute left-0 top-full mt-1 w-80 sm:w-96 z-30 p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-blue-200 dark:border-blue-900 shadow-xl space-y-2"
                                    >
                                        <div class="flex items-center justify-between">
                                            <span class="text-[11px] font-bold text-blue-900 dark:text-blue-300">Pilih Mata Kuliah Kurikulum:</span>
                                            <button type="button" @click="showSingleCourseSearch = false" class="text-slate-400 hover:text-slate-600 text-xs">✕</button>
                                        </div>
                                        <input
                                            v-model="singleCourseSearchQuery"
                                            type="text"
                                            placeholder="Ketik kode / nama MK..."
                                            class="w-full text-xs rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 py-1.5 px-2.5"
                                            autofocus
                                        />
                                        <div class="max-h-48 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700">
                                            <button
                                                v-for="c in filteredSingleCourses"
                                                :key="c.id"
                                                type="button"
                                                @click="selectSingleCourse(c)"
                                                class="w-full text-left py-1.5 px-2 hover:bg-blue-50 dark:hover:bg-slate-700/60 rounded transition flex items-center justify-between text-xs"
                                            >
                                                <div>
                                                    <div class="font-bold text-slate-800 dark:text-white">{{ c.name }}</div>
                                                    <div class="text-[10px] text-slate-500 font-mono">{{ c.code }} • {{ c.sks }} SKS • Sem {{ c.semester || '-' }}</div>
                                                </div>
                                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-blue-100 text-blue-800 font-bold shrink-0">Pilih</span>
                                            </button>
                                            <div v-if="filteredSingleCourses.length === 0" class="p-3 text-center text-slate-400 text-xs">
                                                Mata kuliah tidak ditemukan.
                                            </div>
                                        </div>
                                    </div>

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
                                        Nilai Asal (Sebelum Perbaikan) <span class="text-rose-500">*</span>
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
                        </div>

                        <!-- KHUSUS SURAT KETERANGAN YUDISIUM -->
                        <div v-else-if="selectedType?.code === 'keterangan_yudisium'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    Hari & Tanggal Sidang Skripsi <span class="text-rose-500">*</span>
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
                                    IPK Kelulusan <span class="text-rose-500">*</span>
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
                                    Hari & Tanggal Yudisium <span class="text-rose-500">*</span>
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
                                    Predikat Kelulusan Yudisium <span class="text-rose-500">*</span>
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

                        <!-- KHUSUS SURAT REKOMENDASI BEASISWA -->
                        <div v-else-if="selectedType?.code === 'rekomendasi_beasiswa'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    Nama Program Beasiswa <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="form.extra_data.scholarship_name"
                                    type="text"
                                    placeholder="Contoh: Beasiswa Pendidikan Pemimpin Indonesia"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    Penyelenggara / Lembaga Beasiswa <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="form.extra_data.scholarship_provider"
                                    type="text"
                                    placeholder="Contoh: LEADERS.ID"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    required
                                />
                            </div>
                        </div>

                        <!-- KHUSUS REKOMENDASI PEMBELAJARAN LUAR PRODI -->
                        <div v-else-if="selectedType?.code === 'rekomendasi_luar_prodi'" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Nama Program Kegiatan / Pelatihan <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.program_name"
                                        type="text"
                                        placeholder="Contoh: Program Coding Camp 2025 powered by DBS Foundation"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                        required
                                    />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Periode Pelaksanaan Kegiatan <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.activity_period"
                                        type="text"
                                        placeholder="Contoh: periode Februari - Juli 2025"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                        required
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- KHUSUS SURAT UNDANGAN RAPAT AKADEMIK -->
                        <div v-else-if="selectedType?.code === 'undangan_rapat_akademik'" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Agenda Rapat Akademik <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.agenda"
                                        type="text"
                                        placeholder="Rapat Akademik / Rapat Koordinasi Kurikulum"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                        required
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Hari & Tanggal Rapat <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.meeting_day_date"
                                        type="text"
                                        placeholder="Selasa, 8 September 2026"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                        required
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Waktu Pelaksanaan <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.meeting_time"
                                        type="text"
                                        placeholder="09.00 WIB atau 09.00 WIB s/d Selesai"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                        required
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Format Pertemuan (Tatap Muka / Online) <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="grid grid-cols-2 gap-2 mt-1">
                                        <label class="flex items-center gap-2 p-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 cursor-pointer">
                                            <input
                                                type="radio"
                                                value="offline"
                                                v-model="form.extra_data.meeting_type"
                                                class="text-indigo-600 focus:ring-indigo-500"
                                            />
                                            <span class="text-xs font-medium text-slate-700 dark:text-slate-300">Offline (Tatap Muka)</span>
                                        </label>
                                        <label class="flex items-center gap-2 p-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 cursor-pointer">
                                            <input
                                                type="radio"
                                                value="online"
                                                v-model="form.extra_data.meeting_type"
                                                class="text-indigo-600 focus:ring-indigo-500"
                                            />
                                            <span class="text-xs font-medium text-slate-700 dark:text-slate-300">Online (Google Meet)</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Input Lokasi Offline -->
                            <div v-if="form.extra_data.meeting_type !== 'online'" class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-950/50">
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    Nama Tempat / Ruangan Rapat <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="form.extra_data.meeting_location"
                                    type="text"
                                    placeholder="Ruang 12 Fakultas Ilmu Komputer"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white"
                                    required
                                />
                            </div>

                            <!-- Input Link Online (Google Meet) -->
                            <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-3 p-3.5 rounded-xl border border-indigo-200 dark:border-indigo-900/50 bg-indigo-50/30 dark:bg-indigo-950/20">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Platform Pertemuan
                                    </label>
                                    <select
                                        v-model="form.extra_data.meeting_platform"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white"
                                    >
                                        <option value="Google Meet">Google Meet</option>
                                        <option value="Zoom Meeting">Zoom Meeting</option>
                                    </select>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Link Google Meet / Pertemuan <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.meeting_link"
                                        type="url"
                                        placeholder="https://meet.google.com/abc-defg-hij"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-mono"
                                        required
                                    />
                                </div>
                            </div>
                        </div>



                        <!-- 19. SURAT PENGANTAR PERBAIKAN DATA MAHASISWA PDDIKTI -->
                        <div
                            v-else-if="selectedType?.code === 'perbaikan_data_pddikti'"
                            class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs space-y-5"
                        >
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-teal-600"></span>
                                <h2 class="text-sm font-bold text-slate-900 dark:text-white">
                                    3. Data Permohonan Perbaikan Data Mahasiswa PDDIKTI
                                </h2>
                            </div>

                            <!-- Header Memo & Tujuan -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Lampiran Surat
                                    </label>
                                    <input
                                        v-model="form.extra_data.lampiran_count"
                                        type="text"
                                        placeholder="1 Rangkap"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Perihal Surat <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.subject"
                                        type="text"
                                        placeholder="Pengajuan Perubahan Data Mahasiswa"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white font-bold"
                                    />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Tujuan Surat (Kepada Yth.)
                                    </label>
                                    <textarea
                                        v-model="form.extra_data.target_recipient"
                                        rows="3"
                                        placeholder="Rektor Universitas Subang&#10;u.p. Wakil Rektor I Bid. Akademik&#10;dan kemahasiswaan"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    ></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Kota / Lokasi Tujuan
                                    </label>
                                    <input
                                        v-model="form.extra_data.target_city"
                                        type="text"
                                        placeholder="Tempat"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Pernyataan Bukti Dokumen
                                    </label>
                                    <input
                                        v-model="form.extra_data.evidence_text"
                                        type="text"
                                        placeholder="Data yang diubah sesuai dengan fakta dan dibuktikan dengan dokumen terlampir."
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white text-xs"
                                    />
                                </div>
                            </div>

                            <!-- Dynamic Correction Table -->
                            <div class="space-y-3 pt-3 border-t border-slate-200 dark:border-slate-700">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <label class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                            Tabel Usulan Perbaikan Data Mahasiswa
                                        </label>
                                        <p class="text-[11px] text-slate-400">Rincian data awal dan usulan perbaikan data mahasiswa</p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="addCorrectionItem"
                                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold text-teal-700 bg-teal-50 dark:bg-teal-900/40 hover:bg-teal-100 flex items-center gap-1"
                                    >
                                        + Tambah Mahasiswa
                                    </button>
                                </div>

                                <div class="space-y-3">
                                    <div
                                        v-for="(item, cIdx) in form.extra_data.correction_items"
                                        :key="cIdx"
                                        class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950/60 space-y-3"
                                    >
                                        <div class="flex items-center justify-between pb-1 border-b border-slate-200 dark:border-slate-800">
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                                Mahasiswa #{{ cIdx + 1 }}
                                            </span>
                                            <button
                                                v-if="form.extra_data.correction_items.length > 1"
                                                type="button"
                                                @click="removeCorrectionItem(cIdx)"
                                                class="text-xs font-semibold text-rose-500 hover:text-rose-700"
                                            >
                                                Hapus ✕
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                                            <div class="md:col-span-4">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    NPM <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.npm"
                                                    type="text"
                                                    placeholder="D1A210020"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-mono uppercase"
                                                />
                                            </div>
                                            <div class="md:col-span-8">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Nama Lengkap Mahasiswa <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.name"
                                                    type="text"
                                                    placeholder="FANNY APRILIANTI"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-bold uppercase"
                                                />
                                            </div>
                                            <div class="md:col-span-6">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Data Awal <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.initial_data"
                                                    type="text"
                                                    placeholder="Kesalahan Nama : Fany Aprilianti"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white italic"
                                                />
                                            </div>
                                            <div class="md:col-span-6">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Usulan Perbaikan <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.proposed_correction"
                                                    type="text"
                                                    placeholder="FANNY APRILIANTI"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-bold"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- KHUSUS SURAT PERNYATAAN KESANGGUPAN MENGAJAR (DOSEN YBS) -->
                        <div v-else-if="selectedType?.code === 'kesanggupan_mengajar'" class="space-y-4">
                            <div class="p-3.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-xs text-amber-900 dark:text-amber-200">
                                <strong>Ketentuan Surat:</strong> Surat ini dibuat oleh Admin / Fakultas untuk menyatakan kesanggupan dosen mengajar. Penandatangan surat otomatis adalah <strong>Dosen yang bersangkutan</strong> lengkap dengan kolom Materai 10000 dan NIDN.
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Jabatan Akademik Dosen
                                    </label>
                                    <input
                                        v-model="form.extra_data.academic_rank"
                                        type="text"
                                        placeholder="Lektor / Asisten Ahli / Guru Besar"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Bidang Keahlian
                                    </label>
                                    <input
                                        v-model="form.extra_data.expertise"
                                        type="text"
                                        placeholder="Pemrograman Web & Sistem Informasi"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                            </div>

                            <!-- Tabel Daftar Mata Kuliah Yang Diampu -->
                            <div class="space-y-3 pt-3 border-t border-slate-200 dark:border-slate-800">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-bold text-slate-900 dark:text-white">
                                        Daftar Mata Kuliah Pengajaran
                                    </label>
                                    <button
                                        type="button"
                                        @click="addTeachingCourse"
                                        class="px-3 py-1.5 rounded-lg text-xs font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/50 hover:bg-indigo-100 transition"
                                    >
                                        + Tambah Baris Mata Kuliah
                                    </button>
                                </div>

                                <div class="space-y-2">
                                    <div
                                        v-for="(course, cIdx) in form.extra_data.teaching_courses"
                                        :key="cIdx"
                                        class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 space-y-2"
                                    >
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                                Mata Kuliah #{{ cIdx + 1 }}
                                            </span>
                                            <button
                                                v-if="form.extra_data.teaching_courses.length > 1"
                                                type="button"
                                                @click="removeTeachingCourse(cIdx)"
                                                class="text-xs text-rose-500 hover:text-rose-700 font-bold"
                                            >
                                                Hapus Baris
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-2 md:grid-cols-6 gap-2">
                                            <div>
                                                <label class="block text-[10px] font-medium text-slate-500 mb-0.5">Kode MK</label>
                                                <input
                                                    v-model="course.code"
                                                    type="text"
                                                    placeholder="D1A12004"
                                                    class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 uppercase font-mono font-bold"
                                                />
                                            </div>
                                            <div class="col-span-2">
                                                <label class="block text-[10px] font-medium text-slate-500 mb-0.5">Nama Mata Kuliah</label>
                                                <input
                                                    v-model="course.name"
                                                    type="text"
                                                    placeholder="Desain WEB"
                                                    class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-bold"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-medium text-slate-500 mb-0.5">Jenis Pengajaran</label>
                                                <input
                                                    v-model="course.teaching_type"
                                                    type="text"
                                                    placeholder="Teori / Praktikum"
                                                    class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-medium text-slate-500 mb-0.5">Kelas</label>
                                                <input
                                                    v-model="course.class_name"
                                                    type="text"
                                                    placeholder="3"
                                                    class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-center font-bold"
                                                    @input="course.total_sks = (Number(course.sks) || 0) * (parseInt(course.class_name) || 1); recalcTotalSks();"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-medium text-slate-500 mb-0.5">SKS</label>
                                                <input
                                                    v-model.number="course.sks"
                                                    type="number"
                                                    placeholder="2"
                                                    class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-center font-bold"
                                                    @input="course.total_sks = (Number(course.sks) || 0) * (parseInt(course.class_name) || 1); recalcTotalSks();"
                                                />
                                            </div>
                                        </div>

                                        <div class="flex items-center justify-end gap-2 text-xs pt-1">
                                            <span class="text-slate-500 font-medium">Total SKS MK ini:</span>
                                            <input
                                                v-model.number="course.total_sks"
                                                type="number"
                                                class="w-16 px-2 py-1 text-xs rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-center font-bold text-indigo-600"
                                                @input="recalcTotalSks"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center justify-end gap-3 pt-2">
                                    <label class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                        Jumlah Total Beban SKS:
                                    </label>
                                    <input
                                        v-model="form.extra_data.total_sks_sum"
                                        type="text"
                                        placeholder="29"
                                        class="w-24 px-3 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-center font-black text-indigo-600"
                                    />
                                </div>
                            </div>
                        </div>



                        <!-- 19. SURAT PENGANTAR PERBAIKAN DATA MAHASISWA PDDIKTI -->
                        <div
                            v-else-if="selectedType?.code === 'perbaikan_data_pddikti'"
                            class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs space-y-5"
                        >
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-teal-600"></span>
                                <h2 class="text-sm font-bold text-slate-900 dark:text-white">
                                    3. Data Permohonan Perbaikan Data Mahasiswa PDDIKTI
                                </h2>
                            </div>

                            <!-- Header Memo & Tujuan -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Lampiran Surat
                                    </label>
                                    <input
                                        v-model="form.extra_data.lampiran_count"
                                        type="text"
                                        placeholder="1 Rangkap"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Perihal Surat <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.subject"
                                        type="text"
                                        placeholder="Pengajuan Perubahan Data Mahasiswa"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white font-bold"
                                    />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Tujuan Surat (Kepada Yth.)
                                    </label>
                                    <textarea
                                        v-model="form.extra_data.target_recipient"
                                        rows="3"
                                        placeholder="Rektor Universitas Subang&#10;u.p. Wakil Rektor I Bid. Akademik&#10;dan kemahasiswaan"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    ></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Kota / Lokasi Tujuan
                                    </label>
                                    <input
                                        v-model="form.extra_data.target_city"
                                        type="text"
                                        placeholder="Tempat"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Pernyataan Bukti Dokumen
                                    </label>
                                    <input
                                        v-model="form.extra_data.evidence_text"
                                        type="text"
                                        placeholder="Data yang diubah sesuai dengan fakta dan dibuktikan dengan dokumen terlampir."
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white text-xs"
                                    />
                                </div>
                            </div>

                            <!-- Dynamic Correction Table -->
                            <div class="space-y-3 pt-3 border-t border-slate-200 dark:border-slate-700">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <label class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                            Tabel Usulan Perbaikan Data Mahasiswa
                                        </label>
                                        <p class="text-[11px] text-slate-400">Rincian data awal dan usulan perbaikan data mahasiswa</p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="addCorrectionItem"
                                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold text-teal-700 bg-teal-50 dark:bg-teal-900/40 hover:bg-teal-100 flex items-center gap-1"
                                    >
                                        + Tambah Mahasiswa
                                    </button>
                                </div>

                                <div class="space-y-3">
                                    <div
                                        v-for="(item, cIdx) in form.extra_data.correction_items"
                                        :key="cIdx"
                                        class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950/60 space-y-3"
                                    >
                                        <div class="flex items-center justify-between pb-1 border-b border-slate-200 dark:border-slate-800">
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                                Mahasiswa #{{ cIdx + 1 }}
                                            </span>
                                            <button
                                                v-if="form.extra_data.correction_items.length > 1"
                                                type="button"
                                                @click="removeCorrectionItem(cIdx)"
                                                class="text-xs font-semibold text-rose-500 hover:text-rose-700"
                                            >
                                                Hapus ✕
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                                            <div class="md:col-span-4">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    NPM <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.npm"
                                                    type="text"
                                                    placeholder="D1A210020"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-mono uppercase"
                                                />
                                            </div>
                                            <div class="md:col-span-8">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Nama Lengkap Mahasiswa <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.name"
                                                    type="text"
                                                    placeholder="FANNY APRILIANTI"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-bold uppercase"
                                                />
                                            </div>
                                            <div class="md:col-span-6">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Data Awal <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.initial_data"
                                                    type="text"
                                                    placeholder="Kesalahan Nama : Fany Aprilianti"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white italic"
                                                />
                                            </div>
                                            <div class="md:col-span-6">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Usulan Perbaikan <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.proposed_correction"
                                                    type="text"
                                                    placeholder="FANNY APRILIANTI"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-bold"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- KHUSUS DOKUMEN FAKTA INTEGRITAS DOSEN PENGAMPU -->
                        <div v-else-if="selectedType?.code === 'pakta_integritas'" class="space-y-4">
                            <div class="p-3.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800 text-xs text-indigo-900 dark:text-indigo-200">
                                <strong>Fakta Integritas Dosen:</strong> Dokumen komitmen resmi integritas mengajar dosen pengampu Prodi Sistem Informasi Fakultas Ilmu Komputer Universitas Subang. Penandatangan otomatis adalah <strong>Dosen yang bersangkutan</strong> (Yang Membuat Pernyataan).
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    Nama Lengkap Dosen + Gelar <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="form.student_name"
                                    type="text"
                                    placeholder="BAGUS ALI AKBAR, S.SI., M.Kom"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white uppercase font-bold"
                                    @input="form.extra_data.lecturer_name = form.student_name"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    Mata Kuliah yang Diampu
                                </label>
                                <textarea
                                    v-model="form.extra_data.courses_taught"
                                    rows="2"
                                    placeholder="Pemrograman Web, Sistem Basis Data, Rekayasa Perangkat Lunak"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white font-medium"
                                ></textarea>
                            </div>

                            <!-- Poin Ketentuan -->
                            <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 space-y-2 text-xs">
                                <div class="font-bold text-slate-700 dark:text-slate-300">
                                    5 Poin Ketentuan Fakta Integritas (Tercetak otomatis pada dokumen):
                                </div>
                                <ol class="list-decimal list-inside space-y-1 text-slate-600 dark:text-slate-400 text-xs leading-relaxed">
                                    <li>Bersedia hadir dan melaksanakan perkuliahan tatap muka 14 kali pertemuan secara online (LMS) maupun offline.</li>
                                    <li>Bersedia membuat perangkat pembelajaran baik berupa RPS dan materi pembelajaran.</li>
                                    <li>Bersedia memberikan pelayanan perkuliahan melalui media komunikasi online maupun offline kepada mahasiswa.</li>
                                    <li>Bersedia mengisi Berita Acara Perkuliahan pada setiap pertemuan melalui SIAKAD UNSUB.</li>
                                    <li>Bersedia mengisi nilai UTS dan UAS pada SIAKAD maksimal 2 minggu setelah pelaksanaan ujian.</li>
                                </ol>
                            </div>
                        </div>

                        <!-- KHUSUS SURAT PENGAJUAN KEBUTUHAN DANA -->
                        <div v-else-if="selectedType?.code === 'pengajuan_dana'" class="space-y-4">
                            <div class="p-3.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs text-emerald-900 dark:text-emerald-200">
                                <strong>Pengajuan Kebutuhan Dana Fakultas:</strong> Ditujukan kepada Rektor Universitas Subang. Dilengkapi rincian pagu anggaran, pencairan lalu, pengajuan bulan berjalan, saldo sisa, kalimat terbilang, dan tanda tangan <strong>Dekan & Bendahara</strong>.
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Bulan & Tahun Kebutuhan Dana <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.period_month_year"
                                        type="text"
                                        placeholder="November 2026"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white font-bold"
                                        @input="form.extra_data.submitted_label = 'Pengajuan ' + (form.extra_data.period_month_year || 'November 2026')"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Lampiran Surat
                                    </label>
                                    <input
                                        v-model="form.extra_data.lampiran_count"
                                        type="text"
                                        placeholder="1 (Satu) Berkas"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Tujuan Surat (Kepada Yth.)
                                    </label>
                                    <input
                                        v-model="form.extra_data.target_recipient"
                                        type="text"
                                        placeholder="Rektor Universitas Subang"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2 border-t border-slate-200 dark:border-slate-800">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Nominal Pagu (Rp) <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.pagu_amount"
                                        type="text"
                                        placeholder="165.200.000"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white font-bold"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Sisa Pagu / Jumlah Setelah Pencairan (Rp)
                                    </label>
                                    <input
                                        v-model="form.extra_data.remaining_pagu_amount"
                                        type="text"
                                        placeholder="145.046.500"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-emerald-600 dark:text-emerald-400 font-bold"
                                    />
                                </div>

                                <div>
                                    <label class="block text-[11px] font-medium text-slate-500 mb-1">
                                        Label Pencairan 1 & Nominal (Rp)
                                    </label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <input
                                            v-model="form.extra_data.disbursement_1_label"
                                            type="text"
                                            placeholder="Pencairan bulan September 2026"
                                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                        />
                                        <input
                                            v-model="form.extra_data.disbursement_1_amount"
                                            type="text"
                                            placeholder="6.950.000"
                                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white font-mono"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-medium text-slate-500 mb-1">
                                        Label Pencairan 2 & Nominal (Rp)
                                    </label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <input
                                            v-model="form.extra_data.disbursement_2_label"
                                            type="text"
                                            placeholder="Pencairan bulan Oktober 2026"
                                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                        />
                                        <input
                                            v-model="form.extra_data.disbursement_2_amount"
                                            type="text"
                                            placeholder="13.203.500"
                                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white font-mono"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-medium text-slate-500 mb-1">
                                        Label Pengajuan Bulan Ini & Nominal Kotor (Rp)
                                    </label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <input
                                            v-model="form.extra_data.submitted_label"
                                            type="text"
                                            placeholder="Pengajuan November 2026"
                                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                        />
                                        <input
                                            v-model="form.extra_data.submitted_amount"
                                            type="text"
                                            placeholder="26.101.509"
                                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white font-bold"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-medium text-slate-500 mb-1">
                                        Label Saldo Penggunaan Lalu & Nominal (Rp)
                                    </label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <input
                                            v-model="form.extra_data.last_balance_label"
                                            type="text"
                                            placeholder="Saldo Penggunaan bulan lalu"
                                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                        />
                                        <input
                                            v-model="form.extra_data.last_balance_amount"
                                            type="text"
                                            placeholder="1.439.509"
                                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white font-mono"
                                        />
                                    </div>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-xs font-bold text-slate-900 dark:text-white mb-1">
                                        Jumlah Total Dana Bersih Yang Diajukan (Rp) <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.final_net_amount"
                                        type="text"
                                        placeholder="24.662.000"
                                        class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 font-black"
                                    />
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Terbilang (Ejaan Rupiah) <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.terbilang"
                                        type="text"
                                        placeholder="Dua Puluh Empat Juta Enam Ratus Enam Puluh Dua Ribu Rupiah"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white italic font-medium"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Nama Dekan (Mengetahui)
                                    </label>
                                    <input
                                        v-model="form.extra_data.dekan_name"
                                        type="text"
                                        placeholder="Dr. Tepi Peirisal,S.Sos, M.Si"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white font-bold"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Nama Bendahara Fakultas
                                    </label>
                                    <input
                                        v-model="form.extra_data.treasurer_name"
                                        type="text"
                                        placeholder="Fina Widya Ramdhani, S.AN"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white font-bold"
                                    />
                                </div>
                            </div>
                        </div>



                        <!-- 19. SURAT PENGANTAR PERBAIKAN DATA MAHASISWA PDDIKTI -->
                        <div
                            v-else-if="selectedType?.code === 'perbaikan_data_pddikti'"
                            class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs space-y-5"
                        >
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-teal-600"></span>
                                <h2 class="text-sm font-bold text-slate-900 dark:text-white">
                                    3. Data Permohonan Perbaikan Data Mahasiswa PDDIKTI
                                </h2>
                            </div>

                            <!-- Header Memo & Tujuan -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Lampiran Surat
                                    </label>
                                    <input
                                        v-model="form.extra_data.lampiran_count"
                                        type="text"
                                        placeholder="1 Rangkap"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Perihal Surat <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.subject"
                                        type="text"
                                        placeholder="Pengajuan Perubahan Data Mahasiswa"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white font-bold"
                                    />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Tujuan Surat (Kepada Yth.)
                                    </label>
                                    <textarea
                                        v-model="form.extra_data.target_recipient"
                                        rows="3"
                                        placeholder="Rektor Universitas Subang&#10;u.p. Wakil Rektor I Bid. Akademik&#10;dan kemahasiswaan"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    ></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Kota / Lokasi Tujuan
                                    </label>
                                    <input
                                        v-model="form.extra_data.target_city"
                                        type="text"
                                        placeholder="Tempat"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Pernyataan Bukti Dokumen
                                    </label>
                                    <input
                                        v-model="form.extra_data.evidence_text"
                                        type="text"
                                        placeholder="Data yang diubah sesuai dengan fakta dan dibuktikan dengan dokumen terlampir."
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white text-xs"
                                    />
                                </div>
                            </div>

                            <!-- Dynamic Correction Table -->
                            <div class="space-y-3 pt-3 border-t border-slate-200 dark:border-slate-700">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <label class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                            Tabel Usulan Perbaikan Data Mahasiswa
                                        </label>
                                        <p class="text-[11px] text-slate-400">Rincian data awal dan usulan perbaikan data mahasiswa</p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="addCorrectionItem"
                                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold text-teal-700 bg-teal-50 dark:bg-teal-900/40 hover:bg-teal-100 flex items-center gap-1"
                                    >
                                        + Tambah Mahasiswa
                                    </button>
                                </div>

                                <div class="space-y-3">
                                    <div
                                        v-for="(item, cIdx) in form.extra_data.correction_items"
                                        :key="cIdx"
                                        class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950/60 space-y-3"
                                    >
                                        <div class="flex items-center justify-between pb-1 border-b border-slate-200 dark:border-slate-800">
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                                Mahasiswa #{{ cIdx + 1 }}
                                            </span>
                                            <button
                                                v-if="form.extra_data.correction_items.length > 1"
                                                type="button"
                                                @click="removeCorrectionItem(cIdx)"
                                                class="text-xs font-semibold text-rose-500 hover:text-rose-700"
                                            >
                                                Hapus ✕
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                                            <div class="md:col-span-4">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    NPM <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.npm"
                                                    type="text"
                                                    placeholder="D1A210020"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-mono uppercase"
                                                />
                                            </div>
                                            <div class="md:col-span-8">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Nama Lengkap Mahasiswa <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.name"
                                                    type="text"
                                                    placeholder="FANNY APRILIANTI"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-bold uppercase"
                                                />
                                            </div>
                                            <div class="md:col-span-6">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Data Awal <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.initial_data"
                                                    type="text"
                                                    placeholder="Kesalahan Nama : Fany Aprilianti"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white italic"
                                                />
                                            </div>
                                            <div class="md:col-span-6">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Usulan Perbaikan <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.proposed_correction"
                                                    type="text"
                                                    placeholder="FANNY APRILIANTI"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-bold"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- KHUSUS SURAT PENGAJUAN PENGADAAN SARANA & PRASARANA -->
                        <div v-if="selectedType?.code === 'pengadaan_sarpras'" class="space-y-4">
                            <div class="p-3 rounded-xl bg-blue-50/80 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800 text-xs text-blue-900 dark:text-blue-200 flex items-start gap-2">
                                <span class="text-base">🖥️</span>
                                <div>
                                    <strong>Surat Pengajuan Pengadaan Sarana & Prasarana:</strong>
                                    Ditujukan kepada Rektor Universitas Subang untuk permohonan pengadaan/penambahan fasilitas kerja (komputer, printer, dll.) dilengkapi poin-poin pertimbangan.
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Lampiran Surat
                                    </label>
                                    <input
                                        v-model="form.extra_data.lampiran_count"
                                        type="text"
                                        placeholder="1 Lembar"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Tujuan Surat (Kepada Yth.)
                                    </label>
                                    <input
                                        v-model="form.extra_data.target_recipient"
                                        type="text"
                                        placeholder="Rektor Universitas Subang"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Perihal Surat <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.subject"
                                        type="text"
                                        placeholder="Permohonan Penambahan 2 Unit Komputer dan 1 Unit Printer BAAK Fakultas Ilmu Komputer Universitas Subang"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white font-bold"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Bagian / Unit Kerja Pemohon
                                    </label>
                                    <input
                                        v-model="form.extra_data.unit_kerja"
                                        type="text"
                                        placeholder="BAAK FAKULTAS ILMU KOMPUTER"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white font-bold uppercase"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Tahun Akademik
                                    </label>
                                    <input
                                        v-model="form.extra_data.academic_year"
                                        type="text"
                                        placeholder="2026/2027"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Item Yang Diajukan (Ringkasan Pengadaan) <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.procurement_summary"
                                        type="text"
                                        placeholder="Permohonan Penambahan 2 Unit Komputer dan 1 Unit Printer Epson L3250"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white font-bold"
                                    />
                                </div>
                            </div>

                            <!-- Poin Pertimbangan -->
                            <div class="space-y-3 pt-3 border-t border-slate-200 dark:border-slate-700">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                        Poin-Poin Bahan Pertimbangan
                                    </label>
                                    <button
                                        type="button"
                                        @click="
                                            if (!Array.isArray(form.extra_data.considerations)) form.extra_data.considerations = [];
                                            form.extra_data.considerations.push('');
                                        "
                                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold text-blue-600 bg-blue-50 dark:bg-blue-900/40 hover:bg-blue-100 flex items-center gap-1"
                                    >
                                        + Tambah Poin
                                    </button>
                                </div>

                                <div class="space-y-2">
                                    <div
                                        v-for="(point, pIdx) in form.extra_data.considerations"
                                        :key="pIdx"
                                        class="flex items-center gap-2"
                                    >
                                        <span class="text-xs font-bold text-slate-500 shrink-0">{{ pIdx + 1 }}.</span>
                                        <input
                                            v-model="form.extra_data.considerations[pIdx]"
                                            type="text"
                                            placeholder="Tuliskan poin pertimbangan..."
                                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                        />
                                        <button
                                            v-if="form.extra_data.considerations.length > 1"
                                            type="button"
                                            @click="form.extra_data.considerations.splice(pIdx, 1)"
                                            class="p-2 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg shrink-0"
                                        >
                                            ✕
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Paragraf Harapan -->
                            <div class="pt-2">
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    Paragraf Harapan
                                </label>
                                <textarea
                                    v-model="form.extra_data.hope_text"
                                    rows="2"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                ></textarea>
                            </div>
                        </div>

                        <!-- KHUSUS SURAT PERBAIKAN SARANA & PRASARANA -->
                        <div v-if="selectedType?.code === 'perbaikan_sarpras'" class="space-y-4">
                            <div class="p-3 rounded-xl bg-amber-50/80 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-xs text-amber-900 dark:text-amber-200 flex items-start gap-2">
                                <span class="text-base">🛠️</span>
                                <div>
                                    <strong>Surat Perbaikan Sarana & Prasarana:</strong>
                                    Ditujukan kepada Rektor Universitas Subang untuk permohonan pergantian/perbaikan fasilitas sarana prasarana yang memerlukan pergantian, dilengkapi daftar item sarpras.
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Lampiran Surat
                                    </label>
                                    <input
                                        v-model="form.extra_data.lampiran_count"
                                        type="text"
                                        placeholder="-"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Tujuan Surat (Kepada Yth.)
                                    </label>
                                    <input
                                        v-model="form.extra_data.target_recipient"
                                        type="text"
                                        placeholder="Rektor Universitas Subang"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Perihal Surat <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.subject"
                                        type="text"
                                        placeholder="Permohonan Pergantian Sarana dan Prasarana Fakultas Ilmu Komputer"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white font-bold"
                                    />
                                </div>
                            </div>

                            <!-- Daftar Item Sarana dan Prasarana -->
                            <div class="space-y-3 pt-3 border-t border-slate-200 dark:border-slate-700">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                        Daftar Sarana & Prasarana Yang Dimohonkan
                                    </label>
                                    <button
                                        type="button"
                                        @click="
                                            if (!Array.isArray(form.extra_data.sarpras_items)) form.extra_data.sarpras_items = [];
                                            form.extra_data.sarpras_items.push('');
                                        "
                                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold text-amber-600 bg-amber-50 dark:bg-amber-900/40 hover:bg-amber-100 flex items-center gap-1"
                                    >
                                        + Tambah Item
                                    </button>
                                </div>

                                <div class="space-y-2">
                                    <div
                                        v-for="(item, iIdx) in form.extra_data.sarpras_items"
                                        :key="iIdx"
                                        class="flex items-center gap-2"
                                    >
                                        <span class="text-xs font-bold text-slate-500 shrink-0">{{ iIdx + 1 }}.</span>
                                        <input
                                            v-model="form.extra_data.sarpras_items[iIdx]"
                                            type="text"
                                            placeholder="Contoh: Kursi Pelayanan BAAK Fakultas Ilmu Komputer Sebanyak 10"
                                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                        />
                                        <button
                                            v-if="form.extra_data.sarpras_items.length > 1"
                                            type="button"
                                            @click="form.extra_data.sarpras_items.splice(iIdx, 1)"
                                            class="p-2 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg shrink-0"
                                        >
                                            ✕
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>



                        <!-- 19. SURAT PENGANTAR PERBAIKAN DATA MAHASISWA PDDIKTI -->
                        <div
                            v-else-if="selectedType?.code === 'perbaikan_data_pddikti'"
                            class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs space-y-5"
                        >
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-teal-600"></span>
                                <h2 class="text-sm font-bold text-slate-900 dark:text-white">
                                    3. Data Permohonan Perbaikan Data Mahasiswa PDDIKTI
                                </h2>
                            </div>

                            <!-- Header Memo & Tujuan -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Lampiran Surat
                                    </label>
                                    <input
                                        v-model="form.extra_data.lampiran_count"
                                        type="text"
                                        placeholder="1 Rangkap"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Perihal Surat <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.subject"
                                        type="text"
                                        placeholder="Pengajuan Perubahan Data Mahasiswa"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white font-bold"
                                    />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Tujuan Surat (Kepada Yth.)
                                    </label>
                                    <textarea
                                        v-model="form.extra_data.target_recipient"
                                        rows="3"
                                        placeholder="Rektor Universitas Subang&#10;u.p. Wakil Rektor I Bid. Akademik&#10;dan kemahasiswaan"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    ></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Kota / Lokasi Tujuan
                                    </label>
                                    <input
                                        v-model="form.extra_data.target_city"
                                        type="text"
                                        placeholder="Tempat"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Pernyataan Bukti Dokumen
                                    </label>
                                    <input
                                        v-model="form.extra_data.evidence_text"
                                        type="text"
                                        placeholder="Data yang diubah sesuai dengan fakta dan dibuktikan dengan dokumen terlampir."
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white text-xs"
                                    />
                                </div>
                            </div>

                            <!-- Dynamic Correction Table -->
                            <div class="space-y-3 pt-3 border-t border-slate-200 dark:border-slate-700">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <label class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                            Tabel Usulan Perbaikan Data Mahasiswa
                                        </label>
                                        <p class="text-[11px] text-slate-400">Rincian data awal dan usulan perbaikan data mahasiswa</p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="addCorrectionItem"
                                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold text-teal-700 bg-teal-50 dark:bg-teal-900/40 hover:bg-teal-100 flex items-center gap-1"
                                    >
                                        + Tambah Mahasiswa
                                    </button>
                                </div>

                                <div class="space-y-3">
                                    <div
                                        v-for="(item, cIdx) in form.extra_data.correction_items"
                                        :key="cIdx"
                                        class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950/60 space-y-3"
                                    >
                                        <div class="flex items-center justify-between pb-1 border-b border-slate-200 dark:border-slate-800">
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                                Mahasiswa #{{ cIdx + 1 }}
                                            </span>
                                            <button
                                                v-if="form.extra_data.correction_items.length > 1"
                                                type="button"
                                                @click="removeCorrectionItem(cIdx)"
                                                class="text-xs font-semibold text-rose-500 hover:text-rose-700"
                                            >
                                                Hapus ✕
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                                            <div class="md:col-span-4">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    NPM <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.npm"
                                                    type="text"
                                                    placeholder="D1A210020"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-mono uppercase"
                                                />
                                            </div>
                                            <div class="md:col-span-8">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Nama Lengkap Mahasiswa <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.name"
                                                    type="text"
                                                    placeholder="FANNY APRILIANTI"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-bold uppercase"
                                                />
                                            </div>
                                            <div class="md:col-span-6">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Data Awal <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.initial_data"
                                                    type="text"
                                                    placeholder="Kesalahan Nama : Fany Aprilianti"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white italic"
                                                />
                                            </div>
                                            <div class="md:col-span-6">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Usulan Perbaikan <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.proposed_correction"
                                                    type="text"
                                                    placeholder="FANNY APRILIANTI"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-bold"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- KHUSUS SURAT PEMBERITAHUAN UTS/UAS KE DOSEN -->
                        <div v-if="selectedType?.code === 'pemberitahuan_ujian_dosen'" class="space-y-4">
                            <div class="p-3 rounded-xl bg-indigo-50/80 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800 text-xs text-indigo-900 dark:text-indigo-200 flex items-start gap-2">
                                <span class="text-base">📢</span>
                                <div>
                                    <strong>Surat Pemberitahuan Ujian (UTS / UAS) ke Dosen:</strong>
                                    Ditujukan kepada Bapak/Ibu Dosen Fakultas Ilmu Komputer Universitas Subang mengenai jadwal pelaksanaan ujian daring/luring dan 10 poin ketentuan teknis perkuliahan, soal, presensi, serta penginputan nilai.
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Lampiran Surat
                                    </label>
                                    <input
                                        v-model="form.extra_data.lampiran_count"
                                        type="text"
                                        placeholder="-"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Perihal Surat <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.subject"
                                        type="text"
                                        placeholder="Pemberitahuan Ujian Tengah Semester Ganjil TA.2026/2027"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white font-bold"
                                    />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Tujuan Surat (Kepada Yth.)
                                    </label>
                                    <textarea
                                        v-model="form.extra_data.target_recipient"
                                        rows="2"
                                        placeholder="Bapak/Ibu Dosen&#10;Fakultas Ilmu Komputer Universitas Subang"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    ></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Jenis Ujian
                                    </label>
                                    <select
                                        v-model="form.extra_data.exam_type"
                                        @change="
                                            form.extra_data.subject = 'Pemberitahuan ' + form.extra_data.exam_type + ' ' + (form.extra_data.semester_type || 'Ganjil') + ' TA.' + (form.extra_data.academic_year || '2026/2027')
                                        "
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    >
                                        <option value="Ujian Tengah Semester">Ujian Tengah Semester (UTS)</option>
                                        <option value="Ujian Akhir Semester">Ujian Akhir Semester (UAS)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Semester
                                    </label>
                                    <select
                                        v-model="form.extra_data.semester_type"
                                        @change="
                                            form.extra_data.subject = 'Pemberitahuan ' + (form.extra_data.exam_type || 'Ujian Tengah Semester') + ' ' + form.extra_data.semester_type + ' TA.' + (form.extra_data.academic_year || '2026/2027')
                                        "
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    >
                                        <option value="Ganjil">Ganjil</option>
                                        <option value="Genap">Genap</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Tahun Akademik
                                    </label>
                                    <input
                                        v-model="form.extra_data.academic_year"
                                        type="text"
                                        placeholder="2026/2027"
                                        @input="
                                            form.extra_data.subject = 'Pemberitahuan ' + (form.extra_data.exam_type || 'Ujian Tengah Semester') + ' ' + (form.extra_data.semester_type || 'Ganjil') + ' TA.' + form.extra_data.academic_year
                                        "
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Rentang Tanggal Pelaksanaan Ujian <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.exam_period"
                                        type="text"
                                        placeholder="9 November 2026 s/d 14 November 2026"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white font-bold"
                                    />
                                </div>
                            </div>

                            <!-- Poin Ketentuan Ujian -->
                            <div class="space-y-3 pt-3 border-t border-slate-200 dark:border-slate-700">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                        Poin-Poin Ketentuan Pelaksanaan Ujian
                                    </label>
                                    <button
                                        type="button"
                                        @click="
                                            if (!Array.isArray(form.extra_data.provision_points)) form.extra_data.provision_points = [];
                                            form.extra_data.provision_points.push('');
                                        "
                                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold text-indigo-600 bg-indigo-50 dark:bg-indigo-900/40 hover:bg-indigo-100 flex items-center gap-1"
                                    >
                                        + Tambah Ketentuan
                                    </button>
                                </div>

                                <div class="space-y-2">
                                    <div
                                        v-for="(p, pIdx) in form.extra_data.provision_points"
                                        :key="pIdx"
                                        class="flex items-start gap-2"
                                    >
                                        <span class="text-xs font-bold text-slate-500 pt-1 shrink-0">{{ pIdx + 1 }}.</span>
                                        <textarea
                                            v-model="form.extra_data.provision_points[pIdx]"
                                            rows="2"
                                            placeholder="Tuliskan ketentuan pelaksanaan ujian..."
                                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                        ></textarea>
                                        <button
                                            v-if="form.extra_data.provision_points.length > 1"
                                            type="button"
                                            @click="form.extra_data.provision_points.splice(pIdx, 1)"
                                            class="p-2 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg shrink-0"
                                        >
                                            ✕
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>



                        <!-- 19. SURAT PENGANTAR PERBAIKAN DATA MAHASISWA PDDIKTI -->
                        <div
                            v-else-if="selectedType?.code === 'perbaikan_data_pddikti'"
                            class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs space-y-5"
                        >
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-teal-600"></span>
                                <h2 class="text-sm font-bold text-slate-900 dark:text-white">
                                    3. Data Permohonan Perbaikan Data Mahasiswa PDDIKTI
                                </h2>
                            </div>

                            <!-- Header Memo & Tujuan -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Lampiran Surat
                                    </label>
                                    <input
                                        v-model="form.extra_data.lampiran_count"
                                        type="text"
                                        placeholder="1 Rangkap"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Perihal Surat <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.subject"
                                        type="text"
                                        placeholder="Pengajuan Perubahan Data Mahasiswa"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white font-bold"
                                    />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Tujuan Surat (Kepada Yth.)
                                    </label>
                                    <textarea
                                        v-model="form.extra_data.target_recipient"
                                        rows="3"
                                        placeholder="Rektor Universitas Subang&#10;u.p. Wakil Rektor I Bid. Akademik&#10;dan kemahasiswaan"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    ></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Kota / Lokasi Tujuan
                                    </label>
                                    <input
                                        v-model="form.extra_data.target_city"
                                        type="text"
                                        placeholder="Tempat"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Pernyataan Bukti Dokumen
                                    </label>
                                    <input
                                        v-model="form.extra_data.evidence_text"
                                        type="text"
                                        placeholder="Data yang diubah sesuai dengan fakta dan dibuktikan dengan dokumen terlampir."
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white text-xs"
                                    />
                                </div>
                            </div>

                            <!-- Dynamic Correction Table -->
                            <div class="space-y-3 pt-3 border-t border-slate-200 dark:border-slate-700">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <label class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                            Tabel Usulan Perbaikan Data Mahasiswa
                                        </label>
                                        <p class="text-[11px] text-slate-400">Rincian data awal dan usulan perbaikan data mahasiswa</p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="addCorrectionItem"
                                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold text-teal-700 bg-teal-50 dark:bg-teal-900/40 hover:bg-teal-100 flex items-center gap-1"
                                    >
                                        + Tambah Mahasiswa
                                    </button>
                                </div>

                                <div class="space-y-3">
                                    <div
                                        v-for="(item, cIdx) in form.extra_data.correction_items"
                                        :key="cIdx"
                                        class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950/60 space-y-3"
                                    >
                                        <div class="flex items-center justify-between pb-1 border-b border-slate-200 dark:border-slate-800">
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                                Mahasiswa #{{ cIdx + 1 }}
                                            </span>
                                            <button
                                                v-if="form.extra_data.correction_items.length > 1"
                                                type="button"
                                                @click="removeCorrectionItem(cIdx)"
                                                class="text-xs font-semibold text-rose-500 hover:text-rose-700"
                                            >
                                                Hapus ✕
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                                            <div class="md:col-span-4">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    NPM <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.npm"
                                                    type="text"
                                                    placeholder="D1A210020"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-mono uppercase"
                                                />
                                            </div>
                                            <div class="md:col-span-8">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Nama Lengkap Mahasiswa <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.name"
                                                    type="text"
                                                    placeholder="FANNY APRILIANTI"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-bold uppercase"
                                                />
                                            </div>
                                            <div class="md:col-span-6">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Data Awal <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.initial_data"
                                                    type="text"
                                                    placeholder="Kesalahan Nama : Fany Aprilianti"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white italic"
                                                />
                                            </div>
                                            <div class="md:col-span-6">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Usulan Perbaikan <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.proposed_correction"
                                                    type="text"
                                                    placeholder="FANNY APRILIANTI"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-bold"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- KHUSUS SURAT PENGUMUMAN UTS/UAS KE MAHASISWA -->
                        <div v-if="selectedType?.code === 'pengumuman_ujian_mahasiswa'" class="space-y-4">
                            <div class="p-3 rounded-xl bg-purple-50/80 dark:bg-purple-950/40 border border-purple-200 dark:border-purple-800 text-xs text-purple-900 dark:text-purple-200 flex items-start gap-2">
                                <span class="text-base">📢</span>
                                <div>
                                    <strong>Surat Pengumuman Ujian (UTS / UAS) ke Mahasiswa:</strong>
                                    Format pengumuman resmi bertajuk <u>PENGUMUMAN</u> dengan nomor surat Fasilkom, merinci jadwal ujian daring/luring serta ketentuan berpakaian, lunas kuliah/syarat kartu ujian, komponen nilai, dan tanggal masuk kuliah kembali.
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Jenis Ujian
                                    </label>
                                    <select
                                        v-model="form.extra_data.exam_type"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    >
                                        <option value="Ujian Tengah Semester">Ujian Tengah Semester (UTS)</option>
                                        <option value="Ujian Akhir Semester">Ujian Akhir Semester (UAS)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Semester
                                    </label>
                                    <select
                                        v-model="form.extra_data.semester_type"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    >
                                        <option value="Ganjil">Ganjil</option>
                                        <option value="Genap">Genap</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Tahun Akademik
                                    </label>
                                    <input
                                        v-model="form.extra_data.academic_year"
                                        type="text"
                                        placeholder="2026/2027"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white font-bold"
                                    />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Dasar Surat Rujukan / Edaran
                                    </label>
                                    <input
                                        v-model="form.extra_data.source_reference"
                                        type="text"
                                        placeholder="surat edaran dari Wakil Rektor I Bidang Akademik dan kemahasiswaan"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Rentang Pelaksanaan Ujian <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.exam_period"
                                        type="text"
                                        placeholder="9 s/d 14 November 2026"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white font-bold"
                                    />
                                </div>
                            </div>

                            <!-- Poin Ketentuan Pengumuman -->
                            <div class="space-y-3 pt-3 border-t border-slate-200 dark:border-slate-700">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                        Butir-Butir Ketentuan Pengumuman
                                    </label>
                                    <button
                                        type="button"
                                        @click="
                                            if (!Array.isArray(form.extra_data.provision_points)) form.extra_data.provision_points = [];
                                            form.extra_data.provision_points.push('');
                                        "
                                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold text-purple-600 bg-purple-50 dark:bg-purple-900/40 hover:bg-purple-100 flex items-center gap-1"
                                    >
                                        + Tambah Butir
                                    </button>
                                </div>

                                <div class="space-y-2">
                                    <div
                                        v-for="(p, pIdx) in form.extra_data.provision_points"
                                        :key="pIdx"
                                        class="flex items-start gap-2"
                                    >
                                        <span class="text-xs font-bold text-slate-500 pt-1 shrink-0">{{ pIdx + 1 }}.</span>
                                        <textarea
                                            v-model="form.extra_data.provision_points[pIdx]"
                                            rows="2"
                                            placeholder="Tuliskan butir pengumuman..."
                                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                        ></textarea>
                                        <button
                                            v-if="form.extra_data.provision_points.length > 1"
                                            type="button"
                                            @click="form.extra_data.provision_points.splice(pIdx, 1)"
                                            class="p-2 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg shrink-0"
                                        >
                                            ✕
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>



                        <!-- 19. SURAT PENGANTAR PERBAIKAN DATA MAHASISWA PDDIKTI -->
                        <div
                            v-else-if="selectedType?.code === 'perbaikan_data_pddikti'"
                            class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs space-y-5"
                        >
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-teal-600"></span>
                                <h2 class="text-sm font-bold text-slate-900 dark:text-white">
                                    3. Data Permohonan Perbaikan Data Mahasiswa PDDIKTI
                                </h2>
                            </div>

                            <!-- Header Memo & Tujuan -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Lampiran Surat
                                    </label>
                                    <input
                                        v-model="form.extra_data.lampiran_count"
                                        type="text"
                                        placeholder="1 Rangkap"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Perihal Surat <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.subject"
                                        type="text"
                                        placeholder="Pengajuan Perubahan Data Mahasiswa"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white font-bold"
                                    />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Tujuan Surat (Kepada Yth.)
                                    </label>
                                    <textarea
                                        v-model="form.extra_data.target_recipient"
                                        rows="3"
                                        placeholder="Rektor Universitas Subang&#10;u.p. Wakil Rektor I Bid. Akademik&#10;dan kemahasiswaan"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    ></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Kota / Lokasi Tujuan
                                    </label>
                                    <input
                                        v-model="form.extra_data.target_city"
                                        type="text"
                                        placeholder="Tempat"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Pernyataan Bukti Dokumen
                                    </label>
                                    <input
                                        v-model="form.extra_data.evidence_text"
                                        type="text"
                                        placeholder="Data yang diubah sesuai dengan fakta dan dibuktikan dengan dokumen terlampir."
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white text-xs"
                                    />
                                </div>
                            </div>

                            <!-- Dynamic Correction Table -->
                            <div class="space-y-3 pt-3 border-t border-slate-200 dark:border-slate-700">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <label class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                            Tabel Usulan Perbaikan Data Mahasiswa
                                        </label>
                                        <p class="text-[11px] text-slate-400">Rincian data awal dan usulan perbaikan data mahasiswa</p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="addCorrectionItem"
                                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold text-teal-700 bg-teal-50 dark:bg-teal-900/40 hover:bg-teal-100 flex items-center gap-1"
                                    >
                                        + Tambah Mahasiswa
                                    </button>
                                </div>

                                <div class="space-y-3">
                                    <div
                                        v-for="(item, cIdx) in form.extra_data.correction_items"
                                        :key="cIdx"
                                        class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950/60 space-y-3"
                                    >
                                        <div class="flex items-center justify-between pb-1 border-b border-slate-200 dark:border-slate-800">
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                                Mahasiswa #{{ cIdx + 1 }}
                                            </span>
                                            <button
                                                v-if="form.extra_data.correction_items.length > 1"
                                                type="button"
                                                @click="removeCorrectionItem(cIdx)"
                                                class="text-xs font-semibold text-rose-500 hover:text-rose-700"
                                            >
                                                Hapus ✕
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                                            <div class="md:col-span-4">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    NPM <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.npm"
                                                    type="text"
                                                    placeholder="D1A210020"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-mono uppercase"
                                                />
                                            </div>
                                            <div class="md:col-span-8">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Nama Lengkap Mahasiswa <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.name"
                                                    type="text"
                                                    placeholder="FANNY APRILIANTI"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-bold uppercase"
                                                />
                                            </div>
                                            <div class="md:col-span-6">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Data Awal <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.initial_data"
                                                    type="text"
                                                    placeholder="Kesalahan Nama : Fany Aprilianti"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white italic"
                                                />
                                            </div>
                                            <div class="md:col-span-6">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Usulan Perbaikan <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.proposed_correction"
                                                    type="text"
                                                    placeholder="FANNY APRILIANTI"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-bold"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                        <!-- 19. SURAT PENGANTAR PERBAIKAN DATA MAHASISWA PDDIKTI -->
                        <div
                            v-else-if="selectedType?.code === 'perbaikan_data_pddikti'"
                            class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs space-y-5"
                        >
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-teal-600"></span>
                                <h2 class="text-sm font-bold text-slate-900 dark:text-white">
                                    3. Data Permohonan Perbaikan Data Mahasiswa PDDIKTI
                                </h2>
                            </div>

                            <!-- Header Memo & Tujuan -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Lampiran Surat
                                    </label>
                                    <input
                                        v-model="form.extra_data.lampiran_count"
                                        type="text"
                                        placeholder="1 Rangkap"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Perihal Surat <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.extra_data.subject"
                                        type="text"
                                        placeholder="Pengajuan Perubahan Data Mahasiswa"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white font-bold"
                                    />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Tujuan Surat (Kepada Yth.)
                                    </label>
                                    <textarea
                                        v-model="form.extra_data.target_recipient"
                                        rows="3"
                                        placeholder="Rektor Universitas Subang&#10;u.p. Wakil Rektor I Bid. Akademik&#10;dan kemahasiswaan"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    ></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Kota / Lokasi Tujuan
                                    </label>
                                    <input
                                        v-model="form.extra_data.target_city"
                                        type="text"
                                        placeholder="Tempat"
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Pernyataan Bukti Dokumen
                                    </label>
                                    <input
                                        v-model="form.extra_data.evidence_text"
                                        type="text"
                                        placeholder="Data yang diubah sesuai dengan fakta dan dibuktikan dengan dokumen terlampir."
                                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white text-xs"
                                    />
                                </div>
                            </div>

                            <!-- Dynamic Correction Table -->
                            <div class="space-y-3 pt-3 border-t border-slate-200 dark:border-slate-700">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <label class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                            Tabel Usulan Perbaikan Data Mahasiswa
                                        </label>
                                        <p class="text-[11px] text-slate-400">Rincian data awal dan usulan perbaikan data mahasiswa</p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="addCorrectionItem"
                                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold text-teal-700 bg-teal-50 dark:bg-teal-900/40 hover:bg-teal-100 flex items-center gap-1"
                                    >
                                        + Tambah Mahasiswa
                                    </button>
                                </div>

                                <div class="space-y-3">
                                    <div
                                        v-for="(item, cIdx) in form.extra_data.correction_items"
                                        :key="cIdx"
                                        class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950/60 space-y-3"
                                    >
                                        <div class="flex items-center justify-between pb-1 border-b border-slate-200 dark:border-slate-800">
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                                Mahasiswa #{{ cIdx + 1 }}
                                            </span>
                                            <button
                                                v-if="form.extra_data.correction_items.length > 1"
                                                type="button"
                                                @click="removeCorrectionItem(cIdx)"
                                                class="text-xs font-semibold text-rose-500 hover:text-rose-700"
                                            >
                                                Hapus ✕
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                                            <div class="md:col-span-4">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    NPM <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.npm"
                                                    type="text"
                                                    placeholder="D1A210020"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-mono uppercase"
                                                />
                                            </div>
                                            <div class="md:col-span-8">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Nama Lengkap Mahasiswa <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.name"
                                                    type="text"
                                                    placeholder="FANNY APRILIANTI"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-bold uppercase"
                                                />
                                            </div>
                                            <div class="md:col-span-6">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Data Awal <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.initial_data"
                                                    type="text"
                                                    placeholder="Kesalahan Nama : Fany Aprilianti"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white italic"
                                                />
                                            </div>
                                            <div class="md:col-span-6">
                                                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Usulan Perbaikan <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="item.proposed_correction"
                                                    type="text"
                                                    placeholder="FANNY APRILIANTI"
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-bold"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <!-- TOMBOL SUBMIT -->
                    <div class="flex items-center justify-end gap-3 pt-2">
                        <Link
                            :href="route('letters.index')"
                            class="px-5 py-2.5 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition"
                        >
                            Batal
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-600/30 transition disabled:opacity-50"
                        >
                            <svg v-if="form.processing" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            Kirim Permohonan Surat
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

