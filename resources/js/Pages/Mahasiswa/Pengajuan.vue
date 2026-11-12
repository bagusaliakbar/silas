<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps({
    letterTypes: Array,
    signers: {
        type: Array,
        default: () => [],
    },
    user: Object,
    initialType: String,
});

const defaultTypeId = computed(() => {
    if (props.initialType) {
        const found = props.letterTypes?.find((t) => t.code === props.initialType);
        if (found) return found.id;
    }
    return props.letterTypes?.length > 0 ? props.letterTypes[0].id : '';
});

// Helper: menghasilkan ekstra data yang BERSIH dan HANYA relevan untuk jenis surat tersebut
const getCleanExtraDataForType = (code) => {
    switch (code) {
        case 'pengantar_pkl':
            return {
                target_recipient: '',
                target_agency: '',
                target_city: '',
                internship_duration: '',
                start_date: '',
                end_date: '',
            };
        case 'pengantar_magang':
            return {
                target_recipient: '',
                target_agency: '',
                target_city: '',
                internship_duration: '',
                start_date: '',
                end_date: '',
                group_members: [],
            };
        case 'observasi_matkul':
            return {
                course_name: '',
                lecturer_name: '',
                target_recipient: '',
                target_agency: '',
                target_city: '',
                target_address: '',
                group_members: [],
            };
        case 'izin_penelitian_skripsi':
            return {
                thesis_title: '',
                target_recipient: '',
                target_agency: '',
                target_city: '',
                target_address: '',
            };
        case 'izin_mengikuti_ujian':
            return {
                target_recipient: '',
                target_city: '',
                exam_type: 'UAS',
                semester_type: 'Ganjil',
                exam_dates: '',
                lampiran_count: '2 (dua) lembar',
                exam_schedules: [],
            };
        case 'ujian_susulan':
            return {
                lecturer_name: '',
                course_name: '',
                exam_type: 'UTS',
                original_score: '',
            };
        case 'perbaikan_nilai':
            return {
                lecturer_name: '',
                course_name: '',
                original_score: '',
            };
        case 'keterangan_yudisium':
            return {
                defense_date_text: '',
                ipk: '',
                study_duration: '',
                defense_grade: '',
                defense_result: 'Lulus',
                yudisium_date_text: '',
                yudisium_predicate: 'Dengan Pujian',
            };
        case 'keterangan_kuliah_pns':
            return {
                parent_name: '',
                parent_nip: '',
                parent_rank: '',
                parent_institution: '',
            };
        case 'rekomendasi_beasiswa':
            return {
                scholarship_name: '',
                scholarship_provider: '',
                pejabat_fakultas: 'Ilmu Komputer Universitas Subang',
            };
        case 'rekomendasi_luar_prodi':
            return {
                program_name: '',
                activity_period: '',
                pejabat_prodi: 'Sistem Informasi',
                pejabat_fakultas: 'Ilmu Komputer Universitas Subang',
                fakultas_mahasiswa: 'Ilmu Komputer Universitas Subang',
            };
        case 'izin_seminar_up':
            return {
                activity_name: 'Seminar Usulan Penelitian',
                event_day: '',
                thesis_title: '',
                target_recipient: '',
                target_agency: '',
                target_city: '',
            };
        case 'izin_sidang_ta':
            return {
                activity_name: 'Sidang Tugas Akhir',
                event_day: '',
                thesis_title: '',
                target_recipient: '',
                target_agency: '',
                target_city: '',
            };
        default:
            return {};
    }
};

// Multi-Step Wizard State
const currentStep = ref(props.initialType ? 2 : 1);
const declarationAgreed = ref(false);
const stepErrors = ref([]);

// Step 1 Search & Category Filtering
const searchType = ref('');
const selectedCategory = ref('all');

const getCategoryForCode = (code) => {
    if (['pengantar_pkl', 'pengantar_magang'].includes(code)) return 'magang';
    if (['izin_penelitian_skripsi', 'izin_seminar_up', 'izin_sidang_ta', 'keterangan_yudisium'].includes(code)) return 'skripsi';
    if (['observasi_matkul', 'izin_mengikuti_ujian', 'ujian_susulan', 'perbaikan_nilai'].includes(code)) return 'akademik';
    return 'keterangan';
};

const categories = [
    { id: 'all', label: 'Semua Layanan' },
    { id: 'magang', label: 'Magang & PKL' },
    { id: 'skripsi', label: 'Skripsi & Yudisium' },
    { id: 'akademik', label: 'Akademik & Ujian' },
    { id: 'keterangan', label: 'Surat Keterangan & Rekomendasi' },
];

const filteredLetterTypes = computed(() => {
    if (!props.letterTypes) return [];
    return props.letterTypes.filter((t) => {
        const matchesCategory = selectedCategory.value === 'all' || getCategoryForCode(t.code) === selectedCategory.value;
        const q = searchType.value.toLowerCase().trim();
        const matchesSearch = !q || (t.name && t.name.toLowerCase().includes(q)) || (t.description && t.description.toLowerCase().includes(q));
        return matchesCategory && matchesSearch;
    });
});

const initialSelectedCode = props.letterTypes?.find((t) => t.id === defaultTypeId.value)?.code;

const form = useForm({
    letter_type_id: defaultTypeId.value,
    student_name: props.user.name || '',
    npm: props.user.npm || '',
    study_program: props.user.study_program || 'Sistem Informasi',
    birth_place: props.user.birth_place || '',
    birth_date: props.user.birth_date || '',
    address: props.user.address || 'SUBANG',
    semester: props.user.semester || 1,
    academic_year: props.user.academic_year || '2026/2027',
    purpose: '',
    signer_id: null,
    extra_data: getCleanExtraDataForType(initialSelectedCode),
});

const selectedType = computed(() => {
    return props.letterTypes?.find((t) => t.id === form.letter_type_id);
});

// Auto-Save Draft System (localStorage)
const draftKey = computed(() => {
    const userId = props.user?.id || 'guest';
    const typeId = form.letter_type_id || 'none';
    return `silas_letter_draft_${userId}_${typeId}`;
});

const isDraftSaved = ref(false);
const lastSavedTime = ref('');
const hasExistingDraft = ref(false);
const existingDraftTime = ref('');

const checkExistingDraft = (typeId) => {
    try {
        const key = `silas_letter_draft_${props.user?.id || 'guest'}_${typeId}`;
        const raw = localStorage.getItem(key);
        if (raw) {
            const data = JSON.parse(raw);
            if (data && data.extra_data && Object.keys(data.extra_data).length > 0) {
                const hasContent = Object.values(data.extra_data).some(v => v && (typeof v === 'string' ? v.trim() !== '' : Array.isArray(v) ? v.length > 0 : true));
                if (hasContent) {
                    hasExistingDraft.value = true;
                    existingDraftTime.value = data.savedAt || 'beberapa waktu lalu';
                    return;
                }
            }
        }
    } catch (e) {}
    hasExistingDraft.value = false;
};

const restoreDraft = () => {
    try {
        const raw = localStorage.getItem(draftKey.value);
        if (raw) {
            const data = JSON.parse(raw);
            if (data.extra_data) {
                form.extra_data = { ...form.extra_data, ...data.extra_data };
            }
            if (data.purpose) form.purpose = data.purpose;
            if (data.semester) form.semester = data.semester;
            if (data.academic_year) form.academic_year = data.academic_year;
            hasExistingDraft.value = false;
            isDraftSaved.value = true;
            lastSavedTime.value = data.savedAt || 'baru saja';
        }
    } catch (e) {}
};

const discardDraft = () => {
    try {
        localStorage.removeItem(draftKey.value);
        hasExistingDraft.value = false;
    } catch (e) {}
};

// Reset extra_data saat berganti jenis surat agar data antar surat tidak bocor/bercampur
watch(
    () => form.letter_type_id,
    (newId, oldId) => {
        if (!newId || newId === oldId) return;
        const type = props.letterTypes?.find((t) => t.id === newId);
        if (type) {
            form.extra_data = getCleanExtraDataForType(type.code);
            checkExistingDraft(newId);
        }
    }
);

// Auto-save debounced watcher
let draftSaveTimeout = null;
watch(
    () => [form.extra_data, form.purpose, form.semester, form.academic_year],
    () => {
        if (currentStep.value === 2 && form.letter_type_id) {
            clearTimeout(draftSaveTimeout);
            draftSaveTimeout = setTimeout(() => {
                try {
                    const now = new Date();
                    const timeStr = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                    const payload = {
                        extra_data: form.extra_data,
                        purpose: form.purpose,
                        semester: form.semester,
                        academic_year: form.academic_year,
                        savedAt: timeStr,
                    };
                    localStorage.setItem(draftKey.value, JSON.stringify(payload));
                    lastSavedTime.value = timeStr;
                    isDraftSaved.value = true;
                } catch (e) {}
            }, 800);
        }
    },
    { deep: true }
);

onMounted(() => {
    if (form.letter_type_id) {
        checkExistingDraft(form.letter_type_id);
    }
});

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

// Wizard Step Navigation & Validation
const selectLetterType = (typeId) => {
    form.letter_type_id = typeId;
};

const chooseTypeAndProceed = (typeId) => {
    form.letter_type_id = typeId;
    goToStep(2);
};

const validateStep2 = () => {
    const errs = [];
    let firstErrorField = null;

    const addErr = (msg, fieldId) => {
        errs.push(msg);
        if (!firstErrorField && fieldId) firstErrorField = fieldId;
    };

    if (!form.student_name?.trim()) addErr('Nama Mahasiswa wajib diisi', 'field-student-name');
    if (!form.npm?.trim()) addErr('NPM wajib diisi', 'field-npm');
    if (!form.study_program?.trim()) addErr('Program Studi wajib diisi', 'field-study-program');
    if (!form.semester) addErr('Semester wajib dipilih', 'field-semester');
    if (!form.academic_year?.trim()) addErr('Tahun Akademik wajib diisi', 'field-academic-year');

    const code = selectedType.value?.code;
    if (code === 'pengantar_pkl' || code === 'pengantar_magang') {
        if (!form.extra_data.target_recipient?.trim()) addErr('Penerima Surat (Kepada Yth.) wajib diisi', 'field-target-recipient');
        if (!form.extra_data.target_agency?.trim()) addErr('Instansi / Perusahaan Tujuan wajib diisi', 'field-target-agency');
        if (!form.extra_data.internship_duration?.trim()) addErr('Durasi pelaksanaan wajib diisi', 'field-internship-duration');
        if (!form.extra_data.start_date?.trim()) addErr('Tanggal mulai wajib diisi', 'field-start-date');
        if (!form.extra_data.end_date?.trim()) addErr('Tanggal selesai wajib diisi', 'field-end-date');
    } else if (code === 'observasi_matkul') {
        if (!form.extra_data.course_name?.trim()) addErr('Mata Kuliah wajib diisi', 'field-course-name');
        if (!form.extra_data.lecturer_name?.trim()) addErr('Dosen Pengampu wajib diisi', 'field-lecturer-name');
        if (!form.extra_data.target_recipient?.trim()) addErr('Penerima Surat wajib diisi', 'field-target-recipient');
        if (!form.extra_data.target_agency?.trim()) addErr('Instansi / Perusahaan Tujuan wajib diisi', 'field-target-agency');
    } else if (code === 'izin_penelitian_skripsi') {
        if (!form.extra_data.thesis_title?.trim()) addErr('Judul Skripsi / Penelitian wajib diisi', 'field-thesis-title');
        if (!form.extra_data.target_recipient?.trim()) addErr('Penerima Surat wajib diisi', 'field-target-recipient');
        if (!form.extra_data.target_agency?.trim()) addErr('Instansi Tujuan wajib diisi', 'field-target-agency');
    } else if (code === 'izin_mengikuti_ujian') {
        if (!form.extra_data.target_recipient?.trim()) addErr('Penerima Surat wajib diisi', 'field-target-recipient');
        if (!form.extra_data.exam_type?.trim()) addErr('Jenis Ujian (UTS/UAS) wajib dipilih', 'field-exam-type');
        if (!form.extra_data.exam_schedules || form.extra_data.exam_schedules.length === 0) {
            addErr('Minimal sertakan 1 jadwal ujian pada tabel', 'field-exam-schedules');
        }
    } else if (code === 'ujian_susulan' || code === 'perbaikan_nilai') {
        if (!form.extra_data.lecturer_name?.trim()) addErr('Dosen Pengampu wajib diisi', 'field-lecturer-name');
        if (!form.extra_data.course_name?.trim()) addErr('Mata Kuliah wajib diisi', 'field-course-name');
        if (code === 'perbaikan_nilai' && !form.extra_data.original_score?.trim()) addErr('Nilai Asal wajib diisi', 'field-original-score');
    } else if (code === 'keterangan_yudisium') {
        if (!form.extra_data.defense_date_text?.trim()) addErr('Tanggal Sidang Tugas Akhir wajib diisi', 'field-defense-date');
        if (!form.extra_data.ipk?.trim()) addErr('IPK wajib diisi', 'field-ipk');
        if (!form.extra_data.defense_grade?.trim()) addErr('Nilai Sidang TA wajib diisi', 'field-defense-grade');
        if (!form.extra_data.yudisium_date_text?.trim()) addErr('Tanggal Sidang Yudisium wajib diisi', 'field-yudisium-date');
    } else if (code === 'keterangan_kuliah_pns') {
        if (!form.extra_data.parent_name?.trim()) addErr('Nama Orang Tua PNS wajib diisi', 'field-parent-name');
        if (!form.extra_data.parent_nip?.trim()) addErr('NIP Orang Tua wajib diisi', 'field-parent-nip');
    } else if (code === 'rekomendasi_beasiswa') {
        if (!form.extra_data.scholarship_name?.trim()) addErr('Nama Program Beasiswa wajib diisi', 'field-scholarship-name');
        if (!form.extra_data.scholarship_provider?.trim()) addErr('Penyelenggara / Lembaga Beasiswa wajib diisi', 'field-scholarship-provider');
    } else if (code === 'rekomendasi_luar_prodi') {
        if (!form.extra_data.program_name?.trim()) addErr('Nama Program Kegiatan wajib diisi', 'field-program-name');
        if (!form.extra_data.activity_period?.trim()) addErr('Periode Pelaksanaan Kegiatan wajib diisi', 'field-activity-period');
    }

    stepErrors.value = errs;

    if (firstErrorField) {
        setTimeout(() => {
            const el = document.getElementById(firstErrorField);
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                el.focus();
            }
        }, 150);
    }

    return errs.length === 0;
};

const goToStep = (step) => {
    if (step === 2) {
        if (!form.letter_type_id) {
            stepErrors.value = ['Silakan pilih salah satu kategori surat terlebih dahulu'];
            return;
        }
        stepErrors.value = [];
        currentStep.value = 2;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else if (step === 3) {
        if (validateStep2()) {
            currentStep.value = 3;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
            window.scrollTo({ top: 180, behavior: 'smooth' });
        }
    } else if (step === 1) {
        currentStep.value = 1;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const submit = () => {
    if (!declarationAgreed.value) {
        alert('Silakan centang pernyataan keabsahan data terlebih dahulu.');
        return;
    }
    form.post(route('mahasiswa.letters.store'), {
        onSuccess: () => {
            try {
                localStorage.removeItem(draftKey.value);
            } catch (e) {}
        },
    });
};
</script>

<template>
    <Head title="Pengajuan Surat Permohonan" />

    <AuthenticatedLayout>
        <div class="py-6 sm:py-8">
            <!-- FULL SCREEN CONTAINER (Menghilangkan batasan max-w-5xl agar memenuhi layar secara elegan) -->
            <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6 pb-20 sm:pb-8">
                <!-- TOP HEADER -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div>
                            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight mt-0.5">
                                Formulir Pengajuan Surat Mahasiswa
                            </h1>
                        </div>
                    </div>

                    <!-- STEP INDICATOR BADGE (MOBILE COMPACT) -->
                    <div class="sm:hidden flex items-center justify-between p-3 rounded-xl bg-blue-50 dark:bg-blue-950/40 border border-blue-200/80 dark:border-blue-900/50">
                        <span class="text-xs font-bold text-blue-900 dark:text-blue-200">
                            Langkah {{ currentStep }} dari 3
                        </span>
                        <span class="text-xs text-blue-700 dark:text-blue-300 font-medium">
                            {{ currentStep === 1 ? 'Pilih Jenis Surat' : (currentStep === 2 ? 'Lengkapi Data' : 'Konfirmasi & Kirim') }}
                        </span>
                    </div>
                </div>

                <!-- PROGRESS STEPPER (DESKTOP / TABLET) -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 sm:p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs">
                    <div class="grid grid-cols-3 gap-3 sm:gap-6 relative">
                        <!-- Step 1 Button -->
                        <button
                            type="button"
                            @click="goToStep(1)"
                            class="flex items-center gap-3 sm:gap-4 p-2 sm:p-3 rounded-xl transition text-left group"
                            :class="currentStep === 1 ? 'bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800' : 'hover:bg-slate-50 dark:hover:bg-slate-800/50'"
                        >
                            <div
                                class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center text-xs sm:text-sm font-black shrink-0 transition"
                                :class="[
                                    currentStep === 1
                                        ? 'bg-blue-600 text-white shadow-xs'
                                        : currentStep > 1
                                        ? 'bg-emerald-600 text-white'
                                        : 'bg-slate-100 dark:bg-slate-800 text-slate-400'
                                ]"
                            >
                                <span v-if="currentStep > 1">✓</span>
                                <span v-else>1</span>
                            </div>
                            <div class="min-w-0">
                                <div class="text-[10px] uppercase font-bold tracking-wider" :class="currentStep === 1 ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400'">
                                    Langkah 1
                                </div>
                                <div class="text-xs sm:text-sm font-bold truncate" :class="currentStep === 1 ? 'text-slate-900 dark:text-white' : 'text-slate-600 dark:text-slate-300'">
                                    Pilih Jenis Surat
                                </div>
                            </div>
                        </button>

                        <!-- Step 2 Button -->
                        <button
                            type="button"
                            @click="goToStep(2)"
                            :disabled="!form.letter_type_id"
                            class="flex items-center gap-3 sm:gap-4 p-2 sm:p-3 rounded-xl transition text-left group disabled:opacity-50 disabled:cursor-not-allowed"
                            :class="currentStep === 2 ? 'bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800' : 'hover:bg-slate-50 dark:hover:bg-slate-800/50'"
                        >
                            <div
                                class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center text-xs sm:text-sm font-black shrink-0 transition"
                                :class="[
                                    currentStep === 2
                                        ? 'bg-blue-600 text-white shadow-xs'
                                        : currentStep > 2
                                        ? 'bg-emerald-600 text-white'
                                        : 'bg-slate-100 dark:bg-slate-800 text-slate-400'
                                ]"
                            >
                                <span v-if="currentStep > 2">✓</span>
                                <span v-else>2</span>
                            </div>
                            <div class="min-w-0">
                                <div class="text-[10px] uppercase font-bold tracking-wider" :class="currentStep === 2 ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400'">
                                    Langkah 2
                                </div>
                                <div class="text-xs sm:text-sm font-bold truncate" :class="currentStep === 2 ? 'text-slate-900 dark:text-white' : 'text-slate-600 dark:text-slate-300'">
                                    Lengkapi Data & Syarat
                                </div>
                            </div>
                        </button>

                        <!-- Step 3 Button -->
                        <button
                            type="button"
                            @click="goToStep(3)"
                            :disabled="!form.letter_type_id"
                            class="flex items-center gap-3 sm:gap-4 p-2 sm:p-3 rounded-xl transition text-left group disabled:opacity-50 disabled:cursor-not-allowed"
                            :class="currentStep === 3 ? 'bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800' : 'hover:bg-slate-50 dark:hover:bg-slate-800/50'"
                        >
                            <div
                                class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center text-xs sm:text-sm font-black shrink-0 transition"
                                :class="[
                                    currentStep === 3
                                        ? 'bg-blue-600 text-white shadow-xs'
                                        : 'bg-slate-100 dark:bg-slate-800 text-slate-400'
                                ]"
                            >
                                3
                            </div>
                            <div class="min-w-0">
                                <div class="text-[10px] uppercase font-bold tracking-wider" :class="currentStep === 3 ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400'">
                                    Langkah 3
                                </div>
                                <div class="text-xs sm:text-sm font-bold truncate" :class="currentStep === 3 ? 'text-slate-900 dark:text-white' : 'text-slate-600 dark:text-slate-300'">
                                    Konfirmasi & Kirim
                                </div>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- ERROR ALERT BANNER -->
                <div v-if="stepErrors.length > 0" class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-300 dark:border-rose-800 text-rose-900 dark:text-rose-200">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div class="text-xs">
                            <strong class="font-bold block mb-1">Mohon lengkapi data yang masih belum terisi:</strong>
                            <ul class="list-disc list-inside space-y-0.5 text-rose-800 dark:text-rose-300">
                                <li v-for="(err, idx) in stepErrors" :key="idx">{{ err }}</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- ========================================================================= -->
                <!-- LANGKAH 1: PILIH JENIS SURAT (FULL WIDTH GRID & SMART SEARCH)             -->
                <!-- ========================================================================= -->
                <div v-if="currentStep === 1" class="space-y-6">
                    <!-- SEARCH & CATEGORY FILTER TOOLBAR -->
                    <div class="p-4 sm:p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-4">
                        <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
                            <!-- Search Input -->
                            <div class="relative flex-1">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input
                                    v-model="searchType"
                                    type="text"
                                    placeholder="Cari jenis surat (misal: PKL, Magang, Yudisium, Rekomendasi, Ujian)..."
                                    class="w-full pl-10 pr-10 py-2.5 text-xs sm:text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/80 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-hidden focus:ring-2 focus:ring-blue-600/30 focus:border-blue-600 transition"
                                />
                                <button
                                    v-if="searchType"
                                    type="button"
                                    @click="searchType = ''"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                                    title="Hapus pencarian"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Results Counter -->
                            <div class="text-xs text-slate-500 dark:text-slate-400 shrink-0 self-end md:self-auto font-medium">
                                Menampilkan <strong class="text-slate-900 dark:text-white font-bold">{{ filteredLetterTypes.length }}</strong> jenis surat
                            </div>
                        </div>

                        <!-- Category Tabs Pills -->
                        <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs no-scrollbar">
                            <button
                                v-for="cat in categories"
                                :key="cat.id"
                                type="button"
                                @click="selectedCategory = cat.id"
                                :class="[
                                    'px-3.5 py-1.5 rounded-xl font-semibold transition shrink-0',
                                    selectedCategory === cat.id
                                        ? 'bg-blue-600 text-white shadow-xs'
                                        : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700'
                                ]"
                            >
                                {{ cat.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Visual Letter Cards Grid (Full width up to 4 columns) -->
                    <div v-if="filteredLetterTypes.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        <div
                            v-for="type in filteredLetterTypes"
                            :key="type.id"
                            @click="selectLetterType(type.id)"
                            class="relative p-5 rounded-2xl border transition-all cursor-pointer flex flex-col justify-between group"
                            :class="[
                                form.letter_type_id === type.id
                                    ? 'bg-blue-50/70 dark:bg-blue-950/40 border-blue-600 dark:border-blue-500 shadow-md ring-2 ring-blue-600/30'
                                    : 'bg-white dark:bg-slate-900 border-slate-200/80 dark:border-slate-800 hover:border-blue-300 dark:hover:border-blue-700 hover:shadow-xs'
                            ]"
                        >
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                        {{ getCategoryForCode(type.code) === 'magang' ? 'Magang & PKL' : (getCategoryForCode(type.code) === 'skripsi' ? 'Skripsi & Yudisium' : (getCategoryForCode(type.code) === 'akademik' ? 'Akademik & Ujian' : 'Keterangan')) }}
                                    </span>
                                    <div
                                        class="w-5 h-5 rounded-full flex items-center justify-center text-xs transition"
                                        :class="form.letter_type_id === type.id ? 'bg-blue-600 text-white' : 'border border-slate-300 dark:border-slate-700 text-transparent'"
                                    >
                                        ✓
                                    </div>
                                </div>

                                <div>
                                    <h3 class="font-bold text-sm text-slate-900 dark:text-white leading-snug group-hover:text-blue-600 dark:group-hover:text-blue-300 transition-colors">
                                        {{ type.name }}
                                    </h3>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 line-clamp-3 leading-relaxed">
                                        {{ type.description || 'Layanan permohonan surat akademik resmi Fakultas Ilmu Komputer Universitas Subang.' }}
                                    </p>
                                </div>
                            </div>

                            <div class="pt-4 border-t border-slate-100 dark:border-slate-800/80 mt-4 flex items-center justify-between">
                                <span class="text-[11px] font-medium" :class="form.letter_type_id === type.id ? 'text-blue-600 dark:text-blue-400 font-bold' : 'text-slate-400'">
                                    {{ form.letter_type_id === type.id ? 'Terpilih' : 'Klik untuk memilih' }}
                                </span>
                                <button
                                    type="button"
                                    @click.stop="chooseTypeAndProceed(type.id)"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/60 hover:bg-blue-600 hover:text-white dark:hover:bg-blue-600 dark:hover:text-white transition shadow-2xs"
                                >
                                    <span>Pilih & Lanjut</span>
                                    <span>→</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State for Search Filter -->
                    <div v-else class="p-12 text-center rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-3">
                        <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <h4 class="font-bold text-sm text-slate-900 dark:text-white">Tidak ada jenis surat ditemukan</h4>
                        <p class="text-xs text-slate-500 max-w-md mx-auto">
                            Tidak ada surat yang sesuai dengan kata kunci pencarian atau kategori yang dipilih.
                        </p>
                        <button
                            type="button"
                            @click="searchType = ''; selectedCategory = 'all'"
                            class="px-4 py-2 rounded-xl text-xs font-bold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950 border border-blue-200 dark:border-blue-800 hover:bg-blue-100 transition"
                        >
                            Reset Filter Pencarian
                        </button>
                    </div>

                    <!-- Step 1 Bottom Action Bar -->
                    <div class="p-4 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-xs text-slate-600 dark:text-slate-400 text-center sm:text-left">
                            <span v-if="selectedType" class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                <span>Surat Dipilih: <strong class="text-slate-900 dark:text-white font-bold text-sm">{{ selectedType.name }}</strong></span>
                            </span>
                            <span v-else class="text-slate-400 italic">
                                Klik pada salah satu kartu surat di atas untuk melanjutkan pengisian data.
                            </span>
                        </div>

                        <button
                            type="button"
                            @click="goToStep(2)"
                            :disabled="!form.letter_type_id"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-3 rounded-xl text-xs sm:text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-900/20 transition hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span>Lanjut ke Pengisian Data</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- ========================================================================= -->
                <!-- LANGKAH 2: LENGKAPI DATA & PERSYARATAN KHUSUS (FULL WIDTH)               -->
                <!-- ========================================================================= -->
                <div v-else-if="currentStep === 2" class="space-y-6">
                    <!-- SAVED DRAFT NOTIFICATION BANNER -->
                    <div
                        v-if="hasExistingDraft"
                        class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-700/80 text-amber-900 dark:text-amber-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs"
                    >
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-xl bg-amber-500 text-white flex items-center justify-center font-bold text-sm shrink-0 shadow-2xs">
                                💾
                            </span>
                            <div class="text-xs">
                                <strong class="font-bold block">Draf Isian Ditemukan!</strong>
                                <span>Terdapat data yang tersimpan dari sesi sebelumnya untuk surat ini (tersimpan {{ existingDraftTime }}). Ingin pulihkan isian?</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0 self-end sm:self-auto">
                            <button
                                type="button"
                                @click="discardDraft"
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-amber-100/70 dark:hover:bg-slate-800 transition"
                            >
                                Buang Draf
                            </button>
                            <button
                                type="button"
                                @click="restoreDraft"
                                class="px-3.5 py-1.5 rounded-lg text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 shadow-xs transition"
                            >
                                Pulihkan Draf
                            </button>
                        </div>
                    </div>

                    <!-- Active Letter Summary Bar -->
                    <div class="p-4 rounded-2xl bg-blue-50/70 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shrink-0 shadow-xs font-bold">
                                📄
                            </div>
                            <div>
                                <span class="text-[10px] uppercase font-bold tracking-wider text-blue-600 dark:text-blue-400">
                                    Jenis Surat yang Diajukan
                                </span>
                                <h3 class="font-bold text-sm sm:text-base text-slate-900 dark:text-white leading-tight">
                                    {{ selectedType?.name }}
                                </h3>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 flex-wrap self-end sm:self-auto">
                            <!-- Auto-Save Status Badge -->
                            <span
                                v-if="isDraftSaved"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800"
                            >
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span>Draf tersimpan otomatis ({{ lastSavedTime }})</span>
                            </span>

                            <button
                                type="button"
                                @click="goToStep(1)"
                                class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline px-3.5 py-1.5 rounded-lg bg-white/60 dark:bg-slate-900/60 border border-blue-200 dark:border-blue-700 shrink-0"
                            >
                                Ubah Jenis Surat
                            </button>
                        </div>
                    </div>

                    <!-- Section A: Data Pemohon (Mahasiswa) -->
                    <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                                <h2 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">A. Identitas Mahasiswa Pemohon</h2>
                            </div>
                            <Link
                                :href="route('profile.edit')"
                                class="inline-flex items-center gap-1 text-[11px] font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 transition"
                                title="Perbarui data profil jika ada kekeliruan"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                                <span>Ubah di Profil</span>
                            </Link>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                    Nama Lengkap Mahasiswa <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    :value="form.student_name"
                                    type="text"
                                    disabled
                                    placeholder="Nama Lengkap Mahasiswa"
                                    class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-900/60 text-slate-500 dark:text-slate-400 cursor-not-allowed uppercase"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                    Nomor Pokok Mahasiswa (NPM) <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    :value="form.npm"
                                    type="text"
                                    disabled
                                    placeholder="NPM Mahasiswa"
                                    class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-900/60 text-slate-500 dark:text-slate-400 cursor-not-allowed uppercase font-mono"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                    Program Studi <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    :value="form.study_program"
                                    type="text"
                                    disabled
                                    class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-900/60 text-slate-500 dark:text-slate-400 cursor-not-allowed"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Semester Aktif</label>
                                <select
                                    v-model="form.semester"
                                    class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600"
                                >
                                    <option v-for="s in 8" :key="s" :value="s">Semester {{ s }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Tahun Akademik</label>
                                <input
                                    v-model="form.academic_year"
                                    type="text"
                                    placeholder="2025/2026"
                                    class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Keperluan Pembuatan Surat</label>
                                <input
                                    v-model="form.purpose"
                                    type="text"
                                    placeholder="Contoh: Persyaratan Beasiswa / Keperluan Administrasi"
                                    class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-600"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Tempat Lahir</label>
                                <input
                                    :value="form.birth_place"
                                    type="text"
                                    disabled
                                    placeholder="Tempat Lahir"
                                    class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-900/60 text-slate-500 dark:text-slate-400 cursor-not-allowed"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Tanggal Lahir</label>
                                <input
                                    :value="form.birth_date"
                                    type="date"
                                    disabled
                                    class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-900/60 text-slate-500 dark:text-slate-400 cursor-not-allowed"
                                />
                            </div>

                            <div class="sm:col-span-2 lg:col-span-1">
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Alamat Lengkap (Domisili)</label>
                                <input
                                    :value="form.address"
                                    type="text"
                                    disabled
                                    placeholder="Alamat Domisili"
                                    class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-900/60 text-slate-500 dark:text-slate-400 cursor-not-allowed"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Section B: Informasi Tambahan Khusus Surat Terpilih -->
                    <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                                <h2 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">
                                    B. Rincian Khusus Surat ({{ selectedType?.name }})
                                </h2>
                            </div>
                        </div>

                        <!-- 1. KHUSUS SURAT PENGANTAR PKL -->
                        <div v-if="selectedType?.code === 'pengantar_pkl'" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Tujuan Penerima Surat (Kepada Yth.) <span class="text-rose-500">*</span></label>
                                    <input v-model="form.extra_data.target_recipient" type="text" placeholder="Contoh: Kepala Bagian SDM / Pimpinan Instansi" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400" />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Instansi / Perusahaan Tempat PKL <span class="text-rose-500">*</span></label>
                                    <input v-model="form.extra_data.target_agency" type="text" placeholder="Masukkan nama instansi / perusahaan" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400" />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Alamat / Kota Instansi Tujuan</label>
                                    <input v-model="form.extra_data.target_city" type="text" placeholder="Contoh: Subang / Tempat" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400" />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Durasi Pelaksanaan PKL <span class="text-rose-500">*</span></label>
                                    <input v-model="form.extra_data.internship_duration" type="text" placeholder="Contoh: 1 bulan / 40 hari" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400" />
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Tanggal Mulai Pelaksanaan <span class="text-rose-500">*</span></label>
                                        <input v-model="form.extra_data.start_date" type="text" placeholder="Contoh: 01 Oktober 2026" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Tanggal Selesai Pelaksanaan <span class="text-rose-500">*</span></label>
                                        <input v-model="form.extra_data.end_date" type="text" placeholder="Contoh: 30 November 2026" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. KHUSUS SURAT PENGANTAR MAGANG MBKM -->
                        <div v-else-if="selectedType?.code === 'pengantar_magang'" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Tujuan Penerima Surat (Kepada Yth.) <span class="text-rose-500">*</span></label>
                                    <input v-model="form.extra_data.target_recipient" type="text" placeholder="Contoh: Pimpinan Instansi / Kepala Bagian HRD" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400" />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Instansi / Perusahaan Tujuan <span class="text-rose-500">*</span></label>
                                    <input v-model="form.extra_data.target_agency" type="text" placeholder="Masukkan nama instansi / perusahaan" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400" />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Alamat / Kota Instansi Tujuan</label>
                                    <input v-model="form.extra_data.target_city" type="text" placeholder="Contoh: Subang / Tempat" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400" />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Durasi Pelaksanaan Magang <span class="text-rose-500">*</span></label>
                                    <input v-model="form.extra_data.internship_duration" type="text" placeholder="Contoh: 5 bulan / 1 semester" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400" />
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Tanggal Mulai Pelaksanaan <span class="text-rose-500">*</span></label>
                                        <input v-model="form.extra_data.start_date" type="text" placeholder="Contoh: 01 September 2026" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Tanggal Selesai Pelaksanaan <span class="text-rose-500">*</span></label>
                                        <input v-model="form.extra_data.end_date" type="text" placeholder="Contoh: 31 Januari 2027" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400" />
                                    </div>
                                </div>
                            </div>

                            <!-- Lampiran Anggota Tim MBKM / PKL (Mobile-First Card Accordion) -->
                            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 space-y-3">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                    <div>
                                        <h4 class="text-xs font-bold text-slate-900 dark:text-white">Daftar Rekan / Anggota Kelompok (Lampiran Halaman 2)</h4>
                                        <p class="text-[11px] text-slate-500">Pemohon utama otomatis tercetak sebagai No. 1. Tambahkan rekan jika magang kelompok.</p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="addGroupMember"
                                        class="self-start sm:self-auto inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-950/60 border border-blue-200 dark:border-blue-800 hover:bg-blue-100 dark:hover:bg-blue-900/60 transition shadow-2xs"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        <span>Tambah Anggota</span>
                                    </button>
                                </div>

                                <div v-if="form.extra_data.group_members?.length > 0" class="space-y-3">
                                    <div
                                        v-for="(m, idx) in form.extra_data.group_members"
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
                                                title="Hapus anggota ini"
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
                                                    v-model="m.name"
                                                    type="text"
                                                    placeholder="Masukkan nama lengkap rekan"
                                                    class="w-full min-h-[42px] px-3.5 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400 uppercase focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                                                />
                                            </div>
                                            <div class="sm:col-span-5">
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    Nomor Pokok Mahasiswa (NPM) <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="m.npm"
                                                    type="text"
                                                    placeholder="Contoh: D1A220002"
                                                    class="w-full min-h-[42px] px-3.5 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400 uppercase font-mono focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. KHUSUS SURAT OBSERVASI TUGAS MATA KULIAH -->
                        <div v-else-if="selectedType?.code === 'observasi_matkul'" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Mata Kuliah <span class="text-rose-500">*</span></label>
                                    <input v-model="form.extra_data.course_name" type="text" placeholder="Masukkan nama mata kuliah" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400" />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Dosen Pengampu Mata Kuliah <span class="text-rose-500">*</span></label>
                                    <input v-model="form.extra_data.lecturer_name" type="text" placeholder="Masukkan nama dosen pengampu & gelar" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400" />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Tujuan Penerima Surat (Kepada Yth.) <span class="text-rose-500">*</span></label>
                                    <input v-model="form.extra_data.target_recipient" type="text" placeholder="Contoh: Kepala Dinas / Pimpinan Instansi" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400" />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Instansi / Perusahaan Objek Observasi <span class="text-rose-500">*</span></label>
                                    <input v-model="form.extra_data.target_agency" type="text" placeholder="Masukkan nama instansi / perusahaan lokasi observasi" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400" />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Alamat / Kota Instansi Tujuan</label>
                                    <input v-model="form.extra_data.target_city" type="text" placeholder="Contoh: Subang / Tempat" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400" />
                                </div>
                            </div>

                            <!-- Lampiran Anggota Observasi Kelompok -->
                            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 space-y-3">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                    <div>
                                        <h4 class="text-xs font-bold text-slate-900 dark:text-white">Daftar Anggota Kelompok Observasi (Tabel Lampiran)</h4>
                                        <p class="text-[11px] text-slate-500">Pemohon utama otomatis menjadi No. 1. Tambahkan rekan mahasiswa lainnya jika observasi berkelompok.</p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="addGroupMember"
                                        class="self-start sm:self-auto inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-950/60 border border-blue-200 dark:border-blue-800 hover:bg-blue-100 dark:hover:bg-blue-900/60 transition shadow-2xs"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        <span>Tambah Anggota</span>
                                    </button>
                                </div>

                                <div v-if="form.extra_data.group_members?.length > 0" class="space-y-3">
                                    <div
                                        v-for="(m, idx) in form.extra_data.group_members"
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
                                                title="Hapus anggota ini"
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
                                                    v-model="m.name"
                                                    type="text"
                                                    placeholder="Masukkan nama lengkap rekan"
                                                    class="w-full min-h-[42px] px-3.5 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400 uppercase focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                                                />
                                            </div>
                                            <div class="sm:col-span-3">
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    Nomor Pokok Mahasiswa (NPM) <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="m.npm"
                                                    type="text"
                                                    placeholder="Contoh: D1A220002"
                                                    class="w-full min-h-[42px] px-3.5 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400 uppercase font-mono focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                                                />
                                            </div>
                                            <div class="sm:col-span-4">
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    Alamat Lengkap (Domisili)
                                                </label>
                                                <input
                                                    v-model="m.address"
                                                    type="text"
                                                    placeholder="Masukkan kota domisili"
                                                    class="w-full min-h-[42px] px-3.5 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400 uppercase focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 4. KHUSUS SURAT PENELITIAN SKRIPSI -->
                        <div v-else-if="selectedType?.code === 'izin_penelitian_skripsi'" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Judul Penelitian / Skripsi <span class="text-rose-500">*</span></label>
                                    <textarea v-model="form.extra_data.thesis_title" rows="2" placeholder="Masukkan judul penelitian / skripsi lengkap" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400"></textarea>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Tujuan Penerima Surat (Kepada Yth.) <span class="text-rose-500">*</span></label>
                                    <input v-model="form.extra_data.target_recipient" type="text" placeholder="Contoh: Kepala Bagian / Pimpinan Instansi" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400" />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Instansi / Objek Lokasi Penelitian <span class="text-rose-500">*</span></label>
                                    <input v-model="form.extra_data.target_agency" type="text" placeholder="Masukkan nama instansi / perusahaan lokasi penelitian" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400" />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Alamat / Kota Instansi Tujuan</label>
                                    <input v-model="form.extra_data.target_city" type="text" placeholder="Contoh: Subang / Tempat" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400" />
                                </div>
                            </div>
                        </div>

                        <!-- 5. KHUSUS SURAT IZIN MENGIKUTI UJIAN (UTS / UAS) -->
                        <div v-else-if="selectedType?.code === 'izin_mengikuti_ujian'" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Tujuan Penerima Surat (Kepada Yth.) <span class="text-rose-500">*</span></label>
                                    <input v-model="form.extra_data.target_recipient" type="text" placeholder="Contoh: Pimpinan Perusahaan / HRD" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400" />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Jenis Ujian <span class="text-rose-500">*</span></label>
                                    <select v-model="form.extra_data.exam_type" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white">
                                        <option value="UAS">Ujian Akhir Semester (UAS)</option>
                                        <option value="UTS">Ujian Tengah Semester (UTS)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Semester Perkuliahan</label>
                                    <select v-model="form.extra_data.semester_type" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white">
                                        <option value="Ganjil">Semester Ganjil</option>
                                        <option value="Genap">Semester Genap</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Rentang Tanggal Ujian</label>
                                    <input v-model="form.extra_data.exam_dates" type="text" placeholder="Contoh: 12 - 19 Januari 2026" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400" />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Alamat / Kota Instansi Tujuan</label>
                                    <input v-model="form.extra_data.target_city" type="text" placeholder="Contoh: Subang / Tempat" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400" />
                                </div>
                            </div>

                            <!-- Lampiran Jadwal Ujian (Mobile-First Card Accordion) -->
                            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 space-y-3">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                    <div>
                                        <h4 class="text-xs font-bold text-slate-900 dark:text-white">Daftar Jadwal Ujian (Lampiran Halaman 2)</h4>
                                        <p class="text-[11px] text-slate-500">Isikan mata kuliah ujian yang diajukan izinnya kepada instansi/perusahaan tempat bekerja.</p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="addExamSchedule"
                                        class="self-start sm:self-auto inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-950/60 border border-blue-200 dark:border-blue-800 hover:bg-blue-100 dark:hover:bg-blue-900/60 transition shadow-2xs"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        <span>Tambah Jadwal MK</span>
                                    </button>
                                </div>

                                <div class="space-y-3">
                                    <div
                                        v-for="(sch, idx) in form.extra_data.exam_schedules"
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

                                        <!-- Row 1: Course Name & Class -->
                                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 text-xs">
                                            <div class="sm:col-span-8">
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    Nama Mata Kuliah <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="sch.course_name"
                                                    type="text"
                                                    placeholder="Masukkan nama mata kuliah ujian"
                                                    class="w-full min-h-[42px] px-3.5 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                                                />
                                            </div>
                                            <div class="sm:col-span-4">
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    Kelas
                                                </label>
                                                <input
                                                    v-model="sch.class_name"
                                                    type="text"
                                                    placeholder="Contoh: 4A / TI-1"
                                                    class="w-full min-h-[42px] px-3.5 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                                                />
                                            </div>
                                        </div>

                                        <!-- Row 2: Tanggal, Waktu, Ruang -->
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                                            <div>
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    Hari / Tanggal Ujian
                                                </label>
                                                <input
                                                    v-model="sch.date"
                                                    type="text"
                                                    placeholder="Contoh: Senin, 12 Januari 2026"
                                                    class="w-full min-h-[42px] px-3.5 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    Waktu / Jam Ujian
                                                </label>
                                                <input
                                                    v-model="sch.time"
                                                    type="text"
                                                    placeholder="Contoh: 08.00 - 10.00 WIB"
                                                    class="w-full min-h-[42px] px-3.5 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    Ruang Ujian
                                                </label>
                                                <input
                                                    v-model="sch.room"
                                                    type="text"
                                                    placeholder="Contoh: Ruang 8 / Lab Komputer"
                                                    class="w-full min-h-[42px] px-3.5 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 6. KHUSUS SURAT UJIAN SUSULAN -->
                        <div v-else-if="selectedType?.code === 'ujian_susulan'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Dosen Pengampu Mata Kuliah <span class="text-rose-500">*</span></label>
                                <input v-model="form.extra_data.lecturer_name" type="text" placeholder="Masukkan nama dosen pengampu & gelar" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Mata Kuliah <span class="text-rose-500">*</span></label>
                                <input v-model="form.extra_data.course_name" type="text" placeholder="Masukkan nama mata kuliah ujian" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Jenis Ujian <span class="text-rose-500">*</span></label>
                                <select v-model="form.extra_data.exam_type" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white">
                                    <option value="UTS">Ujian Tengah Semester (UTS)</option>
                                    <option value="UAS">Ujian Akhir Semester (UAS)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nilai Asal (Sebelum Perbaikan)</label>
                                <input v-model="form.extra_data.original_score" type="text" placeholder="Contoh: T / E / D" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400" />
                            </div>
                        </div>

                        <!-- 7. KHUSUS SURAT PERBAIKAN NILAI -->
                        <div v-else-if="selectedType?.code === 'perbaikan_nilai'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Dosen Pengampu Mata Kuliah <span class="text-rose-500">*</span></label>
                                <input v-model="form.extra_data.lecturer_name" type="text" placeholder="Masukkan nama dosen pengampu & gelar" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Mata Kuliah <span class="text-rose-500">*</span></label>
                                <input v-model="form.extra_data.course_name" type="text" placeholder="Masukkan nama mata kuliah" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400" />
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nilai Asal (Sebelum Perbaikan) <span class="text-rose-500">*</span></label>
                                <input v-model="form.extra_data.original_score" type="text" placeholder="Contoh: T atau E / D" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400" />
                            </div>
                        </div>

                        <!-- 8. KHUSUS SURAT KETERANGAN YUDISIUM -->
                        <div v-else-if="selectedType?.code === 'keterangan_yudisium'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Hari & Tanggal Sidang Skripsi <span class="text-rose-500">*</span></label>
                                <input v-model="form.extra_data.defense_date_text" type="text" placeholder="Contoh: Kamis, 27 Agustus 2026" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">IPK Kelulusan <span class="text-rose-500">*</span></label>
                                <input v-model="form.extra_data.ipk" type="text" placeholder="Contoh: 3.75" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Masa Studi <span class="text-rose-500">*</span></label>
                                <input v-model="form.extra_data.study_duration" type="text" placeholder="Contoh: 4 (Empat) Tahun" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nilai Sidang Tugas Akhir <span class="text-rose-500">*</span></label>
                                <input v-model="form.extra_data.defense_grade" type="text" placeholder="Contoh: A" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Hasil Sidang Tugas Akhir <span class="text-rose-500">*</span></label>
                                <select v-model="form.extra_data.defense_result" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white">
                                    <option value="Lulus">Lulus</option>
                                    <option value="Gagal">Gagal</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Hari & Tanggal Yudisium <span class="text-rose-500">*</span></label>
                                <input v-model="form.extra_data.yudisium_date_text" type="text" placeholder="Contoh: Kamis, 27 Agustus 2026" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400" />
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Predikat Kelulusan Yudisium <span class="text-rose-500">*</span></label>
                                <select v-model="form.extra_data.yudisium_predicate" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white">
                                    <option value="Dengan Pujian">Dengan Pujian</option>
                                    <option value="Sangat Memuaskan">Sangat Memuaskan</option>
                                    <option value="Memuaskan">Memuaskan</option>
                                </select>
                            </div>
                        </div>

                        <!-- 9. KHUSUS SURAT KETERANGAN KULIAH PNS -->
                        <div v-else-if="selectedType?.code === 'keterangan_kuliah_pns'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Orang Tua / Wali (PNS) <span class="text-rose-500">*</span></label>
                                <input v-model="form.extra_data.parent_name" type="text" placeholder="Masukkan nama lengkap orang tua / wali" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white uppercase placeholder-slate-400" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">NIP / NRP Orang Tua <span class="text-rose-500">*</span></label>
                                <input v-model="form.extra_data.parent_nip" type="text" placeholder="Contoh: 197501012000031001" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white font-mono placeholder-slate-400" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Pangkat / Golongan Ruang</label>
                                <input v-model="form.extra_data.parent_rank" type="text" placeholder="Contoh: Pembina Tk. I, IV/b" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Instansi / Unit Kerja Orang Tua</label>
                                <input v-model="form.extra_data.parent_institution" type="text" placeholder="Contoh: Dinas Pendidikan Kabupaten Subang" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400" />
                            </div>
                        </div>

                        <!-- 10. KHUSUS SURAT REKOMENDASI BEASISWA -->
                        <div v-else-if="selectedType?.code === 'rekomendasi_beasiswa'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Program Beasiswa <span class="text-rose-500">*</span></label>
                                <input v-model="form.extra_data.scholarship_name" type="text" placeholder="Contoh: Beasiswa Pendidikan Pemimpin Indonesia" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Penyelenggara / Lembaga Beasiswa <span class="text-rose-500">*</span></label>
                                <input v-model="form.extra_data.scholarship_provider" type="text" placeholder="Contoh: LEADERS.ID" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400" />
                            </div>
                        </div>

                        <!-- 11. KHUSUS SURAT REKOMENDASI PEMBELAJARAN LUAR PRODI -->
                        <div v-else-if="selectedType?.code === 'rekomendasi_luar_prodi'" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Program Kegiatan / Pelatihan <span class="text-rose-500">*</span></label>
                                    <input v-model="form.extra_data.program_name" type="text" placeholder="Contoh: Program Coding Camp 2025 powered by DBS Foundation" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400" />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Periode Pelaksanaan Kegiatan <span class="text-rose-500">*</span></label>
                                    <input v-model="form.extra_data.activity_period" type="text" placeholder="Contoh: periode Februari - Juli 2025" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400" />
                                </div>
                            </div>
                            <div class="p-3 bg-violet-50/60 dark:bg-violet-950/30 rounded-xl border border-violet-200/60 dark:border-violet-800/40 text-[11px] text-violet-800 dark:text-violet-300">
                                ℹ️ Surat rekomendasi ini memuat 3 komitmen resmi pimpinan kampus: dukungan penuh pelaksanaan kegiatan, pengakuan konversi SKS, dan koordinasi kendala akademik.
                            </div>
                        </div>

                        <!-- 10. KHUSUS SURAT SEMINAR UP & SIDANG TA -->
                        <div v-else-if="selectedType?.code === 'izin_seminar_up' || selectedType?.code === 'izin_sidang_ta'" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Agenda / Kegiatan</label>
                                    <input v-model="form.extra_data.activity_name" type="text" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white" />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Hari Pelaksanaan</label>
                                    <input v-model="form.extra_data.event_day" type="text" placeholder="Contoh: Kamis" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400" />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Judul Penelitian / Skripsi <span class="text-rose-500">*</span></label>
                                    <textarea v-model="form.extra_data.thesis_title" rows="2" placeholder="Masukkan judul penelitian / skripsi lengkap" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400"></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Tujuan Penerima Surat (Kepada Yth.)</label>
                                    <input v-model="form.extra_data.target_recipient" type="text" placeholder="Contoh: Pimpinan Perusahaan / HRD" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400" />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Alamat / Kota Instansi Tujuan</label>
                                    <input v-model="form.extra_data.target_city" type="text" placeholder="Contoh: Subang / Tempat" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400" />
                                </div>
                            </div>
                        </div>

                        <!-- 11. KHUSUS SURAT KETERANGAN KULIAH UMUM / LAINNYA -->
                        <div v-else class="p-4 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs text-slate-600 dark:text-slate-400">
                            Tidak memerlukan persyaratan khusus tambahan. Surat akan diterbitkan berdasarkan data pemohon yang tertera di Bagian A.
                        </div>
                    </div>

                    <!-- Step 2 Bottom Actions (Desktop / Tablet) -->
                    <div class="flex items-center justify-between gap-3 pt-2">
                        <button
                            type="button"
                            @click="goToStep(1)"
                            class="px-5 py-2.5 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition"
                        >
                            ← Kembali ke Pilih Surat
                        </button>
                        <button
                            type="button"
                            @click="goToStep(3)"
                            class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-900/20 transition hover:scale-[1.02] active:scale-[0.98]"
                        >
                            <span>Lanjut ke Konfirmasi</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </button>
                    </div>

                    <!-- Sticky Mobile Action Bar for Step 2 -->
                    <div class="sm:hidden fixed bottom-0 left-0 right-0 p-3 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 z-30 shadow-lg flex items-center justify-between gap-3">
                        <button
                            type="button"
                            @click="goToStep(1)"
                            class="px-3.5 py-2 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-200 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 active:scale-95 transition"
                        >
                            ← Kembali
                        </button>
                        <span class="text-[11px] font-semibold text-slate-500">
                            Langkah 2 / 3
                        </span>
                        <button
                            type="button"
                            @click="goToStep(3)"
                            class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-sm active:scale-95 transition"
                        >
                            Lanjut →
                        </button>
                    </div>
                </div>

                <!-- ========================================================================= -->
                <!-- LANGKAH 3: KONFIRMASI & TINJAUAN AKHIR (DATA AKURAT & SESUAI JENIS SURAT) -->
                <!-- ========================================================================= -->
                <div v-else-if="currentStep === 3" class="space-y-6">
                    <!-- Step 3 Card: Ringkasan Pengajuan (Full Width) -->
                    <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-6">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-4">
                            <div>
                                <span class="text-[10px] uppercase font-bold tracking-wider text-blue-600 dark:text-blue-400">Langkah 3 dari 3</span>
                                <h2 class="text-base sm:text-lg font-black text-slate-900 dark:text-white">
                                    Tinjau & Konfirmasi Permohonan Surat
                                </h2>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Pastikan seluruh data berikut sudah benar sebelum dikirimkan ke pihak Tata Usaha & Pejabat Fakultas.
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="goToStep(2)"
                                class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline px-3.5 py-1.5 rounded-lg bg-blue-50 dark:bg-blue-950/60 border border-blue-200 dark:border-blue-800 self-start sm:self-auto"
                            >
                                ✏️ Ubah Data
                            </button>
                        </div>

                        <!-- Grid Summary -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <!-- Block 1: Format Dokumen -->
                            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 space-y-3">
                                <div class="text-[11px] font-bold text-blue-800 dark:text-blue-300 uppercase tracking-wide">
                                    1. Format Dokumen
                                </div>
                                <div class="space-y-2 text-xs">
                                    <div>
                                        <span class="text-slate-500 block text-[11px]">Jenis Surat:</span>
                                        <span class="font-bold text-slate-900 dark:text-white">{{ selectedType?.name }}</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-500 block text-[11px]">Nomor Dokumen:</span>
                                        <span class="font-mono text-slate-700 dark:text-slate-300">/{{ selectedType?.letter_code }}/...</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-500 block text-[11px]">Penandatangan TTE Resmi:</span>
                                        <span class="font-bold text-slate-900 dark:text-white">
                                            {{ selectedType?.code === 'keterangan_kuliah_pns' ? 'Dekan Fakultas Ilmu Komputer' : 'Pejabat Penandatangan Fasilkom' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Block 2: Identitas Mahasiswa -->
                            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 space-y-3">
                                <div class="text-[11px] font-bold text-blue-800 dark:text-blue-300 uppercase tracking-wide">
                                    2. Identitas Mahasiswa
                                </div>
                                <div class="space-y-2 text-xs">
                                    <div class="flex justify-between">
                                        <span class="text-slate-500">Nama Lengkap Mahasiswa:</span>
                                        <span class="font-bold text-slate-900 dark:text-white">{{ form.student_name }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-500">Nomor Pokok Mahasiswa (NPM):</span>
                                        <span class="font-mono text-slate-900 dark:text-white">{{ form.npm }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-500">Program Studi:</span>
                                        <span class="text-slate-900 dark:text-white">{{ form.study_program }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-500">Semester Aktif / TA:</span>
                                        <span class="text-slate-900 dark:text-white">Semester {{ form.semester }} ({{ form.academic_year }})</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-500">Alamat Lengkap (Domisili):</span>
                                        <span class="text-slate-900 dark:text-white truncate max-w-[200px]">{{ form.address || '-' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Block 3: Rincian Khusus Dokumen (AKURAT SESUAI JENIS SURAT TERPILIH) -->
                            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 space-y-3 md:col-span-2 lg:col-span-1">
                                <div class="text-[11px] font-bold text-blue-800 dark:text-blue-300 uppercase tracking-wide">
                                    3. Rincian Khusus Surat
                                </div>

                                <!-- PKL Rincian -->
                                <div v-if="selectedType?.code === 'pengantar_pkl'" class="space-y-2 text-xs">
                                    <div>
                                        <span class="text-slate-500 block text-[11px]">Tujuan Penerima Surat (Kepada Yth.):</span>
                                        <strong class="text-slate-900 dark:text-white">{{ form.extra_data.target_recipient }}</strong>
                                    </div>
                                    <div>
                                        <span class="text-slate-500 block text-[11px]">Nama Instansi / Perusahaan Tempat PKL:</span>
                                        <strong class="text-slate-900 dark:text-white">{{ form.extra_data.target_agency }}</strong>
                                    </div>
                                    <div>
                                        <span class="text-slate-500 block text-[11px]">Durasi & Periode Pelaksanaan PKL:</span>
                                        <strong class="text-slate-900 dark:text-white">{{ form.extra_data.internship_duration }} ({{ form.extra_data.start_date }} s/d {{ form.extra_data.end_date }})</strong>
                                    </div>
                                    <div>
                                        <span class="text-slate-500 block text-[11px]">Alamat / Kota Instansi Tujuan:</span>
                                        <strong class="text-slate-900 dark:text-white">{{ form.extra_data.target_city || 'Tempat' }}</strong>
                                    </div>
                                </div>

                                <!-- Magang MBKM Rincian -->
                                <div v-else-if="selectedType?.code === 'pengantar_magang'" class="space-y-2 text-xs">
                                    <div>
                                        <span class="text-slate-500 block text-[11px]">Tujuan Penerima Surat (Kepada Yth.):</span>
                                        <strong class="text-slate-900 dark:text-white">{{ form.extra_data.target_recipient }}</strong>
                                    </div>
                                    <div>
                                        <span class="text-slate-500 block text-[11px]">Nama Instansi / Perusahaan Tujuan:</span>
                                        <strong class="text-slate-900 dark:text-white">{{ form.extra_data.target_agency }}</strong>
                                    </div>
                                    <div>
                                        <span class="text-slate-500 block text-[11px]">Durasi & Periode Pelaksanaan Magang:</span>
                                        <strong class="text-slate-900 dark:text-white">{{ form.extra_data.internship_duration }} ({{ form.extra_data.start_date }} s/d {{ form.extra_data.end_date }})</strong>
                                    </div>
                                    <div>
                                        <span class="text-slate-500 block text-[11px]">Anggota Kelompok Mahasiswa:</span>
                                        <strong class="text-slate-900 dark:text-white">{{ form.extra_data.group_members?.length ? form.extra_data.group_members.length + ' orang' : 'Hanya Pemohon (Individu)' }}</strong>
                                    </div>
                                </div>

                                <!-- Observasi Matkul Rincian -->
                                <div v-else-if="selectedType?.code === 'observasi_matkul'" class="space-y-2 text-xs">
                                    <div>
                                        <span class="text-slate-500 block text-[11px]">Nama Mata Kuliah:</span>
                                        <strong class="text-slate-900 dark:text-white">{{ form.extra_data.course_name }}</strong>
                                    </div>
                                    <div>
                                        <span class="text-slate-500 block text-[11px]">Dosen Pengampu Mata Kuliah:</span>
                                        <strong class="text-slate-900 dark:text-white">{{ form.extra_data.lecturer_name }}</strong>
                                    </div>
                                    <div>
                                        <span class="text-slate-500 block text-[11px]">Instansi / Perusahaan Objek Observasi:</span>
                                        <strong class="text-slate-900 dark:text-white">{{ form.extra_data.target_agency }}</strong>
                                    </div>
                                    <div>
                                        <span class="text-slate-500 block text-[11px]">Tujuan Penerima Surat (Kepada Yth.):</span>
                                        <strong class="text-slate-900 dark:text-white">{{ form.extra_data.target_recipient }}</strong>
                                    </div>
                                </div>

                                <!-- Penelitian Skripsi Rincian -->
                                <div v-else-if="selectedType?.code === 'izin_penelitian_skripsi'" class="space-y-2 text-xs">
                                    <div>
                                        <span class="text-slate-500 block text-[11px]">Judul Penelitian / Skripsi:</span>
                                        <strong class="text-slate-900 dark:text-white">{{ form.extra_data.thesis_title }}</strong>
                                    </div>
                                    <div>
                                        <span class="text-slate-500 block text-[11px]">Instansi / Objek Lokasi Penelitian:</span>
                                        <strong class="text-slate-900 dark:text-white">{{ form.extra_data.target_agency }}</strong>
                                    </div>
                                    <div>
                                        <span class="text-slate-500 block text-[11px]">Tujuan Penerima Surat (Kepada Yth.):</span>
                                        <strong class="text-slate-900 dark:text-white">{{ form.extra_data.target_recipient }}</strong>
                                    </div>
                                </div>

                                <!-- Izin Mengikuti Ujian Rincian -->
                                <div v-else-if="selectedType?.code === 'izin_mengikuti_ujian'" class="space-y-2 text-xs">
                                    <div>
                                        <span class="text-slate-500 block text-[11px]">Tujuan Penerima Surat (Kepada Yth.):</span>
                                        <strong class="text-slate-900 dark:text-white">{{ form.extra_data.target_recipient }}</strong>
                                    </div>
                                    <div>
                                        <span class="text-slate-500 block text-[11px]">Jenis Ujian & Semester Perkuliahan:</span>
                                        <strong class="text-slate-900 dark:text-white">{{ form.extra_data.exam_type }} (Semester {{ form.extra_data.semester_type || 'Ganjil' }})</strong>
                                    </div>
                                    <div>
                                        <span class="text-slate-500 block text-[11px]">Rentang Tanggal Ujian:</span>
                                        <strong class="text-slate-900 dark:text-white">{{ form.extra_data.exam_dates }}</strong>
                                    </div>
                                    <div>
                                        <span class="text-slate-500 block text-[11px]">Jadwal Mata Kuliah Ujian:</span>
                                        <strong class="text-slate-900 dark:text-white">{{ form.extra_data.exam_schedules?.length || 0 }} Mata Kuliah</strong>
                                    </div>
                                </div>

                                <!-- Ujian Susulan Rincian -->
                                <div v-else-if="selectedType?.code === 'ujian_susulan'" class="space-y-2 text-xs">
                                    <div>
                                        <span class="text-slate-500 block text-[11px]">Dosen Pengampu Mata Kuliah:</span>
                                        <strong class="text-slate-900 dark:text-white">{{ form.extra_data.lecturer_name }}</strong>
                                    </div>
                                    <div>
                                        <span class="text-slate-500 block text-[11px]">Nama Mata Kuliah:</span>
                                        <strong class="text-slate-900 dark:text-white">{{ form.extra_data.course_name }}</strong>
                                    </div>
                                    <div>
                                        <span class="text-slate-500 block text-[11px]">Jenis Ujian:</span>
                                        <strong class="text-slate-900 dark:text-white">{{ form.extra_data.exam_type }} Susulan</strong>
                                    </div>
                                    <div>
                                        <span class="text-slate-500 block text-[11px]">Nilai Asal (Sebelum Perbaikan):</span>
                                        <strong class="text-slate-900 dark:text-white">{{ form.extra_data.original_score || '-' }}</strong>
                                    </div>
                                </div>

                                <!-- Perbaikan Nilai Rincian -->
                                <div v-else-if="selectedType?.code === 'perbaikan_nilai'" class="space-y-2 text-xs">
                                    <div>
                                        <span class="text-slate-500 block text-[11px]">Dosen Pengampu Mata Kuliah:</span>
                                        <strong class="text-slate-900 dark:text-white">{{ form.extra_data.lecturer_name }}</strong>
                                    </div>
                                    <div>
                                        <span class="text-slate-500 block text-[11px]">Nama Mata Kuliah:</span>
                                        <strong class="text-slate-900 dark:text-white">{{ form.extra_data.course_name }}</strong>
                                    </div>
                                    <div>
                                        <span class="text-slate-500 block text-[11px]">Nilai Asal (Sebelum Perbaikan):</span>
                                        <strong class="text-slate-900 dark:text-white font-mono text-sm text-blue-700 dark:text-blue-400">{{ form.extra_data.original_score }}</strong>
                                    </div>
                                </div>

                                <!-- Yudisium Rincian -->
                                <div v-else-if="selectedType?.code === 'keterangan_yudisium'" class="space-y-2 text-xs">
                                    <div>
                                        <span class="text-slate-500 block text-[11px]">Hari & Tanggal Sidang Skripsi:</span>
                                        <strong class="text-slate-900 dark:text-white">{{ form.extra_data.defense_date_text }}</strong>
                                    </div>
                                    <div>
                                        <span class="text-slate-500 block text-[11px]">IPK Kelulusan & Masa Studi:</span>
                                        <strong class="text-slate-900 dark:text-white">{{ form.extra_data.ipk }} ({{ form.extra_data.study_duration }})</strong>
                                    </div>
                                    <div>
                                        <span class="text-slate-500 block text-[11px]">Nilai & Hasil Sidang Tugas Akhir:</span>
                                        <strong class="text-slate-900 dark:text-white">{{ form.extra_data.defense_grade }} ({{ form.extra_data.defense_result }})</strong>
                                    </div>
                                    <div>
                                        <span class="text-slate-500 block text-[11px]">Hari & Tanggal Yudisium & Predikat:</span>
                                        <strong class="text-slate-900 dark:text-white">{{ form.extra_data.yudisium_predicate }}</strong>
                                    </div>
                                </div>

                                <!-- PNS Rincian -->
                                <div v-else-if="selectedType?.code === 'keterangan_kuliah_pns'" class="space-y-2 text-xs">
                                    <div>
                                        <span class="text-slate-500 block text-[11px]">Nama Orang Tua / Wali (PNS):</span>
                                        <strong class="text-slate-900 dark:text-white">{{ form.extra_data.parent_name }}</strong>
                                    </div>
                                    <div>
                                        <span class="text-slate-500 block text-[11px]">NIP / NRP Orang Tua:</span>
                                        <strong class="text-slate-900 dark:text-white font-mono">{{ form.extra_data.parent_nip }}</strong>
                                    </div>
                                    <div>
                                        <span class="text-slate-500 block text-[11px]">Pangkat / Instansi Orang Tua:</span>
                                        <strong class="text-slate-900 dark:text-white">{{ form.extra_data.parent_rank }} ({{ form.extra_data.parent_institution }})</strong>
                                    </div>
                                </div>

                                <!-- Rekomendasi Beasiswa Rincian -->
                                <div v-else-if="selectedType?.code === 'rekomendasi_beasiswa'" class="space-y-2 text-xs">
                                    <div>
                                        <span class="text-slate-500 block text-[11px]">Nama Program Beasiswa:</span>
                                        <strong class="text-slate-900 dark:text-white">{{ form.extra_data.scholarship_name }}</strong>
                                    </div>
                                    <div>
                                        <span class="text-slate-500 block text-[11px]">Penyelenggara / Lembaga Beasiswa:</span>
                                        <strong class="text-slate-900 dark:text-white">{{ form.extra_data.scholarship_provider }}</strong>
                                    </div>
                                </div>

                                <!-- Rekomendasi Luar Prodi Rincian -->
                                <div v-else-if="selectedType?.code === 'rekomendasi_luar_prodi'" class="space-y-2 text-xs">
                                    <div>
                                        <span class="text-slate-500 block text-[11px]">Nama Program Kegiatan:</span>
                                        <strong class="text-slate-900 dark:text-white">{{ form.extra_data.program_name }}</strong>
                                    </div>
                                    <div>
                                        <span class="text-slate-500 block text-[11px]">Periode Pelaksanaan:</span>
                                        <strong class="text-slate-900 dark:text-white">{{ form.extra_data.activity_period }}</strong>
                                    </div>
                                </div>

                                <!-- Seminar UP / Sidang TA Rincian -->
                                <div v-else-if="selectedType?.code === 'izin_seminar_up' || selectedType?.code === 'izin_sidang_ta'" class="space-y-2 text-xs">
                                    <div>
                                        <span class="text-slate-500 block text-[11px]">Nama Agenda / Kegiatan:</span>
                                        <strong class="text-slate-900 dark:text-white">{{ form.extra_data.activity_name }} ({{ form.extra_data.event_day }})</strong>
                                    </div>
                                    <div>
                                        <span class="text-slate-500 block text-[11px]">Judul Penelitian / Skripsi:</span>
                                        <strong class="text-slate-900 dark:text-white">{{ form.extra_data.thesis_title }}</strong>
                                    </div>
                                </div>

                                <!-- Keterangan Umum / Lainnya -->
                                <div v-else class="space-y-2 text-xs">
                                    <div>
                                        <span class="text-slate-500 block text-[11px]">Keperluan Pembuatan Surat:</span>
                                        <strong class="text-slate-900 dark:text-white">{{ form.purpose || 'Administrasi Akademik' }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Legal Declaration Checkbox -->
                        <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 flex items-start gap-3">
                            <input
                                id="declarationCheck"
                                v-model="declarationAgreed"
                                type="checkbox"
                                class="w-4 h-4 mt-0.5 rounded border-slate-300 dark:border-slate-700 text-blue-600 focus:ring-blue-600"
                            />
                            <label for="declarationCheck" class="text-xs text-slate-700 dark:text-slate-300 cursor-pointer select-none">
                                <strong>Pernyataan Kebenaran Data:</strong> Saya menyatakan dengan sungguh-sungguh bahwa seluruh data permohonan surat di atas adalah benar, lengkap, dan sesuai dengan berkas akademik saya di Fakultas Ilmu Komputer Universitas Subang.
                            </label>
                        </div>
                    </div>

                    <!-- Step 3 Bottom Actions (Desktop / Tablet) -->
                    <div class="flex items-center justify-between gap-3 pt-2">
                        <button
                            type="button"
                            @click="goToStep(2)"
                            class="px-5 py-2.5 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition"
                        >
                            ← Ubah Data Surat
                        </button>
                        <button
                            type="button"
                            @click="submit"
                            :disabled="!declarationAgreed || form.processing"
                            class="inline-flex items-center gap-2 px-8 py-3 rounded-xl text-xs sm:text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-lg shadow-blue-900/25 transition hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg v-if="form.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span>{{ form.processing ? 'Mengirim Pengajuan...' : 'Kirim Permohonan Surat Sekarang' }}</span>
                        </button>
                    </div>

                    <!-- Sticky Mobile Action Bar for Step 3 -->
                    <div class="sm:hidden fixed bottom-0 left-0 right-0 p-3 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 z-30 shadow-lg flex items-center justify-between gap-3">
                        <button
                            type="button"
                            @click="goToStep(2)"
                            class="px-3.5 py-2 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-200 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 active:scale-95 transition"
                        >
                            ← Ubah Data
                        </button>
                        <button
                            type="button"
                            @click="submit"
                            :disabled="!declarationAgreed || form.processing"
                            class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-sm active:scale-95 transition disabled:opacity-50"
                        >
                            {{ form.processing ? 'Mengirim...' : 'Kirim Sekarang →' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
