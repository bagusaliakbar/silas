<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import LetterPdfViewer from '@/Components/LetterPdfViewer.vue';

const props = defineProps({
    mouTypes: {
        type: Array,
        default: () => [],
    },
    signers: {
        type: Array,
        default: () => [],
    },
    hasActiveCertificate: {
        type: Boolean,
        default: false,
    },
    activeCertificateInfo: {
        type: Object,
        default: null,
    },
    user: {
        type: Object,
        required: true,
    },
});

// Layout View Mode: 'balanced' (6:6) | 'preview-wide' (4:8) | 'editor-wide' (8:4)
const layoutMode = ref('balanced');

const editorColClass = computed(() => {
    if (layoutMode.value === 'preview-wide') return 'lg:col-span-5 xl:col-span-4';
    if (layoutMode.value === 'editor-wide') return 'lg:col-span-7 xl:col-span-8';
    return 'lg:col-span-6 xl:col-span-6';
});

const previewColClass = computed(() => {
    if (layoutMode.value === 'preview-wide') return 'lg:col-span-7 xl:col-span-8';
    if (layoutMode.value === 'editor-wide') return 'lg:col-span-5 xl:col-span-4';
    return 'lg:col-span-6 xl:col-span-6';
});

// Fullscreen Modal for PDF Preview

// Active Tab in Editor: 'info' | 'parties' | 'scope' | 'duration' | 'contact'
const activeStep = ref('info');

const initialType = props.mouTypes[0] || {};

// Indonesian words helper
const numberToIndonesianWords = (num) => {
    num = parseInt(num, 10);
    if (isNaN(num)) return '';
    const words = [
        'nol', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan',
        'sepuluh', 'sebelas', 'dua belas', 'tiga belas', 'empat belas', 'lima belas',
        'enam belas', 'tujuh belas', 'delapan belas', 'sembilan belas', 'dua puluh'
    ];
    if (num <= 20) return words[num];
    if (num < 100) {
        const tens = Math.floor(num / 10);
        const rem = num % 10;
        const tensWords = ['', '', 'dua puluh', 'tiga puluh', 'empat puluh', 'lima puluh', 'enam puluh', 'tujuh puluh', 'delapan puluh', 'sembilan puluh'];
        return tensWords[tens] + (rem ? ' ' + words[rem] : '');
    }
    if (num < 200) {
        return 'seratus' + (num - 100 ? ' ' + numberToIndonesianWords(num - 100) : '');
    }
    if (num < 1000) {
        const hundreds = Math.floor(num / 100);
        const rem = num % 100;
        return words[hundreds] + ' ratus' + (rem ? ' ' + numberToIndonesianWords(rem) : '');
    }
    if (num < 2000) {
        return 'seribu' + (num - 1000 ? ' ' + numberToIndonesianWords(num - 1000) : '');
    }
    if (num < 10000) {
        const thousands = Math.floor(num / 1000);
        const rem = num % 1000;
        return numberToIndonesianWords(thousands) + ' ribu' + (rem ? ' ' + numberToIndonesianWords(rem) : '');
    }
    return String(num);
};

const daysMap = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
const monthsMap = [
    '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const applyDateWords = (dateStr) => {
    if (!dateStr) return;
    try {
        const parts = dateStr.split('-');
        if (parts.length === 3) {
            const year = parseInt(parts[0], 10);
            const month = parseInt(parts[1], 10);
            const day = parseInt(parts[2], 10);
            const d = new Date(year, month - 1, day);

            form.extra_data.signing_day_words = daysMap[d.getDay()] || '';
            form.extra_data.signing_date_words = numberToIndonesianWords(day);
            form.extra_data.signing_month_words = monthsMap[month] || '';
            form.extra_data.signing_year_words = numberToIndonesianWords(year);
            form.extra_data.signing_date_formatted = `${String(day).padStart(2, '0')}-${String(month).padStart(2, '0')}-${year}`;
        }
    } catch (e) {
        console.error('Failed to parse date', e);
    }
};

const defaultSigner = props.signers.find((s) => s.id === 'dekan') || props.signers[0] || {
    name: 'Dr. Tepi Peirisal, S.Sos, M.Si',
    signature_title: 'Dekan',
    id: 'dekan',
    nik: '0410420142',
};

const isManualFacultyNumber = ref(false);

const form = useForm({
    letter_type_id: initialType.id || 42,
    academic_year: '2025/2026',
    signer_id: defaultSigner.id || 'dekan',
    direct_sign: false,
    extra_data: {
        mou_title: 'PENYELENGGARAAN PROGRAM MAGANG BERSERTIFIKAT - MBKM',
        partner_mou_number: '012/HRD-MOU/XI/2026',
        faculty_mou_number: '',
        signing_city: 'Subang',
        signing_date: '2026-11-07',
        signing_day_words: 'Sabtu',
        signing_date_words: 'tujuh',
        signing_month_words: 'November',
        signing_year_words: 'dua ribu dua puluh enam',
        signing_date_formatted: '07-11-2026',
        
        // Pihak Pertama (Mitra)
        partner_name: 'PT SOLUSI TEKNOLOGI NUSANTARA',
        partner_business_field: 'Pengembangan Perangkat Lunak dan Layanan Teknologi Informasi',
        partner_address: 'Jl. Jenderal Sudirman Kav. 52-53, Jakarta Selatan',
        partner_official_name: 'Budi Santoso, S.T., M.M.',
        partner_official_position: 'Direktur Utama',
        partner_phone: '(021) 5219988',
        partner_email: 'kerjasama@solusiteknologi.co.id',

        // Pihak Kedua (Fasilkom UNSUB)
        faculty_official_name: defaultSigner.name || 'Dr. Tepi Peirisal, S.Sos, M.Si',
        faculty_official_position: 'Dekan Fakultas Ilmu Komputer Universitas Subang',
        faculty_nik: '0410420142',

        // Pasal 1: Definisi
        pasal_1_definitions: [
            'Merdeka Belajar Kampus Merdeka yang biasa disingkat MBKM adalah Program yang dicanangkan oleh Menteri Pendidikan dan Kebudayaan yang bertujuan mendorong mahasiswa untuk menguasai berbagai keilmuan untuk bekal memasuki dunia kerja.',
            'Pendidikan adalah usaha sadar dan terencana untuk mewujudkan suasana belajar dan proses pembelajaran agar peserta didik secara aktif mengembangkan potensi dirinya untuk memiliki kekuatan spiritual keagamaan, pengendalian diri, kepribadian, kecerdasan akhlak mulia serta keterampilan yang diperlukan dirinya, masyarakat, bangsa dan negara;',
            'Penelitian adalah kegiatan yang dilakukan menurut kaidah dan metode ilmiah secara sistematis untuk memperoleh informasi data dan keterangan yang berkaitan dengan pemahaman dan/atau pengujian suatu cabang pengetahuan dan teknologi',
            'Kompetensi adalah kemampuan kerja setiap individu yang mencakup aspek pengetahuan, keterampilan, dan sikap kerja yang sesuai dengan standardisasi yang diharapkan',
        ],

        // Pasal 2: Maksud & Tujuan
        pasal_2_intent: [
            'Maksud Perjanjian Kerja Sama ini adalah untuk mendukung langkah PARA PIHAK dalam melakukan kerja sama di bidang Pendidikan, Penelitian, Pengabdian Kepada Masyarakat dan bidang Lainnya dengan mendayagunakan potensi, keahlian dan sumber daya yang dimiliki PARA PIHAK.',
            'Perjanjian Kerja Sama ini bertujuan untuk mengadakan kerja sama awal dalam program magang bersertifikat dengan memanfaatkan sumber daya yang dimiliki PARA PIHAK demi kemajuan bersama',
        ],

        // Pasal 3: Ruang Lingkup
        pasal_3_scope: [
            'Penyelenggaraan Program Magang Bersertifikat;',
            'Peningkatan dan Pengembangan Kompetensi Sumber Daya Manusia; dan',
            'Kegiatan lain yang disepakati oleh PARA PIHAK.',
        ],

        // Pasal 4: Pelaksanaan
        pasal_4_implementation: [
            'PIHAK PERTAMA menginformasikan kepada PIHAK KEDUA terkait peserta yang diterima Program Magang;',
            'Calon Peserta Program yang telah lulus seleksi akan mengikuti kegiatan Program sesuai ketentuan yang telah ditetapkan oleh PIHAK PERTAMA',
            'PARA PIHAK bekerja sama untuk melaksanakan Program Magang, bagi Calon Peserta yang merupakan Mahasiswa dari PIHAK KEDUA.',
        ],

        // Pasal 5: Hak dan Kewajiban Para Pihak
        pasal_5_rights_p1: [
            'Menentukan jumlah Calon Peserta Program;',
            'Mengatur penempatan Peserta Program;',
            'Mendapatkan Peserta Program yang berasal dari Mahasiswa PIHAK KEDUA;',
            'Menetapkan peraturan yang harus ditaati oleh peserta selama Program Magang Bersertifikat berlangsung',
        ],
        pasal_5_obligations_p1: [
            'Melakukan wawancara dalam pelaksanaan seleksi di lingkungan PIHAK PERTAMA (apabila diperlukan);',
            'Menyediakan fasilitas beserta sarana dan prasana pelaksanaan Program (terbatas pada lokasi dimana pelaksanaan Program tersebut dilaksanakan);',
            'Menyiapkan mentor /pembimbing lapangan yang bertugas mendampingi dan menilai kinerja mahasiswa selama magang bersama dosen pembimbing PIHAK KEDUA;',
            'Memberikan hak dan jaminan sesuai peraturan perundangan.',
        ],
        pasal_5_rights_p2: [
            'Menerima Sertifikat Magang dan nilai untuk Mahasiswa PIHAK KEDUA yang mengikuti Program Magang.',
        ],
        pasal_5_obligations_p2: [
            'Melakukan seleksi Calon Peserta Program;',
            'Menyiapkan Dosen Pembimbing untuk Mahasiswa Magang;',
        ],

        // Pasal 6: Jangka Waktu Program Magang
        pasal_6_terms: [
            'Jangka waktu penyelenggaraan Program adalah 5 (lima) bulan terhitung mulai Calon Peserta Program telah mengikuti Program pertama kali dilaksanakan di lingkungan PIHAK PERTAMA.',
            'Jangka waktu penyelenggaraan Program sebagaimana dimaksud pada ayat (1) dapat diperpanjang berdasarkan evaluasi dan kesepakatan PARA PIHAK.',
            'Peserta Program dinyatakan sudah mengakhiri Program apabila PIHAK PERTAMA menyatakan Peserta Program telah mengikuti seluruh rangkaian Program dan dinyatakan berkompeten atau tidak berkompeten.',
        ],

        // Pasal 7: Jangka Waktu Perjanjian & Pengakhiran
        pasal_7_term1: 'Perjanjian ini berlaku selama 2 (dua) tahun terhitung sejak tanggal ditandatanganinya perjanjian ini dan dapat diubah, diperpanjang atau diakhiri berdasarkan kesepakatan tertulis dari PARA PIHAK.',
        pasal_7_termination_reasons: [
            'Berakhirnya jangka waktu Perjanjian;',
            'Kesepakatan PARA PIHAK; dan/atau',
            'Terdapat ketentuan perundangan-undangan dan/atau kebijakan yang tidak memungkinkan dilaksanakannya kerja sama menurut Perjanjian ini, tanpa terikat dengan ketentuan waktu sebagaimana dimaksud pada ayat (1).',
        ],
        pasal_7_term3: 'Apabila salah satu PIHAK berkeinginan untuk mengakhiri Perjanjian ini sebelum jangka waktu sebagaimana dimaksud pada ayat (1), maka PIHAK yang memiliki keinginan tersebut memberitahu selambat-lambatnya 30 (tiga puluh) hari kalender sebelum Perjanjian Kerja Sama ini diakhiri.',
        pasal_7_term4: 'Apabila pada saat Perjanjian ini berakhir atau diputuskan terdapat kewajiban yang belum dapat diselesaikan, maka ketentuan dalam Perjanjian ini tetap berlaku sampai diselesaikannya kewajiban tersebut, paling lambat 3 (tiga) bulan sejak Perjanjian ini berakhir.',

        // Pasal 8: Kerahasiaan Informasi
        pasal_8_term1: 'PARA PIHAK sepakat bahwa informasi tertentu diklasifikasikan sebagai rahasia (“Informasi Rahasia”).',
        pasal_8_term2_intro: 'PARA PIHAK akan secara ketat menjaga kerahasiaan dan tidak akan digunakan untuk tujuan lain selain pelaksanaan Perjanjian Kerjasama, atau diberikan kepada PIHAK lain, lembaga atau perusahaan lain segala Informasi Rahasia kecuali dengan persetujuan tertulis dari masing-masing PIHAK. Ketentuan ini tidak berlaku terhadap:',
        pasal_8_exceptions: [
            'Pemberitahuan kepada direktur, petugas atau karyawan dari Pihak yang secara langsung terlibat dengan Informasi Rahasia tersebut berdasarkan Perjanjian Kerja sama ini.',
            'Informasi yang merupakan informasi publik.',
            'Informasi yang tersedia pada PARA PIHAK yang pada dasarnya dikategorikan nonrahasia sebelum Perjanjian Kerjasama ini berlaku.',
            'Informasi yang diperbolehkan atau diizinkan untuk diungkapkan berdasarkan peraturan perundang-undangan, Peraturan Pemerintah, Surat Keputusan, Undang-Undang, atau peraturan yang berlaku.',
        ],
        pasal_8_term3: 'Kewajiban PARA PIHAK terhadap Kerahasiaan akan bertahan sampai dengan 5 (lima) tahun setelah berakhirnya Perjanjian Kerja Sama ini.',

        // Durasi & Ketentuan (Parameter Angka)
        program_duration_months: 5,
        agreement_duration_years: 2,
        notice_days: 30,
        obligation_months: 3,
        confidentiality_duration_years: 5,

        // Pasal 9: Korespondensi (Poin 2 & 3)
        pasal_9_term2: 'Dalam hal terjadi perubahan alamat dari alamat sebagaimana dimaksud pada ayat (1) atau alamat terakhir yang tercatat pada PARA PIHAK, maka perubahan harus diberitahukan secara tertulis kepada Pihak lain, paling lambat 5 (lima) hari kerja sebelum perubahan alamat dimaksud berlaku efektif',
        pasal_9_term3: 'Dalam hal perubahan alamat sebagaimana dimaksud pada ayat (2) tidak diberitahukan, maka surat-menyurat atau pemberitahuan dengan pengiriman yang ditujukan ke alamat di atas atau alamat terakhir yang diketahui/tercatat pada PARA PIHAK sebagaimana dimaksud pada ayat (1), dianggap telah diberikan sebagaimana mestinya.',

        // Kontak Korespondensi
        partner_contact_pic: 'Human Capital & Academic Partnership',
        faculty_contact_pic: 'Bagian Kerjasama & MBKM Fasilkom UNSUB',
        faculty_address: 'Jl. R.A Kartini No. 3 Subang, Jawa Barat 41285',
        faculty_phone: '(0260) 411415',
        faculty_email: 'fasilkom@unsub.ac.id',

        // Pasal 10 & 11
        pasal_10_text: 'Apabila timbul perselisihan yang terjadi dalam dan atau sebagai akibat dari dilaksanakannya Perjanjian ini maka PARA PIHAK sepakat untuk menyelesaikannya secara musyawarah.',
        pasal_11_text1: 'Hal-hal yang belum diatur dalam perjanjian ini akan diatur kemudian secara tertulis dalam addendum yang merupakan bagian tidak terpisahkan dari Perjanjian ini;',
        pasal_11_text2: 'Perjanjian ini dibuat tanpa paksaan dari siapapun, rangkap dua dan bermaterai cukup serta masing-masing mempunyai kekuatan hukum yang sama.',
    },
});

// Update Fasilkom official when signer dropdown changes
watch(() => form.signer_id, (newVal) => {
    const s = props.signers.find(item => item.id === newVal);
    if (s) {
        form.extra_data.faculty_official_name = s.name;
        form.extra_data.faculty_official_position = `${s.signature_title} Fakultas Ilmu Komputer Universitas Subang`;
        if (s.nik) {
            form.extra_data.faculty_nik = s.nik;
        }
    }
});

// Watch date changes to update Indonesian words
watch(() => form.extra_data.signing_date, (newDate) => {
    applyDateWords(newDate);
});

// Helpers for dynamically adding / removing list items across articles
const addArrayItem = (key, defaultText = '') => {
    if (!Array.isArray(form.extra_data[key])) {
        form.extra_data[key] = [];
    }
    form.extra_data[key].push(defaultText);
};

const removeArrayItem = (key, index) => {
    if (Array.isArray(form.extra_data[key]) && form.extra_data[key].length > 1) {
        form.extra_data[key].splice(index, 1);
    }
};

// Live Debounced PDF Preview
const previewPdfBase64 = ref(null);
const isLiveSyncing = ref(false);
const previewErrorMessage = ref('');
const lastSyncedAt = ref('');
let debounceTimeout = null;

const fetchPreview = async () => {
    isLiveSyncing.value = true;
    previewErrorMessage.value = '';

    try {
        const response = await window.axios.post(route('letters.mou-studio.preview'), {
            letter_type_id: form.letter_type_id,
            academic_year: form.academic_year,
            signer_id: form.signer_id,
            signer_name: form.extra_data.faculty_official_name,
            signer_title: form.extra_data.faculty_official_position,
            extra_data: form.extra_data,
        });

        if (response.data?.success && response.data?.pdfBase64) {
            previewPdfBase64.value = response.data.pdfBase64;
            const now = new Date();
            lastSyncedAt.value = now.toTimeString().split(' ')[0];
        } else {
            previewErrorMessage.value = response.data?.message || 'Gagal memuat pratinjau MoU.';
        }
    } catch (err) {
        previewErrorMessage.value = err.response?.data?.message || err.message || 'Terjadi kesalahan koneksi saat merender MoU.';
    } finally {
        isLiveSyncing.value = false;
    }
};

const triggerDebouncedPreview = () => {
    if (debounceTimeout) clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
        fetchPreview();
    }, 450);
};

// Deep watch on form changes for live preview
watch(
    () => form.extra_data.program_duration_months,
    (newVal) => {
        if (!newVal) return;
        const words = numberToIndonesianWords(newVal);
        if (form.extra_data.pasal_6_terms && form.extra_data.pasal_6_terms[0]) {
            form.extra_data.pasal_6_terms[0] = `Jangka waktu penyelenggaraan Program adalah ${newVal} (${words}) bulan terhitung mulai Calon Peserta Program telah mengikuti Program pertama kali dilaksanakan di lingkungan PIHAK PERTAMA.`;
        }
    }
);

watch(
    () => form.extra_data.agreement_duration_years,
    (newVal) => {
        if (!newVal) return;
        const words = numberToIndonesianWords(newVal);
        form.extra_data.pasal_7_term1 = `Perjanjian ini berlaku selama ${newVal} (${words}) tahun terhitung sejak tanggal ditandatanganinya perjanjian ini dan dapat diubah, diperpanjang atau diakhiri berdasarkan kesepakatan tertulis dari PARA PIHAK.`;
    }
);

watch(
    () => form.extra_data.notice_days,
    (newVal) => {
        if (!newVal) return;
        const words = numberToIndonesianWords(newVal);
        form.extra_data.pasal_7_term3 = `Apabila salah satu PIHAK berkeinginan untuk mengakhiri Perjanjian ini sebelum jangka waktu sebagaimana dimaksud pada ayat (1), maka PIHAK yang memiliki keinginan tersebut memberitahu selambat-lambatnya ${newVal} (${words}) hari kalender sebelum Perjanjian Kerja Sama ini diakhiri.`;
    }
);

watch(
    () => form.extra_data.obligation_months,
    (newVal) => {
        if (!newVal) return;
        const words = numberToIndonesianWords(newVal);
        form.extra_data.pasal_7_term4 = `Apabila pada saat Perjanjian ini berakhir atau diputuskan terdapat kewajiban yang belum dapat diselesaikan, maka ketentuan dalam Perjanjian ini tetap berlaku sampai diselesaikannya kewajiban tersebut, paling lambat ${newVal} (${words}) bulan sejak Perjanjian ini berakhir.`;
    }
);

watch(
    () => form.extra_data.confidentiality_duration_years,
    (newVal) => {
        if (!newVal) return;
        const words = numberToIndonesianWords(newVal);
        form.extra_data.pasal_8_term3 = `Kewajiban PARA PIHAK terhadap Kerahasiaan akan bertahan sampai dengan ${newVal} (${words}) tahun setelah berakhirnya Perjanjian Kerja Sama ini.`;
    }
);

// Deep watch on form changes for live preview
watch(
    () => [
        form.letter_type_id,
        form.academic_year,
        form.signer_id,
        form.extra_data,
    ],
    () => {
        triggerDebouncedPreview();
    },
    { deep: true }
);

// Modal Confirm Direct Sign


const submitMou = (directSign = false) => {
    form.direct_sign = directSign;
    form.post(route('letters.mou-studio.store'), {
        preserveScroll: true,
    });
};

onMounted(() => {
    fetchPreview();
});
</script>

<template>
    <Head title="Studio Nota Kesepahaman (MoU)" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 text-xs font-medium text-slate-500 dark:text-slate-400">
                        <Link :href="route('letters.index')" class="hover:text-teal-600 transition-colors">
                            Layanan Persuratan
                        </Link>
                        <span>/</span>
                        <span class="text-teal-600 dark:text-teal-400 font-semibold">Studio MoU</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div>
                            <h2 class="text-xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
                                Studio Nota Kesepahaman (MoU)
                            </h2>
                        </div>
                    </div>
                </div>

                <!-- TOP ACTIONS & LAYOUT SWITCHER -->
                <div class="flex items-center gap-2.5 flex-wrap">
                    <!-- VIEW MODE SWITCHER -->
                    <div class="hidden sm:flex items-center bg-slate-100 dark:bg-slate-800/80 p-1 rounded-xl border border-slate-200 dark:border-slate-700 text-xs mr-2">
                        <button
                            type="button"
                            @click="layoutMode = 'editor-wide'"
                            :class="[
                                'px-2.5 py-1.5 rounded-lg font-semibold transition text-[11px]',
                                layoutMode === 'editor-wide'
                                    ? 'bg-white dark:bg-slate-900 text-teal-600 dark:text-teal-400 shadow-xs'
                                    : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'
                            ]"
                            title="Fokus Editor Form (65:35)"
                        >
                            Editor Luas
                        </button>
                        <button
                            type="button"
                            @click="layoutMode = 'balanced'"
                            :class="[
                                'px-2.5 py-1.5 rounded-lg font-semibold transition text-[11px]',
                                layoutMode === 'balanced'
                                    ? 'bg-white dark:bg-slate-900 text-teal-600 dark:text-teal-400 shadow-xs'
                                    : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'
                            ]"
                            title="Split Seimbang (50:50)"
                        >
                            Split 50:50
                        </button>
                        <button
                            type="button"
                            @click="layoutMode = 'preview-wide'"
                            :class="[
                                'px-2.5 py-1.5 rounded-lg font-semibold transition text-[11px]',
                                layoutMode === 'preview-wide'
                                    ? 'bg-white dark:bg-slate-900 text-teal-600 dark:text-teal-400 shadow-xs'
                                    : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'
                            ]"
                            title="Fokus Pratinjau PDF (35:65)"
                        >
                            Preview Luas
                        </button>
                    </div>

                    <button
                        type="button"
                        @click="fetchPreview"
                        :disabled="isLiveSyncing"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-xs transition-colors"
                        title="Perbarui tampilan PDF"
                    >
                        <svg class="w-3.5 h-3.5" :class="{ 'animate-spin': isLiveSyncing }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>Sinkronkan</span>
                    </button>

                    <button
                        type="button"
                        @click="submitMou(false)"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-teal-700 dark:text-teal-300 bg-teal-50 dark:bg-teal-950/60 border border-teal-200 dark:border-teal-800 hover:bg-teal-100 dark:hover:bg-teal-900/60 transition-colors"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                        </svg>
                        <span>Simpan Draf</span>
                    </button>
                </div>
            </div>
        </template>

        <!-- FULL-WIDTH STUDIO CONTAINER (FLUID EDGE-TO-EDGE WITH COMFORTABLE PADDING) -->
        <div class="pb-10 pt-4">
            <div class="w-full px-4 sm:px-6 lg:px-8 2xl:px-12">
                <!-- MAIN GRID: FLUID SPLIT COLUMNS -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                    
                    <!-- LEFT COLUMN: EDITOR TABS -->
                    <div :class="[editorColClass, 'space-y-4 transition-all duration-300']">
                        
                        <!-- TAB NAVIGATION PILLS -->
                        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-xs p-1.5 flex flex-wrap gap-1">
                            <button
                                type="button"
                                @click="activeStep = 'info'"
                                :class="[
                                    'flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold transition-all',
                                    activeStep === 'info'
                                        ? 'bg-teal-600 text-white shadow-sm'
                                        : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
                                ]"
                            >
                                <span>1. Info & Waktu</span>
                            </button>
                            <button
                                type="button"
                                @click="activeStep = 'parties'"
                                :class="[
                                    'flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold transition-all',
                                    activeStep === 'parties'
                                        ? 'bg-teal-600 text-white shadow-sm'
                                        : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
                                ]"
                            >
                                <span>2. Para Pihak</span>
                            </button>
                            <button
                                type="button"
                                @click="activeStep = 'scope'"
                                :class="[
                                    'flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold transition-all',
                                    activeStep === 'scope'
                                        ? 'bg-teal-600 text-white shadow-sm'
                                        : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
                                ]"
                            >
                                <span>3. Konsiderans & Lingkup</span>
                            </button>
                            <button
                                type="button"
                                @click="activeStep = 'duration'"
                                :class="[
                                    'flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold transition-all',
                                    activeStep === 'duration'
                                        ? 'bg-teal-600 text-white shadow-sm'
                                        : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
                                ]"
                            >
                                <span>4. Pelaksanaan & Durasi</span>
                            </button>
                            <button
                                type="button"
                                @click="activeStep = 'contact'"
                                :class="[
                                    'flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold transition-all',
                                    activeStep === 'contact'
                                        ? 'bg-teal-600 text-white shadow-sm'
                                        : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
                                ]"
                            >
                                <span>5. Korespondensi & TTE</span>
                            </button>
                        </div>

                        <!-- TAB 1: INFO JUDUL, NOMOR & TANGGAL -->
                        <div v-show="activeStep === 'info'" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-xs p-5 space-y-4">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                                <div>
                                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">
                                        Informasi Judul & Waktu Penandatanganan
                                    </h3>
                                    <p class="text-[11px] text-slate-500">
                                        Format penomoran ganda (Mitra & Fasilkom) serta tanggal dalam bentuk terbilang otomatis.
                                    </p>
                                </div>
                                <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-md bg-teal-50 text-teal-700 dark:bg-teal-950 dark:text-teal-300">
                                    Halaman 1
                                </span>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                        Judul Nota Kesepahaman (MOU) <span class="text-rose-500">*</span>
                                    </label>
                                    <textarea
                                        v-model="form.extra_data.mou_title"
                                        rows="2"
                                        class="w-full text-xs font-semibold rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2.5 focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                        placeholder="Contoh: PENYELENGGARAAN PROGRAM MAGANG BERSERTIFIKAT - MBKM"
                                    ></textarea>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                            Nomor MOU Pihak Pertama (Mitra) <span class="text-rose-500">*</span>
                                        </label>
                                        <input
                                            v-model="form.extra_data.partner_mou_number"
                                            type="text"
                                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2.5 focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                            placeholder="Contoh: 012/HRD-MOU/XI/2026"
                                        />
                                        <p class="text-[10px] text-slate-400 mt-1">Nomor surat resmi dari institusi/perusahaan mitra.</p>
                                    </div>

                                    <div>
                                        <div class="flex items-center justify-between mb-1">
                                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                                                Nomor MOU Pihak Kedua (Fasilkom)
                                            </label>
                                            <button
                                                type="button"
                                                @click="isManualFacultyNumber = !isManualFacultyNumber; if (!isManualFacultyNumber) { form.extra_data.faculty_mou_number = ''; triggerDebouncedPreview(); }"
                                                class="text-[11px] font-medium text-teal-600 dark:text-teal-400 hover:underline flex items-center gap-1 cursor-pointer"
                                            >
                                                <span>{{ isManualFacultyNumber ? 'Gunakan Nomor Otomatis' : 'Input Manual Nomor' }}</span>
                                            </button>
                                        </div>

                                        <div v-if="!isManualFacultyNumber" class="flex items-center justify-between px-3 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-dashed border-slate-300 dark:border-slate-700 text-xs">
                                            <div class="flex items-center gap-2 text-slate-600 dark:text-slate-300 font-mono">
                                                <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                                <span class="font-bold text-emerald-700 dark:text-emerald-400">Otomatis oleh Sistem</span>
                                                <span class="text-slate-400 text-[11px]">([Nomor]/MOU/FIK-US/...)</span>
                                            </div>
                                            <button
                                                type="button"
                                                @click="isManualFacultyNumber = true"
                                                class="px-2 py-0.5 text-[10px] font-semibold text-teal-700 dark:text-teal-300 bg-teal-50 dark:bg-teal-950/60 rounded-md hover:bg-teal-100 cursor-pointer"
                                            >
                                                Ubah
                                            </button>
                                        </div>

                                        <div v-else class="space-y-1">
                                            <input
                                                v-model="form.extra_data.faculty_mou_number"
                                                type="text"
                                                class="w-full text-xs font-mono font-bold rounded-xl border border-teal-300 dark:border-teal-600 bg-white dark:bg-slate-900 p-2.5 focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                                placeholder="Contoh: 015/MOU/FIK-US/XI/2026"
                                            />
                                            <p class="text-[10px] text-slate-400">
                                                Masukkan nomor MoU resmi jika sudah memiliki nomor agenda dari buku register.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                            Tanggal Penandatanganan <span class="text-rose-500">*</span>
                                        </label>
                                        <input
                                            v-model="form.extra_data.signing_date"
                                            type="date"
                                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2.5 focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                            Kota Penandatanganan <span class="text-rose-500">*</span>
                                        </label>
                                        <input
                                            v-model="form.extra_data.signing_city"
                                            type="text"
                                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2.5 focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                            placeholder="Contoh: Subang"
                                        />
                                    </div>
                                </div>

                                <!-- KARTU BANTU TERBILANG TANGGAL -->
                                <div class="p-4 rounded-xl border border-teal-200 dark:border-teal-800 bg-teal-50/50 dark:bg-teal-950/30 space-y-2.5">
                                    <div class="flex items-center justify-between">
                                        <div class="text-xs font-bold text-teal-900 dark:text-teal-200 flex items-center gap-1.5">
                                            <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span>Bentuk Terbilang Tanggal (Otomatis & Dapat Diedit)</span>
                                        </div>
                                        <button
                                            type="button"
                                            @click="applyDateWords(form.extra_data.signing_date)"
                                            class="text-[11px] font-bold text-teal-700 dark:text-teal-300 hover:underline"
                                        >
                                            Regenerasi Terbilang
                                        </button>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-2.5 pt-1">
                                        <div>
                                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-0.5">Hari</label>
                                            <input
                                                v-model="form.extra_data.signing_day_words"
                                                type="text"
                                                class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-0.5">Tanggal</label>
                                            <input
                                                v-model="form.extra_data.signing_date_words"
                                                type="text"
                                                class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-0.5">Bulan</label>
                                            <input
                                                v-model="form.extra_data.signing_month_words"
                                                type="text"
                                                class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-0.5">Tahun</label>
                                            <input
                                                v-model="form.extra_data.signing_year_words"
                                                type="text"
                                                class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2"
                                            />
                                        </div>
                                    </div>
                                    <div class="text-[11px] text-teal-800 dark:text-teal-300 italic pt-1">
                                        Hasil: "Pada hari ini, {{ form.extra_data.signing_day_words }} tanggal {{ form.extra_data.signing_date_words }} bulan {{ form.extra_data.signing_month_words }} tahun {{ form.extra_data.signing_year_words }} ({{ form.extra_data.signing_date_formatted }}) bertempat di {{ form.extra_data.signing_city }}..."
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: PARA PIHAK (MITRA & FASILKOM) -->
                        <div v-show="activeStep === 'parties'" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-xs p-5 space-y-4">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                                <div>
                                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">
                                        Identitas Para Pihak Penandatangan
                                    </h3>
                                    <p class="text-[11px] text-slate-500">
                                        Pihak Pertama (Mitra Industri / Perusahaan) dan Pihak Kedua (Fakultas Ilmu Komputer Universitas Subang).
                                    </p>
                                </div>
                                <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-md bg-teal-50 text-teal-700 dark:bg-teal-950 dark:text-teal-300">
                                    Halaman 1 & 6
                                </span>
                            </div>

                            <!-- SECTION PIHAK PERTAMA (MITRA) -->
                            <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 space-y-3">
                                <div class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-full bg-teal-600 text-white text-[10px] flex items-center justify-center font-bold">1</span>
                                    <span>PIHAK PERTAMA (Institusi / Mitra Perusahaan)</span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                            Nama Institusi / Perusahaan <span class="text-rose-500">*</span>
                                        </label>
                                        <input
                                            v-model="form.extra_data.partner_name"
                                            type="text"
                                            class="w-full text-xs font-semibold rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2.5 focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                            placeholder="Contoh: PT SOLUSI TEKNOLOGI NUSANTARA"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                            Bidang Usaha / Operasional <span class="text-rose-500">*</span>
                                        </label>
                                        <input
                                            v-model="form.extra_data.partner_business_field"
                                            type="text"
                                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2.5 focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                            placeholder="Contoh: Industri Teknologi Informasi & Software Development"
                                        />
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                            Nama Pejabat Pihak Pertama <span class="text-rose-500">*</span>
                                        </label>
                                        <input
                                            v-model="form.extra_data.partner_official_name"
                                            type="text"
                                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2.5 focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                            placeholder="Contoh: Budi Santoso, S.T., M.M."
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                            Jabatan Pejabat Pihak Pertama <span class="text-rose-500">*</span>
                                        </label>
                                        <input
                                            v-model="form.extra_data.partner_official_position"
                                            type="text"
                                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2.5 focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                            placeholder="Contoh: Direktur Utama"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                        Alamat Lengkap Perusahaan <span class="text-rose-500">*</span>
                                    </label>
                                    <textarea
                                        v-model="form.extra_data.partner_address"
                                        rows="2"
                                        class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2.5 focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                        placeholder="Contoh: Jl. Jenderal Sudirman Kav. 52-53, Jakarta Selatan"
                                    ></textarea>
                                </div>
                            </div>

                            <!-- SECTION PIHAK KEDUA (FASILKOM UNSUB) -->
                            <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 space-y-3">
                                <div class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-full bg-teal-600 text-white text-[10px] flex items-center justify-center font-bold">2</span>
                                    <span>PIHAK KEDUA (Fakultas Ilmu Komputer Universitas Subang)</span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                            Pilih Pejabat Penandatangan
                                        </label>
                                        <select
                                            v-model="form.signer_id"
                                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2.5 focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                        >
                                            <option v-for="s in signers" :key="s.id" :value="s.id">
                                                {{ s.signature_title }} - {{ s.name }}
                                            </option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                            NIK / NIDN Pejabat
                                        </label>
                                        <input
                                            v-model="form.extra_data.faculty_nik"
                                            type="text"
                                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2.5 focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                        Nama Lengkap Pejabat Fasilkom
                                    </label>
                                    <input
                                        v-model="form.extra_data.faculty_official_name"
                                        type="text"
                                        class="w-full text-xs font-semibold rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2.5 focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                        Jabatan Pejabat Fasilkom
                                    </label>
                                    <input
                                        v-model="form.extra_data.faculty_official_position"
                                        type="text"
                                        class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2.5 focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- TAB 3: KONSIDERANS & RUANG LINGKUP -->
                        <div v-show="activeStep === 'scope'" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-xs p-5 space-y-4">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                                <div>
                                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">
                                        Konsiderans, Maksud, Tujuan & Ruang Lingkup
                                    </h3>
                                    <p class="text-[11px] text-slate-500">
                                        Semua poin klausul dapat diedit langsung dan akan langsung tercermin pada halaman 2 & 3 dokumen PDF.
                                    </p>
                                </div>
                                <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-md bg-teal-50 text-teal-700 dark:bg-teal-950 dark:text-teal-300">
                                    Halaman 2 & 3
                                </span>
                            </div>

                            <div class="space-y-4">
                                <!-- PASAL 1: DEFINISI -->
                                <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 space-y-2.5">
                                    <div class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center justify-between">
                                        <span>PASAL 1: DEFINISI & TERMINOLOGI</span>
                                        <button
                                            type="button"
                                            @click="addArrayItem('pasal_1_definitions', '')"
                                            class="px-2 py-0.5 rounded-md text-[10px] font-bold text-teal-700 bg-teal-50 dark:bg-teal-950/60 dark:text-teal-300 hover:bg-teal-100 flex items-center gap-1"
                                        >
                                            + Tambah Definisi
                                        </button>
                                    </div>
                                    <div v-for="(def, idx) in form.extra_data.pasal_1_definitions" :key="idx" class="space-y-1">
                                        <div class="flex items-center justify-between">
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400">
                                                Poin {{ idx + 1 }}
                                            </label>
                                            <button
                                                v-if="form.extra_data.pasal_1_definitions.length > 1"
                                                type="button"
                                                @click="removeArrayItem('pasal_1_definitions', idx)"
                                                class="text-rose-500 hover:text-rose-700 text-[10px] font-semibold"
                                            >
                                                Hapus ✕
                                            </button>
                                        </div>
                                        <textarea
                                            v-model="form.extra_data.pasal_1_definitions[idx]"
                                            rows="2"
                                            class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2"
                                        ></textarea>
                                    </div>
                                </div>

                                <!-- PASAL 2: MAKSUD DAN TUJUAN -->
                                <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 space-y-2.5">
                                    <div class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center justify-between">
                                        <span>PASAL 2: MAKSUD DAN TUJUAN</span>
                                        <button
                                            type="button"
                                            @click="addArrayItem('pasal_2_intent', '')"
                                            class="px-2 py-0.5 rounded-md text-[10px] font-bold text-teal-700 bg-teal-50 dark:bg-teal-950/60 dark:text-teal-300 hover:bg-teal-100 flex items-center gap-1"
                                        >
                                            + Tambah Maksud & Tujuan
                                        </button>
                                    </div>
                                    <div v-for="(intent, idx) in form.extra_data.pasal_2_intent" :key="idx" class="space-y-1">
                                        <div class="flex items-center justify-between">
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400">
                                                Ayat ({{ idx + 1 }})
                                            </label>
                                            <button
                                                v-if="form.extra_data.pasal_2_intent.length > 1"
                                                type="button"
                                                @click="removeArrayItem('pasal_2_intent', idx)"
                                                class="text-rose-500 hover:text-rose-700 text-[10px] font-semibold"
                                            >
                                                Hapus ✕
                                            </button>
                                        </div>
                                        <textarea
                                            v-model="form.extra_data.pasal_2_intent[idx]"
                                            rows="2"
                                            class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2"
                                        ></textarea>
                                    </div>
                                </div>

                                <!-- PASAL 3: RUANG LINGKUP -->
                                <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 space-y-2.5">
                                    <div class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center justify-between">
                                        <span>PASAL 3: RUANG LINGKUP KERJASAMA</span>
                                        <button
                                            type="button"
                                            @click="addArrayItem('pasal_3_scope', '')"
                                            class="px-2 py-0.5 rounded-md text-[10px] font-bold text-teal-700 bg-teal-50 dark:bg-teal-950/60 dark:text-teal-300 hover:bg-teal-100 flex items-center gap-1"
                                        >
                                            + Tambah Ruang Lingkup
                                        </button>
                                    </div>
                                    <div v-for="(scope, idx) in form.extra_data.pasal_3_scope" :key="idx" class="space-y-1">
                                        <div class="flex items-center justify-between">
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400">
                                                Butir ({{ idx + 1 }})
                                            </label>
                                            <button
                                                v-if="form.extra_data.pasal_3_scope.length > 1"
                                                type="button"
                                                @click="removeArrayItem('pasal_3_scope', idx)"
                                                class="text-rose-500 hover:text-rose-700 text-[10px] font-semibold"
                                            >
                                                Hapus ✕
                                            </button>
                                        </div>
                                        <input
                                            v-model="form.extra_data.pasal_3_scope[idx]"
                                            type="text"
                                            class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 4: PELAKSANAAN & DURASI -->
                        <div v-show="activeStep === 'duration'" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-xs p-5 space-y-4">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                                <div>
                                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">
                                        Pelaksanaan, Jangka Waktu, & Kerahasiaan Informasi
                                    </h3>
                                    <p class="text-[11px] text-slate-500">
                                        Ketentuan Pasal 4 (Pelaksanaan), Pasal 6 (Durasi Magang), Pasal 7 (Durasi MoU), dan Pasal 8 (Kerahasiaan/NDA).
                                    </p>
                                </div>
                                <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-md bg-teal-50 text-teal-700 dark:bg-teal-950 dark:text-teal-300">
                                    Halaman 3, 4 & 5
                                </span>
                            </div>

                            <!-- PASAL 4: PELAKSANAAN -->
                            <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 space-y-2.5">
                                <div class="flex items-center justify-between">
                                    <div class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                        PASAL 4: MEKANISME PELAKSANAAN PROGRAM
                                    </div>
                                    <button
                                        type="button"
                                        @click="addArrayItem('pasal_4_implementation', 'Ketentuan pelaksanaan lainnya sesuai kesepakatan Para Pihak.')"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-bold text-teal-700 bg-teal-50 dark:bg-teal-950/60 dark:text-teal-300 hover:bg-teal-100 dark:hover:bg-teal-900/60 border border-teal-200 dark:border-teal-800 rounded-lg transition-colors cursor-pointer"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Tambah Mekanisme
                                    </button>
                                </div>
                                <div v-for="(impl, idx) in form.extra_data.pasal_4_implementation" :key="idx" class="space-y-1">
                                    <div class="flex items-center justify-between">
                                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400">
                                            Poin ({{ idx + 1 }})
                                        </label>
                                        <button
                                            v-if="form.extra_data.pasal_4_implementation.length > 1"
                                            type="button"
                                            @click="removeArrayItem('pasal_4_implementation', idx)"
                                            class="text-[10px] text-red-500 hover:text-red-700 font-semibold cursor-pointer"
                                        >
                                            ✕ Hapus
                                        </button>
                                    </div>
                                    <textarea
                                        v-model="form.extra_data.pasal_4_implementation[idx]"
                                        rows="2"
                                        class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2"
                                    ></textarea>
                                </div>
                            </div>

                            <!-- PASAL 5: HAK DAN KEWAJIBAN PARA PIHAK -->
                            <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 space-y-3">
                                <div class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                    PASAL 5: HAK DAN KEWAJIBAN PARA PIHAK
                                </div>

                                <!-- Hak & Kewajiban Pihak Pertama -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                                    <div class="space-y-2 p-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900">
                                        <div class="flex items-center justify-between">
                                            <div class="text-[11px] font-bold text-slate-700 dark:text-slate-300">
                                                Ayat (1): Hak PIHAK PERTAMA (Mitra)
                                            </div>
                                            <button
                                                type="button"
                                                @click="addArrayItem('pasal_5_rights_p1', 'Menerima dukungan koordinasi dari PIHAK KEDUA.')"
                                                class="px-2 py-0.5 text-[10px] font-bold text-teal-700 bg-teal-50 dark:bg-teal-950/60 dark:text-teal-300 hover:bg-teal-100 rounded-md border border-teal-200 dark:border-teal-800 cursor-pointer"
                                            >
                                                + Tambah Hak
                                            </button>
                                        </div>
                                        <div v-for="(right, rIdx) in form.extra_data.pasal_5_rights_p1" :key="rIdx" class="space-y-0.5">
                                            <div class="flex items-center justify-between">
                                                <label class="block text-[10px] text-slate-500 font-semibold">{{ String.fromCharCode(97 + rIdx) }}.</label>
                                                <button
                                                    v-if="form.extra_data.pasal_5_rights_p1.length > 1"
                                                    type="button"
                                                    @click="removeArrayItem('pasal_5_rights_p1', rIdx)"
                                                    class="text-[10px] text-red-500 hover:text-red-700 cursor-pointer"
                                                >
                                                    ✕ Hapus
                                                </button>
                                            </div>
                                            <textarea
                                                v-model="form.extra_data.pasal_5_rights_p1[rIdx]"
                                                rows="2"
                                                class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-1.5"
                                            ></textarea>
                                        </div>
                                    </div>

                                    <div class="space-y-2 p-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900">
                                        <div class="flex items-center justify-between">
                                            <div class="text-[11px] font-bold text-slate-700 dark:text-slate-300">
                                                Ayat (2): Kewajiban PIHAK PERTAMA (Mitra)
                                            </div>
                                            <button
                                                type="button"
                                                @click="addArrayItem('pasal_5_obligations_p1', 'Memberikan bimbingan teknis kepada mahasiswa peserta magang.')"
                                                class="px-2 py-0.5 text-[10px] font-bold text-teal-700 bg-teal-50 dark:bg-teal-950/60 dark:text-teal-300 hover:bg-teal-100 rounded-md border border-teal-200 dark:border-teal-800 cursor-pointer"
                                            >
                                                + Tambah Kewajiban
                                            </button>
                                        </div>
                                        <div v-for="(oblg, oIdx) in form.extra_data.pasal_5_obligations_p1" :key="oIdx" class="space-y-0.5">
                                            <div class="flex items-center justify-between">
                                                <label class="block text-[10px] text-slate-500 font-semibold">{{ String.fromCharCode(97 + oIdx) }}.</label>
                                                <button
                                                    v-if="form.extra_data.pasal_5_obligations_p1.length > 1"
                                                    type="button"
                                                    @click="removeArrayItem('pasal_5_obligations_p1', oIdx)"
                                                    class="text-[10px] text-red-500 hover:text-red-700 cursor-pointer"
                                                >
                                                    ✕ Hapus
                                                </button>
                                            </div>
                                            <textarea
                                                v-model="form.extra_data.pasal_5_obligations_p1[oIdx]"
                                                rows="2"
                                                class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-1.5"
                                            ></textarea>
                                        </div>
                                    </div>
                                </div>

                                <!-- Hak & Kewajiban Pihak Kedua -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                                    <div class="space-y-2 p-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900">
                                        <div class="flex items-center justify-between">
                                            <div class="text-[11px] font-bold text-slate-700 dark:text-slate-300">
                                                Ayat (3): Hak PIHAK KEDUA (Fasilkom UNSUB)
                                            </div>
                                            <button
                                                type="button"
                                                @click="addArrayItem('pasal_5_rights_p2', 'Memperoleh laporan kemajuan peserta magang secara berkala.')"
                                                class="px-2 py-0.5 text-[10px] font-bold text-teal-700 bg-teal-50 dark:bg-teal-950/60 dark:text-teal-300 hover:bg-teal-100 rounded-md border border-teal-200 dark:border-teal-800 cursor-pointer"
                                            >
                                                + Tambah Hak
                                            </button>
                                        </div>
                                        <div v-for="(right, rIdx) in form.extra_data.pasal_5_rights_p2" :key="rIdx" class="space-y-0.5">
                                            <div class="flex items-center justify-between">
                                                <label class="block text-[10px] text-slate-500 font-semibold">{{ String.fromCharCode(97 + rIdx) }}.</label>
                                                <button
                                                    v-if="form.extra_data.pasal_5_rights_p2.length > 1"
                                                    type="button"
                                                    @click="removeArrayItem('pasal_5_rights_p2', rIdx)"
                                                    class="text-[10px] text-red-500 hover:text-red-700 cursor-pointer"
                                                >
                                                    ✕ Hapus
                                                </button>
                                            </div>
                                            <textarea
                                                v-model="form.extra_data.pasal_5_rights_p2[rIdx]"
                                                rows="2"
                                                class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-1.5"
                                            ></textarea>
                                        </div>
                                    </div>

                                    <div class="space-y-2 p-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900">
                                        <div class="flex items-center justify-between">
                                            <div class="text-[11px] font-bold text-slate-700 dark:text-slate-300">
                                                Ayat (4): Kewajiban PIHAK KEDUA (Fasilkom UNSUB)
                                            </div>
                                            <button
                                                type="button"
                                                @click="addArrayItem('pasal_5_obligations_p2', 'Memastikan peserta magang mematuhi ketentuan yang berlaku di lingkungan PIHAK PERTAMA.')"
                                                class="px-2 py-0.5 text-[10px] font-bold text-teal-700 bg-teal-50 dark:bg-teal-950/60 dark:text-teal-300 hover:bg-teal-100 rounded-md border border-teal-200 dark:border-teal-800 cursor-pointer"
                                            >
                                                + Tambah Kewajiban
                                            </button>
                                        </div>
                                        <div v-for="(oblg, oIdx) in form.extra_data.pasal_5_obligations_p2" :key="oIdx" class="space-y-0.5">
                                            <div class="flex items-center justify-between">
                                                <label class="block text-[10px] text-slate-500 font-semibold">{{ String.fromCharCode(97 + oIdx) }}.</label>
                                                <button
                                                    v-if="form.extra_data.pasal_5_obligations_p2.length > 1"
                                                    type="button"
                                                    @click="removeArrayItem('pasal_5_obligations_p2', oIdx)"
                                                    class="text-[10px] text-red-500 hover:text-red-700 cursor-pointer"
                                                >
                                                    ✕ Hapus
                                                </button>
                                            </div>
                                            <textarea
                                                v-model="form.extra_data.pasal_5_obligations_p2[oIdx]"
                                                rows="2"
                                                class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-1.5"
                                            ></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- PASAL 6: JANGKA WAKTU DAN BERAKHIRNYA PROGRAM -->
                            <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 space-y-2.5">
                                <div class="flex items-center justify-between">
                                    <div class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                        PASAL 6: JANGKA WAKTU DAN BERAKHIRNYA PROGRAM
                                    </div>
                                    <button
                                        type="button"
                                        @click="addArrayItem('pasal_6_terms', 'Ketentuan evaluasi dan perpanjangan pelaksanaan program.')"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-bold text-teal-700 bg-teal-50 dark:bg-teal-950/60 dark:text-teal-300 hover:bg-teal-100 dark:hover:bg-teal-900/60 border border-teal-200 dark:border-teal-800 rounded-lg transition-colors cursor-pointer"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Tambah Ayat
                                    </button>
                                </div>
                                <div v-for="(term, tIdx) in form.extra_data.pasal_6_terms" :key="tIdx" class="space-y-1">
                                    <div class="flex items-center justify-between">
                                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400">
                                            Ayat ({{ tIdx + 1 }})
                                        </label>
                                        <button
                                            v-if="form.extra_data.pasal_6_terms.length > 1"
                                            type="button"
                                            @click="removeArrayItem('pasal_6_terms', tIdx)"
                                            class="text-[10px] text-red-500 hover:text-red-700 font-semibold cursor-pointer"
                                        >
                                            ✕ Hapus
                                        </button>
                                    </div>
                                    <textarea
                                        v-model="form.extra_data.pasal_6_terms[tIdx]"
                                        rows="2"
                                        class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2"
                                    ></textarea>
                                </div>
                            </div>

                            <!-- PASAL 7: JANGKA WAKTU DAN PENGAKHIRAN PERJANJIAN -->
                            <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 space-y-2.5">
                                <div class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                    PASAL 7: JANGKA WAKTU DAN PENGAKHIRAN PERJANJIAN
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                        Ayat (1): Masa Berlaku Perjanjian
                                    </label>
                                    <textarea
                                        v-model="form.extra_data.pasal_7_term1"
                                        rows="2"
                                        class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2"
                                    ></textarea>
                                </div>

                                <div class="space-y-1.5 p-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900">
                                    <div class="flex items-center justify-between">
                                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300">
                                            Ayat (2): Sebab-sebab Pengakhiran Kerjasama
                                        </label>
                                        <button
                                            type="button"
                                            @click="addArrayItem('pasal_7_termination_reasons', 'Terjadi perubahan regulasi atau kebijakan pemerintah yang mengharuskan berakhirnya kerjasama.')"
                                            class="px-2 py-0.5 text-[10px] font-bold text-teal-700 bg-teal-50 dark:bg-teal-950/60 dark:text-teal-300 hover:bg-teal-100 rounded-md border border-teal-200 dark:border-teal-800 cursor-pointer"
                                        >
                                            + Tambah Alasan
                                        </button>
                                    </div>
                                    <div v-for="(rText, rIdx) in form.extra_data.pasal_7_termination_reasons" :key="rIdx" class="space-y-0.5">
                                        <div class="flex items-center justify-between">
                                            <label class="block text-[10px] text-slate-500 font-semibold">{{ String.fromCharCode(97 + rIdx) }}.</label>
                                            <button
                                                v-if="form.extra_data.pasal_7_termination_reasons.length > 1"
                                                type="button"
                                                @click="removeArrayItem('pasal_7_termination_reasons', rIdx)"
                                                class="text-[10px] text-red-500 hover:text-red-700 cursor-pointer"
                                            >
                                                ✕ Hapus
                                            </button>
                                        </div>
                                        <textarea
                                            v-model="form.extra_data.pasal_7_termination_reasons[rIdx]"
                                            rows="2"
                                            class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-1.5"
                                        ></textarea>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                        Ayat (3): Pemberitahuan Pengakhiran Perjanjian
                                    </label>
                                    <textarea
                                        v-model="form.extra_data.pasal_7_term3"
                                        rows="2"
                                        class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2"
                                    ></textarea>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                        Ayat (4): Penyelesaian Kewajiban yang Belum Tuntas
                                    </label>
                                    <textarea
                                        v-model="form.extra_data.pasal_7_term4"
                                        rows="2"
                                        class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2"
                                    ></textarea>
                                </div>
                            </div>

                            <!-- PASAL 8: KERAHASIAAN INFORMASI -->
                            <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 space-y-2.5">
                                <div class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                    PASAL 8: KERAHASIAAN INFORMASI (NDA)
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                        Ayat (1): Definisi Informasi Rahasia
                                    </label>
                                    <textarea
                                        v-model="form.extra_data.pasal_8_term1"
                                        rows="2"
                                        class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2"
                                    ></textarea>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                        Ayat (2): Kewajiban Penjagaan Kerahasiaan
                                    </label>
                                    <textarea
                                        v-model="form.extra_data.pasal_8_term2_intro"
                                        rows="3"
                                        class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2"
                                    ></textarea>
                                </div>

                                <div class="space-y-1.5 p-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900">
                                    <div class="flex items-center justify-between">
                                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300">
                                            Pengecualian Kerahasiaan (Ayat 2 Butir a - d)
                                        </label>
                                        <button
                                            type="button"
                                            @click="addArrayItem('pasal_8_exceptions', 'Informasi yang disetujui secara tertulis oleh Pihak Pemilik untuk diungkapkan.')"
                                            class="px-2 py-0.5 text-[10px] font-bold text-teal-700 bg-teal-50 dark:bg-teal-950/60 dark:text-teal-300 hover:bg-teal-100 rounded-md border border-teal-200 dark:border-teal-800 cursor-pointer"
                                        >
                                            + Tambah Pengecualian
                                        </button>
                                    </div>
                                    <div v-for="(ex, exIdx) in form.extra_data.pasal_8_exceptions" :key="exIdx" class="space-y-0.5">
                                        <div class="flex items-center justify-between">
                                            <label class="block text-[10px] text-slate-500 font-semibold">{{ String.fromCharCode(97 + exIdx) }}.</label>
                                            <button
                                                v-if="form.extra_data.pasal_8_exceptions.length > 1"
                                                type="button"
                                                @click="removeArrayItem('pasal_8_exceptions', exIdx)"
                                                class="text-[10px] text-red-500 hover:text-red-700 cursor-pointer"
                                            >
                                                ✕ Hapus
                                            </button>
                                        </div>
                                        <textarea
                                            v-model="form.extra_data.pasal_8_exceptions[exIdx]"
                                            rows="2"
                                            class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-1.5"
                                        ></textarea>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                        Ayat (3): Durasi Masa Berlaku Kerahasiaan
                                    </label>
                                    <textarea
                                        v-model="form.extra_data.pasal_8_term3"
                                        rows="2"
                                        class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2"
                                    ></textarea>
                                </div>
                            </div>

                            <!-- DURASI WAKTU -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 space-y-1.5">
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                        Durasi Pelaksanaan Magang (Pasal 6)
                                    </label>
                                    <div class="flex items-center gap-2">
                                        <input
                                            v-model.number="form.extra_data.program_duration_months"
                                            type="number"
                                            min="1"
                                            max="12"
                                            class="w-24 text-xs font-bold text-center rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2"
                                        />
                                        <span class="text-xs text-slate-600 dark:text-slate-400 font-semibold">Bulan (Standar 1 Semester MBKM = 5 Bulan)</span>
                                    </div>
                                    <p class="text-[10px] text-slate-400">Terhitung sejak mahasiswa mulai magang di institusi mitra.</p>
                                </div>

                                <div class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 space-y-1.5">
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                        Masa Berlaku Perjanjian MoU (Pasal 7)
                                    </label>
                                    <div class="flex items-center gap-2">
                                        <input
                                            v-model.number="form.extra_data.agreement_duration_years"
                                            type="number"
                                            min="1"
                                            max="10"
                                            class="w-24 text-xs font-bold text-center rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2"
                                        />
                                        <span class="text-xs text-slate-600 dark:text-slate-400 font-semibold">Tahun (Dapat diperpanjang atas kesepakatan)</span>
                                    </div>
                                    <p class="text-[10px] text-slate-400">Jangka waktu umum kemitraan institusi.</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 space-y-1">
                                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300">
                                        Pemberitahuan Tertulis
                                    </label>
                                    <div class="flex items-center gap-1.5">
                                        <input
                                            v-model.number="form.extra_data.notice_days"
                                            type="number"
                                            min="7"
                                            max="90"
                                            class="w-16 text-xs text-center rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-1.5"
                                        />
                                        <span class="text-[11px] text-slate-500">Hari</span>
                                    </div>
                                    <p class="text-[9px] text-slate-400">Jika salah satu pihak mengakhiri perjanjian.</p>
                                </div>

                                <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 space-y-1">
                                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300">
                                        Penyelesaian Kewajiban
                                    </label>
                                    <div class="flex items-center gap-1.5">
                                        <input
                                            v-model.number="form.extra_data.obligation_months"
                                            type="number"
                                            min="1"
                                            max="12"
                                            class="w-16 text-xs text-center rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-1.5"
                                        />
                                        <span class="text-[11px] text-slate-500">Bulan</span>
                                    </div>
                                    <p class="text-[9px] text-slate-400">Batas akhir penuntasan mahasiswa magang.</p>
                                </div>

                                <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 space-y-1">
                                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300">
                                        Kerahasiaan Data (NDA)
                                    </label>
                                    <div class="flex items-center gap-1.5">
                                        <input
                                            v-model.number="form.extra_data.confidentiality_duration_years"
                                            type="number"
                                            min="1"
                                            max="20"
                                            class="w-16 text-xs text-center rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-1.5"
                                        />
                                        <span class="text-[11px] text-slate-500">Tahun</span>
                                    </div>
                                    <p class="text-[9px] text-slate-400">Berlaku setelah perjanjian berakhir.</p>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 5: KORESPONDENSI, PASAL 10-11 & TTE -->
                        <div v-show="activeStep === 'contact'" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-xs p-5 space-y-4">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                                <div>
                                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">
                                        Korespondensi Resmi, Klausul Penutup & Penerbitan TTE
                                    </h3>
                                    <p class="text-[11px] text-slate-500">
                                        Ketentuan Pasal 9 (Surat Menyurat), Pasal 10 (Perselisihan), Pasal 11 (Lain-lain) dan Tanda Tangan Elektronik Dekan.
                                    </p>
                                </div>
                                <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-md bg-teal-50 text-teal-700 dark:bg-teal-950 dark:text-teal-300">
                                    Halaman 5 & 6
                                </span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- KONTAK MITRA -->
                                <div class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 space-y-2.5">
                                    <div class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                        Kontak Korespondensi Mitra (Pihak I)
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-0.5">Telepon Resmi</label>
                                        <input
                                            v-model="form.extra_data.partner_phone"
                                            type="text"
                                            class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-0.5">Email Resmi</label>
                                        <input
                                            v-model="form.extra_data.partner_email"
                                            type="email"
                                            class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2"
                                        />
                                    </div>
                                </div>

                                <!-- KONTAK FASILKOM -->
                                <div class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 space-y-2.5">
                                    <div class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                        Kontak Korespondensi Fasilkom (Pihak II)
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-0.5">Telepon Kampus</label>
                                        <input
                                            v-model="form.extra_data.faculty_phone"
                                            type="text"
                                            class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-0.5">Email Fakultas</label>
                                        <input
                                            v-model="form.extra_data.faculty_email"
                                            type="email"
                                            class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- PASAL 9: KORESPONDENSI & ALAMAT -->
                            <div class="space-y-3">
                                <div class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                    PASAL 9: KORESPONDENSI RESMI
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                        Pasal 9 Ayat (2): Ketentuan Perubahan Alamat
                                    </label>
                                    <textarea
                                        v-model="form.extra_data.pasal_9_term2"
                                        rows="2"
                                        class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2"
                                    ></textarea>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                        Pasal 9 Ayat (3): Keabsahan Pengiriman Surat
                                    </label>
                                    <textarea
                                        v-model="form.extra_data.pasal_9_term3"
                                        rows="2"
                                        class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2"
                                    ></textarea>
                                </div>
                            </div>

                            <!-- PASAL 10 & 11 -->
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                        Pasal 10: Penyelesaian Perselisihan
                                    </label>
                                    <textarea
                                        v-model="form.extra_data.pasal_10_text"
                                        rows="2"
                                        class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2"
                                    ></textarea>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                            Pasal 11 Ayat (1): Addendum
                                        </label>
                                        <textarea
                                            v-model="form.extra_data.pasal_11_text1"
                                            rows="2"
                                            class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2"
                                        ></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                            Pasal 11 Ayat (2): Keabsahan Hukum
                                        </label>
                                        <textarea
                                            v-model="form.extra_data.pasal_11_text2"
                                            rows="2"
                                            class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2"
                                        ></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- RIGHT COLUMN: LIVE PDF PREVIEW (FLUID SIZING & FULL-HEIGHT) -->
                    <div :class="[previewColClass, 'sticky top-20 space-y-3 transition-all duration-300']">
                        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-xs p-3">
                            <div class="flex items-center justify-between mb-2 px-1">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full" :class="isLiveSyncing ? 'bg-amber-400 animate-ping' : 'bg-teal-500'"></span>
                                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                        Pratinjau Live Dokumen MoU (6 Hal)
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span v-if="lastSyncedAt" class="text-[10px] text-slate-400 font-mono">
                                        Sinkron: {{ lastSyncedAt }}
                                    </span>
                                </div>
                            </div>

                            <!-- PDF PREVIEW EMBED (TALL VIEWPORT) -->
                            <div class="rounded-xl border border-slate-200/90 dark:border-slate-800 bg-white dark:bg-slate-900 p-1 shadow-xs h-[calc(100vh-175px)] min-h-[640px] overflow-hidden flex flex-col">
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
        </div>

    </AuthenticatedLayout>
</template>
