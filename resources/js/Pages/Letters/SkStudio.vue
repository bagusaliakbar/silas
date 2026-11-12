<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import LetterPdfViewer from '@/Components/LetterPdfViewer.vue';

const props = defineProps({
    skTypes: {
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
    students: {
        type: Array,
        default: () => [],
    },
    lecturers: {
        type: Array,
        default: () => [],
    },
    courses: {
        type: Array,
        default: () => [],
    },
    user: {
        type: Object,
        required: true,
    },
});

// Layout View Mode: 'balanced' (6:6) | 'preview-wide' (5:7 / 4:8) | 'editor-wide' (7:5 / 8:4)
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

// Active Tab in Editor: 'info' | 'considerations' | 'dictums' | 'matrix' | 'sign'
const activeStep = ref('info');

const initialType = props.skTypes[0] || {};

const selectedSkType = computed(() => {
    return props.skTypes.find((t) => t.id === form.letter_type_id) || props.skTypes[0] || {};
});

// Preset Default Data Generator for different SK Types
const getDefaultSkData = (typeCode) => {
    switch (typeCode) {
        case 'sk_dekan_dosen_mengajar':
            return {
                about_title: "PENUNJUKAN DOSEN PENGAJAR SEMESTER GANJIL TAHUN AKADEMIK\n2024/2025\nPROGRAM STUDI SISTEM INFORMASI S1",
                study_program: 'Program Studi Sistem Informasi S1',
                semester_type: 'Ganjil',
                academic_year: '2024/2025',
                decision_city: 'Subang',
                decision_date_text: '06 September 2024',
                dekan_name: 'Dr. TEPI PEIRISAL, M.Si',
                menetapkan_text: 'Dosen Pengajar Semester Ganjil Tahun Akademik 2024/2025 Program Studi Sistem Informasi S1',
                menimbang: [
                    'Bahwa untuk pelaksanaan proses belajar mengajar Semester Ganjil Tahun Akademik 2024/2025, pada Fakultas Ilmu Komputer perlu ditunjukkan tenaga pengajar;',
                    'Bahwa saudara-saudara yang namanya tercantum dalam daftar lampiran keputusan ini, mampu dan memenuhi syarat untuk ditunjukkan sebagai dosen pengajar Semester Ganjil Tahun Akademik 2024/2025, pada Fakultas Ilmu Komputer Universitas Subang;',
                    'Bahwa untuk memenuhi poin a dan b di atas, maka perlu ditetapkan melalui Keputusan Dekan Fakultas Ilmu Komputer.',
                ],
                mengingat: [
                    'Undang-undang Nomor : 20 Tahun 2003 tentang Sistem Pendidikan Nasional ;',
                    'Undang-undang No.14 Tahun 2005 tentang Guru dan Dosen',
                    'Peraturan pemerintah No.17 Tahun 2010 Tentang Penyelenggaraan dan Pengelolaan Pendidikan.',
                    'Keputusan Menteri Pendidikan Nasional Republik Indonesia Nomor : 232/U/2002 tentang Pedoman Penyusunan Kurikulum Pendidikan Tinggi dan Penilaian Hasil Belajar',
                    'Keputusan Menteri Pendidikan Nasional Republik Indonesia Nomor : 045/U/2002 tentang Kurikulum Inti Pendidikan',
                    'Surat Keputusan Menteri Pendidikan Nasional Republik Indonesia Nomor : 33/D/O/2005 tentang Penggabungan Sekolah Tinggi Ilmu Administrasi (STIA) Kutawaringin di Subang dan Sekolah Tinggi Teknologi (STT) Kutawaringin di Subang menjadi Universitas Subang serta Penambahan Program Studi Baru yang diselenggarakan oleh Yayasan Kutawaringin Subang di Subang.',
                    'Surat Keputusan Yayasan Kutawaringin Nomor : 18/Y/XII/2007 tanggal 28 Oktober 2007 tentang Penetapan Statuta Universitas Subang',
                ],
                memperhatikan: [],
                dictums: [
                    {
                        label: 'Pertama',
                        text: 'Menunjukkan Saudara-saudara yang namanya tercantum dalam daftar lampiran surat keputusan ini, sebagai dosen pengajar Semester Ganjil Tahun Akademik 2024/2025, dalam mata kuliah masing-masing sebagaimana tercantum dalam lampiran keputusan ini',
                    },
                    {
                        label: 'Kedua',
                        text: 'Keputusan ini berlaku sejak tanggal di tetapkan dengan ketentuan apabila dikemudian hari ternyata terdapat kekeliruan dalam Keputusan ini akan diperbaiki sebagaimana mestinya.',
                    },
                ],
                tembusan_list: [
                    'Yayasan Kutawaringin Subang',
                    'Rektor Universitas Subang',
                    'Arsip',
                ],
                mbkm_matrix: [],
                teaching_lecturers: [
                    {
                        name: 'Bambang Tjahjo Utomo, MT',
                        courses: [
                            { name: 'APSI II', sks: '3', semester: '5', class: 'A' },
                            { name: 'APSI II', sks: '3', semester: '5', class: 'B' },
                            { name: 'APSI II', sks: '3', semester: '5', class: 'C' },
                            { name: 'Bisnis Online', sks: '3', semester: '7', class: 'A' },
                            { name: 'Bisnis Online', sks: '3', semester: '7', class: 'B' },
                            { name: 'Digital Marketing', sks: '3', semester: '7', class: 'A' },
                            { name: 'Digital Marketing', sks: '3', semester: '7', class: 'B' },
                            { name: 'Praktek Manajemen lanjut', sks: '2', semester: '7', class: 'A KP' },
                            { name: 'Praktek Analisis lanjut', sks: '4', semester: '7', class: 'A KP' },
                        ],
                    },
                ],
            };
        case 'sk_dekan_pembimbing_ta':
            return {
                about_title: "DOSEN PEMBIMBING TUGAS AKHIR\nFAKULTAS ILMU KOMPUTER UNIVERSITAS SUBANG\nSEMESTER GANJIL TAHUN AKADEMIK 2025/2026",
                study_program: 'Program Studi Sistem Informasi S1',
                semester_type: 'Ganjil',
                academic_year: '2025/2026',
                decision_city: 'Subang',
                decision_date_text: '15 September 2025',
                dekan_name: 'Dr. TEPI PEIRISAL, M.Si',
                menetapkan_text: '',
                menimbang: [
                    'Bahwa mahasiswa mempunyai hak untuk mendapat bimbingan dari dosen yang bertanggungjawab atas program studi yang diikutinya dalam penyelesaian studi;',
                    'Bahwa mahasiswa sebagai bagian dari civitas akademika perlu lebih ditingkatkan kualitasnya serta diarahkan agar dapat menyelesaikan studi tepat pada waktunya;',
                    'Bahwa untuk mewujudkan hal tersebut di atas, maka kepada mahasiswa sebagai peserta didik diberikan bimbingan akademik;',
                    'Bahwa sesuai dengan hal-hal tersebut di atas, maka perlu ditetapkan persyaratan, tugas, wewenang Pembimbing Akademik dengan Keputusan Dekan Fakultas Ilmu Komputer Universitas Subang.',
                ],
                mengingat: [
                    'Undang-Undang Nomor 20 Tahun 2003 tentang Sistem Pendidikan Nasional;',
                    'Undang-Undang Nomor 14 Tahun 2005 tentang Guru dan Dosen;',
                    'Undang-Undang Nomor 12 Tahun 2012 tentang Perguruan Tinggi;',
                    'Peraturan Pemerintah Nomor 37 Tahun 2009 tentang Dosen;',
                    'Peraturan Pemerintah Nomor 4 Tahun 2014 tentang Penyelenggaraan Pendidikan dan Pengelolaan Perguruan Tinggi;',
                    'Peraturan Pemerintah Nomor 4 Tahun 2022 tentang Perubahan atas Peraturan Pemerintah Nomor 57 Tahun 2021 tentang Standar Nasional Pendidikan;',
                    'Peraturan Menteri Pendidikan, Kebudayaan, Riset, dan Teknologi Republik Indonesia Nomor 53 Tahun 2023 tentang Penjaminan Mutu Pendidikan Tinggi;',
                    'Peraturan Menteri Pendidikan Nasional Republik Indonesia Nomor : 33/D/O/2005 tentang Penggabungan Sekolah Tinggi Ilmu Administrasi (STIA) Kutawaringin di Subang dan Sekolah Tinggi Teknologi (STT) Kutawaringin di Subang menjadi Universitas Subang serta Penambahan Program Studi Baru yang diselenggarakan oleh Yayasan Kutawaringin Subang di Subang;',
                    'Peraturan Yayasan Kutawaringin Nomor 15 Tahun 2020 Tentang Penetapan Statuta Universitas Subang;',
                    'Surat Keputusan Rektor Universitas Subang Nomor 25/US/X/2013 Tentang Penetapan Perubahan Struktur Organisasi dan Tata Kerja Universitas Subang;',
                    'Surat Keputusan Rektor Nomor 68/US/VII/2022 tentang pedoman Penelitian dan Pengabdian kepada Masyarakat (PkM) Universitas Subang;',
                    'Surat Keputusan Rektor Nomor 93/US/VII/2022 tentang Pedoman Akademik Universitas Subang;',
                    'Surat Keputusan Rektor Nomor 71/US/XI/2023 tentang Pengangkatan Dekan Fakultas Ilmu Komputer Universitas Subang masa jabatan 2023-2027;',
                    'Berdasarkan Keputusan LAM INFOKOM No. 110/SK/LAM-INFOKOM/Ak/S/VIII/2025, menyatakan bahwa program studi Sistem Informasi pada Program Sarjana Universitas Subang, Kab. Subang Memenuhi Syarat Peringkat AKREDITASI BAIK SEKALI, sejak tanggal 8 Agustus 2025 sampai dengan 8 Agustus 2030.',
                ],
                memperhatikan: [],
                dictums: [
                    {
                        label: 'Pertama',
                        text: 'Menunjuk nama-nama dosen sebagai pembimbing tugas akhir pada Fakultas Ilmu Komputer Universitas Subang Semester Ganjil Tahun Akademik 2025/2026 sebagaimana tercantum pada lampiran Surat Keputusan ini',
                    },
                    {
                        label: 'Kedua',
                        text: 'Prosedur bimbingan tugas akhir sesuai dengan pedoman penyusunan tugas akhir di Fakultas Ilmu Komputer Universitas Subang.',
                    },
                    {
                        label: 'Ketiga',
                        text: 'Keputusan ini mulai berlaku sejak tanggal ditetapkan dan apabila terdapat kekeliruan dalam keputusan ini akan diperbaiki sebagaimana mestinya',
                    },
                ],
                tembusan_list: [
                    'Yayasan Kutawaringin Subang',
                    'Rektor Universitas Subang',
                    'Yang bersangkutan untuk diketahui dan seperlunya',
                ],
                attachment_subtitle: "DOSEN PEMBIMBING PENULISAN TUGAS AKHIR\nFAKULTAS ILMU KOMPUTER UNIVERSITAS SUBANG SEMESTER GANJIL TAHUN AKADEMIK 2025/2026",
                ta_matrix: [
                    {
                        npm: 'D1A210001',
                        name: 'RIZKI RAMADHAN',
                        title: 'Sistem Informasi Geografis Pemetaan UMKM Berbasis Web di Kabupaten Subang',
                        advisor_1: 'Dr. TEPI PEIRISAL, M.Si',
                        advisor_2: 'Bagus Ali Akbar, S.Si., M.Kom',
                    },
                ],
            };
        case 'sk_dekan_penguji_sidang_ta':
            return {
                about_title: "PELAKSANAAN KEGIATAN PENGUJIAN SEMINAR TUGAS AKHIR/ SKRIPSI\nSEMESTER GANJIL TAHUN AKADEMIK 2026/2027\nPROGRAM STUDI SISTEM INFORMASI S1\nFAKULTAS ILMU KOMPUTER UNIVERSTAS SUBANG",
                study_program: 'Program Studi Sistem Informasi S1',
                semester_type: 'Ganjil',
                academic_year: '2026/2027',
                decision_city: 'Subang',
                decision_date_text: '14 Oktober 2026',
                dekan_name: 'Dr. TEPI PEIRISAL, M.Si',
                menetapkan_text: 'Pelaksanaan Kegiatan Pengujian Seminar Tugas Akhir / Skripsi Semester Ganjil Tahun Akademik 2026/2027 Program Studi Sistem Informasi S1 Fakultas Ilmu Komputer Universitas Subang;',
                wave_info: 'GELOMBANG I',
                exam_schedule_date: 'Kamis, 15 Oktober 2026',
                exam_room: 'RUANG 8',
                menimbang: [
                    'Bahwa untuk Kelancaran pelaksanaan Kegiatan Pengujian Tugas Akhir Semester Ganjil Tahun Akademik 2026/2027 bagi mahasiswa Program Studi S1 Sistem Informasi Pada Fakultas Ilmu Komputer Universitas Subang perlu ditunjukkan penguji seminar tugas Akhir/skripsi ;',
                    'Bahwa saudara-saudara yang namanya tercantum dalam daftar lampiran keputusan ini, dipandang mampu dan memenuhi syarat untuk ditunjukkan sebagai penguji seminar tugas Akhir/skripsi Mahasiswa Program Studi S1 Sistem Informasi Pada Fakultas Ilmu Komputer Universitas Subang;',
                    'Bahwa untuk memenuhi poin a dan b di atas, maka perlu ditetapkan melalui Keputusan Dekan Fakultas Ilmu Komputer.',
                ],
                mengingat: [
                    'Undang-Undang Nomor 20 Tahun 2003 tentang Sistem Pendidikan Nasional;',
                    'Undang-Undang Nomor 14 Tahun 2005 tentang Guru dan Dosen;',
                    'Undang-Undang Nomor 12 Tahun 2012 tentang Perguruan Tinggi;',
                    'Peraturan Pemerintah Nomor 37 Tahun 2009 tentang Dosen;',
                    'Peraturan Pemerintah Nomor 4 Tahun 2014 tentang Penyelenggaraan Pendidikan dan Pengelolaan Perguruan Tinggi;',
                    'Peraturan Pemerintah Nomor 4 Tahun 2022 tentang Perubahan atas Peraturan Pemerintah Nomor 57 Tahun 2021 tentang Standar Nasional Pendidikan;',
                    'Peraturan Menteri Pendidikan, Kebudayaan, Riset, dan Teknologi Republik Indonesia Nomor 53 Tahun 2023 tentang Penjaminan Mutu Pendidikan Tinggi;',
                    'Peraturan Menteri Pendidikan Nasional Republik Indonesia Nomor : 33/D/O/2005 tentang Penggabungan Sekolah Tinggi Ilmu Administrasi (STIA) Kutawaringin di Subang dan Sekolah Tinggi Teknologi (STT) Kutawaringin di Subang menjadi Universitas Subang serta Penambahan Program Studi Baru yang diselenggarakan oleh Yayasan Kutawaringin Subang di Subang;',
                    'Peraturan Yayasan Kutawaringin Nomor 15 Tahun 2020 Tentang Penetapan Statuta Universitas Subang;',
                    'Surat Keputusan Rektor Universitas Subang Nomor 25/US/X/2013 Tentang Penetapan Perubahan Struktur Organisasi dan Tata Kerja Universitas Subang;',
                    'Surat Keputusan Rektor Nomor 68/US/VII/2022 tentang pedoman Penelitian dan Pengabdian kepada Masyarakat (PkM) Universitas Subang;',
                    'Surat Keputusan Rektor Nomor 93/US/VII/2022 tentang Pedoman Akademik Universitas Subang;',
                    'Surat Keputusan Rektor Nomor 71/US/XI/2023 tentang Pengangkatan Dekan Fakultas Ilmu Komputer Universitas Subang masa jabatan 2023-2027;',
                    'Berdasarkan Keputusan LAM INFOKOM No. 110/SK/LAM-INFOKOM/Ak/S/VIII/2025, menyatakan bahwa program studi Sistem Informasi pada Program Sarjana Universitas Subang, Kab. Subang Memenuhi Syarat Peringkat AKREDITASI BAIK SEKALI, sejak tanggal 8 Agustus 2025 sampai dengan 8 Agustus 2030.',
                ],
                memperhatikan: [],
                dictums: [
                    {
                        label: 'Pertama',
                        text: 'Menyetujui pelaksanaan Pengujian Seminar Tugas Akhir/Skripsi Semester Ganjil Tahun Akademik 2026/2027 yang diselenggarakan oleh Program Studi Sistem Informasi dilaksanakan pada Hari Kamis Tanggal 15 Oktober 2026;',
                    },
                    {
                        label: 'Kedua',
                        text: 'Menetapkan peserta ujian dan penguji sebagaimana tercantum dalam lampiran Surat Keputusan yang merupakan bagian tak terpisakan dari keputusan ini;',
                    },
                    {
                        label: 'Ketiga',
                        text: 'Keputusan ini berlaku sejak tanggal di tetapkan dengan ketentuan, apabila dikemudian hari ternyata terdapat kekeliruan dalam Keputusan ini maka akan diperbaiki sebagaimana mestinya.',
                    },
                ],
                tembusan_list: [
                    'Yayasan Kutawaringin Subang',
                    'Rektor Universitas Subang',
                    'Arsip',
                ],
                attachment_subtitle: "Pelaksanaan Kegiatan Pengujian Seminar Tugas Akhir/ Skripsi Semester Ganjil Tahun Akademik 2026/2027 Program Studi Sistem Informasi S1 Fakultas Ilmu Komputer Universitas Subang",
                seminar_matrix: [
                    {
                        is_header_row: true,
                        time: '07.30 – 08.00',
                        note: 'Persiapan dan Pembukaan Seminar',
                    },
                    {
                        is_header_row: false,
                        time: '08.00 – 08.45',
                        npm: 'D1A230046',
                        name: 'SYIFA MUTAALIA',
                        thesis_title: 'PERANCANGAN AI AGENT BERBASIS LARGE LANGUAGE MODEL UNTUK MENINGKATKAN EFISIENSI LAYANAN INFORMASI KEPEMILUAN PADA KPU KABUPATEN SUBANG',
                        advisor_1: 'Sofwandi Noor, MT',
                        advisor_2: 'Bambang Tjahjo Utomo, MT',
                        examiner_1: 'Bagus Ali Akbar, S.Si., M.Kom',
                        examiner_2: 'Tazkia Salsabila Ardan, M.Kom',
                    },
                ],
            };
        case 'sk_dekan_pengujian_sidang_skripsi':
            return {
                about_title: "PELAKSANAAN KEGIATAN PENGUJIAN SIDANG TUGAS AKHIR/SKRIPSI\nSEMESTER GANJIL TAHUN AKADEMIK 2026/2027\nPROGRAM STUDI SISTEM INFORMASI S1\nFAKULTAS ILMU KOMPUTER UNIVERSITAS SUBANG",
                study_program: 'Program Studi Sistem Informasi S1',
                semester_type: 'Ganjil',
                academic_year: '2026/2027',
                decision_city: 'Subang',
                decision_date_text: '14 Oktober 2026',
                dekan_name: 'Dr. TEPI PEIRISAL, M.Si',
                menetapkan_text: 'Pelaksanaan Kegiatan Pengujian Sidang Tugas Akhir/Skripsi Semester Ganjil Tahun Akademik 2026/2027 Program Studi Sistem Informasi S1 Fakultas Ilmu Komputer Universitas Subang',
                wave_info: 'GELOMBANG 1',
                exam_schedule_date: 'Kamis, 15 Oktober 2026',
                exam_room: 'RUANG 8',
                menimbang: [
                    'Bahwa untuk Kelancaran pelaksanaan Kegiatan Pengujian Sidang Tugas Akhir/Skripsi Semester Ganjil Tahun Akademik 2026/2027 bagi mahasiswa Program Studi S1 Sistem Informasi Pada Fakultas Ilmu Komputer Universitas Subang perlu ditunjukkan penguji sidang tugas Akhir/skripsi;',
                    'Bahwa saudara-saudara yang namanya tercantum dalam daftar lampiran keputusan ini, dipandang mampu dan memenuhi syarat untuk ditunjukkan sebagai penguji sidang tugas Akhir/skripsi Mahasiswa Program Studi S1 Sistem Informasi Pada Fakultas Ilmu Komputer Universitas Subang;',
                    'Bahwa untuk memenuhi poin a dan b di atas, maka perlu ditetapkan melalui Keputusan Dekan Fakultas Ilmu Komputer.',
                ],
                mengingat: [
                    'Undang-Undang Nomor 20 Tahun 2003 tentang Sistem Pendidikan Nasional;',
                    'Undang-Undang Nomor 14 Tahun 2005 tentang Guru dan Dosen;',
                    'Undang-Undang Nomor 12 Tahun 2012 tentang Perguruan Tinggi;',
                    'Peraturan Pemerintah Nomor 37 Tahun 2009 tentang Dosen;',
                    'Peraturan Pemerintah Nomor 4 Tahun 2014 tentang Penyelenggaraan Pendidikan dan Pengelolaan Perguruan Tinggi;',
                    'Peraturan Pemerintah Nomor 4 Tahun 2022 tentang Perubahan atas Peraturan Pemerintah Nomor 57 Tahun 2021 tentang Standar Nasional Pendidikan;',
                    'Peraturan Menteri Pendidikan, Kebudayaan, Riset, dan Teknologi Republik Indonesia Nomor 53 Tahun 2023 tentang Penjaminan Mutu Pendidikan Tinggi;',
                    'Peraturan Menteri Pendidikan Nasional Republik Indonesia Nomor : 33/D/O/2005 tentang Penggabungan Sekolah Tinggi Ilmu Administrasi (STIA) Kutawaringin di Subang dan Sekolah Tinggi Teknologi (STT) Kutawaringin di Subang menjadi Universitas Subang serta Penambahan Program Studi Baru yang diselenggarakan oleh Yayasan Kutawaringin Subang di Subang;',
                    'Peraturan Yayasan Kutawaringin Nomor 15 Tahun 2020 Tentang Penetapan Statuta Universitas Subang;',
                    'Surat Keputusan Rektor Universitas Subang Nomor 25/US/X/2013 Tentang Penetapan Perubahan Struktur Organisasi dan Tata Kerja Universitas Subang;',
                    'Surat Keputusan Rektor Nomor 68/US/VII/2022 tentang pedoman Penelitian dan Pengabdian kepada Masyarakat (PkM) Universitas Subang;',
                    'Surat Keputusan Rektor Nomor 93/US/VII/2022 tentang Pedoman Akademik Universitas Subang;',
                    'Surat Keputusan Rektor Nomor 71/US/XI/2023 tentang Pengangkatan Dekan Fakultas Ilmu Komputer Universitas Subang masa jabatan 2023-2027;',
                    'Berdasarkan Keputusan LAM INFOKOM No. 110/SK/LAM-INFOKOM/Ak/S/VIII/2025, menyatakan bahwa program studi Sistem Informasi pada Program Sarjana Universitas Subang, Kab. Subang Memenuhi Syarat Peringkat AKREDITASI BAIK SEKALI, sejak tanggal 8 Agustus 2025 sampai dengan 8 Agustus 2030.',
                ],
                memperhatikan: [],
                dictums: [
                    {
                        label: 'Pertama',
                        text: "Menyetujui pelaksanaan Pengujian Sidang Tugas Akhir/Skripsi Semester Ganjil Tahun Akademik 2026 yang diselenggarakan oleh Program Studi Sistem Informasi dilaksanakan pada Hari Jum'at tanggal 16 Oktober 2026.",
                    },
                    {
                        label: 'Kedua',
                        text: 'Menetapkan peserta ujian dan penguji sebagaimana tercantum dalam lampiran Surat Keputusan yang merupakan bagian tak terpisakan dari keputusan ini;',
                    },
                    {
                        label: 'Ketiga',
                        text: 'Keputusan ini berlaku sejak tanggal di tetapkan dengan ketentuan, apabila dikemudian hari ternyata terdapat kekeliruan dalam Keputusan ini maka akan diperbaiki sebagaimana mestinya.',
                    },
                ],
                tembusan_list: [
                    'Yayasan Kutawaringin Subang',
                    'Rektor Universitas Subang',
                    'Arsip',
                ],
                attachment_subtitle: "Pelaksanaan Kegiatan Pengujian Tugas Akhir Semester Ganjil Tahun Akademik 2026/2027 Program Studi Sistem Informasi S1 Fakultas Ilmu Komputer Universitas Subang",
                sidang_matrix: [
                    {
                        is_header_row: true,
                        time: '07.30 – 08.00',
                        note: 'Persiapan dan Pembukaan Sidang',
                    },
                    {
                        is_header_row: false,
                        time: '08.00 – 09.00',
                        npm: 'D1A230046',
                        name: 'SYIFA MUTAALIA',
                        thesis_title: 'PERANCANGAN AI AGENT BERBASIS LARGE LANGUAGE MODEL UNTUK MENINGKATKAN EFISIENSI LAYANAN INFORMASI KEPEMILUAN PADA KPU KABUPATEN SUBANG',
                        advisor_1: 'Sofwandi Noor, MT',
                        advisor_2: 'Bambang Tjahjo Utomo, MT',
                        examiner_1: 'Bagus Ali Akbar, S.Si., M.Kom',
                        examiner_2: 'Tazkia Salsabila Ardan, M.Kom',
                    },
                ],
            };
        case 'sk_dekan_gkm_ukm':
            return {
                about_title: "PENUNJUKAN DAN PENGANGKATAN KETUA\nGUGUS KENDALI MUTU (GKM) PADA FAKULTAS DAN UNIT KENDALI MUTU\n(UKM) PADA PROGRAM STUDI SISTEM INFORMASI UNIVERSITAS SUBANG",
                study_program: 'Program Studi Sistem Informasi',
                academic_year: '2023/2024',
                decision_city: 'Subang',
                decision_date_text: '24 November 2023',
                meeting_date_text: '13 November 2023',
                dekan_name: 'Dr. Tepi Peirisal, S.Sos, M.Si',
                dekan_nik: '0410420142',
                gkm_chairman_name: 'MAYA DESTRIANI, S.Kom., M.Kom',
                ukm_chairman_name: 'CACA ARIF HERDIAN, S.Kom., M.Kom',
                menetapkan_text: '',
                menimbang: [
                    'Bahwa dalam rangka mencapai Standar Nasional Pendidikan Tinggi diperlukan sistem penjaminan mutu pendidikan tinggi di Fakultas Ilmu Komputer Universitas Subang;',
                    'Bahwa Fakultas Ilmu Komputer bertugas menetapkan, melaksanakan, mengevaluasi, mengendalikan, dan meningkatkan Standar Dikti secara berkelanjutan perlu sistem penjaminan mutu internal;',
                    'Bahwa dalam upaya mengimplementasikan penjaminan mutu internal di Fakultas Ilmu Komputer Universitas Subang ditetapkan tugas pokok dan fungsi serta jabatan pelaksanaannya;',
                    'Bahwa untuk keperluan yang dimaksud pada nomor 1, 2 dan 3 diatas maka perlu ditetapkan dengan Surat Keputusan Dekan Fakultas Ilmu Komputer Universitas Subang.',
                ],
                mengingat: [
                    'Undang-Undang Republik Indonesia Nomor 12 Tahun 2012 tentang Pendidikan Tinggi.',
                    'Peraturan Pemerintah Republik Indonesia Nomor 4 Tahun 2014 tentang Penyelenggaraan Pendidikan Tinggi dan Pengelolaan Perguruan Tinggi.',
                    'Peraturan Menteri RISTEKDIKTI Nomor 44 Tahun 2015 tentang Standar Nasional Pendidikan Tinggi.',
                    'Peraturan Menteri RISTEKDIKTI Nomor 32 Tahun 2016 tentang Akreditasi Program Studi dan Perguruan Tinggi.',
                    'Peraturan Menteri RISTEKDIKTI Nomor 62 Tahun 2016 tentang Sistem Penjaminan Mutu Pendidikan Tinggi.',
                    'Surat Menteri RISTEKDIKTI Nomor 255/B/SE/VIII/2016 tentang Panduan Penyusunan Kurikulum Pendidikan Tinggi',
                    'Surat Keputusan Yayasan Nomor 24/Y/IX/2013 tanggal 10 September 2013 tentang Penetapan Perubahan Statuta Universitas Subang.',
                    'Surat Keputusan Rektor No. 24/US/X/2013 Tanggal 1 Oktober 2013 tentang Penetapan Perubahan Struktur Organisasi dan Tata Kerja Universitas Subang',
                    'Surat Keputusan Rektor Universitas Subang Nomor 26/US/X/2013 tentang Kebijakan Pedoman Umum Penyelenggaraan Pendidikan Universitas Subang.',
                    'Surat Keputusan Rektor Universitas Subang Nomor 27/US/X/2013 tentang Kebijakan Pedoman Umum Penyelenggaraan Pendidikan Universitas Subang.',
                    'Keputusan Rektor Universitas Subang Nomor 58/US/XI/2017 Tentang Pelaksanaan Kebijakan Sistem penjaminan Mutu Internal (SPMI) Universitas Subang',
                ],
                memperhatikan: [
                    'Berita Acara rapat Fakultas Ilmu Komputer pada tanggal 13 November 2023 tentang lembaga penjaminan mutu pendidikan.',
                ],
                dictums: [
                    {
                        label: '1.',
                        text: 'Menunjuk dan mengangkat Saudara MAYA DESTRIANI, S.Kom., M.Kom sebagai Ketua Gugus Kendali Mutu (GKM) pada Fakultas dan Saudara CACA ARIF HERDIAN, S.Kom., M.Kom sebagai Ketua Unit Kedali Mutu (UKM) pada Program studi Sistem Informasi Universitas Subang.',
                    },
                    {
                        label: '2.',
                        text: "Tugas dan wewenang Ketua Gugus Kendali Mutu di lingkungan Fakultas adalah sebagai berikut :\n1. Menyiapkan bahan kebijakan sistem penjaminan mutu kepada Dekan;\n2. Mengkoordinasikan penyusunan dokumen-dokumen yang diperlukan dalam rangka pelaksanaan peningkatan mutu akademik dan non akademik;\n3. Memantau dan mengevaluasi pelaksanaan mutu akademik dan non akademik.\n4. Melakukan kajian terhadap upaya peningkatan mutu baik akademik maupun non akademik.\n5. Mempersiapkan Akreditasi, Evaluasi dan Implementasinya.",
                    },
                    {
                        label: '3.',
                        text: "Tugas dan wewenang Katua Unit Kendali Mutu (UKM) di lingkup Fakultas Ilmu Komputer sebagai berikut :\n1. Menyiapkan bahan untuk Ketua GKM dalam menetapkan sistem penjamin mutu internal program studi.\n2. Pelaksanaan dan mengembangkan sistem Unit Kendali Mutu.\n3. Mengkoordinasikan penyusunan dokumen-dokumen yang diperlukan dalam rangka pelaksanaan peningkatan mutu akademik dan non akademik.\n4. Memantau dan mengevaluasi pelaksanaan mutu akademik dan non akademik.\n5. Melakukan kajian terhadap pelaksanaan Unit Kendali Mutu baik akademik maupun non akademik.\n6. Mempersiapkan akreditasi, evaluasi dan implementasinya.",
                    },
                    {
                        label: '4.',
                        text: 'Dalam rangka persiapan dan pelaksanaan akreditasi Fakultas Ilmu Komputer Universitas Subang maka Gugus Kendali Mutu dan Unit Kendali Mutu membantu memberikan bahan dan dokumen mutu yang diperlukan oleh lembaga penjaminan mutu pendidikan.',
                    },
                    {
                        label: '5.',
                        text: 'Segala pembiayaan yang timbul sebagai akibat dan pelaksanaan keputusan ini bersumber pada anggaran pendapatan dan belanja Fakultas untuk Gugus Kendali Mutu (GKM) dan Unit Kendali Mutu (UKM) dan Anggaran Pendapatan dan Belanja Fakultas Ilmu Komputer Universitas Subang untuk Lembaga Penjaminan Mutu Pendidikan (LPMP).',
                    },
                    {
                        label: '6.',
                        text: 'Keputusan ini mulai berlaku sejak tanggal ditetapkan dengan ketentuan apabila dikemudian hari ternyata ada kekeliruan kesalahan dalam penetapannya akan diadakan perubahan dan perbaikan sebagaimana mestinya.',
                    },
                ],
                tembusan_list: [
                    'Yth. Ketua Yayasan Kutawaringin Subang',
                    'Yth. Rektor Universitas Subang',
                    'Arsip.',
                ],
                tembusan_is_numbered: true,
            };
        case 'sk_dekan_pengunduran_diri':
            return {
                about_title: "PENGUNDURAN DIRI MAHASISWA\nDI LINGKUNGAN FAKULTAS ILMU KOMPUTER\nSEMESTER GANJIL TA. 2026/2027",
                study_program: 'Prodi Sistem Informasi',
                semester_type: 'Ganjil',
                academic_year: '2026/2027',
                decision_city: 'Subang',
                decision_date_text: '24 Oktober 2026',
                dekan_name: 'Dr. TEPI PEIRISAL, M.Si',
                menetapkan_text: 'KEPUTUSAN DEKAN FAKULTAS ILMU KOMPUTER TENTANG PENGUNDURAN DIRI MAHASISWA DI LINGKUNGAN FAKULTAS ILMU KOMPUTER',
                menimbang: [
                    'Bahwa dalam rangka tertib administrasi penyelenggaraan Pendidikan dan meningkatkan mutu Pendidikan di lingkungan Fakultas Ilmu Komputer Universitas Subang, dipandang perlu melakukan evaluasi terhadap mahasiswa yang tidak memiliki kriteria akademik;',
                    'Bahwa berdasarkan SK Rektor tentang Peraturan Akademik Universitas Subang Pasal 72 tentang Evaluasi dan Batas Waktu Studi Program Sarjana dimana jika mahasiswa tidak bisa menyelesaikan perkuliahan dengan batas waktu maksimal studi 14 (semester) dan atau melakukan pengunduran diri maka mahasiswa tersebut dinyatakan mengundurkan diri dari Universitas Subang;',
                    'Bahwa berdasarkan hasil evaluasi yang di lakukan, terdapat mahasiswa yang tidak memiliki kriteria akademik di batas waktu studi, sehingga status mahasiswa tersebut perlu dilakukan perubahan menjadi mengundurkan diri dari Fakultas Ilmu Komputer;',
                    'Bahwa perbahan status pengunduran diri mahasiswa Fakultas Ilmu Komputer Universitads Subang sebagaimana tercantum pada butir a,b dan c di atas, maka perlu ditetapkan melalui Keputusan Dekan Fakultas Ilmu Komputer.',
                ],
                mengingat: [
                    'Undang-Undang Nomor 20 Tahun 2003 tentang Sistem Pendidikan Nasional;',
                    'Undang-Undang Nomor 14 Tahun 2005 tentang Guru dan Dosen;',
                    'Undang-Undang Nomor 12 Tahun 2012 tentang Perguruan Tinggi;',
                    'Peraturan Pemerintah Nomor 37 Tahun 2009 tentang Dosen;',
                    'Peraturan Pemerintah Nomor 4 Tahun 2014 tentang Penyelenggaraan Pendidikan dan Pengelolaan Perguruan Tinggi;',
                    'Peraturan Pemerintah Nomor 4 Tahun 2022 tentang Perubahan atas Peraturan Pemerintah Nomor 57 Tahun 2021 tentang Standar Nasional Pendidikan;',
                    'Peraturan Menteri Pendidikan, Kebudayaan, Riset, dan Teknologi Republik Indonesia Nomor 53 Tahun 2023 tentang Penjaminan Mutu Pendidikan Tinggi;',
                    'Peraturan Menteri Pendidikan Nasional Republik Indonesia Nomor : 33/D/O/2005 tentang Penggabungan Sekolah Tinggi Ilmu Administrasi (STIA) Kutawaringin di Subang dan Sekolah Tinggi Teknologi (STT) Kutawaringin di Subang menjadi Universitas Subang serta Penambahan Program Studi Baru yang diselenggarakan oleh Yayasan Kutawaringin Subang di Subang;',
                    'Peraturan Yayasan Kutawaringin Nomor 15 Tahun 2020 Tentang Penetapan Statuta Universitas Subang;',
                    'Surat Keputusan Rektor Universitas Subang Nomor 25/US/X/2013 Tentang Penetapan Perubahan Struktur Organisasi dan Tata Kerja Universitas Subang;',
                    'Surat Keputusan Rektor Nomor 68/US/VII/2022 tentang pedoman Penelitian dan Pengabdian kepada Masyarakat (PkM) Universitas Subang;',
                    'Surat Keputusan Rektor Nomor 93/US/VII/2022 tentang Pedoman Akademik Universitas Subang;',
                    'Surat Keputusan Rektor Nomor 71/US/XI/2023 tentang Pengangkatan Dekan Fakultas Ilmu Komputer Universitas Subang masa jabatan 2023-2027;',
                    'Surat Keputusan Dekan Fakultas Ilmu Komputer Nomor 548/D/FIK-US/X/2025 tentang Pelaksanaan Kegiatan Pengujian Sidang Tugas Akhir/Skripsi Semester Ganjil Tahun Akademik 2025/2026 Program Studi Sistem Informasi S1 Fakultas Ilmu Komputer universitas Subang;',
                    'Berdasarkan Keputusan LAM INFOKOM No. 110/SK/LAM-INFOKOM/Ak/S/VIII/2025, menyatakan bahwa program studi Sistem Informasi pada Program Sarjana Universitas Subang, Kab. Subang Memenuhi Syarat Peringkat AKREDITASI BAIK SEKALI, sejak tanggal 8 Agustus 2025 sampai dengan 8 Agustus 2030.',
                ],
                memperhatikan: [],
                dictums: [
                    {
                        label: 'Pertama',
                        text: 'Menetapkan mahasiswa yang habis masa studi 14 (empat belas) semester untuk program Sarjana (S1) dan mahasiswa yang mengundurkan diri dirubah statusnya menjadi mengundurkan diri.',
                    },
                    {
                        label: 'Kedua',
                        text: 'Nama-nama yang di maksud pada bulir di atas, terlampir pada keputusan ini..',
                    },
                    {
                        label: 'Ketiga',
                        text: 'Kepada seluruh mahasiswa terlampir keputusan ini dinyatakan Mengundurkan Diri dari Fakultas Ilmu Komputer Universitas Subang',
                    },
                    {
                        label: 'Keempat',
                        text: 'Keputusan ini berlaku sejak tanggal di tetapkan dengan ketentuan apabila dikemudian hari ternyata terdapat kekeliruan dalam Keputusan ini akan diperbaiki sebagaimana mestinya.',
                    },
                ],
                tembusan_list: [
                    'Yayasan Kutawaringin Subang',
                    'Rektor Universitas Subang',
                    'Yang bersangkutan untuk diketahui dan seperlunya',
                ],
                attachment_subtitle: "PENGUNDURAN DIRI MAHASISWA DI LINGKUNGAN FAKULTAS ILMU KOMPUTER",
                resignation_matrix: [
                    {
                        npm: 'D1A190012',
                        name: 'ADITYA BUDHI RAHMAT',
                        reason: 'Mengajukan Pengunduran Diri',
                        ipk: '2.10',
                    },
                    {
                        npm: 'D1A190025',
                        name: 'ALIF HIDAYAT',
                        reason: 'Mengajukan Pengunduran Diri',
                        ipk: '1.04',
                    },
                ],
            };
        case 'sk_dekan_dosen_wali':
            return {
                about_title: "PENYELENGGARAAN PERWALIAN/DOSEN WALI\nFAKULTAS ILMU KOMPUTER UNIVERSITAS SUBANG\nTAHUN AKADEMIK 2024/2025",
                study_program: 'Program Studi Sistem Informasi S1',
                semester_type: 'Ganjil',
                academic_year: '2024/2025',
                decision_city: 'Subang',
                decision_date_text: '18 November 2024',
                dekan_name: 'Dr. TEPI PEIRISAL., M.Si.',
                menetapkan_text: '',
                menimbang: [
                    'Bahwa dalam rangka meningkatkan kualitas penyelenggaraan pendidikan dirasa perlu adanya perwalian/dosen wali;',
                    'Bahwa sehubungan dengan butir a, mahasiswa diberi kesempatan untuk melakukan perwalian dalam rangka merencanakan perkuliahan yang telah ditentukan;',
                    'Bahwa sebagai sarana untuk memberikan kesempatan kepada mahasiswa sebagaimana disebutkan dalam butir a dan b perlu diselenggarakan Perwalian Akademik pada Awal Semester Ganjil Tahun Akademik 2024/2025.',
                    'Penyelenggaraan Perwalian Akademik yang dimaksud perlu ditetapkan dengan Keputusan Dekan;',
                ],
                mengingat: [
                    'Undang-undang Nomor 20 Tahun 2003 tentang Sistem Pendidikan Nasional ;',
                    'Peraturan pemerintah Nomor 60 Tahun 1999 Tentang Pendidikan Tinggi.',
                    'Keputusan Menteri Pendidikan Nasional Nomor : 184/U/2001 tentang Pedoman Pengawasan, Pengendalian dan Pembinaan Program Diploma, Sarjana dan Pascasarjana di Perguruan Tinggi.',
                    'Surat Keputusan Menteri Pendidikan Nasional Republik Indonesia Nomor : 33/D/O/2005 tentang Penggabungan Sekolah Tinggi Ilmu Administrasi (STIA) Kutawaringin di Subang dan Sekolah Tinggi Teknologi (STT) Kutawaringin di Subang menjadi Universitas Subang serta Penambahan Program Studi Baru yang diselenggarakan oleh Yayasan Kutawaringin Subang di Subang.',
                ],
                memperhatikan: [
                    'Kurikulum Program Studi Sistem Informasi Universitas Subang',
                    'Kalender Akademik Fakultas Ilmu Komputer Universitas Subang',
                    'Pedoman Akademik Proses Belajar Mengajar di Fakultas Ilmu Komputer Universitas Subang.',
                ],
                dictums: [
                    {
                        label: 'Pertama',
                        text: 'Menyelenggarakan perwalian pada awal Semester Ganjil Tahun Akademik 2024/2025 dengan menunjuk nama-nama dosen wali sebagaimana tercantum pada lampiran Surat Keputusan.',
                    },
                    {
                        label: 'Kedua',
                        text: 'Yang berhak melakukan perwalian adalah mahasiswa yang aktif terdaftar secara akademik dan yang telah melakukan daftar ulang pada semester Ganjil Tahun Akademik 2024/2025.',
                    },
                    {
                        label: 'Ketiga',
                        text: 'Prosedur penyelenggaraan mahasiswa yang akan mengikuti Perwalian Akademik sesuai dengan pedoman akademik proses belajar mengajar di Fakultas Ilmu Komputer Universitas Subang.',
                    },
                    {
                        label: 'Keempat',
                        text: 'Segala pembiayaan sebagai akibat dari pelaksanaan Surat Keputusan ini dibebankan kepada biaya kuliah/praktikum Fakultas Ilmu Komputer Universitas Subang Tahun Akademik 2024/2025.',
                    },
                    {
                        label: 'Kelima',
                        text: 'Keputusan ini mulai berlaku sejak tanggal ditetapkan dan apabila terdapat kekeliruan dalam keputusan ini akan diperbaiki sebagaimana mestinya.',
                    },
                ],
                tembusan_list: [
                    'Yth. Yayasan Kutawaringin Subang',
                    'Yth. Pembantu Rektor Bidang Akademik Kemahasiswaan Universitas Subang',
                    'Arsip',
                ],
                attachment_subtitle: "Penyelenggaraan Perwalian/Dosen Wali Fakultas Ilmu Komputer Universitas Subang Tahun Akademik 2024/2025",
                wali_matrix: [
                    {
                        npm: 'D1A210001',
                        name: 'RIZKI RAMADHAN',
                        advisor: 'Bagus Ali Akbar, S.Si., M.Kom',
                    },
                ],
            };
        case 'sk_dekan_yudisium':
            return {
                about_title: "KELULUSAN UJIAN SIDANG SARJANA\nSEMESTER GENAP TAHUN AKADEMIK 2025/2026\nPROGRAM STUDI SISTEM INFORMASI\nFAKULTAS ILMU KOMPUTER",
                study_program: 'Program Studi Sistem Informasi',
                semester_type: 'Genap',
                academic_year: '2025/2026',
                decision_city: 'Subang',
                decision_date_text: '27 Agustus 2026',
                dekan_name: 'Dr. TEPI PEIRISAL,M.Si',
                menetapkan_text: 'Bahwa nama-nama yang terlampir dalam Surat Keputusan ini dinyatakan Lulus dan telah menyelesaikan Pendidikan Jenjang Sarjana (Strata Satu) Program Studi Sistem Informasi dan berhak menerima Surat Keterangan Lulus (SKL), tindak lanjut proses Penerbitan Ijazah dan dapat mengikuti Wisuda Tahun Akademik 2026/2027;',
                menimbang: [
                    'Bahwa Kelulusan adalah proses penetapan mahasiswa yang dinyatakan telah menyelesaikan pendidikannya dan berhak mengikuti wisuda;',
                    'Bahwa untuk kelancaran pelaksanaan wisuda Tahun Akademik 2025/2026, maka dipandang perlu menetapkan peserta Yudisium Program Studi Sistem Informasi Fakultas Ilmu Komputer Universitas Subang Tahun Akademik 2025/2026;',
                    'Bahwa untuk memenuhi butir-butir tersebut diatas perlu ditetapkan dalam Surat Keputusan Dekan Fakultas Ilmu Komputer Universitas Subang.',
                ],
                mengingat: [
                    'Undang-Undang Nomor 20 Tahun 2003 tentang Sistem Pendidikan Nasional;',
                    'Undang-Undang Nomor 14 Tahun 2005 tentang Guru dan Dosen;',
                    'Undang-Undang Nomor 12 Tahun 2012 tentang Perguruan Tinggi;',
                    'Peraturan Pemerintah Nomor 37 Tahun 2009 tentang Dosen;',
                    'Peraturan Pemerintah Nomor 4 Tahun 2014 tentang Penyelenggaraan Pendidikan dan Pengelolaan Perguruan Tinggi;',
                    'Peraturan Pemerintah Nomor 4 Tahun 2022 tentang Perubahan atas Peraturan Pemerintah Nomor 57 Tahun 2021 tentang Standar Nasional Pendidikan;',
                    'Peraturan Menteri Pendidikan Tinggi, Sains, dan Teknologi Republik Indonesia Nomor 39 Tahun 2025 Tentang Penjaminan Mutu Pendidikan Tinggi;',
                    'Peraturan Menteri Pendidikan Nasional Republik Indonesia Nomor : 33/D/O/2005 tentang Penggabungan Sekolah Tinggi Ilmu Administrasi (STIA) Kutawaringin di Subang dan Sekolah Tinggi Teknologi (STT) Kutawaringin di Subang menjadi Universitas Subang serta Penambahan Program Studi Baru yang diselenggarakan oleh Yayasan Kutawaringin Subang di Subang;',
                    'Peraturan Yayasan Kutawaringin Nomor 15 Tahun 2020 Tentang Penetapan Statuta Universitas Subang;',
                    'Surat Keputusan Rektor Universitas Subang Nomor 25/US/X/2013 Tentang Penetapan Perubahan Struktur Organisasi dan Tata Kerja Universitas Subang;',
                    'Surat Keputusan Rektor Nomor 68/US/VII/2022 tentang pedoman Penelitian dan Pengabdian kepada Masyarakat (PkM) Universitas Subang;',
                    'Surat Keputusan Rektor Nomor 93/US/VII/2022 tentang Pedoman Akademik Universitas Subang;',
                    'Surat Keputusan Rektor Nomor 71/US/XI/2023 tentang Pengangkatan Dekan Fakultas Ilmu Komputer Universitas Subang masa jabatan 2023-2027;',
                    'Berdasarkan Keputusan LAM INFOKOM No. 110/SK/LAM-INFOKOM/Ak/S/VIII/2025, menyatakan bahwa program studi Sistem Informasi pada Program Sarjana Universitas Subang, Kab. Subang Memenuhi Syarat Peringkat AKREDITASI BAIK SEKALI, sejak tanggal 8 Agustus 2025 sampai dengan 8 Agustus 2030.',
                    'Surat Keputusan Dekan Fakultas Ilmu Komputer Nomor 394/D/FIK-US/VIII/2026 tentang Pelaksanaan Kegiatan Pengujian Sidang Tugas Akhir/Skripsi Semester Genap Tahun Akademik 2025/2026 Program Studi Sistem Informasi S1 Fakultas Ilmu Komputer universitas Subang;',
                ],
                memperhatikan: [
                    'Hasil Ujian Sidang Skripsi Program Studi Sistem Informasi pada tanggal 26-27 Agustus 2026.',
                ],
                dictums: [
                    {
                        label: 'Pertama',
                        text: 'Bahwa nama-nama yang terlampir dalam Surat Keputusan ini dinyatakan Lulus dan telah menyelesaikan Pendidikan Jenjang Sarjana (Strata Satu) Program Studi Sistem Informasi dan berhak menerima Surat Keterangan Lulus (SKL), tindak lanjut proses Penerbitan Ijazah dan dapat mengikuti Wisuda Tahun Akademik 2026/2027;',
                    },
                    {
                        label: 'Kedua',
                        text: 'Para Lulusan berhak menggunakan Ijazah dan Gelar Kesarjanaan sesuai dengan Program Studi dan jenjang pendidikannya serta aturan yang berlaku;',
                    },
                    {
                        label: 'Ketiga',
                        text: 'Keputusan ini berlaku sejak tanggal ditetapkan, dengan ketentuan apabila terdapat kekeliruan atau perubahan dikemudian hari, akan diperbaiki sebagaimana mestinya.',
                    },
                ],
                tembusan_list: [
                    'Yayasan Kutawaringin Subang',
                    'Rektor Universitas Subang',
                    'Ka. Biro Akademik dan Kemahasiswaan UNSUB',
                    'Ka. Program Studi Sistem Informasi Fakultas Ilmu Komputer',
                    'Kasubag. Akademik dan Kemahasiswaaan Fakultas Ilmu Komputer',
                ],
                attachment_subtitle: "DAFTAR PESERTA YUDISIUM PROGRAM STUDI SISTEM INFORMASI\nGENAP TAHUN AKADEMIK 2025/2026\nFAKULTAS ILMU KOMPUTER\nUNIVERSITAS SUBANG",
                yudisium_matrix: [
                    {
                        npm: 'D1A210001',
                        name: 'RIZKI RAMADHAN',
                        thesis_title: 'Sistem Informasi Geografis Pemetaan UMKM Berbasis Web di Kabupaten Subang',
                        ipk: '3.85',
                        predicate: 'Dengan Pujian (Cum Laude)',
                    },
                ],
            };
        case 'sk_dekan_pembimbing_mbkm':
        default:
            return {
                about_title: "PENYELENGGARAAN DAN PENETAPAN DOSEN PEMBIMBING BKP MBKM\nSEMESTER GANJIL\nFAKULTAS ILMU KOMPUTER UNIVERSITAS SUBANG\nTAHUN AKADEMIK 2025/2026",
                semester_type: 'Ganjil',
                decision_city: 'Subang',
                decision_date_text: '01 September 2025',
                dekan_name: 'Dr. TEPI PEIRISAL, M. Si.',
                menimbang: [
                    'Bahwa dalam rangka kebijakan merdeka belajar yang tertuang dalam Permendikbud No. 3 Tahun 2020 memberikan hak kepada mahasiswa untuk 3 semester belajar di luar program studinya;',
                    'Bahwa sehubungan dengan butir a, mahasiswa diberi kesempatan untuk mempraktekan ilmunya sesuai dengan program Bentuk Kegiatan Pembelajaran Merdeka Belajar Kampus Merdeka (BKP MBKM) yang dipilih setiap mahasiswa;',
                    'Bahwa sebagai sarana untuk memberikan kesempatan kepada mahasiswa sebagaimana disebutkan dalam butir a dan b tentang program Merdeka Belajar Kampus Merdeka perlu diselenggarakan Bentuk Kegiatan Pembelajaran Merdeka Belajar Kampus Merdeka Tahun Akademik 2022/2023 dengan bimbingan Dosen Pembimbing;',
                    'Penyelenggaraan Bentuk Kegiatan Pembelajaran Merdeka Belajar Kampus Merdeka dan penetapan dosen pembimbing yang dimaksud perlu ditetapkan dengan Keputusan Dekan;',
                ],
                mengingat: [
                    'Undang-undang Republik Indonesia Nomor 20 tahun 2003 tentang Sistem Pendidikan Nasional;',
                    'Undang-undang Republik Indonesia Nomor 12 tahun 2012 tentang Sistem Pendidikan Tinggi;',
                    'Peraturan Presiden Nomor 8 Tahun 2012 tentang kerangka Kualifikasi Nasional Indonesia;',
                    'Peraturan pemerintah No.17 Tahun 2010 Tentang Penyelenggaraan dan Pengelolaan Pendidikan;',
                    'Peraturan Menteri Pendidikan dan Kebudayaan Nomor 3 tahun 2020 tentang Standar Nasional Pendidikan Tinggi;',
                    'Surat Keputusan Menteri Pendidikan Nasional Republik Indonesia Nomor : 33/D/O/2005 tentang Penggabungan Sekolah Tinggi Ilmu Administrasi (STIA) Kutawaringin di Subang dan Sekolah Tinggi Teknologi (STT) Kutawaringin di Subang menjadi Universitas Subang serta Penambahan Program Studi Baru yang diselenggarakan oleh Yayasan Kutawaringin Subang di Subang;',
                    'Surat Keputusan Yayasan Kutawaringin Nomor : 18/Y/XII/2007 tanggal 28 Oktober 2007 tentang Penetapan Statuta Universitas Subang;',
                    'Permendikbud No. 3 Tahun 2020 tentang Standar Nasional Pendidikan Tinggi.',
                ],
                memperhatikan: [
                    'Kurikulum Program Studi Sistem Informasi Universitas Subang',
                    'Kalender Akademik Fakultas Ilmu Komputer Universitas Subang',
                    'Pedoman Akademik Proses Belajar Mengajar di Fakultas Ilmu Komputer Universitas Subang.',
                    'Rapat Akademik tentang Bentuk Kegiatan Pembelajaran Merdeka Belajar Kampus Merdeka (BKP MBKM)',
                ],
                dictums: [
                    {
                        label: 'Pertama',
                        text: 'Menyelenggarakan Program Bentuk Kegiatan Pembelajaran Merdeka Belajar Kampus Merdeka (BKP MBKM) Semester Ganjil Tahun Akademik 2025/2026 dengan menunjuk nama-nama dosen pembimbing sebagaimana tercantum pada lampiran Surat Keputusan',
                    },
                    {
                        label: 'Kedua',
                        text: 'Yang berhak mengikuti Program Bentuk Kegiatan Pembelajaran Merdeka Belajar Kampus Merdeka (BKP MBKM) adalah mahasiswa Fakultas Ilmu Komputer yang telah menempuh 80 sks pada Tahun Akademik 2025/2026.',
                    },
                    {
                        label: 'Ketiga',
                        text: 'Untuk mengikuti Program Bentuk Kegiatan Pembelajaran Merdeka Belajar Kampus Merdeka (BKP MBKM) yang dimaksud pada butir kedua, mahasiswa yang bersangkutan telah terdaftar sebagai mahasiswa Fakultas Ilmu Komputer Universitas Subang Semester Ganjil Tahun Akademik 2025/2026.',
                    },
                    {
                        label: 'Keempat',
                        text: 'Prosedur penyelenggaraan mahasiswa yang akan mengikuti Program Bentuk Kegiatan Pembelajaran Merdeka Belajar Kampus Merdeka (BKP MBKM) sesuai dengan pedoman akademik proses belajar mengajar di Fakultas Ilmu Komputer Universitas Subang.',
                    },
                    {
                        label: 'Kelima',
                        text: 'Segala pembiayaan sebagai akibat dari pelaksanaan Surat Keputusan ini dibebankan kepada biaya kuliah/ praktikum Fakultas Ilmu Komputer Universitas Subang Tahun Akademik 2025/2026.',
                    },
                    {
                        label: 'Keenam',
                        text: 'Keputusan ini mulai berlaku sejak tanggal ditetapkan dan apabila terdapat kekeliruan dalam keputusan ini akan diperbaiki sebagaimana mestinya.',
                    },
                ],
                tembusan_list: [
                    'Yth. Yayasan Kutawaringin Subang',
                    'Yth. Wakil Rektor II Bidang Akademik Kemahasiswaan Universitas Subang',
                ],
                attachment_subtitle: "Daftar Nama Dosen Pembimbing Program Bentuk Kegiatan Pembelajaran\nMerdeka Belajar Kampus Merdeka (BKP MBKM) Semester Ganjil\nFakultas Ilmu Komputer Universitas Subang\nTahun Akademik 2025/2026",
                mbkm_matrix: [
                    {
                        npm: 'D1A210020',
                        name: 'FANNY APRILIANTI',
                        type: 'Studi Independen',
                        company: 'Dicoding Indonesia',
                        position: 'Pengembang Front-End Web dan Back-End',
                        advisor_1: 'Bagus Ali Akbar, S.Si., M.Kom',
                        advisor_2: 'Achmad Syafaat, S.Kom., MT',
                    },
                    {
                        npm: 'D1A210025',
                        name: 'LUNA AMELLA WARDHANI',
                        type: 'Magang Mandiri',
                        company: 'PT. Len Industri (Persero)',
                        position: 'UI/UX & Mobile Developer',
                        advisor_1: 'Ir. Syarif Hidayat, MT',
                        advisor_2: 'Bagus Ali Akbar, S.Si., M.Kom',
                    },
                ],
            };
    }
};

const onSkTypeChange = () => {
    const selected = selectedSkType.value;
    const defaults = getDefaultSkData(selected.code);
    form.extra_data = { ...defaults };
    if (selected.code === 'sk_dekan_gkm_ukm' && activeStep.value === 'matrix') {
        activeStep.value = 'dictums';
    }
    if (form.extra_data.dekan_name) {
        const foundSigner = props.signers.find(s => s.name === form.extra_data.dekan_name || s.id === 'dekan');
        if (foundSigner) {
            form.signer_id = foundSigner.id;
        }
    }
};

const onSignerChange = () => {
    const s = props.signers.find(item => item.id === form.signer_id || item.code === form.signer_id);
    if (s) {
        form.extra_data.dekan_name = s.name;
        if (s.nip) {
            form.extra_data.dekan_nik = s.nip;
        }
    }
    fetchLivePreview();
};

const isManualLetterNumber = ref(false);

const form = useForm({
    letter_type_id: initialType.id || '',
    letter_number: '',
    academic_year: '2025/2026',
    signer_id: 'dekan',
    direct_sign: false,
    extra_data: getDefaultSkData(initialType.code || 'sk_dekan_pembimbing_mbkm'),
});

// UI & Live Syncing
const isLiveSyncing = ref(false);
const previewPdfBase64 = ref(null);
const previewErrorMessage = ref(null);
const lastSyncedAt = ref(null);

const showSkTypeDropdown = ref(false);
const skTypeSearchQuery = ref('');
let syncTimeout = null;

// Filtered SK types by search query
const filteredSkTypes = computed(() => {
    if (!skTypeSearchQuery.value.trim()) return props.skTypes;
    const q = skTypeSearchQuery.value.toLowerCase().trim();
    return props.skTypes.filter((t) => {
        const nameMatch = t.name?.toLowerCase().includes(q);
        const codeMatch = t.letter_code?.toLowerCase().includes(q);
        const descMatch = t.description?.toLowerCase().includes(q);
        return nameMatch || codeMatch || descMatch;
    });
});

const selectSkType = (t) => {
    form.letter_type_id = t.id;
    showSkTypeDropdown.value = false;
    skTypeSearchQuery.value = '';
    
    // Langsung muat default data berdasarkan jenis SK yang dipilih
    const defaults = getDefaultSkData(t.code);
    form.extra_data = { ...defaults };

    if (defaults.academic_year) {
        form.academic_year = defaults.academic_year;
    }

    if (t.code === 'sk_dekan_gkm_ukm' && activeStep.value === 'matrix') {
        activeStep.value = 'dictums';
    }

    if (form.extra_data.dekan_name) {
        const foundSigner = props.signers.find(s => s.name === form.extra_data.dekan_name || s.id === 'dekan');
        if (foundSigner) {
            form.signer_id = foundSigner.id;
        }
    }

    fetchLivePreview();
};

// Fetch Live Preview
const fetchLivePreview = async () => {
    isLiveSyncing.value = true;
    previewErrorMessage.value = null;

    try {
        const res = await window.axios.post(route('letters.sk-studio.preview'), {
            letter_type_id: form.letter_type_id,
            letter_number: form.letter_number,
            academic_year: form.academic_year,
            signer_id: form.signer_id,
            extra_data: form.extra_data,
        });

        if (res.data?.success && res.data?.pdfBase64) {
            previewPdfBase64.value = res.data.pdfBase64;
            lastSyncedAt.value = new Date().toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
            });
        } else {
            previewErrorMessage.value = res.data?.message || 'Format pratinjau SK tidak valid.';
        }
    } catch (e) {
        console.error('Error previewing SK:', e);
        previewErrorMessage.value = e.response?.data?.message || 'Gagal merender simulasi SK.';
    } finally {
        isLiveSyncing.value = false;
    }
};

const triggerDebouncedSync = () => {
    clearTimeout(syncTimeout);
    syncTimeout = setTimeout(() => {
        fetchLivePreview();
    }, 450);
};

watch(
    () => form,
    () => {
        triggerDebouncedSync();
    },
    { deep: true }
);

// Helpers for Considerations
const addMenimbang = () => {
    form.extra_data.menimbang.push('');
};
const removeMenimbang = (idx) => {
    form.extra_data.menimbang.splice(idx, 1);
};

const addMengingat = () => {
    form.extra_data.mengingat.push('');
};
const removeMengingat = (idx) => {
    form.extra_data.mengingat.splice(idx, 1);
};

const addMemperhatikan = () => {
    form.extra_data.memperhatikan.push('');
};
const removeMemperhatikan = (idx) => {
    form.extra_data.memperhatikan.splice(idx, 1);
};

// Helpers for Dictums
const addDictum = () => {
    const isNumbered = selectedSkType.value?.code === 'sk_dekan_gkm_ukm';
    const nextIdx = form.extra_data.dictums?.length || 0;
    let nextLabel = '';
    if (isNumbered) {
        nextLabel = `${nextIdx + 1}.`;
    } else {
        const labels = ['Pertama', 'Kedua', 'Ketiga', 'Keempat', 'Kelima', 'Keenam', 'Ketujuh', 'Kedelapan', 'Kesembilan', 'Kesepuluh'];
        nextLabel = labels[nextIdx] || `Ke-${nextIdx + 1}`;
    }
    if (!Array.isArray(form.extra_data.dictums)) {
        form.extra_data.dictums = [];
    }
    form.extra_data.dictums.push({
        label: nextLabel,
        text: '',
    });
};
const removeDictum = (idx) => {
    form.extra_data.dictums.splice(idx, 1);
};

// Helpers for Matrix Rows
const addMatrixRow = () => {
    if (!Array.isArray(form.extra_data.mbkm_matrix)) {
        form.extra_data.mbkm_matrix = [];
    }
    form.extra_data.mbkm_matrix.push({
        npm: '',
        name: '',
        type: 'Studi Independen',
        company: '',
        position: '',
        advisor_1: 'Bagus Ali Akbar, S.Si., M.Kom',
        advisor_2: '',
    });
};
const removeMatrixRow = (idx) => {
    form.extra_data.mbkm_matrix.splice(idx, 1);
};

// Helpers for TA Matrix Rows (SK Dosen Pembimbing Tugas Akhir)
const addTaRow = () => {
    if (!Array.isArray(form.extra_data.ta_matrix)) {
        form.extra_data.ta_matrix = [];
    }
    form.extra_data.ta_matrix.push({
        npm: '',
        name: '',
        title: '',
        advisor_1: 'Dr. TEPI PEIRISAL, M.Si',
        advisor_2: '',
    });
};
const removeTaRow = (idx) => {
    form.extra_data.ta_matrix.splice(idx, 1);
};

// Helpers for Dosen Wali Matrix Rows (SK Dosen Wali Akademik)
const addWaliRow = () => {
    if (!Array.isArray(form.extra_data.wali_matrix)) {
        form.extra_data.wali_matrix = [];
    }
    form.extra_data.wali_matrix.push({
        npm: '',
        name: '',
        advisor: 'Bagus Ali Akbar, S.Si., M.Kom',
    });
};
const removeWaliRow = (idx) => {
    form.extra_data.wali_matrix.splice(idx, 1);
};

const activeWaliStudentDropdownIdx = ref(null);
const waliStudentSearchQuery = ref('');
const activeWaliAdvisorDropdownIdx = ref(null);
const waliAdvisorSearchQuery = ref('');

const selectWaliStudent = (rIdx, student) => {
    if (form.extra_data?.wali_matrix?.[rIdx]) {
        form.extra_data.wali_matrix[rIdx].npm = student.npm || '';
        form.extra_data.wali_matrix[rIdx].name = student.name || '';
    }
    activeWaliStudentDropdownIdx.value = null;
    waliStudentSearchQuery.value = '';
};

const filteredWaliStudents = computed(() => {
    if (!props.students) return [];
    if (!waliStudentSearchQuery.value) return props.students.slice(0, 10);
    const q = waliStudentSearchQuery.value.toLowerCase();
    return props.students.filter(
        (s) => s.name?.toLowerCase().includes(q) || s.npm?.toLowerCase().includes(q)
    ).slice(0, 15);
});

const selectWaliAdvisor = (rIdx, lecturer) => {
    if (form.extra_data?.wali_matrix?.[rIdx]) {
        form.extra_data.wali_matrix[rIdx].advisor = lecturer.name;
    }
    activeWaliAdvisorDropdownIdx.value = null;
    waliAdvisorSearchQuery.value = '';
};

const filteredWaliAdvisors = computed(() => {
    if (!props.lecturers) return [];
    if (!waliAdvisorSearchQuery.value) return props.lecturers.slice(0, 10);
    const q = waliAdvisorSearchQuery.value.toLowerCase();
    return props.lecturers.filter(
        (l) => l.name?.toLowerCase().includes(q) || l.nidn?.toLowerCase().includes(q)
    ).slice(0, 15);
});

// Helpers for Seminar TA Matrix Rows (SK Pelaksanaan Pengujian Seminar Tugas Akhir)
const addSeminarOpeningRow = () => {
    if (!Array.isArray(form.extra_data.seminar_matrix)) {
        form.extra_data.seminar_matrix = [];
    }
    form.extra_data.seminar_matrix.push({
        is_header_row: true,
        time: '07.30 – 08.00',
        note: 'Persiapan dan Pembukaan Seminar',
    });
};

const addSeminarStudentRow = () => {
    if (!Array.isArray(form.extra_data.seminar_matrix)) {
        form.extra_data.seminar_matrix = [];
    }
    form.extra_data.seminar_matrix.push({
        is_header_row: false,
        time: '08.00 – 08.45',
        npm: '',
        name: '',
        thesis_title: '',
        advisor_1: '',
        advisor_2: '',
        examiner_1: '',
        examiner_2: '',
    });
};

const removeSeminarRow = (idx) => {
    form.extra_data.seminar_matrix.splice(idx, 1);
};

const activeSeminarStudentDropdownIdx = ref(null);
const seminarStudentSearchQuery = ref('');
const activeSeminarLecturerDropdownKey = ref(null); // `${rIdx}-adv1`, `${rIdx}-adv2`, `${rIdx}-ex1`, `${rIdx}-ex2`
const seminarLecturerSearchQuery = ref('');

const selectSeminarStudent = (rIdx, student) => {
    if (form.extra_data?.seminar_matrix?.[rIdx]) {
        form.extra_data.seminar_matrix[rIdx].npm = student.npm || '';
        form.extra_data.seminar_matrix[rIdx].name = student.name || '';
    }
    activeSeminarStudentDropdownIdx.value = null;
    seminarStudentSearchQuery.value = '';
};

const filteredSeminarStudents = computed(() => {
    if (!props.students) return [];
    if (!seminarStudentSearchQuery.value) return props.students.slice(0, 10);
    const q = seminarStudentSearchQuery.value.toLowerCase();
    return props.students.filter(
        (s) => s.name?.toLowerCase().includes(q) || s.npm?.toLowerCase().includes(q)
    ).slice(0, 15);
});

const selectSeminarLecturer = (rIdx, fieldName, lecturer) => {
    if (form.extra_data?.seminar_matrix?.[rIdx]) {
        form.extra_data.seminar_matrix[rIdx][fieldName] = lecturer.name;
    }
    activeSeminarLecturerDropdownKey.value = null;
    seminarLecturerSearchQuery.value = '';
};

const filteredSeminarLecturers = computed(() => {
    if (!props.lecturers) return [];
    if (!seminarLecturerSearchQuery.value) return props.lecturers.slice(0, 10);
    const q = seminarLecturerSearchQuery.value.toLowerCase();
    return props.lecturers.filter(
        (l) => l.name?.toLowerCase().includes(q) || l.nidn?.toLowerCase().includes(q)
    ).slice(0, 15);
});

// Helpers for Sidang TA Matrix Rows (SK Pelaksanaan Pengujian Sidang Tugas Akhir)
const addSidangOpeningRow = () => {
    if (!Array.isArray(form.extra_data.sidang_matrix)) {
        form.extra_data.sidang_matrix = [];
    }
    form.extra_data.sidang_matrix.push({
        is_header_row: true,
        time: '07.30 – 08.00',
        note: 'Persiapan dan Pembukaan Sidang',
    });
};

const addSidangStudentRow = () => {
    if (!Array.isArray(form.extra_data.sidang_matrix)) {
        form.extra_data.sidang_matrix = [];
    }
    form.extra_data.sidang_matrix.push({
        is_header_row: false,
        time: '08.00 – 09.00',
        npm: '',
        name: '',
        thesis_title: '',
        advisor_1: '',
        advisor_2: '',
        examiner_1: '',
        examiner_2: '',
    });
};

const removeSidangRow = (idx) => {
    form.extra_data.sidang_matrix.splice(idx, 1);
};

const activeSidangStudentDropdownIdx = ref(null);
const sidangStudentSearchQuery = ref('');
const activeSidangLecturerDropdownKey = ref(null);
const sidangLecturerSearchQuery = ref('');

const selectSidangStudent = (rIdx, student) => {
    if (form.extra_data?.sidang_matrix?.[rIdx]) {
        form.extra_data.sidang_matrix[rIdx].npm = student.npm || '';
        form.extra_data.sidang_matrix[rIdx].name = student.name || '';
    }
    activeSidangStudentDropdownIdx.value = null;
    sidangStudentSearchQuery.value = '';
};

const filteredSidangStudents = computed(() => {
    if (!props.students) return [];
    if (!sidangStudentSearchQuery.value) return props.students.slice(0, 10);
    const q = sidangStudentSearchQuery.value.toLowerCase();
    return props.students.filter(
        (s) => s.name?.toLowerCase().includes(q) || s.npm?.toLowerCase().includes(q)
    ).slice(0, 15);
});

const selectSidangLecturer = (rIdx, fieldName, lecturer) => {
    if (form.extra_data?.sidang_matrix?.[rIdx]) {
        form.extra_data.sidang_matrix[rIdx][fieldName] = lecturer.name;
    }
    activeSidangLecturerDropdownKey.value = null;
    sidangLecturerSearchQuery.value = '';
};

const filteredSidangLecturers = computed(() => {
    if (!props.lecturers) return [];
    if (!sidangLecturerSearchQuery.value) return props.lecturers.slice(0, 10);
    const q = sidangLecturerSearchQuery.value.toLowerCase();
    return props.lecturers.filter(
        (l) => l.name?.toLowerCase().includes(q) || l.nidn?.toLowerCase().includes(q)
    ).slice(0, 15);
});

// Helpers for Resignation Matrix Rows (SK Pengunduran Diri Mahasiswa)
const addResignationRow = () => {
    if (!Array.isArray(form.extra_data.resignation_matrix)) {
        form.extra_data.resignation_matrix = [];
    }
    form.extra_data.resignation_matrix.push({
        npm: '',
        name: '',
        reason: 'Mengajukan Pengunduran Diri',
        ipk: '2.00',
    });
};

const removeResignationRow = (idx) => {
    form.extra_data.resignation_matrix.splice(idx, 1);
};

const activeResignationStudentDropdownIdx = ref(null);
const resignationStudentSearchQuery = ref('');

const selectResignationStudent = (rIdx, student) => {
    if (form.extra_data?.resignation_matrix?.[rIdx]) {
        form.extra_data.resignation_matrix[rIdx].npm = student.npm || '';
        form.extra_data.resignation_matrix[rIdx].name = student.name || '';
    }
    activeResignationStudentDropdownIdx.value = null;
    resignationStudentSearchQuery.value = '';
};

const filteredResignationStudents = computed(() => {
    if (!props.students) return [];
    if (!resignationStudentSearchQuery.value) return props.students.slice(0, 10);
    const q = resignationStudentSearchQuery.value.toLowerCase();
    return props.students.filter(
        (s) => s.name?.toLowerCase().includes(q) || s.npm?.toLowerCase().includes(q)
    ).slice(0, 15);
});

// Helpers for Yudisium Matrix Rows (SK Kelulusan / Yudisium)
const addYudisiumRow = () => {
    if (!Array.isArray(form.extra_data.yudisium_matrix)) {
        form.extra_data.yudisium_matrix = [];
    }
    form.extra_data.yudisium_matrix.push({
        npm: '',
        name: '',
        thesis_title: '',
        ipk: '3.50',
        predicate: 'Sangat Memuaskan',
    });
};
const removeYudisiumRow = (idx) => {
    form.extra_data.yudisium_matrix.splice(idx, 1);
};

const activeYudisiumStudentDropdownIdx = ref(null);
const yudisiumStudentSearchQuery = ref('');

const selectYudisiumStudent = (rIdx, student) => {
    if (form.extra_data?.yudisium_matrix?.[rIdx]) {
        form.extra_data.yudisium_matrix[rIdx].npm = student.npm || '';
        form.extra_data.yudisium_matrix[rIdx].name = student.name || '';
    }
    activeYudisiumStudentDropdownIdx.value = null;
    yudisiumStudentSearchQuery.value = '';
};

const filteredYudisiumStudents = computed(() => {
    if (!props.students) return [];
    if (!yudisiumStudentSearchQuery.value) return props.students.slice(0, 10);
    const q = yudisiumStudentSearchQuery.value.toLowerCase();
    return props.students.filter(
        (s) => s.name?.toLowerCase().includes(q) || s.npm?.toLowerCase().includes(q)
    ).slice(0, 15);
});

// Autocomplete helpers for TA matrix
const activeTaStudentDropdownIdx = ref(null);
const taStudentSearchQuery = ref('');
const activeTaAdvisorDropdownKey = ref(null); // `${rIdx}-1` or `${rIdx}-2`
const taAdvisorSearchQuery = ref('');

const selectTaStudent = (rIdx, student) => {
    if (form.extra_data?.ta_matrix?.[rIdx]) {
        form.extra_data.ta_matrix[rIdx].npm = student.npm || '';
        form.extra_data.ta_matrix[rIdx].name = student.name || '';
    }
    activeTaStudentDropdownIdx.value = null;
    taStudentSearchQuery.value = '';
};

const filteredTaStudents = computed(() => {
    if (!props.students) return [];
    if (!taStudentSearchQuery.value) return props.students.slice(0, 10);
    const q = taStudentSearchQuery.value.toLowerCase();
    return props.students.filter(
        (s) => s.name?.toLowerCase().includes(q) || s.npm?.toLowerCase().includes(q)
    ).slice(0, 15);
});

const selectTaAdvisor = (rIdx, advisorNum, lecturer) => {
    if (form.extra_data?.ta_matrix?.[rIdx]) {
        if (advisorNum === 1) {
            form.extra_data.ta_matrix[rIdx].advisor_1 = lecturer.name;
        } else {
            form.extra_data.ta_matrix[rIdx].advisor_2 = lecturer.name;
        }
    }
    activeTaAdvisorDropdownKey.value = null;
    taAdvisorSearchQuery.value = '';
};

const filteredTaAdvisors = computed(() => {
    if (!props.lecturers) return [];
    if (!taAdvisorSearchQuery.value) return props.lecturers.slice(0, 10);
    const q = taAdvisorSearchQuery.value.toLowerCase();
    return props.lecturers.filter(
        (l) => l.name?.toLowerCase().includes(q) || l.nidn?.toLowerCase().includes(q)
    ).slice(0, 15);
});

// Helpers for Teaching Schedule Lecturers (SK Dosen Mengajar)
const activeLecturerDropdownIdx = ref(null);
const lecturerSearchQuery = ref('');
const activeCourseDropdownKey = ref(null); // format: `${lIdx}-${cIdx}`
const courseSearchQuery = ref('');

const openLecturerDropdown = (lIdx) => {
    activeLecturerDropdownIdx.value = lIdx;
    lecturerSearchQuery.value = '';
};

const selectMasterLecturer = (lIdx, masterLecturer) => {
    if (form.extra_data?.teaching_lecturers?.[lIdx]) {
        form.extra_data.teaching_lecturers[lIdx].name = masterLecturer.name;
    }
    activeLecturerDropdownIdx.value = null;
    lecturerSearchQuery.value = '';
};

const filteredMasterLecturers = computed(() => {
    if (!props.lecturers) return [];
    if (!lecturerSearchQuery.value) return props.lecturers.slice(0, 10);
    const q = lecturerSearchQuery.value.toLowerCase();
    return props.lecturers.filter(
        (l) => l.name?.toLowerCase().includes(q) || l.nidn?.toLowerCase().includes(q)
    ).slice(0, 15);
});

const openCourseDropdown = (lIdx, cIdx) => {
    activeCourseDropdownKey.value = `${lIdx}-${cIdx}`;
    courseSearchQuery.value = '';
};

// Autocomplete helpers for GKM & UKM Chairmen
const activeGkmDropdown = ref(false);
const gkmSearchQuery = ref('');
const activeUkmDropdown = ref(false);
const ukmSearchQuery = ref('');

const filteredGkmLecturers = computed(() => {
    if (!props.lecturers) return [];
    if (!gkmSearchQuery.value) return props.lecturers.slice(0, 10);
    const q = gkmSearchQuery.value.toLowerCase();
    return props.lecturers.filter(
        (l) => l.name?.toLowerCase().includes(q) || l.nidn?.toLowerCase().includes(q)
    ).slice(0, 15);
});

const filteredUkmLecturers = computed(() => {
    if (!props.lecturers) return [];
    if (!ukmSearchQuery.value) return props.lecturers.slice(0, 10);
    const q = ukmSearchQuery.value.toLowerCase();
    return props.lecturers.filter(
        (l) => l.name?.toLowerCase().includes(q) || l.nidn?.toLowerCase().includes(q)
    ).slice(0, 15);
});

const selectGkmLecturer = (lecturer) => {
    form.extra_data.gkm_chairman_name = lecturer.name;
    activeGkmDropdown.value = false;
    gkmSearchQuery.value = '';
    updateGkmDictumText();
};

const selectUkmLecturer = (lecturer) => {
    form.extra_data.ukm_chairman_name = lecturer.name;
    activeUkmDropdown.value = false;
    ukmSearchQuery.value = '';
    updateGkmDictumText();
};

const updateGkmDictumText = () => {
    if (selectedSkType.value?.code === 'sk_dekan_gkm_ukm' && Array.isArray(form.extra_data?.dictums) && form.extra_data.dictums.length > 0) {
        const gkm = form.extra_data.gkm_chairman_name || '...';
        const ukm = form.extra_data.ukm_chairman_name || '...';
        form.extra_data.dictums[0].text = `Menunjuk dan mengangkat Saudara ${gkm} sebagai Ketua Gugus Kendali Mutu (GKM) pada Fakultas dan Saudara ${ukm} sebagai Ketua Unit Kedali Mutu (UKM) pada Program studi Sistem Informasi Universitas Subang.`;
    }
};

const selectMasterCourse = (lIdx, cIdx, masterCourse) => {
    if (form.extra_data?.teaching_lecturers?.[lIdx]?.courses?.[cIdx]) {
        const c = form.extra_data.teaching_lecturers[lIdx].courses[cIdx];
        c.name = masterCourse.name;
        if (masterCourse.sks) c.sks = String(masterCourse.sks);
        if (masterCourse.semester) c.semester = String(masterCourse.semester);
    }
    activeCourseDropdownKey.value = null;
    courseSearchQuery.value = '';
};

const filteredMasterCourses = computed(() => {
    if (!props.courses) return [];
    if (!courseSearchQuery.value) return props.courses.slice(0, 10);
    const q = courseSearchQuery.value.toLowerCase();
    return props.courses.filter(
        (c) => c.name?.toLowerCase().includes(q) || c.code?.toLowerCase().includes(q)
    ).slice(0, 15);
});

const addTeachingLecturer = () => {
    if (!Array.isArray(form.extra_data.teaching_lecturers)) {
        form.extra_data.teaching_lecturers = [];
    }
    form.extra_data.teaching_lecturers.push({
        name: '',
        courses: [
            { name: '', sks: '3', semester: '1', class: 'A' },
        ],
    });
};
const removeTeachingLecturer = (lIdx) => {
    form.extra_data.teaching_lecturers.splice(lIdx, 1);
};

const addCourseToLecturer = (lIdx) => {
    if (!form.extra_data.teaching_lecturers[lIdx].courses) {
        form.extra_data.teaching_lecturers[lIdx].courses = [];
    }
    form.extra_data.teaching_lecturers[lIdx].courses.push({
        name: '',
        sks: '3',
        semester: '1',
        class: 'A',
    });
};
const removeCourseFromLecturer = (lIdx, cIdx) => {
    form.extra_data.teaching_lecturers[lIdx].courses.splice(cIdx, 1);
};

// Helpers for Tembusan
const addTembusan = () => {
    form.extra_data.tembusan_list.push('');
};
const removeTembusan = (idx) => {
    form.extra_data.tembusan_list.splice(idx, 1);
};

const submitSk = (directSign = false) => {
    form.direct_sign = directSign;
    form.post(route('letters.sk-studio.store'), {
        preserveScroll: true,
        onError: (err) => {
            console.error('Validation errors:', err);
        },
    });
};

onMounted(() => {
    fetchLivePreview();
});
</script>

<template>
    <Head title="Studio Surat Keputusan (SK)" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 text-xs font-medium text-slate-500 dark:text-slate-400">
                        <Link :href="route('letters.index')" class="hover:text-indigo-600 transition-colors">
                            Layanan Persuratan
                        </Link>
                        <span>/</span>
                        <span class="text-indigo-600 dark:text-indigo-400 font-semibold">Studio SK</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div>
                            <h2 class="text-xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
                                Studio Surat Keputusan (SK)
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
                                    ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-xs'
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
                                    ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-xs'
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
                                    ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-xs'
                                    : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'
                            ]"
                            title="Fokus Pratinjau PDF (35:65)"
                        >
                            Preview Luas
                        </button>
                    </div>

                    <button
                        type="button"
                        @click="fetchLivePreview"
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
                        @click="submitSk(false)"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 transition-colors"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                        </svg>
                        <span>Simpan Draf</span>
                    </button>
                </div>
            </div>
        </template>

        <!-- FULL-WIDTH STUDIO CONTAINER -->
        <div class="pb-10 pt-4">
            <div class="w-full px-4 sm:px-6 lg:px-8 2xl:px-12">
                <!-- MAIN GRID: FLUID SPLIT COLUMNS -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                    <!-- LEFT COLUMN: TABBED FORM EDITOR -->
                    <div :class="[editorColClass, 'space-y-4 transition-all duration-300']">
                        <!-- STEP NAVIGATION PILLS -->
                        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-xs p-1.5 flex flex-wrap gap-1">
                            <button
                                type="button"
                                @click="activeStep = 'info'"
                                :class="[
                                    'flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold transition-all',
                                    activeStep === 'info'
                                        ? 'bg-indigo-600 text-white shadow-sm'
                                        : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
                                ]"
                            >
                                <span>1. Info & Judul SK</span>
                            </button>
                            <button
                                type="button"
                                @click="activeStep = 'considerations'"
                                :class="[
                                    'flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold transition-all',
                                    activeStep === 'considerations'
                                        ? 'bg-indigo-600 text-white shadow-sm'
                                        : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
                                ]"
                            >
                                <span>2. Konsiderans</span>
                            </button>
                            <button
                                type="button"
                                @click="activeStep = 'dictums'"
                                :class="[
                                    'flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold transition-all',
                                    activeStep === 'dictums'
                                        ? 'bg-indigo-600 text-white shadow-sm'
                                        : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
                                ]"
                            >
                                <span>3. Diktum Putusan</span>
                            </button>
                            <button
                                v-if="selectedSkType.code !== 'sk_dekan_gkm_ukm'"
                                type="button"
                                @click="activeStep = 'matrix'"
                                :class="[
                                    'flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold transition-all',
                                    activeStep === 'matrix'
                                        ? 'bg-indigo-600 text-white shadow-sm'
                                        : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
                                ]"
                            >
                                <span>4. Matriks Lampiran</span>
                            </button>
                            <button
                                type="button"
                                @click="activeStep = 'sign'"
                                :class="[
                                    'flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold transition-all',
                                    activeStep === 'sign'
                                        ? 'bg-indigo-600 text-white shadow-sm'
                                        : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
                                ]"
                            >
                                <span>5. TTD & Tembusan</span>
                            </button>
                        </div>

                        <!-- Form Card Container -->
                        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-xs p-5 space-y-5">

                        <!-- TAB 1: INFO & JUDUL SK -->
                        <div v-show="activeStep === 'info'" class="space-y-4">
                            <!-- DROPDOWN PILIHAN JENIS SK DEKAN (SEARCHABLE) -->
                            <div class="p-4 rounded-xl bg-gradient-to-r from-indigo-50/70 to-purple-50/70 dark:from-indigo-950/40 dark:to-purple-950/40 border border-indigo-100 dark:border-indigo-900/60 space-y-2">
                                <div class="flex items-center justify-between">
                                    <label class="block text-xs font-bold text-indigo-950 dark:text-indigo-200 uppercase tracking-wider">
                                        Pilih Format / Jenis SK Dekan
                                    </label>
                                    <span class="text-[10px] font-semibold text-indigo-600 dark:text-indigo-400">
                                        {{ skTypes.length }} Format Tersedia
                                    </span>
                                </div>

                                <!-- Searchable Custom Dropdown Trigger -->
                                <div class="relative">
                                    <button
                                        type="button"
                                        @click="showSkTypeDropdown = !showSkTypeDropdown"
                                        :class="[
                                            'w-full text-left rounded-xl border p-2.5 sm:p-3 transition flex items-center justify-between gap-2 shadow-2xs',
                                            showSkTypeDropdown
                                                ? 'border-indigo-600 ring-2 ring-indigo-600/20 bg-indigo-50/30 dark:bg-indigo-950/30'
                                                : 'border-indigo-200 dark:border-indigo-800 bg-white dark:bg-slate-900 hover:border-indigo-300 dark:hover:border-indigo-700'
                                        ]"
                                    >
                                        <div class="flex items-center gap-2 min-w-0">
                                            <span class="shrink-0 px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800/60">
                                                {{ selectedSkType.letter_code || 'D/FIK-US' }}
                                            </span>
                                            <span class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white truncate">
                                                {{ selectedSkType.name || 'Pilih Jenis SK Dekan' }}
                                            </span>
                                        </div>
                                        <svg
                                            :class="['w-4 h-4 text-slate-400 shrink-0 transition-transform duration-200', showSkTypeDropdown ? 'rotate-180 text-indigo-600' : '']"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>

                                    <!-- Backdrop Overlay for Outside Click -->
                                    <div
                                        v-if="showSkTypeDropdown"
                                        @click="showSkTypeDropdown = false"
                                        class="fixed inset-0 z-30"
                                    ></div>

                                    <!-- Dropdown Menu with Search Input -->
                                    <div
                                        v-if="showSkTypeDropdown"
                                        class="absolute left-0 right-0 top-full mt-1.5 z-40 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xl overflow-hidden animate-in fade-in zoom-in-95 duration-150"
                                    >
                                        <!-- Search Input Header -->
                                        <div class="p-2.5 border-b border-slate-100 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-900">
                                            <div class="relative">
                                                <input
                                                    v-model="skTypeSearchQuery"
                                                    type="text"
                                                    placeholder="Cari format SK (misal: Mengajar, MBKM, Tugas Akhir, Pembimbing)..."
                                                    class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white pl-8 pr-3 py-2 focus:ring-2 focus:ring-indigo-600 focus:border-indigo-600 placeholder-slate-400 font-normal"
                                                    autofocus
                                                    @keydown.esc="showSkTypeDropdown = false"
                                                />
                                                <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                </svg>
                                            </div>
                                        </div>

                                        <!-- Results List -->
                                        <div class="max-h-64 overflow-y-auto p-1.5 space-y-0.5 divide-y divide-slate-100 dark:divide-slate-800 bg-white dark:bg-slate-900 font-normal">
                                            <div
                                                v-for="t in filteredSkTypes"
                                                :key="t.id"
                                                @click="selectSkType(t)"
                                                :class="[
                                                    'p-2.5 rounded-xl cursor-pointer transition flex items-center justify-between gap-3 text-xs',
                                                    t.id === form.letter_type_id
                                                        ? 'bg-indigo-50 dark:bg-indigo-950/70 text-indigo-900 dark:text-indigo-200 font-semibold'
                                                        : 'hover:bg-slate-50 dark:hover:bg-slate-800/80 text-slate-700 dark:text-slate-300'
                                                ]"
                                            >
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <span class="shrink-0 px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                                        {{ t.letter_code || 'D/FIK-US' }}
                                                    </span>
                                                    <span class="truncate">{{ t.name }}</span>
                                                </div>
                                                <svg
                                                    v-if="t.id === form.letter_type_id"
                                                    class="w-4 h-4 text-indigo-600 dark:text-indigo-400 shrink-0"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>

                                            <!-- Empty State -->
                                            <div
                                                v-if="filteredSkTypes.length === 0"
                                                class="p-6 text-center text-xs text-slate-400 space-y-1 font-normal"
                                            >
                                                <p class="font-medium">Format SK tidak ditemukan</p>
                                                <p class="text-[11px] text-slate-500">Coba kata kunci pencarian yang lain.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <p class="text-[11px] text-slate-500 dark:text-slate-400 italic">
                                    {{ selectedSkType.description || 'Format baku Surat Keputusan Dekan Fasilkom Universitas Subang.' }}
                                </p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div class="space-y-1.5">
                                    <div class="flex items-center justify-between">
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                                            Nomor Surat Keputusan (SK)
                                        </label>
                                        <button
                                            type="button"
                                            @click="isManualLetterNumber = !isManualLetterNumber; if (!isManualLetterNumber) { form.letter_number = ''; triggerDebouncedSync(); }"
                                            class="text-[11px] font-medium text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1"
                                        >
                                            <span>{{ isManualLetterNumber ? 'Gunakan Nomor Otomatis' : 'Input Manual Nomor SK' }}</span>
                                        </button>
                                    </div>
                                    <div v-if="!isManualLetterNumber" class="flex items-center justify-between px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-dashed border-slate-300 dark:border-slate-700 text-xs">
                                        <div class="flex items-center gap-2 text-slate-600 dark:text-slate-300 font-mono">
                                            <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                            <span class="font-bold text-emerald-700 dark:text-emerald-400">Otomatis oleh Sistem</span>
                                            <span class="text-slate-400 text-[11px]">([Nomor]/{{ selectedSkType.letter_code || 'D/FIK-US' }}/...)</span>
                                        </div>
                                        <button
                                            type="button"
                                            @click="isManualLetterNumber = true"
                                            class="px-2 py-0.5 text-[10px] font-semibold text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-950/60 rounded-md hover:bg-indigo-100"
                                        >
                                            Ubah
                                        </button>
                                    </div>
                                    <div v-else class="space-y-1">
                                        <input
                                            v-model="form.letter_number"
                                            @input="triggerDebouncedSync"
                                            type="text"
                                            placeholder="Contoh: 394/D/FIK-US/VIII/2026"
                                            class="w-full text-xs rounded-xl border border-indigo-300 dark:border-indigo-600 bg-white dark:bg-slate-800 p-2.5 font-mono font-bold focus:ring-indigo-500 focus:border-indigo-500"
                                        />
                                        <p class="text-[10px] text-slate-400">
                                            Masukkan nomor SK resmi jika sudah memiliki nomor agenda dari buku register TU.
                                        </p>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                        Tahun Akademik
                                    </label>
                                    <input
                                        v-model="form.academic_year"
                                        @input="triggerDebouncedSync"
                                        type="text"
                                        placeholder="2025/2026"
                                        class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-bold"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                        Semester
                                    </label>
                                    <select
                                        v-model="form.extra_data.semester_type"
                                        class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                                    >
                                        <option value="Ganjil">Ganjil</option>
                                        <option value="Genap">Genap</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                        Tanggal Penetapan SK
                                    </label>
                                    <input
                                        v-model="form.extra_data.decision_date_text"
                                        type="text"
                                        placeholder="01 September 2025"
                                        class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                                    />
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                        Judul SK (Bagian TENTANG) <span class="text-rose-500">*</span>
                                    </label>
                                    <textarea
                                        v-model="form.extra_data.about_title"
                                        rows="3"
                                        placeholder="PENYELENGGARAAN DAN PENETAPAN DOSEN PEMBIMBING BKP MBKM..."
                                        class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-bold uppercase"
                                    ></textarea>
                                </div>
                                <div v-if="selectedSkType.code === 'sk_dekan_dosen_mengajar' || selectedSkType.code === 'sk_dekan_yudisium' || selectedSkType.code === 'sk_dekan_penguji_sidang_ta' || selectedSkType.code === 'sk_dekan_pengujian_sidang_skripsi' || selectedSkType.code === 'sk_dekan_pengunduran_diri' || selectedSkType.code === 'sk_dekan_gkm_ukm'" class="sm:col-span-2">
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                        Program Studi
                                    </label>
                                    <input
                                        v-model="form.extra_data.study_program"
                                        type="text"
                                        placeholder="PROGRAM STUDI SISTEM INFORMASI"
                                        class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-bold uppercase"
                                    />
                                </div>
                                <div v-if="selectedSkType.code === 'sk_dekan_gkm_ukm'" class="sm:col-span-2 p-3.5 rounded-xl border border-indigo-100 dark:border-indigo-900/50 bg-indigo-50/50 dark:bg-indigo-950/20 space-y-3">
                                    <div class="text-xs font-bold text-indigo-900 dark:text-indigo-300">
                                        Pejabat yang Ditunjuk & Diangkat
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <!-- Ketua GKM -->
                                        <div class="relative">
                                            <div class="flex items-center justify-between mb-1">
                                                <label class="block text-[11px] font-semibold text-slate-700 dark:text-slate-300">
                                                    Ketua Gugus Kendali Mutu (GKM) Fakultas <span class="text-rose-500">*</span>
                                                </label>
                                                <button
                                                    type="button"
                                                    @click="activeGkmDropdown = !activeGkmDropdown"
                                                    class="text-[10px] text-indigo-600 dark:text-indigo-400 font-bold hover:underline"
                                                >
                                                    Pilih Dosen
                                                </button>
                                            </div>
                                            <input
                                                v-model="form.extra_data.gkm_chairman_name"
                                                @input="updateGkmDictumText"
                                                type="text"
                                                placeholder="MAYA DESTRIANI, S.Kom., M.Kom"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2.5 font-bold"
                                            />
                                            <!-- Dropdown Dosen GKM -->
                                            <div
                                                v-if="activeGkmDropdown"
                                                class="absolute left-0 top-full mt-1 w-full z-30 p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-indigo-200 dark:border-indigo-900 shadow-xl space-y-2"
                                            >
                                                <div class="flex items-center justify-between">
                                                    <span class="text-[11px] font-bold text-indigo-900 dark:text-indigo-300">Pilih dari Master Dosen:</span>
                                                    <button type="button" @click="activeGkmDropdown = false" class="text-slate-400 hover:text-slate-600 text-xs font-bold">✕</button>
                                                </div>
                                                <input
                                                    v-model="gkmSearchQuery"
                                                    type="text"
                                                    placeholder="Cari dosen..."
                                                    class="w-full text-xs rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 py-1.5 px-2.5"
                                                    autofocus
                                                />
                                                <div class="max-h-44 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700">
                                                    <button
                                                        v-for="l in filteredGkmLecturers"
                                                        :key="l.id"
                                                        type="button"
                                                        @click="selectGkmLecturer(l)"
                                                        class="w-full text-left py-1.5 px-2 hover:bg-indigo-50 dark:hover:bg-slate-700/60 rounded transition flex items-center justify-between text-xs"
                                                    >
                                                        <div>
                                                            <div class="font-bold text-slate-800 dark:text-white">{{ l.name }}</div>
                                                            <div class="text-[10px] text-slate-500 font-mono">{{ l.nidn || '-' }}</div>
                                                        </div>
                                                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-800 font-bold shrink-0">Pilih</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Ketua UKM -->
                                        <div class="relative">
                                            <div class="flex items-center justify-between mb-1">
                                                <label class="block text-[11px] font-semibold text-slate-700 dark:text-slate-300">
                                                    Ketua Unit Kendali Mutu (UKM) Prodi <span class="text-rose-500">*</span>
                                                </label>
                                                <button
                                                    type="button"
                                                    @click="activeUkmDropdown = !activeUkmDropdown"
                                                    class="text-[10px] text-indigo-600 dark:text-indigo-400 font-bold hover:underline"
                                                >
                                                    Pilih Dosen
                                                </button>
                                            </div>
                                            <input
                                                v-model="form.extra_data.ukm_chairman_name"
                                                @input="updateGkmDictumText"
                                                type="text"
                                                placeholder="CACA ARIF HERDIAN, S.Kom., M.Kom"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2.5 font-bold"
                                            />
                                            <!-- Dropdown Dosen UKM -->
                                            <div
                                                v-if="activeUkmDropdown"
                                                class="absolute left-0 top-full mt-1 w-full z-30 p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-indigo-200 dark:border-indigo-900 shadow-xl space-y-2"
                                            >
                                                <div class="flex items-center justify-between">
                                                    <span class="text-[11px] font-bold text-indigo-900 dark:text-indigo-300">Pilih dari Master Dosen:</span>
                                                    <button type="button" @click="activeUkmDropdown = false" class="text-slate-400 hover:text-slate-600 text-xs font-bold">✕</button>
                                                </div>
                                                <input
                                                    v-model="ukmSearchQuery"
                                                    type="text"
                                                    placeholder="Cari dosen..."
                                                    class="w-full text-xs rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 py-1.5 px-2.5"
                                                    autofocus
                                                />
                                                <div class="max-h-44 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700">
                                                    <button
                                                        v-for="l in filteredUkmLecturers"
                                                        :key="l.id"
                                                        type="button"
                                                        @click="selectUkmLecturer(l)"
                                                        class="w-full text-left py-1.5 px-2 hover:bg-indigo-50 dark:hover:bg-slate-700/60 rounded transition flex items-center justify-between text-xs"
                                                    >
                                                        <div>
                                                            <div class="font-bold text-slate-800 dark:text-white">{{ l.name }}</div>
                                                            <div class="text-[10px] text-slate-500 font-mono">{{ l.nidn || '-' }}</div>
                                                        </div>
                                                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-800 font-bold shrink-0">Pilih</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="sm:col-span-2">
                                            <label class="block text-[11px] font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                                Tanggal Berita Acara Rapat Fakultas (Memperhatikan)
                                            </label>
                                            <input
                                                v-model="form.extra_data.meeting_date_text"
                                                type="text"
                                                placeholder="13 November 2023"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2.5"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: KONSIDERANS (MENIMBANG, MENGINGAT, MEMPERHATIKAN) -->
                        <div v-show="activeStep === 'considerations'" class="space-y-5">
                            <!-- MENIMBANG -->
                            <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/40 space-y-3">
                                <div class="flex items-center justify-between pb-1 border-b border-slate-200 dark:border-slate-700">
                                    <div class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                        A. Menimbang (Latar Belakang & Alasan Hukum)
                                    </div>
                                    <button
                                        type="button"
                                        @click="addMenimbang"
                                        class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline"
                                    >
                                        + Tambah Poin
                                    </button>
                                </div>
                                <div class="space-y-2">
                                    <div
                                        v-for="(item, mIdx) in form.extra_data.menimbang"
                                        :key="mIdx"
                                        class="flex items-start gap-2"
                                    >
                                        <span class="text-xs font-bold text-slate-500 pt-1 shrink-0">{{ String.fromCharCode(97 + mIdx) }}.</span>
                                        <textarea
                                            v-model="form.extra_data.menimbang[mIdx]"
                                            rows="2"
                                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2"
                                        ></textarea>
                                        <button
                                            v-if="form.extra_data.menimbang.length > 1"
                                            type="button"
                                            @click="removeMenimbang(mIdx)"
                                            class="text-rose-500 hover:text-rose-700 p-1 text-xs shrink-0"
                                        >
                                            ✕
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- MENGINGAT -->
                            <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/40 space-y-3">
                                <div class="flex items-center justify-between pb-1 border-b border-slate-200 dark:border-slate-700">
                                    <div class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                        B. Mengingat (Dasar Hukum: UU, PP, Permendikbud, SK)
                                    </div>
                                    <button
                                        type="button"
                                        @click="addMengingat"
                                        class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline"
                                    >
                                        + Tambah Dasar Hukum
                                    </button>
                                </div>
                                <div class="space-y-2">
                                    <div
                                        v-for="(item, gIdx) in form.extra_data.mengingat"
                                        :key="gIdx"
                                        class="flex items-start gap-2"
                                    >
                                        <span class="text-xs font-bold text-slate-500 pt-1 shrink-0">{{ gIdx + 1 }}.</span>
                                        <textarea
                                            v-model="form.extra_data.mengingat[gIdx]"
                                            rows="2"
                                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2"
                                        ></textarea>
                                        <button
                                            v-if="form.extra_data.mengingat.length > 1"
                                            type="button"
                                            @click="removeMengingat(gIdx)"
                                            class="text-rose-500 hover:text-rose-700 p-1 text-xs shrink-0"
                                        >
                                            ✕
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- MEMPERHATIKAN -->
                            <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/40 space-y-3">
                                <div class="flex items-center justify-between pb-1 border-b border-slate-200 dark:border-slate-700">
                                    <div class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                        C. Memperhatikan (Hasil Rapat, Kalender, Pedoman)
                                    </div>
                                    <button
                                        type="button"
                                        @click="addMemperhatikan"
                                        class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline"
                                    >
                                        + Tambah Butir
                                    </button>
                                </div>
                                <div class="space-y-2">
                                    <div
                                        v-for="(item, hIdx) in form.extra_data.memperhatikan"
                                        :key="hIdx"
                                        class="flex items-start gap-2"
                                    >
                                        <span class="text-xs font-bold text-slate-500 pt-1 shrink-0">{{ hIdx + 1 }}.</span>
                                        <textarea
                                            v-model="form.extra_data.memperhatikan[hIdx]"
                                            rows="2"
                                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2"
                                        ></textarea>
                                        <button
                                            v-if="form.extra_data.memperhatikan.length > 1"
                                            type="button"
                                            @click="removeMemperhatikan(hIdx)"
                                            class="text-rose-500 hover:text-rose-700 p-1 text-xs shrink-0"
                                        >
                                            ✕
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 3: DIKTUM PUTUSAN -->
                        <div v-show="activeStep === 'dictums'" class="space-y-4">
                            <!-- ISI KETETAPAN (BAGIAN MENETAPKAN SEBELUM BUTIR PERTAMA) -->
                            <div
                                v-if="selectedSkType.code === 'sk_dekan_dosen_mengajar' || selectedSkType.code === 'sk_dekan_yudisium' || selectedSkType.code === 'sk_dekan_penguji_sidang_ta' || selectedSkType.code === 'sk_dekan_pengujian_sidang_skripsi' || selectedSkType.code === 'sk_dekan_pengunduran_diri' || form.extra_data.menetapkan_text"
                                class="p-3.5 rounded-xl border border-indigo-100 dark:border-indigo-900/60 bg-indigo-50/50 dark:bg-indigo-950/30 space-y-2"
                            >
                                <label class="block text-xs font-bold text-indigo-950 dark:text-indigo-200">
                                    Isi Ketetapan (Diktum Menetapkan)
                                </label>
                                <textarea
                                    v-model="form.extra_data.menetapkan_text"
                                    @input="triggerDebouncedSync"
                                    rows="2"
                                    placeholder="Isi ketetapan bagian Menetapkan..."
                                    class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2.5 font-medium leading-relaxed"
                                ></textarea>
                                <p class="text-[10px] text-slate-400">
                                    Teks yang tercetak persis setelah kata "Menetapkan :" sebelum butir pasal pertama.
                                </p>
                            </div>

                            <div class="flex items-center justify-between pb-1 border-b border-slate-200 dark:border-slate-700">
                                <div>
                                    <div class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                        Diktum Keputusan (MEMUTUSKAN -> Menetapkan)
                                    </div>
                                    <p class="text-[10px] text-slate-400">Poin pasal keputusan: Pertama, Kedua, Ketiga, dst.</p>
                                </div>
                                <button
                                    type="button"
                                    @click="addDictum"
                                    class="px-2.5 py-1 rounded-lg text-[11px] font-bold text-indigo-700 bg-indigo-50 dark:bg-indigo-900/40 hover:bg-indigo-100 flex items-center gap-1"
                                >
                                    + Tambah Diktum
                                </button>
                            </div>

                            <div class="space-y-3">
                                <div
                                    v-for="(d, dIdx) in form.extra_data.dictums"
                                    :key="dIdx"
                                    class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 space-y-2"
                                >
                                    <div class="flex items-center justify-between">
                                        <input
                                            v-model="d.label"
                                            type="text"
                                            placeholder="Pertama"
                                            class="w-32 text-xs font-bold rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 p-1.5"
                                        />
                                        <button
                                            v-if="form.extra_data.dictums.length > 1"
                                            type="button"
                                            @click="removeDictum(dIdx)"
                                            class="text-rose-500 hover:text-rose-700 text-xs font-semibold"
                                        >
                                            Hapus ✕
                                        </button>
                                    </div>
                                    <textarea
                                        v-model="d.text"
                                        rows="3"
                                        placeholder="Isi ketentuan diktum keputusan..."
                                        class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2"
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 4: MATRIKS LAMPIRAN -->
                        <div v-show="activeStep === 'matrix'" class="space-y-4">
                            <!-- IF SK DOSEN MENGAJAR -->
                            <template v-if="selectedSkType.code === 'sk_dekan_dosen_mengajar'">
                                <div class="flex items-center justify-between pb-1 border-b border-slate-200 dark:border-slate-700">
                                    <div>
                                        <div class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                            Matriks Lampiran: Daftar Dosen & Jadwal Mengajar (Landscape)
                                        </div>
                                        <p class="text-[10px] text-slate-400">Kelola daftar dosen pengajar beserta mata kuliah, SKS, semester, dan kelas yang diampu</p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="addTeachingLecturer"
                                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold text-indigo-700 bg-indigo-50 dark:bg-indigo-900/40 hover:bg-indigo-100 flex items-center gap-1 shadow-xs"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Tambah Dosen
                                    </button>
                                </div>

                                <div class="space-y-4">
                                    <div
                                        v-for="(lecturer, lIdx) in form.extra_data.teaching_lecturers"
                                        :key="lIdx"
                                        class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 space-y-3 shadow-xs"
                                    >
                                        <div class="flex items-center justify-between pb-2 border-b border-slate-200 dark:border-slate-700">
                                            <div class="flex items-center gap-2">
                                                <span class="w-6 h-6 rounded-full bg-indigo-600 text-white flex items-center justify-center text-xs font-bold">
                                                    {{ lIdx + 1 }}
                                                </span>
                                                <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                                    Dosen Pengajar #{{ lIdx + 1 }}
                                                </span>
                                            </div>
                                            <button
                                                v-if="form.extra_data.teaching_lecturers.length > 1"
                                                type="button"
                                                @click="removeTeachingLecturer(lIdx)"
                                                class="text-rose-500 hover:text-rose-700 text-xs font-semibold flex items-center gap-1"
                                            >
                                                Hapus Dosen ✕
                                            </button>
                                        </div>

                                        <!-- NAMA DOSEN -->
                                        <div class="relative">
                                            <div class="flex items-center justify-between mb-1">
                                                <label class="block text-[11px] font-semibold text-slate-700 dark:text-slate-300">
                                                    Nama Lengkap Dosen (Beserta Gelar)
                                                </label>
                                                <button
                                                    type="button"
                                                    @click="activeLecturerDropdownIdx === lIdx ? activeLecturerDropdownIdx = null : openLecturerDropdown(lIdx)"
                                                    class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1"
                                                >
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                    Pilih dari Master Dosen
                                                </button>
                                            </div>
                                            <input
                                                v-model="lecturer.name"
                                                type="text"
                                                placeholder="Contoh: Bambang Tjahjo Utomo, MT"
                                                class="w-full text-xs p-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-bold"
                                            />

                                            <!-- Dropdown Popover Master Dosen -->
                                            <div
                                                v-if="activeLecturerDropdownIdx === lIdx"
                                                class="absolute left-0 top-full mt-1 w-full max-w-md z-30 p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-indigo-200 dark:border-indigo-900 shadow-xl space-y-2"
                                            >
                                                <div class="flex items-center justify-between">
                                                    <span class="text-[11px] font-bold text-indigo-900 dark:text-indigo-300">
                                                        Cari & Pilih Master Dosen:
                                                    </span>
                                                    <button
                                                        type="button"
                                                        @click="activeLecturerDropdownIdx = null"
                                                        class="text-slate-400 hover:text-slate-600 text-xs font-bold"
                                                    >
                                                        ✕
                                                    </button>
                                                </div>
                                                <input
                                                    v-model="lecturerSearchQuery"
                                                    type="text"
                                                    placeholder="Ketik nama / NIDN dosen..."
                                                    class="w-full text-xs rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 py-1.5 px-2.5"
                                                    autofocus
                                                />
                                                <div class="max-h-48 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700">
                                                    <button
                                                        v-for="ld in filteredMasterLecturers"
                                                        :key="ld.id"
                                                        type="button"
                                                        @click="selectMasterLecturer(lIdx, ld)"
                                                        class="w-full text-left py-1.5 px-2 hover:bg-indigo-50 dark:hover:bg-slate-700/60 rounded transition flex items-center justify-between text-xs"
                                                    >
                                                        <div>
                                                            <div class="font-bold text-slate-800 dark:text-white">{{ ld.name }}</div>
                                                            <div class="text-[10px] text-slate-500 font-mono">
                                                                <span v-if="ld.nidn">NIDN: {{ ld.nidn }}</span>
                                                                <span v-if="ld.study_program"> • {{ ld.study_program }}</span>
                                                            </div>
                                                        </div>
                                                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-800 font-bold shrink-0">
                                                            Pilih
                                                        </span>
                                                    </button>
                                                    <div v-if="filteredMasterLecturers.length === 0" class="p-3 text-center text-slate-400 text-xs">
                                                        Dosen tidak ditemukan
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- NESTED LIST OF COURSES -->
                                        <div class="space-y-2 pt-1">
                                            <div class="flex items-center justify-between">
                                                <span class="text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider">
                                                    Daftar Mata Kuliah Yang Diampu:
                                                </span>
                                                <button
                                                    type="button"
                                                    @click="addCourseToLecturer(lIdx)"
                                                    class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-0.5"
                                                >
                                                    + Tambah Mata Kuliah
                                                </button>
                                            </div>

                                            <div class="space-y-2">
                                                <div
                                                    v-for="(course, cIdx) in lecturer.courses"
                                                    :key="cIdx"
                                                    class="grid grid-cols-12 gap-2 p-2.5 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 items-center"
                                                >
                                                    <div class="col-span-12 sm:col-span-5 relative">
                                                        <div class="flex items-center justify-between mb-0.5">
                                                            <label class="block text-[10px] text-slate-400">Mata Kuliah</label>
                                                            <button
                                                                type="button"
                                                                @click="activeCourseDropdownKey === `${lIdx}-${cIdx}` ? activeCourseDropdownKey = null : openCourseDropdown(lIdx, cIdx)"
                                                                class="text-[10px] text-indigo-600 dark:text-indigo-400 hover:underline font-bold"
                                                            >
                                                                Pilih Master MK
                                                            </button>
                                                        </div>
                                                        <input
                                                            v-model="course.name"
                                                            type="text"
                                                            placeholder="Contoh: APSI II"
                                                            class="w-full text-xs p-1.5 rounded border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800"
                                                        />

                                                        <!-- Dropdown Popover Master MK -->
                                                        <div
                                                            v-if="activeCourseDropdownKey === `${lIdx}-${cIdx}`"
                                                            class="absolute left-0 top-full mt-1 w-72 sm:w-80 z-30 p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-indigo-200 dark:border-indigo-900 shadow-xl space-y-2"
                                                        >
                                                            <div class="flex items-center justify-between">
                                                                <span class="text-[11px] font-bold text-indigo-900 dark:text-indigo-300">
                                                                    Cari Mata Kuliah:
                                                                </span>
                                                                <button
                                                                    type="button"
                                                                    @click="activeCourseDropdownKey = null"
                                                                    class="text-slate-400 hover:text-slate-600 text-xs font-bold"
                                                                >
                                                                    ✕
                                                                </button>
                                                            </div>
                                                            <input
                                                                v-model="courseSearchQuery"
                                                                type="text"
                                                                placeholder="Ketik kode / nama MK..."
                                                                class="w-full text-xs rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 py-1.5 px-2.5"
                                                                autofocus
                                                            />
                                                            <div class="max-h-48 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700">
                                                                <button
                                                                    v-for="mc in filteredMasterCourses"
                                                                    :key="mc.id"
                                                                    type="button"
                                                                    @click="selectMasterCourse(lIdx, cIdx, mc)"
                                                                    class="w-full text-left py-1.5 px-2 hover:bg-indigo-50 dark:hover:bg-slate-700/60 rounded transition flex items-center justify-between text-xs"
                                                                >
                                                                    <div>
                                                                        <div class="font-bold text-slate-800 dark:text-white">{{ mc.name }}</div>
                                                                        <div class="text-[10px] text-slate-500 font-mono">
                                                                            {{ mc.code }} • {{ mc.sks }} SKS <span v-if="mc.semester">• Sem {{ mc.semester }}</span>
                                                                        </div>
                                                                    </div>
                                                                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-800 font-bold shrink-0">
                                                                        Pilih
                                                                    </span>
                                                                </button>
                                                                <div v-if="filteredMasterCourses.length === 0" class="p-3 text-center text-slate-400 text-xs">
                                                                    Mata kuliah tidak ditemukan
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-span-4 sm:col-span-2">
                                                        <label class="block text-[10px] text-slate-400 mb-0.5">SKS</label>
                                                        <input
                                                            v-model="course.sks"
                                                            type="text"
                                                            placeholder="3"
                                                            class="w-full text-xs p-1.5 text-center rounded border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800"
                                                        />
                                                    </div>
                                                    <div class="col-span-4 sm:col-span-2">
                                                        <label class="block text-[10px] text-slate-400 mb-0.5">Semester</label>
                                                        <input
                                                            v-model="course.semester"
                                                            type="text"
                                                            placeholder="5"
                                                            class="w-full text-xs p-1.5 text-center rounded border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800"
                                                        />
                                                    </div>
                                                    <div class="col-span-3 sm:col-span-2">
                                                        <label class="block text-[10px] text-slate-400 mb-0.5">Kelas</label>
                                                        <input
                                                            v-model="course.class"
                                                            type="text"
                                                            placeholder="A"
                                                            class="w-full text-xs p-1.5 text-center rounded border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 uppercase"
                                                        />
                                                    </div>
                                                    <div class="col-span-1 flex items-center justify-center pt-3">
                                                        <button
                                                            v-if="lecturer.courses.length > 1"
                                                            type="button"
                                                            @click="removeCourseFromLecturer(lIdx, cIdx)"
                                                            class="text-rose-500 hover:text-rose-700 text-xs font-bold p-1"
                                                            title="Hapus Mata Kuliah"
                                                        >
                                                            ✕
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- IF SK DOSEN PEMBIMBING TUGAS AKHIR -->
                            <template v-else-if="selectedSkType.code === 'sk_dekan_pembimbing_ta'">
                                <div class="flex items-center justify-between pb-1 border-b border-slate-200 dark:border-slate-700">
                                    <div>
                                        <div class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                            Matriks Lampiran: Daftar Pembimbing Tugas Akhir (Landscape)
                                        </div>
                                        <p class="text-[10px] text-slate-400">Rincian mahasiswa (NPM, Nama), Judul Tugas Akhir, serta Pembimbing I & II</p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="addTaRow"
                                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold text-indigo-700 bg-indigo-50 dark:bg-indigo-900/40 hover:bg-indigo-100 flex items-center gap-1 shadow-xs"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Tambah Mahasiswa
                                    </button>
                                </div>

                                <div class="space-y-3">
                                    <div
                                        v-for="(row, rIdx) in form.extra_data.ta_matrix"
                                        :key="rIdx"
                                        class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 space-y-2.5 shadow-xs"
                                    >
                                        <div class="flex items-center justify-between pb-1 border-b border-slate-200 dark:border-slate-700">
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                                Mahasiswa #{{ rIdx + 1 }}
                                            </span>
                                            <button
                                                v-if="form.extra_data.ta_matrix.length > 1"
                                                type="button"
                                                @click="removeTaRow(rIdx)"
                                                class="text-rose-500 hover:text-rose-700 text-xs font-semibold"
                                            >
                                                Hapus ✕
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-2">
                                            <!-- NPM & Mahasiswa -->
                                            <div class="sm:col-span-4 relative">
                                                <div class="flex items-center justify-between mb-0.5">
                                                    <label class="block text-[10px] font-semibold text-slate-500">NPM</label>
                                                    <button
                                                        type="button"
                                                        @click="activeTaStudentDropdownIdx === rIdx ? activeTaStudentDropdownIdx = null : activeTaStudentDropdownIdx = rIdx; taStudentSearchQuery = ''"
                                                        class="text-[10px] text-indigo-600 dark:text-indigo-400 hover:underline font-bold"
                                                    >
                                                        Pilih Master Mahasiswa
                                                    </button>
                                                </div>
                                                <input
                                                    v-model="row.npm"
                                                    type="text"
                                                    placeholder="D1A210001"
                                                    class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-mono uppercase"
                                                />

                                                <!-- Dropdown Mahasiswa -->
                                                <div
                                                    v-if="activeTaStudentDropdownIdx === rIdx"
                                                    class="absolute left-0 top-full mt-1 w-72 sm:w-80 z-30 p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-indigo-200 dark:border-indigo-900 shadow-xl space-y-2"
                                                >
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-[11px] font-bold text-indigo-900 dark:text-indigo-300">Cari Mahasiswa:</span>
                                                        <button type="button" @click="activeTaStudentDropdownIdx = null" class="text-slate-400 hover:text-slate-600 text-xs font-bold">✕</button>
                                                    </div>
                                                    <input
                                                        v-model="taStudentSearchQuery"
                                                        type="text"
                                                        placeholder="Ketik nama / NPM..."
                                                        class="w-full text-xs rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 py-1.5 px-2.5"
                                                        autofocus
                                                    />
                                                    <div class="max-h-48 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700">
                                                        <button
                                                            v-for="s in filteredTaStudents"
                                                            :key="s.id"
                                                            type="button"
                                                            @click="selectTaStudent(rIdx, s)"
                                                            class="w-full text-left py-1.5 px-2 hover:bg-indigo-50 dark:hover:bg-slate-700/60 rounded transition flex items-center justify-between text-xs"
                                                        >
                                                            <div>
                                                                <div class="font-bold text-slate-800 dark:text-white uppercase">{{ s.name }}</div>
                                                                <div class="text-[10px] text-slate-500 font-mono">{{ s.npm || '-' }}</div>
                                                            </div>
                                                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-800 font-bold shrink-0">Pilih</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="sm:col-span-8">
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Nama Mahasiswa</label>
                                                <input
                                                    v-model="row.name"
                                                    type="text"
                                                    placeholder="RIZKI RAMADHAN"
                                                    class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-bold uppercase"
                                                />
                                            </div>

                                            <!-- Judul Tugas Akhir -->
                                            <div class="sm:col-span-12">
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Judul Tugas Akhir</label>
                                                <textarea
                                                    v-model="row.title"
                                                    rows="2"
                                                    placeholder="Contoh: Rancang Bangun Sistem Informasi E-Commerce Berbasis Web..."
                                                    class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900"
                                                ></textarea>
                                            </div>

                                            <!-- Pembimbing 1 -->
                                            <div class="sm:col-span-6 relative">
                                                <div class="flex items-center justify-between mb-0.5">
                                                    <label class="block text-[10px] font-semibold text-slate-500">Pembimbing I</label>
                                                    <button
                                                        type="button"
                                                        @click="activeTaAdvisorDropdownKey === `${rIdx}-1` ? activeTaAdvisorDropdownKey = null : activeTaAdvisorDropdownKey = `${rIdx}-1`; taAdvisorSearchQuery = ''"
                                                        class="text-[10px] text-indigo-600 dark:text-indigo-400 hover:underline font-bold"
                                                    >
                                                        Pilih Dosen
                                                    </button>
                                                </div>
                                                <input
                                                    v-model="row.advisor_1"
                                                    type="text"
                                                    placeholder="Dr. TEPI PEIRISAL, M.Si"
                                                    class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-medium"
                                                />

                                                <!-- Dropdown Pembimbing 1 -->
                                                <div
                                                    v-if="activeTaAdvisorDropdownKey === `${rIdx}-1`"
                                                    class="absolute left-0 top-full mt-1 w-72 sm:w-80 z-30 p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-indigo-200 dark:border-indigo-900 shadow-xl space-y-2"
                                                >
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-[11px] font-bold text-indigo-900 dark:text-indigo-300">Pilih Pembimbing I:</span>
                                                        <button type="button" @click="activeTaAdvisorDropdownKey = null" class="text-slate-400 hover:text-slate-600 text-xs font-bold">✕</button>
                                                    </div>
                                                    <input
                                                        v-model="taAdvisorSearchQuery"
                                                        type="text"
                                                        placeholder="Cari dosen..."
                                                        class="w-full text-xs rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 py-1.5 px-2.5"
                                                        autofocus
                                                    />
                                                    <div class="max-h-48 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700">
                                                        <button
                                                            v-for="l in filteredTaAdvisors"
                                                            :key="l.id"
                                                            type="button"
                                                            @click="selectTaAdvisor(rIdx, 1, l)"
                                                            class="w-full text-left py-1.5 px-2 hover:bg-indigo-50 dark:hover:bg-slate-700/60 rounded transition flex items-center justify-between text-xs"
                                                        >
                                                            <div>
                                                                <div class="font-bold text-slate-800 dark:text-white">{{ l.name }}</div>
                                                                <div class="text-[10px] text-slate-500 font-mono">{{ l.nidn || '' }}</div>
                                                            </div>
                                                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-800 font-bold shrink-0">Pilih</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Pembimbing 2 -->
                                            <div class="sm:col-span-6 relative">
                                                <div class="flex items-center justify-between mb-0.5">
                                                    <label class="block text-[10px] font-semibold text-slate-500">Pembimbing II</label>
                                                    <button
                                                        type="button"
                                                        @click="activeTaAdvisorDropdownKey === `${rIdx}-2` ? activeTaAdvisorDropdownKey = null : activeTaAdvisorDropdownKey = `${rIdx}-2`; taAdvisorSearchQuery = ''"
                                                        class="text-[10px] text-indigo-600 dark:text-indigo-400 hover:underline font-bold"
                                                    >
                                                        Pilih Dosen
                                                    </button>
                                                </div>
                                                <input
                                                    v-model="row.advisor_2"
                                                    type="text"
                                                    placeholder="Bagus Ali Akbar, S.Si., M.Kom"
                                                    class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-medium"
                                                />

                                                <!-- Dropdown Pembimbing 2 -->
                                                <div
                                                    v-if="activeTaAdvisorDropdownKey === `${rIdx}-2`"
                                                    class="absolute left-0 top-full mt-1 w-72 sm:w-80 z-30 p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-indigo-200 dark:border-indigo-900 shadow-xl space-y-2"
                                                >
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-[11px] font-bold text-indigo-900 dark:text-indigo-300">Pilih Pembimbing II:</span>
                                                        <button type="button" @click="activeTaAdvisorDropdownKey = null" class="text-slate-400 hover:text-slate-600 text-xs font-bold">✕</button>
                                                    </div>
                                                    <input
                                                        v-model="taAdvisorSearchQuery"
                                                        type="text"
                                                        placeholder="Cari dosen..."
                                                        class="w-full text-xs rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 py-1.5 px-2.5"
                                                        autofocus
                                                    />
                                                    <div class="max-h-48 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700">
                                                        <button
                                                            v-for="l in filteredTaAdvisors"
                                                            :key="l.id"
                                                            type="button"
                                                            @click="selectTaAdvisor(rIdx, 2, l)"
                                                            class="w-full text-left py-1.5 px-2 hover:bg-indigo-50 dark:hover:bg-slate-700/60 rounded transition flex items-center justify-between text-xs"
                                                        >
                                                            <div>
                                                                <div class="font-bold text-slate-800 dark:text-white">{{ l.name }}</div>
                                                                <div class="text-[10px] text-slate-500 font-mono">{{ l.nidn || '' }}</div>
                                                            </div>
                                                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-800 font-bold shrink-0">Pilih</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- IF SK DOSEN WALI AKADEMIK -->
                            <template v-else-if="selectedSkType.code === 'sk_dekan_dosen_wali'">
                                <div class="flex items-center justify-between pb-1 border-b border-slate-200 dark:border-slate-700">
                                    <div>
                                        <div class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                            Matriks Lampiran: Daftar Perwalian / Dosen Wali (Landscape)
                                        </div>
                                        <p class="text-[10px] text-slate-400">Rincian mahasiswa (NPM, Nama) beserta Dosen Wali pembimbing akademik</p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="addWaliRow"
                                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold text-indigo-700 bg-indigo-50 dark:bg-indigo-900/40 hover:bg-indigo-100 flex items-center gap-1 shadow-xs"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Tambah Mahasiswa
                                    </button>
                                </div>

                                <div class="space-y-3">
                                    <div
                                        v-for="(row, rIdx) in form.extra_data.wali_matrix"
                                        :key="rIdx"
                                        class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 space-y-2.5 shadow-xs"
                                    >
                                        <div class="flex items-center justify-between pb-1 border-b border-slate-200 dark:border-slate-700">
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                                Mahasiswa #{{ rIdx + 1 }}
                                            </span>
                                            <button
                                                v-if="form.extra_data.wali_matrix.length > 1"
                                                type="button"
                                                @click="removeWaliRow(rIdx)"
                                                class="text-rose-500 hover:text-rose-700 text-xs font-semibold"
                                            >
                                                Hapus ✕
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-2">
                                            <!-- NPM & Mahasiswa -->
                                            <div class="sm:col-span-4 relative">
                                                <div class="flex items-center justify-between mb-0.5">
                                                    <label class="block text-[10px] font-semibold text-slate-500">NPM</label>
                                                    <button
                                                        type="button"
                                                        @click="activeWaliStudentDropdownIdx === rIdx ? activeWaliStudentDropdownIdx = null : activeWaliStudentDropdownIdx = rIdx; waliStudentSearchQuery = ''"
                                                        class="text-[10px] text-indigo-600 dark:text-indigo-400 hover:underline font-bold"
                                                    >
                                                        Pilih Mahasiswa
                                                    </button>
                                                </div>
                                                <input
                                                    v-model="row.npm"
                                                    type="text"
                                                    placeholder="D1A210001"
                                                    class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-mono uppercase"
                                                />

                                                <!-- Dropdown Mahasiswa -->
                                                <div
                                                    v-if="activeWaliStudentDropdownIdx === rIdx"
                                                    class="absolute left-0 top-full mt-1 w-72 sm:w-80 z-30 p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-indigo-200 dark:border-indigo-900 shadow-xl space-y-2"
                                                >
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-[11px] font-bold text-indigo-900 dark:text-indigo-300">Cari Mahasiswa:</span>
                                                        <button type="button" @click="activeWaliStudentDropdownIdx = null" class="text-slate-400 hover:text-slate-600 text-xs font-bold">✕</button>
                                                    </div>
                                                    <input
                                                        v-model="waliStudentSearchQuery"
                                                        type="text"
                                                        placeholder="Ketik nama / NPM..."
                                                        class="w-full text-xs rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 py-1.5 px-2.5"
                                                        autofocus
                                                    />
                                                    <div class="max-h-48 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700">
                                                        <button
                                                            v-for="s in filteredWaliStudents"
                                                            :key="s.id"
                                                            type="button"
                                                            @click="selectWaliStudent(rIdx, s)"
                                                            class="w-full text-left py-1.5 px-2 hover:bg-indigo-50 dark:hover:bg-slate-700/60 rounded transition flex items-center justify-between text-xs"
                                                        >
                                                            <div>
                                                                <div class="font-bold text-slate-800 dark:text-white uppercase">{{ s.name }}</div>
                                                                <div class="text-[10px] text-slate-500 font-mono">{{ s.npm || '-' }}</div>
                                                            </div>
                                                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-800 font-bold shrink-0">Pilih</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="sm:col-span-8">
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Nama Mahasiswa</label>
                                                <input
                                                    v-model="row.name"
                                                    type="text"
                                                    placeholder="RIZKI RAMADHAN"
                                                    class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-bold uppercase"
                                                />
                                            </div>

                                            <!-- Dosen Wali -->
                                            <div class="sm:col-span-12 relative">
                                                <div class="flex items-center justify-between mb-0.5">
                                                    <label class="block text-[10px] font-semibold text-slate-500">Dosen Wali</label>
                                                    <button
                                                        type="button"
                                                        @click="activeWaliAdvisorDropdownIdx === rIdx ? activeWaliAdvisorDropdownIdx = null : activeWaliAdvisorDropdownIdx = rIdx; waliAdvisorSearchQuery = ''"
                                                        class="text-[10px] text-indigo-600 dark:text-indigo-400 hover:underline font-bold"
                                                    >
                                                        Pilih Dosen
                                                    </button>
                                                </div>
                                                <input
                                                    v-model="row.advisor"
                                                    type="text"
                                                    placeholder="Bagus Ali Akbar, S.Si., M.Kom"
                                                    class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-medium"
                                                />

                                                <!-- Dropdown Dosen Wali -->
                                                <div
                                                    v-if="activeWaliAdvisorDropdownIdx === rIdx"
                                                    class="absolute left-0 top-full mt-1 w-72 sm:w-80 z-30 p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-indigo-200 dark:border-indigo-900 shadow-xl space-y-2"
                                                >
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-[11px] font-bold text-indigo-900 dark:text-indigo-300">Pilih Dosen Wali:</span>
                                                        <button type="button" @click="activeWaliAdvisorDropdownIdx = null" class="text-slate-400 hover:text-slate-600 text-xs font-bold">✕</button>
                                                    </div>
                                                    <input
                                                        v-model="waliAdvisorSearchQuery"
                                                        type="text"
                                                        placeholder="Cari dosen..."
                                                        class="w-full text-xs rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 py-1.5 px-2.5"
                                                        autofocus
                                                    />
                                                    <div class="max-h-48 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700">
                                                        <button
                                                            v-for="l in filteredWaliAdvisors"
                                                            :key="l.id"
                                                            type="button"
                                                            @click="selectWaliAdvisor(rIdx, l)"
                                                            class="w-full text-left py-1.5 px-2 hover:bg-indigo-50 dark:hover:bg-slate-700/60 rounded transition flex items-center justify-between text-xs"
                                                        >
                                                            <div>
                                                                <div class="font-bold text-slate-800 dark:text-white">{{ l.name }}</div>
                                                                <div class="text-[10px] text-slate-500 font-mono">{{ l.nidn || '' }}</div>
                                                            </div>
                                                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-800 font-bold shrink-0">Pilih</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- IF SK KELULUSAN / YUDISIUM -->
                            <template v-else-if="selectedSkType.code === 'sk_dekan_yudisium'">
                                <div class="space-y-1.5 pb-2 border-b border-slate-200 dark:border-slate-700">
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                        Sub-judul Lampiran (Di Atas Tabel Peserta Yudisium)
                                    </label>
                                    <textarea
                                        v-model="form.extra_data.attachment_subtitle"
                                        @input="triggerDebouncedSync"
                                        rows="4"
                                        class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 focus:ring-indigo-500 focus:border-indigo-500 leading-relaxed font-sans font-semibold"
                                        placeholder="DAFTAR PESERTA YUDISIUM PROGRAM STUDI SISTEM INFORMASI..."
                                    ></textarea>
                                    <p class="text-[10px] text-slate-400">Gunakan baris baru (Enter) untuk memisahkan baris judul lampiran.</p>
                                </div>

                                <div class="flex items-center justify-between pb-1 border-b border-slate-200 dark:border-slate-700">
                                    <div>
                                        <div class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                            Matriks Lampiran: Daftar Peserta Yudisium (Landscape)
                                        </div>
                                        <p class="text-[10px] text-slate-400">Rincian mahasiswa lulusan (NPM, Nama, Judul Skripsi, IPK, Predikat Yudisium)</p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="addYudisiumRow"
                                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold text-indigo-700 bg-indigo-50 dark:bg-indigo-900/40 hover:bg-indigo-100 flex items-center gap-1 shadow-xs"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Tambah Lulusan
                                    </button>
                                </div>

                                <div class="space-y-3">
                                    <div
                                        v-for="(row, rIdx) in form.extra_data.yudisium_matrix"
                                        :key="rIdx"
                                        class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 space-y-2.5 shadow-xs"
                                    >
                                        <div class="flex items-center justify-between pb-1 border-b border-slate-200 dark:border-slate-700">
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                                Lulusan #{{ rIdx + 1 }}
                                            </span>
                                            <button
                                                v-if="form.extra_data.yudisium_matrix.length > 1"
                                                type="button"
                                                @click="removeYudisiumRow(rIdx)"
                                                class="text-rose-500 hover:text-rose-700 text-xs font-semibold"
                                            >
                                                Hapus ✕
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-2">
                                            <!-- NPM & Mahasiswa -->
                                            <div class="sm:col-span-4 relative">
                                                <div class="flex items-center justify-between mb-0.5">
                                                    <label class="block text-[10px] font-semibold text-slate-500">NPM</label>
                                                    <button
                                                        type="button"
                                                        @click="activeYudisiumStudentDropdownIdx === rIdx ? activeYudisiumStudentDropdownIdx = null : activeYudisiumStudentDropdownIdx = rIdx; yudisiumStudentSearchQuery = ''"
                                                        class="text-[10px] text-indigo-600 dark:text-indigo-400 hover:underline font-bold"
                                                    >
                                                        Pilih Mahasiswa
                                                    </button>
                                                </div>
                                                <input
                                                    v-model="row.npm"
                                                    type="text"
                                                    placeholder="D1A210001"
                                                    class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-mono uppercase"
                                                />

                                                <!-- Dropdown Mahasiswa -->
                                                <div
                                                    v-if="activeYudisiumStudentDropdownIdx === rIdx"
                                                    class="absolute left-0 top-full mt-1 w-72 sm:w-80 z-30 p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-indigo-200 dark:border-indigo-900 shadow-xl space-y-2"
                                                >
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-[11px] font-bold text-indigo-900 dark:text-indigo-300">Cari Mahasiswa:</span>
                                                        <button type="button" @click="activeYudisiumStudentDropdownIdx = null" class="text-slate-400 hover:text-slate-600 text-xs font-bold">✕</button>
                                                    </div>
                                                    <input
                                                        v-model="yudisiumStudentSearchQuery"
                                                        type="text"
                                                        placeholder="Ketik nama / NPM..."
                                                        class="w-full text-xs rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 py-1.5 px-2.5"
                                                        autofocus
                                                    />
                                                    <div class="max-h-48 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700">
                                                        <button
                                                            v-for="s in filteredYudisiumStudents"
                                                            :key="s.id"
                                                            type="button"
                                                            @click="selectYudisiumStudent(rIdx, s)"
                                                            class="w-full text-left py-1.5 px-2 hover:bg-indigo-50 dark:hover:bg-slate-700/60 rounded transition flex items-center justify-between text-xs"
                                                        >
                                                            <div>
                                                                <div class="font-bold text-slate-800 dark:text-white uppercase">{{ s.name }}</div>
                                                                <div class="text-[10px] text-slate-500 font-mono">{{ s.npm || '-' }}</div>
                                                            </div>
                                                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-800 font-bold shrink-0">Pilih</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="sm:col-span-8">
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Nama Mahasiswa</label>
                                                <input
                                                    v-model="row.name"
                                                    type="text"
                                                    placeholder="RIZKI RAMADHAN"
                                                    class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-bold uppercase"
                                                />
                                            </div>

                                            <!-- Judul Skripsi -->
                                            <div class="sm:col-span-6">
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Judul Skripsi</label>
                                                <textarea
                                                    v-model="row.thesis_title"
                                                    rows="2"
                                                    placeholder="Judul skripsi mahasiswa..."
                                                    class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900"
                                                ></textarea>
                                            </div>

                                            <!-- IPK -->
                                            <div class="sm:col-span-2">
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">IPK</label>
                                                <input
                                                    v-model="row.ipk"
                                                    type="text"
                                                    placeholder="3.85"
                                                    class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-bold text-center"
                                                />
                                            </div>

                                            <!-- Predikat Yudisium -->
                                            <div class="sm:col-span-4">
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Predikat Yudisium</label>
                                                <input
                                                    v-model="row.predicate"
                                                    type="text"
                                                    placeholder="Dengan Pujian (Cum Laude)"
                                                    class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-medium"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- IF SK PELAKSANAAN PENGUJIAN SEMINAR TUGAS AKHIR -->
                            <template v-else-if="selectedSkType.code === 'sk_dekan_penguji_sidang_ta'">
                                <div class="flex items-center justify-between pb-1 border-b border-slate-200 dark:border-slate-700">
                                    <div>
                                        <div class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                            Matriks Lampiran: Jadwal Seminar Skripsi (Landscape)
                                        </div>
                                        <p class="text-[10px] text-slate-400">Jadwal pembukaan, mahasiswa, judul, pembimbing (1 & 2), dan penguji (1 & 2)</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            @click="addSeminarOpeningRow"
                                            class="px-2.5 py-1 rounded-lg text-[11px] font-bold text-amber-700 bg-amber-50 dark:bg-amber-900/40 hover:bg-amber-100 flex items-center gap-1 shadow-xs"
                                        >
                                            + Baris Persiapan
                                        </button>
                                        <button
                                            type="button"
                                            @click="addSeminarStudentRow"
                                            class="px-2.5 py-1 rounded-lg text-[11px] font-bold text-indigo-700 bg-indigo-50 dark:bg-indigo-900/40 hover:bg-indigo-100 flex items-center gap-1 shadow-xs"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                            + Mahasiswa Ujian
                                        </button>
                                    </div>
                                </div>

                                <!-- Pengaturan Ruangan & Tanggal Ujian -->
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 p-3 rounded-xl bg-indigo-50/50 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/50">
                                    <div>
                                        <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-400 mb-0.5">Gelombang</label>
                                        <input
                                            v-model="form.extra_data.wave_info"
                                            type="text"
                                            placeholder="GELOMBANG I"
                                            class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-bold uppercase"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-400 mb-0.5">Hari / Tanggal Ujian</label>
                                        <input
                                            v-model="form.extra_data.exam_schedule_date"
                                            type="text"
                                            placeholder="Kamis, 15 Oktober 2026"
                                            class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-medium"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-400 mb-0.5">Ruangan Ujian</label>
                                        <input
                                            v-model="form.extra_data.exam_room"
                                            type="text"
                                            placeholder="RUANG 8"
                                            class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-bold uppercase"
                                        />
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <div
                                        v-for="(row, rIdx) in form.extra_data.seminar_matrix"
                                        :key="rIdx"
                                        class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 space-y-2.5 shadow-xs"
                                    >
                                        <div class="flex items-center justify-between pb-1 border-b border-slate-200 dark:border-slate-700">
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                                {{ row.is_header_row ? 'Baris Khusus / Pembukaan' : `Peserta Seminar #${rIdx + 1}` }}
                                            </span>
                                            <button
                                                v-if="form.extra_data.seminar_matrix.length > 1"
                                                type="button"
                                                @click="removeSeminarRow(rIdx)"
                                                class="text-rose-500 hover:text-rose-700 text-xs font-semibold"
                                            >
                                                Hapus ✕
                                            </button>
                                        </div>

                                        <!-- JIKA BARIS PEMBUKAAN / HEADER ROW -->
                                        <div v-if="row.is_header_row" class="grid grid-cols-1 sm:grid-cols-12 gap-2">
                                            <div class="sm:col-span-3">
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Waktu</label>
                                                <input
                                                    v-model="row.time"
                                                    type="text"
                                                    placeholder="07.30 – 08.00"
                                                    class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-center font-mono"
                                                />
                                            </div>
                                            <div class="sm:col-span-9">
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Keterangan Kegiatan</label>
                                                <input
                                                    v-model="row.note"
                                                    type="text"
                                                    placeholder="Persiapan dan Pembukaan Seminar"
                                                    class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-medium"
                                                />
                                            </div>
                                        </div>

                                        <!-- JIKA PESERTA SEMINAR -->
                                        <div v-else class="grid grid-cols-1 sm:grid-cols-12 gap-2">
                                            <div class="sm:col-span-2">
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Waktu</label>
                                                <input
                                                    v-model="row.time"
                                                    type="text"
                                                    placeholder="08.00 – 08.45"
                                                    class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-center font-mono"
                                                />
                                            </div>

                                            <div class="sm:col-span-3 relative">
                                                <div class="flex items-center justify-between mb-0.5">
                                                    <label class="block text-[10px] font-semibold text-slate-500">NPM</label>
                                                    <button
                                                        type="button"
                                                        @click="activeSeminarStudentDropdownIdx === rIdx ? activeSeminarStudentDropdownIdx = null : activeSeminarStudentDropdownIdx = rIdx; seminarStudentSearchQuery = ''"
                                                        class="text-[10px] text-indigo-600 dark:text-indigo-400 hover:underline font-bold"
                                                    >
                                                        Pilih Mahasiswa
                                                    </button>
                                                </div>
                                                <input
                                                    v-model="row.npm"
                                                    type="text"
                                                    placeholder="D1A230046"
                                                    class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-mono uppercase"
                                                />

                                                <!-- Dropdown Mahasiswa -->
                                                <div
                                                    v-if="activeSeminarStudentDropdownIdx === rIdx"
                                                    class="absolute left-0 top-full mt-1 w-72 sm:w-80 z-30 p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-indigo-200 dark:border-indigo-900 shadow-xl space-y-2"
                                                >
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-[11px] font-bold text-indigo-900 dark:text-indigo-300">Cari Mahasiswa:</span>
                                                        <button type="button" @click="activeSeminarStudentDropdownIdx = null" class="text-slate-400 hover:text-slate-600 text-xs font-bold">✕</button>
                                                    </div>
                                                    <input
                                                        v-model="seminarStudentSearchQuery"
                                                        type="text"
                                                        placeholder="Ketik nama / NPM..."
                                                        class="w-full text-xs rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 py-1.5 px-2.5"
                                                        autofocus
                                                    />
                                                    <div class="max-h-48 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700">
                                                        <button
                                                            v-for="s in filteredSeminarStudents"
                                                            :key="s.id"
                                                            type="button"
                                                            @click="selectSeminarStudent(rIdx, s)"
                                                            class="w-full text-left py-1.5 px-2 hover:bg-indigo-50 dark:hover:bg-slate-700/60 rounded transition flex items-center justify-between text-xs"
                                                        >
                                                            <div>
                                                                <div class="font-bold text-slate-800 dark:text-white uppercase">{{ s.name }}</div>
                                                                <div class="text-[10px] text-slate-500 font-mono">{{ s.npm || '-' }}</div>
                                                            </div>
                                                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-800 font-bold shrink-0">Pilih</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="sm:col-span-7">
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Nama Mahasiswa</label>
                                                <input
                                                    v-model="row.name"
                                                    type="text"
                                                    placeholder="SYIFA MUTAALIA"
                                                    class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-bold uppercase"
                                                />
                                            </div>

                                            <!-- Judul Skripsi -->
                                            <div class="sm:col-span-12">
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Judul Skripsi</label>
                                                <textarea
                                                    v-model="row.thesis_title"
                                                    rows="2"
                                                    placeholder="PERANCANGAN AI AGENT BERBASIS LARGE LANGUAGE MODEL..."
                                                    class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-medium"
                                                ></textarea>
                                            </div>

                                            <!-- Pembimbing 1 -->
                                            <div class="sm:col-span-6 relative">
                                                <div class="flex items-center justify-between mb-0.5">
                                                    <label class="block text-[10px] font-semibold text-slate-500">Pembimbing 1</label>
                                                    <button
                                                        type="button"
                                                        @click="activeSeminarLecturerDropdownKey === `${rIdx}-adv1` ? activeSeminarLecturerDropdownKey = null : activeSeminarLecturerDropdownKey = `${rIdx}-adv1`; seminarLecturerSearchQuery = ''"
                                                        class="text-[10px] text-indigo-600 dark:text-indigo-400 hover:underline font-bold"
                                                    >
                                                        Pilih Dosen
                                                    </button>
                                                </div>
                                                <input
                                                    v-model="row.advisor_1"
                                                    type="text"
                                                    placeholder="Sofwandi Noor, MT"
                                                    class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-medium"
                                                />

                                                <!-- Dropdown Pembimbing 1 -->
                                                <div
                                                    v-if="activeSeminarLecturerDropdownKey === `${rIdx}-adv1`"
                                                    class="absolute left-0 top-full mt-1 w-72 sm:w-80 z-30 p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-indigo-200 dark:border-indigo-900 shadow-xl space-y-2"
                                                >
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-[11px] font-bold text-indigo-900 dark:text-indigo-300">Pilih Pembimbing 1:</span>
                                                        <button type="button" @click="activeSeminarLecturerDropdownKey = null" class="text-slate-400 hover:text-slate-600 text-xs font-bold">✕</button>
                                                    </div>
                                                    <input
                                                        v-model="seminarLecturerSearchQuery"
                                                        type="text"
                                                        placeholder="Cari dosen..."
                                                        class="w-full text-xs rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 py-1.5 px-2.5"
                                                        autofocus
                                                    />
                                                    <div class="max-h-48 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700">
                                                        <button
                                                            v-for="l in filteredSeminarLecturers"
                                                            :key="l.id"
                                                            type="button"
                                                            @click="selectSeminarLecturer(rIdx, 'advisor_1', l)"
                                                            class="w-full text-left py-1.5 px-2 hover:bg-indigo-50 dark:hover:bg-slate-700/60 rounded transition flex items-center justify-between text-xs"
                                                        >
                                                            <div>
                                                                <div class="font-bold text-slate-800 dark:text-white">{{ l.name }}</div>
                                                                <div class="text-[10px] text-slate-500 font-mono">{{ l.nidn || '' }}</div>
                                                            </div>
                                                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-800 font-bold shrink-0">Pilih</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Pembimbing 2 -->
                                            <div class="sm:col-span-6 relative">
                                                <div class="flex items-center justify-between mb-0.5">
                                                    <label class="block text-[10px] font-semibold text-slate-500">Pembimbing 2</label>
                                                    <button
                                                        type="button"
                                                        @click="activeSeminarLecturerDropdownKey === `${rIdx}-adv2` ? activeSeminarLecturerDropdownKey = null : activeSeminarLecturerDropdownKey = `${rIdx}-adv2`; seminarLecturerSearchQuery = ''"
                                                        class="text-[10px] text-indigo-600 dark:text-indigo-400 hover:underline font-bold"
                                                    >
                                                        Pilih Dosen
                                                    </button>
                                                </div>
                                                <input
                                                    v-model="row.advisor_2"
                                                    type="text"
                                                    placeholder="Bambang Tjahjo Utomo, MT"
                                                    class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-medium"
                                                />

                                                <!-- Dropdown Pembimbing 2 -->
                                                <div
                                                    v-if="activeSeminarLecturerDropdownKey === `${rIdx}-adv2`"
                                                    class="absolute left-0 top-full mt-1 w-72 sm:w-80 z-30 p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-indigo-200 dark:border-indigo-900 shadow-xl space-y-2"
                                                >
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-[11px] font-bold text-indigo-900 dark:text-indigo-300">Pilih Pembimbing 2:</span>
                                                        <button type="button" @click="activeSeminarLecturerDropdownKey = null" class="text-slate-400 hover:text-slate-600 text-xs font-bold">✕</button>
                                                    </div>
                                                    <input
                                                        v-model="seminarLecturerSearchQuery"
                                                        type="text"
                                                        placeholder="Cari dosen..."
                                                        class="w-full text-xs rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 py-1.5 px-2.5"
                                                        autofocus
                                                    />
                                                    <div class="max-h-48 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700">
                                                        <button
                                                            v-for="l in filteredSeminarLecturers"
                                                            :key="l.id"
                                                            type="button"
                                                            @click="selectSeminarLecturer(rIdx, 'advisor_2', l)"
                                                            class="w-full text-left py-1.5 px-2 hover:bg-indigo-50 dark:hover:bg-slate-700/60 rounded transition flex items-center justify-between text-xs"
                                                        >
                                                            <div>
                                                                <div class="font-bold text-slate-800 dark:text-white">{{ l.name }}</div>
                                                                <div class="text-[10px] text-slate-500 font-mono">{{ l.nidn || '' }}</div>
                                                            </div>
                                                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-800 font-bold shrink-0">Pilih</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Penguji 1 -->
                                            <div class="sm:col-span-6 relative">
                                                <div class="flex items-center justify-between mb-0.5">
                                                    <label class="block text-[10px] font-semibold text-slate-500">Penguji 1</label>
                                                    <button
                                                        type="button"
                                                        @click="activeSeminarLecturerDropdownKey === `${rIdx}-ex1` ? activeSeminarLecturerDropdownKey = null : activeSeminarLecturerDropdownKey = `${rIdx}-ex1`; seminarLecturerSearchQuery = ''"
                                                        class="text-[10px] text-indigo-600 dark:text-indigo-400 hover:underline font-bold"
                                                    >
                                                        Pilih Dosen
                                                    </button>
                                                </div>
                                                <input
                                                    v-model="row.examiner_1"
                                                    type="text"
                                                    placeholder="Bagus Ali Akbar, S.Si., M.Kom"
                                                    class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-medium"
                                                />

                                                <!-- Dropdown Penguji 1 -->
                                                <div
                                                    v-if="activeSeminarLecturerDropdownKey === `${rIdx}-ex1`"
                                                    class="absolute left-0 top-full mt-1 w-72 sm:w-80 z-30 p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-indigo-200 dark:border-indigo-900 shadow-xl space-y-2"
                                                >
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-[11px] font-bold text-indigo-900 dark:text-indigo-300">Pilih Penguji 1:</span>
                                                        <button type="button" @click="activeSeminarLecturerDropdownKey = null" class="text-slate-400 hover:text-slate-600 text-xs font-bold">✕</button>
                                                    </div>
                                                    <input
                                                        v-model="seminarLecturerSearchQuery"
                                                        type="text"
                                                        placeholder="Cari dosen..."
                                                        class="w-full text-xs rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 py-1.5 px-2.5"
                                                        autofocus
                                                    />
                                                    <div class="max-h-48 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700">
                                                        <button
                                                            v-for="l in filteredSeminarLecturers"
                                                            :key="l.id"
                                                            type="button"
                                                            @click="selectSeminarLecturer(rIdx, 'examiner_1', l)"
                                                            class="w-full text-left py-1.5 px-2 hover:bg-indigo-50 dark:hover:bg-slate-700/60 rounded transition flex items-center justify-between text-xs"
                                                        >
                                                            <div>
                                                                <div class="font-bold text-slate-800 dark:text-white">{{ l.name }}</div>
                                                                <div class="text-[10px] text-slate-500 font-mono">{{ l.nidn || '' }}</div>
                                                            </div>
                                                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-800 font-bold shrink-0">Pilih</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Penguji 2 -->
                                            <div class="sm:col-span-6 relative">
                                                <div class="flex items-center justify-between mb-0.5">
                                                    <label class="block text-[10px] font-semibold text-slate-500">Penguji 2</label>
                                                    <button
                                                        type="button"
                                                        @click="activeSeminarLecturerDropdownKey === `${rIdx}-ex2` ? activeSeminarLecturerDropdownKey = null : activeSeminarLecturerDropdownKey = `${rIdx}-ex2`; seminarLecturerSearchQuery = ''"
                                                        class="text-[10px] text-indigo-600 dark:text-indigo-400 hover:underline font-bold"
                                                    >
                                                        Pilih Dosen
                                                    </button>
                                                </div>
                                                <input
                                                    v-model="row.examiner_2"
                                                    type="text"
                                                    placeholder="Tazkia Salsabila Ardan, M.Kom"
                                                    class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-medium"
                                                />

                                                <!-- Dropdown Penguji 2 -->
                                                <div
                                                    v-if="activeSeminarLecturerDropdownKey === `${rIdx}-ex2`"
                                                    class="absolute left-0 top-full mt-1 w-72 sm:w-80 z-30 p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-indigo-200 dark:border-indigo-900 shadow-xl space-y-2"
                                                >
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-[11px] font-bold text-indigo-900 dark:text-indigo-300">Pilih Penguji 2:</span>
                                                        <button type="button" @click="activeSeminarLecturerDropdownKey = null" class="text-slate-400 hover:text-slate-600 text-xs font-bold">✕</button>
                                                    </div>
                                                    <input
                                                        v-model="seminarLecturerSearchQuery"
                                                        type="text"
                                                        placeholder="Cari dosen..."
                                                        class="w-full text-xs rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 py-1.5 px-2.5"
                                                        autofocus
                                                    />
                                                    <div class="max-h-48 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700">
                                                        <button
                                                            v-for="l in filteredSeminarLecturers"
                                                            :key="l.id"
                                                            type="button"
                                                            @click="selectSeminarLecturer(rIdx, 'examiner_2', l)"
                                                            class="w-full text-left py-1.5 px-2 hover:bg-indigo-50 dark:hover:bg-slate-700/60 rounded transition flex items-center justify-between text-xs"
                                                        >
                                                            <div>
                                                                <div class="font-bold text-slate-800 dark:text-white">{{ l.name }}</div>
                                                                <div class="text-[10px] text-slate-500 font-mono">{{ l.nidn || '' }}</div>
                                                            </div>
                                                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-800 font-bold shrink-0">Pilih</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- JIKA SK SIDANG TUGAS AKHIR (sk_dekan_pengujian_sidang_skripsi) -->
                            <template v-else-if="selectedSkType.code === 'sk_dekan_pengujian_sidang_skripsi'">
                                <div class="p-3 bg-indigo-50/70 dark:bg-indigo-950/30 rounded-xl border border-indigo-100 dark:border-indigo-900/50 space-y-2">
                                    <div class="text-xs font-bold text-indigo-950 dark:text-indigo-200">
                                        Pengaturan Header Lampiran Sidang Skripsi
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                        <div>
                                            <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Gelombang</label>
                                            <input
                                                v-model="form.extra_data.wave_info"
                                                type="text"
                                                placeholder="GELOMBANG 1"
                                                class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-bold"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Hari / Tanggal Sidang</label>
                                            <input
                                                v-model="form.extra_data.exam_schedule_date"
                                                type="text"
                                                placeholder="Kamis, 15 Oktober 2026"
                                                class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-bold"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Ruangan Ujian</label>
                                            <input
                                                v-model="form.extra_data.exam_room"
                                                type="text"
                                                placeholder="RUANG 8"
                                                class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-bold"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between pb-1 border-b border-slate-200 dark:border-slate-700">
                                    <div>
                                        <div class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                            Jadwal & Matriks Pengujian Sidang Skripsi
                                        </div>
                                        <p class="text-[10px] text-slate-400">Susunan waktu, mahasiswa, judul skripsi, pembimbing, dan penguji sidang</p>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <button
                                            type="button"
                                            @click="addSidangOpeningRow"
                                            class="px-2.5 py-1 rounded-lg text-[11px] font-bold text-slate-700 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 flex items-center gap-1 shadow-xs"
                                        >
                                            + Pembukaan
                                        </button>
                                        <button
                                            type="button"
                                            @click="addSidangStudentRow"
                                            class="px-2.5 py-1 rounded-lg text-[11px] font-bold text-indigo-700 bg-indigo-50 dark:bg-indigo-900/40 hover:bg-indigo-100 flex items-center gap-1 shadow-xs"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                            + Peserta Sidang
                                        </button>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <div
                                        v-for="(row, rIdx) in form.extra_data.sidang_matrix"
                                        :key="rIdx"
                                        class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 space-y-2.5 shadow-xs"
                                    >
                                        <div class="flex items-center justify-between pb-1 border-b border-slate-200 dark:border-slate-700">
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                                {{ row.is_header_row ? '📌 Baris Sesi Pembukaan / Persiapan' : `🎓 Mahasiswa Sidang #${rIdx}` }}
                                            </span>
                                            <button
                                                type="button"
                                                @click="removeSidangRow(rIdx)"
                                                class="text-rose-500 hover:text-rose-700 text-xs font-semibold"
                                            >
                                                Hapus ✕
                                            </button>
                                        </div>

                                        <!-- JIKA BARIS PEMBUKAAN / HEADER ROW -->
                                        <div v-if="row.is_header_row" class="grid grid-cols-1 sm:grid-cols-12 gap-2">
                                            <div class="sm:col-span-3">
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Waktu</label>
                                                <input
                                                    v-model="row.time"
                                                    type="text"
                                                    placeholder="07.30 – 08.00"
                                                    class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-center font-mono"
                                                />
                                            </div>
                                            <div class="sm:col-span-9">
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Keterangan Kegiatan</label>
                                                <input
                                                    v-model="row.note"
                                                    type="text"
                                                    placeholder="Persiapan dan Pembukaan Sidang"
                                                    class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-medium"
                                                />
                                            </div>
                                        </div>

                                        <!-- JIKA PESERTA SIDANG -->
                                        <div v-else class="grid grid-cols-1 sm:grid-cols-12 gap-2">
                                            <div class="sm:col-span-2">
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Waktu</label>
                                                <input
                                                    v-model="row.time"
                                                    type="text"
                                                    placeholder="08.00 – 09.00"
                                                    class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-center font-mono"
                                                />
                                            </div>

                                            <div class="sm:col-span-3 relative">
                                                <div class="flex items-center justify-between mb-0.5">
                                                    <label class="block text-[10px] font-semibold text-slate-500">NPM</label>
                                                    <button
                                                        type="button"
                                                        @click="activeSidangStudentDropdownIdx === rIdx ? activeSidangStudentDropdownIdx = null : activeSidangStudentDropdownIdx = rIdx; sidangStudentSearchQuery = ''"
                                                        class="text-[10px] text-indigo-600 dark:text-indigo-400 hover:underline font-bold"
                                                    >
                                                        Pilih Mahasiswa
                                                    </button>
                                                </div>
                                                <input
                                                    v-model="row.npm"
                                                    type="text"
                                                    placeholder="D1A230046"
                                                    class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-mono uppercase"
                                                />

                                                <!-- Dropdown Mahasiswa -->
                                                <div
                                                    v-if="activeSidangStudentDropdownIdx === rIdx"
                                                    class="absolute left-0 top-full mt-1 w-72 sm:w-80 z-30 p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-indigo-200 dark:border-indigo-900 shadow-xl space-y-2"
                                                >
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-[11px] font-bold text-indigo-900 dark:text-indigo-300">Cari Mahasiswa:</span>
                                                        <button type="button" @click="activeSidangStudentDropdownIdx = null" class="text-slate-400 hover:text-slate-600 text-xs font-bold">✕</button>
                                                    </div>
                                                    <input
                                                        v-model="sidangStudentSearchQuery"
                                                        type="text"
                                                        placeholder="Ketik nama / NPM..."
                                                        class="w-full text-xs rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 py-1.5 px-2.5"
                                                        autofocus
                                                    />
                                                    <div class="max-h-48 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700">
                                                        <button
                                                            v-for="s in filteredSidangStudents"
                                                            :key="s.id"
                                                            type="button"
                                                            @click="selectSidangStudent(rIdx, s)"
                                                            class="w-full text-left py-1.5 px-2 hover:bg-indigo-50 dark:hover:bg-slate-700/60 rounded transition flex items-center justify-between text-xs"
                                                        >
                                                            <div>
                                                                <div class="font-bold text-slate-800 dark:text-white uppercase">{{ s.name }}</div>
                                                                <div class="text-[10px] text-slate-500 font-mono">{{ s.npm || '-' }}</div>
                                                            </div>
                                                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-800 font-bold shrink-0">Pilih</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="sm:col-span-7">
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Nama Mahasiswa</label>
                                                <input
                                                    v-model="row.name"
                                                    type="text"
                                                    placeholder="SYIFA MUTAALIA"
                                                    class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-bold uppercase"
                                                />
                                            </div>

                                            <!-- Judul Skripsi -->
                                            <div class="sm:col-span-12">
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Judul Skripsi</label>
                                                <textarea
                                                    v-model="row.thesis_title"
                                                    rows="2"
                                                    placeholder="PERANCANGAN AI AGENT BERBASIS LARGE LANGUAGE MODEL..."
                                                    class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-medium"
                                                ></textarea>
                                            </div>

                                            <!-- Pembimbing 1 -->
                                            <div class="sm:col-span-6 relative">
                                                <div class="flex items-center justify-between mb-0.5">
                                                    <label class="block text-[10px] font-semibold text-slate-500">Pembimbing 1</label>
                                                    <button
                                                        type="button"
                                                        @click="activeSidangLecturerDropdownKey === `${rIdx}-adv1` ? activeSidangLecturerDropdownKey = null : activeSidangLecturerDropdownKey = `${rIdx}-adv1`; sidangLecturerSearchQuery = ''"
                                                        class="text-[10px] text-indigo-600 dark:text-indigo-400 hover:underline font-bold"
                                                    >
                                                        Pilih Dosen
                                                    </button>
                                                </div>
                                                <input
                                                    v-model="row.advisor_1"
                                                    type="text"
                                                    placeholder="Sofwandi Noor, MT"
                                                    class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-medium"
                                                />

                                                <!-- Dropdown Pembimbing 1 -->
                                                <div
                                                    v-if="activeSidangLecturerDropdownKey === `${rIdx}-adv1`"
                                                    class="absolute left-0 top-full mt-1 w-72 sm:w-80 z-30 p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-indigo-200 dark:border-indigo-900 shadow-xl space-y-2"
                                                >
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-[11px] font-bold text-indigo-900 dark:text-indigo-300">Pilih Pembimbing 1:</span>
                                                        <button type="button" @click="activeSidangLecturerDropdownKey = null" class="text-slate-400 hover:text-slate-600 text-xs font-bold">✕</button>
                                                    </div>
                                                    <input
                                                        v-model="sidangLecturerSearchQuery"
                                                        type="text"
                                                        placeholder="Cari dosen..."
                                                        class="w-full text-xs rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 py-1.5 px-2.5"
                                                        autofocus
                                                    />
                                                    <div class="max-h-48 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700">
                                                        <button
                                                            v-for="l in filteredSidangLecturers"
                                                            :key="l.id"
                                                            type="button"
                                                            @click="selectSidangLecturer(rIdx, 'advisor_1', l)"
                                                            class="w-full text-left py-1.5 px-2 hover:bg-indigo-50 dark:hover:bg-slate-700/60 rounded transition flex items-center justify-between text-xs"
                                                        >
                                                            <div>
                                                                <div class="font-bold text-slate-800 dark:text-white">{{ l.name }}</div>
                                                                <div class="text-[10px] text-slate-500 font-mono">{{ l.nidn || '' }}</div>
                                                            </div>
                                                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-800 font-bold shrink-0">Pilih</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Pembimbing 2 -->
                                            <div class="sm:col-span-6 relative">
                                                <div class="flex items-center justify-between mb-0.5">
                                                    <label class="block text-[10px] font-semibold text-slate-500">Pembimbing 2</label>
                                                    <button
                                                        type="button"
                                                        @click="activeSidangLecturerDropdownKey === `${rIdx}-adv2` ? activeSidangLecturerDropdownKey = null : activeSidangLecturerDropdownKey = `${rIdx}-adv2`; sidangLecturerSearchQuery = ''"
                                                        class="text-[10px] text-indigo-600 dark:text-indigo-400 hover:underline font-bold"
                                                    >
                                                        Pilih Dosen
                                                    </button>
                                                </div>
                                                <input
                                                    v-model="row.advisor_2"
                                                    type="text"
                                                    placeholder="Bambang Tjahjo Utomo, MT"
                                                    class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-medium"
                                                />

                                                <!-- Dropdown Pembimbing 2 -->
                                                <div
                                                    v-if="activeSidangLecturerDropdownKey === `${rIdx}-adv2`"
                                                    class="absolute left-0 top-full mt-1 w-72 sm:w-80 z-30 p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-indigo-200 dark:border-indigo-900 shadow-xl space-y-2"
                                                >
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-[11px] font-bold text-indigo-900 dark:text-indigo-300">Pilih Pembimbing 2:</span>
                                                        <button type="button" @click="activeSidangLecturerDropdownKey = null" class="text-slate-400 hover:text-slate-600 text-xs font-bold">✕</button>
                                                    </div>
                                                    <input
                                                        v-model="sidangLecturerSearchQuery"
                                                        type="text"
                                                        placeholder="Cari dosen..."
                                                        class="w-full text-xs rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 py-1.5 px-2.5"
                                                        autofocus
                                                    />
                                                    <div class="max-h-48 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700">
                                                        <button
                                                            v-for="l in filteredSidangLecturers"
                                                            :key="l.id"
                                                            type="button"
                                                            @click="selectSidangLecturer(rIdx, 'advisor_2', l)"
                                                            class="w-full text-left py-1.5 px-2 hover:bg-indigo-50 dark:hover:bg-slate-700/60 rounded transition flex items-center justify-between text-xs"
                                                        >
                                                            <div>
                                                                <div class="font-bold text-slate-800 dark:text-white">{{ l.name }}</div>
                                                                <div class="text-[10px] text-slate-500 font-mono">{{ l.nidn || '' }}</div>
                                                            </div>
                                                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-800 font-bold shrink-0">Pilih</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Penguji 1 -->
                                            <div class="sm:col-span-6 relative">
                                                <div class="flex items-center justify-between mb-0.5">
                                                    <label class="block text-[10px] font-semibold text-slate-500">Penguji 1</label>
                                                    <button
                                                        type="button"
                                                        @click="activeSidangLecturerDropdownKey === `${rIdx}-ex1` ? activeSidangLecturerDropdownKey = null : activeSidangLecturerDropdownKey = `${rIdx}-ex1`; sidangLecturerSearchQuery = ''"
                                                        class="text-[10px] text-indigo-600 dark:text-indigo-400 hover:underline font-bold"
                                                    >
                                                        Pilih Dosen
                                                    </button>
                                                </div>
                                                <input
                                                    v-model="row.examiner_1"
                                                    type="text"
                                                    placeholder="Bagus Ali Akbar, S.Si., M.Kom"
                                                    class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-medium"
                                                />

                                                <!-- Dropdown Penguji 1 -->
                                                <div
                                                    v-if="activeSidangLecturerDropdownKey === `${rIdx}-ex1`"
                                                    class="absolute left-0 top-full mt-1 w-72 sm:w-80 z-30 p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-indigo-200 dark:border-indigo-900 shadow-xl space-y-2"
                                                >
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-[11px] font-bold text-indigo-900 dark:text-indigo-300">Pilih Penguji 1:</span>
                                                        <button type="button" @click="activeSidangLecturerDropdownKey = null" class="text-slate-400 hover:text-slate-600 text-xs font-bold">✕</button>
                                                    </div>
                                                    <input
                                                        v-model="sidangLecturerSearchQuery"
                                                        type="text"
                                                        placeholder="Cari dosen..."
                                                        class="w-full text-xs rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 py-1.5 px-2.5"
                                                        autofocus
                                                    />
                                                    <div class="max-h-48 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700">
                                                        <button
                                                            v-for="l in filteredSidangLecturers"
                                                            :key="l.id"
                                                            type="button"
                                                            @click="selectSidangLecturer(rIdx, 'examiner_1', l)"
                                                            class="w-full text-left py-1.5 px-2 hover:bg-indigo-50 dark:hover:bg-slate-700/60 rounded transition flex items-center justify-between text-xs"
                                                        >
                                                            <div>
                                                                <div class="font-bold text-slate-800 dark:text-white">{{ l.name }}</div>
                                                                <div class="text-[10px] text-slate-500 font-mono">{{ l.nidn || '' }}</div>
                                                            </div>
                                                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-800 font-bold shrink-0">Pilih</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Penguji 2 -->
                                            <div class="sm:col-span-6 relative">
                                                <div class="flex items-center justify-between mb-0.5">
                                                    <label class="block text-[10px] font-semibold text-slate-500">Penguji 2</label>
                                                    <button
                                                        type="button"
                                                        @click="activeSidangLecturerDropdownKey === `${rIdx}-ex2` ? activeSidangLecturerDropdownKey = null : activeSidangLecturerDropdownKey = `${rIdx}-ex2`; sidangLecturerSearchQuery = ''"
                                                        class="text-[10px] text-indigo-600 dark:text-indigo-400 hover:underline font-bold"
                                                    >
                                                        Pilih Dosen
                                                    </button>
                                                </div>
                                                <input
                                                    v-model="row.examiner_2"
                                                    type="text"
                                                    placeholder="Tazkia Salsabila Ardan, M.Kom"
                                                    class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-medium"
                                                />

                                                <!-- Dropdown Penguji 2 -->
                                                <div
                                                    v-if="activeSidangLecturerDropdownKey === `${rIdx}-ex2`"
                                                    class="absolute left-0 top-full mt-1 w-72 sm:w-80 z-30 p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-indigo-200 dark:border-indigo-900 shadow-xl space-y-2"
                                                >
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-[11px] font-bold text-indigo-900 dark:text-indigo-300">Pilih Penguji 2:</span>
                                                        <button type="button" @click="activeSidangLecturerDropdownKey = null" class="text-slate-400 hover:text-slate-600 text-xs font-bold">✕</button>
                                                    </div>
                                                    <input
                                                        v-model="sidangLecturerSearchQuery"
                                                        type="text"
                                                        placeholder="Cari dosen..."
                                                        class="w-full text-xs rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 py-1.5 px-2.5"
                                                        autofocus
                                                    />
                                                    <div class="max-h-48 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700">
                                                        <button
                                                            v-for="l in filteredSidangLecturers"
                                                            :key="l.id"
                                                            type="button"
                                                            @click="selectSidangLecturer(rIdx, 'examiner_2', l)"
                                                            class="w-full text-left py-1.5 px-2 hover:bg-indigo-50 dark:hover:bg-slate-700/60 rounded transition flex items-center justify-between text-xs"
                                                        >
                                                            <div>
                                                                <div class="font-bold text-slate-800 dark:text-white">{{ l.name }}</div>
                                                                <div class="text-[10px] text-slate-500 font-mono">{{ l.nidn || '' }}</div>
                                                            </div>
                                                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-800 font-bold shrink-0">Pilih</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- JIKA SK PENGUNDURAN DIRI MAHASISWA (sk_dekan_pengunduran_diri) -->
                            <template v-else-if="selectedSkType.code === 'sk_dekan_pengunduran_diri'">
                                <div class="flex items-center justify-between pb-1 border-b border-slate-200 dark:border-slate-700">
                                    <div>
                                        <div class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                            Daftar Mahasiswa Mengundurkan Diri
                                        </div>
                                        <p class="text-[10px] text-slate-400">Daftar mahasiswa yang dinyatakan mengundurkan diri (NIM, Nama, Keterangan, IPK)</p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="addResignationRow"
                                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold text-indigo-700 bg-indigo-50 dark:bg-indigo-900/40 hover:bg-indigo-100 flex items-center gap-1 shadow-xs"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Tambah Mahasiswa
                                    </button>
                                </div>

                                <div class="space-y-3">
                                    <div
                                        v-for="(row, rIdx) in form.extra_data.resignation_matrix"
                                        :key="rIdx"
                                        class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 space-y-2.5 shadow-xs"
                                    >
                                        <div class="flex items-center justify-between pb-1 border-b border-slate-200 dark:border-slate-700">
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                                Mahasiswa #{{ rIdx + 1 }}
                                            </span>
                                            <button
                                                type="button"
                                                @click="removeResignationRow(rIdx)"
                                                class="text-rose-500 hover:text-rose-700 text-xs font-semibold"
                                            >
                                                Hapus ✕
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-2">
                                            <!-- NIM -->
                                            <div class="sm:col-span-3 relative">
                                                <div class="flex items-center justify-between mb-0.5">
                                                    <label class="block text-[10px] font-semibold text-slate-500">NIM / NPM</label>
                                                    <button
                                                        type="button"
                                                        @click="activeResignationStudentDropdownIdx === rIdx ? activeResignationStudentDropdownIdx = null : activeResignationStudentDropdownIdx = rIdx; resignationStudentSearchQuery = ''"
                                                        class="text-[10px] text-indigo-600 dark:text-indigo-400 hover:underline font-bold"
                                                    >
                                                        Pilih Mahasiswa
                                                    </button>
                                                </div>
                                                <input
                                                    v-model="row.npm"
                                                    type="text"
                                                    placeholder="D1A190012"
                                                    class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-mono uppercase"
                                                />

                                                <!-- Dropdown Mahasiswa -->
                                                <div
                                                    v-if="activeResignationStudentDropdownIdx === rIdx"
                                                    class="absolute left-0 top-full mt-1 w-72 sm:w-80 z-30 p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-indigo-200 dark:border-indigo-900 shadow-xl space-y-2"
                                                >
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-[11px] font-bold text-indigo-900 dark:text-indigo-300">Cari Mahasiswa:</span>
                                                        <button type="button" @click="activeResignationStudentDropdownIdx = null" class="text-slate-400 hover:text-slate-600 text-xs font-bold">✕</button>
                                                    </div>
                                                    <input
                                                        v-model="resignationStudentSearchQuery"
                                                        type="text"
                                                        placeholder="Ketik nama / NIM..."
                                                        class="w-full text-xs rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 py-1.5 px-2.5"
                                                        autofocus
                                                    />
                                                    <div class="max-h-48 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700">
                                                        <button
                                                            v-for="s in filteredResignationStudents"
                                                            :key="s.id"
                                                            type="button"
                                                            @click="selectResignationStudent(rIdx, s)"
                                                            class="w-full text-left py-1.5 px-2 hover:bg-indigo-50 dark:hover:bg-slate-700/60 rounded transition flex items-center justify-between text-xs"
                                                        >
                                                            <div>
                                                                <div class="font-bold text-slate-800 dark:text-white uppercase">{{ s.name }}</div>
                                                                <div class="text-[10px] text-slate-500 font-mono">{{ s.npm || '-' }}</div>
                                                            </div>
                                                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-800 font-bold shrink-0">Pilih</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- NAMA -->
                                            <div class="sm:col-span-5">
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Nama Lengkap</label>
                                                <input
                                                    v-model="row.name"
                                                    type="text"
                                                    placeholder="ADITYA BUDHI RAHMAT"
                                                    class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-bold uppercase"
                                                />
                                            </div>

                                            <!-- KETERANGAN -->
                                            <div class="sm:col-span-3">
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Keterangan</label>
                                                <input
                                                    v-model="row.reason"
                                                    type="text"
                                                    placeholder="Mengajukan Pengunduran Diri"
                                                    class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-medium"
                                                />
                                            </div>

                                            <!-- IPK -->
                                            <div class="sm:col-span-1">
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">IPK</label>
                                                <input
                                                    v-model="row.ipk"
                                                    type="text"
                                                    placeholder="2.10"
                                                    class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-center font-bold font-mono"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- JIKA SK PENGANGKATAN GKM & UKM (TANPA LAMPIRAN / MANDIRI) -->
                            <template v-else-if="selectedSkType.code === 'sk_dekan_gkm_ukm'">
                                <div class="p-6 rounded-2xl bg-indigo-50/50 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/40 text-center space-y-3">
                                    <div class="w-12 h-12 mx-auto rounded-2xl bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div class="max-w-md mx-auto space-y-1">
                                        <h3 class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                            SK Pengangkatan GKM & UKM Berformat Mandiri
                                        </h3>
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                            Format SK ini tidak memerlukan tabel matriks lampiran (landscape) terpisah. Seluruh penetapan pejabat GKM & UKM beserta rincian 5 butir tugas & wewenang telah tertuang lengkap pada naskah SK (Diktum Putusan).
                                        </p>
                                    </div>
                                    <div class="pt-2">
                                        <button
                                            type="button"
                                            @click="activeStep = 'dictums'"
                                            class="px-3 py-1.5 rounded-xl bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700 transition shadow-xs"
                                        >
                                            Kelola Diktum Putusan
                                        </button>
                                    </div>
                                </div>
                            </template>

                            <!-- ELSE: STANDARD MBKM MATRIX -->
                            <template v-else>
                                <div class="space-y-1.5 pb-2 border-b border-slate-200 dark:border-slate-700">
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                        Sub-judul Lampiran (Di Atas Tabel Matriks MBKM)
                                    </label>
                                    <textarea
                                        v-model="form.extra_data.attachment_subtitle"
                                        @input="triggerDebouncedSync"
                                        rows="4"
                                        class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 focus:ring-indigo-500 focus:border-indigo-500 leading-relaxed font-sans"
                                        placeholder="Daftar Nama Dosen Pembimbing Program Bentuk Kegiatan Pembelajaran..."
                                    ></textarea>
                                    <p class="text-[10px] text-slate-400">Gunakan baris baru (Enter) untuk memisahkan baris judul lampiran.</p>
                                </div>

                                <div class="flex items-center justify-between pb-1 border-b border-slate-200 dark:border-slate-700">
                                <div>
                                    <div class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                        Tabel Matriks Lampiran SK (Halaman Landscape)
                                    </div>
                                    <p class="text-[10px] text-slate-400">Rincian mahasiswa, mitra kegiatan, posisi, dan Dosen Pembimbing 1 & 2</p>
                                </div>
                                <button
                                    type="button"
                                    @click="addMatrixRow"
                                    class="px-2.5 py-1 rounded-lg text-[11px] font-bold text-indigo-700 bg-indigo-50 dark:bg-indigo-900/40 hover:bg-indigo-100 flex items-center gap-1 shadow-xs"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Tambah Baris
                                </button>
                            </div>

                            <div class="space-y-3">
                                <div
                                    v-for="(row, rIdx) in form.extra_data.mbkm_matrix"
                                    :key="rIdx"
                                    class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 space-y-2.5 shadow-xs"
                                >
                                    <div class="flex items-center justify-between pb-1 border-b border-slate-200 dark:border-slate-700">
                                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                            Baris #{{ rIdx + 1 }}
                                        </span>
                                        <button
                                            v-if="form.extra_data.mbkm_matrix.length > 1"
                                            type="button"
                                            @click="removeMatrixRow(rIdx)"
                                            class="text-rose-500 hover:text-rose-700 text-xs font-semibold"
                                        >
                                            Hapus ✕
                                        </button>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-2">
                                        <div class="sm:col-span-3">
                                            <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">NPM</label>
                                            <input
                                                v-model="row.npm"
                                                type="text"
                                                placeholder="D1A210020"
                                                class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-mono uppercase"
                                            />
                                        </div>
                                        <div class="sm:col-span-5">
                                            <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Nama Mahasiswa</label>
                                            <input
                                                v-model="row.name"
                                                type="text"
                                                placeholder="FANNY APRILIANTI"
                                                class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-bold uppercase"
                                            />
                                        </div>
                                        <div class="sm:col-span-4">
                                            <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Jenis BKP</label>
                                            <input
                                                v-model="row.type"
                                                type="text"
                                                placeholder="Studi Independen"
                                                class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900"
                                            />
                                        </div>

                                        <div class="sm:col-span-6">
                                            <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Mitra / Perusahaan</label>
                                            <input
                                                v-model="row.company"
                                                type="text"
                                                placeholder="Dicoding Indonesia"
                                                class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900"
                                            />
                                        </div>
                                        <div class="sm:col-span-6">
                                            <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Posisi / Learning Path</label>
                                            <input
                                                v-model="row.position"
                                                type="text"
                                                placeholder="Front-End & Back-End"
                                                class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900"
                                            />
                                        </div>

                                        <div class="sm:col-span-6">
                                            <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Pembimbing 1</label>
                                            <input
                                                v-model="row.advisor_1"
                                                type="text"
                                                placeholder="Bagus Ali Akbar, S.Si., M.Kom"
                                                class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-medium"
                                            />
                                        </div>
                                        <div class="sm:col-span-6">
                                            <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Pembimbing 2</label>
                                            <input
                                                v-model="row.advisor_2"
                                                type="text"
                                                placeholder="Achmad Syafaat, S.Kom., MT"
                                                class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 font-medium"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            </template>
                        </div>

                        <!-- TAB 5: TTD & TEMBUSAN -->
                        <div v-show="activeStep === 'sign'" class="space-y-4">
                            <!-- Pejabat Penandatangan Resmi (TTE) -->
                            <div class="p-4 rounded-xl border border-indigo-100 dark:border-indigo-900/40 bg-indigo-50/50 dark:bg-indigo-950/20 space-y-3">
                                <div>
                                    <label class="block text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider mb-1">
                                        Pejabat Penandatangan Resmi (TTE)
                                    </label>
                                    <select
                                        v-model="form.signer_id"
                                        @change="onSignerChange"
                                        class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-bold text-slate-800 dark:text-slate-200"
                                    >
                                        <option v-for="s in signers" :key="s.id" :value="s.id">
                                            {{ s.display_name || s.name }} ({{ s.role_title }})
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                        Nama Pejabat pada Naskah
                                    </label>
                                    <input
                                        v-model="form.extra_data.dekan_name"
                                        type="text"
                                        placeholder="Dr. TEPI PEIRISAL, M. Si."
                                        class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-bold"
                                        @input="triggerDebouncedSync"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                        NIK Pejabat (Opsional)
                                    </label>
                                    <input
                                        v-model="form.extra_data.dekan_nik"
                                        type="text"
                                        placeholder="0410420142"
                                        class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-mono"
                                        @input="triggerDebouncedSync"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                        Kota Penetapan
                                    </label>
                                    <input
                                        v-model="form.extra_data.decision_city"
                                        type="text"
                                        placeholder="Subang"
                                        class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                                        @input="triggerDebouncedSync"
                                    />
                                </div>
                            </div>

                            <!-- TEMBUSAN -->
                            <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 space-y-3">
                                <div class="flex items-center justify-between pb-1 border-b border-slate-200 dark:border-slate-700">
                                    <div class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                        Daftar Tembusan SK
                                    </div>
                                    <button
                                        type="button"
                                        @click="addTembusan"
                                        class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline"
                                    >
                                        + Tambah Tembusan
                                    </button>
                                </div>
                                <div class="space-y-2">
                                    <div
                                        v-for="(t, tIdx) in form.extra_data.tembusan_list"
                                        :key="tIdx"
                                        class="flex items-center gap-2"
                                    >
                                        <span class="text-xs font-bold text-slate-400 shrink-0">•</span>
                                        <input
                                            v-model="form.extra_data.tembusan_list[tIdx]"
                                            type="text"
                                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2"
                                        />
                                        <button
                                            v-if="form.extra_data.tembusan_list.length > 1"
                                            type="button"
                                            @click="removeTembusan(tIdx)"
                                            class="text-rose-500 hover:text-rose-700 text-xs shrink-0"
                                        >
                                            ✕
                                        </button>
                                    </div>
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
                                    <span class="w-2.5 h-2.5 rounded-full" :class="isLiveSyncing ? 'bg-amber-400 animate-ping' : 'bg-emerald-500'"></span>
                                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                        Pratinjau Live Dokumen SK Dekan
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

