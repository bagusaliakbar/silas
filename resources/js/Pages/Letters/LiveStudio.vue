<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import LetterPdfViewer from '@/Components/LetterPdfViewer.vue';

const props = defineProps({
    letterTypes: {
        type: Array,
        required: true,
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

// Helper for default empty extra data structure by letter type
const getDefaultDataForType = (typeCode) => {
    switch (typeCode) {
        case 'pengantar_pkl':
            return {
                target_recipient: '',
                target_agency: '',
                target_city: '',
                target_address: '',
                internship_duration: '',
                start_date: '',
                end_date: '',
            };
        case 'pengantar_magang':
            return {
                target_recipient: '',
                target_agency: '',
                target_city: '',
                target_address: '',
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
                group_members: [],
            };
        case 'izin_mengikuti_ujian':
            return {
                subject: 'Permohonan Izin Mengikuti UAS',
                lampiran_count: '2 (dua) lembar',
                target_recipient: '',
                target_agency: '',
                target_city: '',
                exam_type: 'UAS',
                lampiran_exam_title: 'UJIAN AKHIR SEMESTER',
                semester_type: 'Ganjil',
                academic_year: '2026/2027',
                exam_dates: '',
                exam_schedules: [],
            };
        case 'ujian_susulan':
            return {
                course_name: '',
                lecturer_name: '',
                exam_type: '',
                original_score: '',
                reason: '',
            };
        case 'perbaikan_nilai':
            return {
                subject: 'Perbaikan Nilai',
                course_name: '',
                lecturer_name: '',
                original_score: '',
            };
        case 'keterangan_yudisium':
            return {
                sk_number: '270 /D/FIK-US/VI/2026',
                sk_date: '26 Juni 2026',
                semester_type: 'Genap',
                ipk: '3.19',
                yudisium_predicate: 'SANGAT MEMUASKAN',
                degree_title: 'Sarjana Komputer (S.Kom)',
            };
        case 'keterangan_kuliah_pns':
            return {
                parent_name: '',
                parent_nip: '',
                parent_rank: '',
                parent_institution: '',
            };
        case 'undangan_rapat_akademik':
            return {
                agenda: 'Rapat Akademik',
                meeting_day_date: 'Selasa, 8 September 2026',
                meeting_time: '09.00 WIB s/d Selesai',
                meeting_type: 'offline',
                meeting_location: 'Ruang 12 Fakultas Ilmu Komputer',
                meeting_platform: 'Google Meet',
                meeting_link: 'https://meet.google.com/abc-defg-hij',
                recipient_name: '',
                lampiran_count: '-',
            };
        case 'tugas_mengajar':
            return {
                lecturer_status: '',
                nidn: '',
                semester_type: '',
                teaching_schedules: [],
            };
        case 'kesanggupan_mengajar':
            return {
                lecturer_name: '',
                nidn: '',
                nip: '',
                functional_position: '',
                academic_rank: 'Tenaga Pengajar',
                address: 'Subang',
                email: '',
                expertise: '',
                semester_type: 'Ganjil',
                total_sks_sum: '',
                teaching_courses: [
                    {
                        code: '',
                        name: '',
                        teaching_type: '',
                        class_name: '',
                        sks: 2,
                        total_sks: 2,
                    },
                ],
            };
        case 'pengajuan_dana':
            return {
                target_recipient: 'Rektor Universitas Subang',
                target_city: 'Subang',
                lampiran_count: '1 (Satu) Berkas',
                period_month_year: 'November 2026',
                pagu_amount: '165.200.000',
                disbursement_1_label: 'Pencairan bulan September 2026',
                disbursement_1_amount: '6.950.000',
                disbursement_2_label: 'Pencairan bulan Oktober 2026',
                disbursement_2_amount: '13.203.500',
                remaining_pagu_amount: '145.046.500',
                submitted_label: 'Pengajuan November 2026',
                submitted_amount: '26.101.509',
                last_balance_label: 'Saldo Penggunaan bulan lalu',
                last_balance_amount: '1.439.509',
                final_net_amount: '24.662.000',
                terbilang: 'Dua Puluh Empat Juta Enam Ratus Enam Puluh Dua Ribu Rupiah',
                dekan_name: 'Dr. Tepi Peirisal,S.Sos, M.Si',
                treasurer_name: 'Fina Widya Ramdhani, S.AN',
                letter_date_text: 'Subang, 2 November 2026',
            };
        case 'pengadaan_sarpras':
            return {
                lampiran_count: '1 Lembar',
                target_recipient: 'Rektor Universitas Subang',
                target_city: 'Tempat',
                subject: 'Permohonan Pengadaan Sarana dan Prasarana Fakultas Ilmu Komputer Universitas Subang',
                unit_kerja: 'BAAK FAKULTAS ILMU KOMPUTER',
                academic_year: '2026/2027',
                procurement_summary: 'Permohonan Pengadaan Sarana dan Prasarana',
                sarpras_items: [
                    'Printer Epson L3250 Sebanyak 1 Unit',
                    'Komputer Core i5 Sebanyak 2 Unit',
                ],
                considerations: [
                    'Perangkat di bagian kerja sudah tidak dapat digunakan secara optimal.',
                    'Meningkatnya beban kerja dan kompleksitas operasional layanan akademik.',
                    'Perangkat yang ada saat ini sudah berusia lebih dari 10 tahun.',
                ],
                hope_text: 'Kami berharap permohonan ini dapat dipenuhi guna memenuhi kelancaran operasional harian dan pelayanan akademik bagi mahasiswa.',
            };
        case 'perbaikan_sarpras':
            return {
                lampiran_count: '-',
                target_recipient: 'Rektor Universitas Subang',
                target_city: 'Tempat',
                subject: 'Permohonan Pergantian Sarana dan Prasarana Fakultas Ilmu Komputer',
                sarpras_items: [
                    'Kursi Pelayanan BAAK Fakultas Ilmu Komputer Sebanyak 10',
                    'Sofa 1 Set Ruang Dosen',
                ],
            };
        case 'pemberitahuan_ujian_dosen':
            return {
                lampiran_count: '-',
                target_recipient: "Bapak/Ibu Dosen\nFakultas Ilmu Komputer Universitas Subang",
                target_city: 'Tempat',
                exam_type: 'Ujian Tengah Semester',
                semester_type: 'Ganjil',
                academic_year: '2026/2027',
                exam_period: '9 November 2026 s/d 14 November 2026',
                subject: 'Pemberitahuan Ujian Tengah Semester Ganjil TA.2026/2027',
                provision_points: [
                    'Perkuliahan Daring dan atau Luring dilaksanakan sampai dengan tanggal 7 November 2026.',
                    'Yang diujikan adalah mata kuliah yang telah melaksanakan Perkuliahan Daring dan atau Luring selama 7 (tujuh) kali sebelum UTS.',
                    'Jika pelaksanaan UTS dilaksanakan secara daring maka Soal UTS dapat di upload langsung ke elearning.unsub.ac.id oleh dosen pengampu matakuliah yang bersangkutan. Atau jika pelaksanaan UTS dilaksanakan luring maka Soal UTS dapat diserahkan ke Bagian Akademik Fakultas Ilmu Komputer maksimal tanggal 7 November 2026.',
                    'Mata Kuliah inti Prodi yang wajib melakukan perkuliahan luring maka wajib melaksanakan UTS secara luring.',
                    'Mahasiswa yang belum melunasi uang kuliah atau kurang dari 75% tidak diperkenankan mengikuti UTS.',
                    'Pakaian yang digunakan selama UTS luring adalah putih hitam dengan memakai jas almamater.',
                    'Dosen pengampu agar mengawas pada saat mata kuliahnya diujikan baik secara daring atau luring, untuk mengarahkan pengisian daftar hadir di SIAKAD sesuai jadwal.',
                    'Petugas administrasi di ruangan melakukan verifikasi kartu ujian, mengingatkan pengisian absen di SIAKAD, menandatangani kartu ujian bersama dosen pengampu dapat mengakses presensi di SIAKAD.',
                    'Pengumpulan nilai UTS Ganjil maksimal Tanggal 21 November 2026 di Entry langsung oleh Dosen dalam SIAKAD UNSUB.',
                    'Tanggal 16 November 2026 kuliah seperti biasa.',
                ],
            };
        case 'pengumuman_ujian_mahasiswa':
            return {
                exam_type: 'Ujian Tengah Semester',
                semester_type: 'Ganjil',
                academic_year: '2026/2027',
                exam_period: '9 s/d 14 November 2026',
                source_reference: 'surat edaran dari Wakil Rektor I Bidang Akademik dan kemahasiswaan',
                provision_points: [
                    'Perkuliahan dilaksanakan sampai dengan tanggal 7 November 2026.',
                    'Yang diujikan adalah mata kuliah yang telah melaksanakan Perkuliahan Daring dan atau Luring selama 7 (tujuh) kali sebelum UTS.',
                    'Mahasiswa yang belum melunasi uang kuliah atau kurang dari 75%, serta bagi mahasiswa angkatan 2026, Angakatan 2023 dan P2K yang belum melunasi sampai dengan bulan November 2026 tidak diperkenankan mengikuti ujian.',
                    'Mahasiswa Menggunakan Pakaian Putih Hitam dan Jas Almamater, jika UTS diadakan secara Luring.',
                    'Hasil ujian maupun tugas/komponen Nilai Akhir lainnya, akan secepatnya diumumkan oleh Dosen kepada mahasiswa di SIAKAD maksimal 2 ( dua ) minggu setelah ujian.',
                    'Tanggal 16 November 2026 kuliah seperti biasa',
                ],
            };
        case 'perbaikan_data_pddikti':
            return {
                lampiran_count: '1 Rangkap',
                subject: 'Pengajuan Perubahan Data Mahasiswa',
                target_recipient: "Rektor Universitas Subang\nu.p. Wakil Rektor I Bid. Akademik\ndan kemahasiswaan",
                target_city: 'Tempat',
                evidence_text: 'Data yang diubah sesuai dengan fakta dan dibuktikan dengan dokumen terlampir.',
                correction_items: [
                    {
                        npm: 'D1A210020',
                        name: 'FANNY APRILIANTI',
                        initial_data: 'Kesalahan Nama : Fany Aprilianti',
                        proposed_correction: 'FANNY APRILIANTI',
                    },
                ],
            };
        case 'pembukaan_periode':
            return {
                lampiran_count: '1 (satu) berkas',
                subject: 'Pengajuan Pembukaan Periode Tipe 2',
                target_recipient: "Rektor Universitas Subang\nu.p. Wakil Rektor I Bid. Akademik\ndan kemahasiswaan",
                target_city: 'Tempat',
                purpose_description: 'Sehubungan dengan kebutuhan perbaikan data PIN mahasiswa yang akan lulus, dengan ini kami mengajukan permohonan Pembukaan Periode Tipe 2 melalui Feeder PDDIKTI untuk memperbaiki data pada Program Studi Sistem Informasi (57201). Adapun data yang kami ajukan sebagaimana data terlampir.',
            };
        case 'pengajuan_ttd_ijazah':
            return {
                lampiran_count: '1 (satu) bendel',
                subject: 'Pengajuan Penandatanganan Ijazah',
                target_recipient: 'Rektor Universitas Subang',
                target_city: 'Subang',
                opening_text: 'Bersama surat ini kami sampaikan bahwa Fakultas Ilmu Komputer Universitas Subang telah melaksanakan sidang komprehensif pada:',
                body_text: 'bersamaan dengan ini kami mengajukan penandatanganan ijazah untuk mahasiswa tersebut diatas, data suplemen pengajuan terlampir.',
                total_count_text: '32 (Tiga Puluh Dua ) mahasiswa',
                session_summary_table: [
                    {
                        session_date: '27 Agustus 2026',
                        count_text: '32 (Tiga Puluh Dua ) mahasiswa',
                    },
                ],
            };
        case 'pengajuan_blanko_ijazah_transkrip':
            return {
                lampiran_count: '1 (satu) berkas',
                subject: 'Pengajuan Blanko Ijazah dan Blanko Transkrip',
                target_recipient: 'Rektor Universitas Subang',
                target_city: 'Subang',
                opening_text: 'Bersama surat ini kami sampaikan bahwa Fakultas Ilmu Komputer Universitas Subang telah melaksanakan sidang komprehensif pada:',
                body_text: 'Sehubungan dengan hal tersebut, kami mengajukan permohonan blanko ijazah dan blanko transkrip pendamping ijazah untuk mahasiswa sebagaimana data terlampir.',
                closing_text: 'Demikian surat pengajuan ini kami sampaikan untuk dapat dipergunakan sebagaimana mestinya. Atas perhatian dan kerja samanya kami ucapkan terima kasih.',
                show_total_row: false,
                total_count_text: '12 ( Dua belas ) mahasiswa',
                session_summary_table: [
                    {
                        session_date: '26 Juni 2026',
                        count_text: '12 ( Dua belas ) mahasiswa',
                    },
                ],
            };
        case 'rekomendasi_pinjaman_bjb':
            return {
                employee_name: '',
                employee_nik: '',
                employee_position: '',
                loan_purpose: '',
                bank_name: 'Bank BJB',
                dekan_name: 'Dr. Tepi Peirisal, S.Sos, M.Si',
                dekan_nik: '0410420142',
                dekan_position: 'Dekan Fakultas Ilmu Komputer',
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
        case 'pakta_integritas':
            return {
                lecturer_name: '',
                nidn: '',
                email: '',
                courses_taught: '',
                integrity_points: [
                    'Bersedia hadir dan melaksanakan perkuliahan tatap muka pada kelas yang telah dijadwalkan oleh Prodi 14 kali pertemuan secara online di LMS (Learning Management Sistem) Universitas Subang Komputer maupun offline di lingkungan Fakultas Ilmu Komputer, bilamana dalam pelaksanaan perkuliahan tidak melaksanakan kewajiban mengajar dalam jangka waktu 3 kali berturut-turut tanpa konfirmasi kepada Kaprodi, maka Kaprodi berhak mengambil alih matakuliah dan mengganti Dosen Pengampu Mata Kuliah.',
                    'Bersedia membuat perangkat pembelajaran baik berupa RPS dan materi pembelajaran.',
                    'Bersedia memberikan pelayanan terkait dengan perkuliahan melalui media komunikasi online maupun offline dengan mahasiswa yang mengontrak mata kuliah yang diampu (dapat merespon pertanyaan mahasiswa terkait dengan perkuliahan).',
                    'Bersedia mengisi Berita Acara Perkuliahan pada setiap pertemuan melalui SIAKAD UNSUB.',
                    'Bersedia mengisi nilai hasil Ujian Tengah Semester (UTS) dan Ujian Akhir Semester (UAS) pada SIAKAD maksimal dua minggu setelah pelaksanaan Ujian. Bilamana hingga batas waktu yang telah ditentukan belum melakukan pengumpulan nilai maka Kaprodi berhak mengambil alih dalam pemberian nilai mahasiswa.',
                ],
                teaching_courses: [
                    {
                        code: '',
                        name: '',
                    },
                ],
            };
        case 'tugas_pembimbing_mbkm':
            return {
                lecturer_status: 'Dosen Tetap',
                nidn: '',
                semester_type: 'Ganjil',
                mbkm_students: [
                    {
                        name: '',
                        npm: '',
                        mitra_project: '',
                        role: 'Pembimbing I',
                    },
                ],
            };
        case 'tugas_pembimbing_ta':
            return {
                lecturer_status: '',
                nidn: '',
                semester_type: '',
                ta_students: [],
            };
        case 'izin_seminar_up':
            return {
                subject: 'Permohonan Izin Mengikuti Seminar Usulan Penelitian',
                target_recipient: 'HRD PT. Uwu Jump Indonesia',
                target_city: 'Tempat',
                activity_name: 'Seminar Usulan Penelitian',
                event_day: 'Kamis',
                group_members: [],
            };
        case 'izin_sidang_ta':
            return {
                subject: 'Permohonan Izin Mengikuti Sidang Tugas Akhir',
                target_recipient: 'HRD PT. Uwu Jump Indonesia',
                target_city: 'Tempat',
                activity_name: 'Sidang Tugas Akhir',
                event_day: 'Kamis',
                group_members: [],
            };
        default:
            return {};
    }
};

// Standar teks narasi untuk Pakta Integritas
const defaultPaktaNarration = {
    opening: 'Saya yang bertanda tangan di bawah ini:',
    content: 'Menyatakan bahwa saya akan melaksanakan ketentuan sebagai berikut:',
    closing: 'Demikian pernyataan ini saya buat, apabila di kemudian hari saya tidak memenuhi ketentuan di atas, saya bersedia menerima sanksi baik berupa teguran lisan, teguran tertulis, serta tidak diberi kesempatan mengampu mata kuliah pada semester berikutnya.',
};

// Standar teks narasi untuk Pengajuan Kebutuhan Dana Fakultas ke Rektor
const defaultPengajuanDanaNarration = {
    opening: 'Bersama ini kami sampaikan dengan hormat kebutuhan dana bulan November 2026 sebesar :',
    content: '',
    closing: 'Demikian surat permohonan pengajuan kebutuhan dana ini kami sampaikan, atas perhatian dan kebijaksanaan Bapak Rektor kami ucapkan terima kasih.',
};

// Standar teks narasi untuk Surat Pengajuan Pengadaan Sarana & Prasarana
const defaultPengadaanSarprasNarration = {
    opening: 'Sehubungan dengan meningkatnya aktivitas dan kebutuhan perangkat kerja di BAAK FAKULTAS ILMU KOMPUTER di Tahun akademik 2026/2027, bersama ini kami mengajukan permohonan pengadaan sarana dan prasarana dengan rincian dan spesifikasi sebagaimana terlampir guna menunjang pekerjaan harian.',
    content: 'Kami berharap permohonan ini dapat dipenuhi guna memenuhi kelancaran operasional harian dan pelayanan akademik bagi mahasiswa.',
    closing: 'Demikian surat permohonan ini kami sampaikan. Atas perhatian dan kerjasamanya, kami ucapkan terima kasih.',
};

// Standar teks narasi untuk Surat Perbaikan Sarana & Prasarana
const defaultPerbaikanSarprasNarration = {
    opening: 'Bersama ini kami sampaikan surat permohonan pergantian Sarana dan Prasarana Fakultas Ilmu Komputer Universitas Subang yang memerlukan pergantian.',
    content: 'Adapun sarana dan prasarana yang kami mohonkan yaitu :',
    closing: 'Demikian surat permohonan ini kami sampaikan, atas perhatiannya kami ucapkan terimakasih.',
};

// Standar teks narasi untuk Surat Pemberitahuan UTS/UAS ke Dosen
const defaultPemberitahuanUjianDosenNarration = {
    opening: 'Diberitahukan sesuai dengan kalender Akademik Fakultas Ilmu Komputer, Bahwa pelaksanaan Ujian Tengah Semester Ganjil TA. 2026/2027 akan dilaksanakan secara Daring dan atau Luring mulai tanggal 9 November 2026 s/d 14 November 2026 dengan ketentuan sebagai berikut :',
    content: '',
    closing: 'Demikian agar maklum, atas perhatiannya kami ucapkan terimakasih.',
};

// Standar teks narasi untuk Surat Pengumuman UTS/UAS ke Mahasiswa
const defaultPengumumanUjianMahasiswaNarration = {
    opening: 'Diberitahukan sesuai dengan surat edaran dari Wakil Rektor I Bidang Akademik dan kemahasiswaan, Bahwa pelaksanaan Ujian Tengah Semester Ganjil TA. 2026/2027 akan dilaksanakan secara Daring dan atau Luring mulai tanggal 9 s/d 14 November 2026 dengan ketentuan sebagai berikut :',
    content: '',
    closing: 'Demikian agar maklum, atas perhatiannya kami ucapkan terimakasih.',
};

// Standar teks narasi untuk Surat Pengantar Perbaikan Data Mahasiswa PDDIKTI
const defaultPerbaikanDataPddiktiNarration = {
    opening: 'Sehubungan dengan kesalahan pada data mahasiswa di bawah ini, dengan ini kami mengajukan perubahan data mahasiswa sesuai dengan tabel berikut ini:',
    content: 'Data yang diubah sesuai dengan fakta dan dibuktikan dengan dokumen terlampir.',
    closing: 'Demikian surat ini kami buat untuk dapat ditindaklanjuti. Atas perhatiannya kami ucapkan terimakasih.',
};

// Standar teks narasi untuk Surat Pengajuan Penandatanganan Ijazah
const defaultPengajuanTtdIjazahNarration = {
    opening: 'Bersama surat ini kami sampaikan bahwa Fakultas Ilmu Komputer Universitas Subang telah melaksanakan sidang komprehensif pada:',
    content: 'bersamaan dengan ini kami mengajukan penandatanganan ijazah untuk mahasiswa tersebut diatas, data suplemen pengajuan terlampir.',
    closing: 'Demikian surat pengajuan ini dibuat untuk dapat dipergunakan sebagaimana mestinya.',
};

// Standar teks narasi untuk Surat Pengajuan Blanko Ijazah dan Blanko Transkrip
const defaultPengajuanBlankoNarration = {
    opening: 'Bersama surat ini kami sampaikan bahwa Fakultas Ilmu Komputer Universitas Subang telah melaksanakan sidang komprehensif pada:',
    content: 'Sehubungan dengan hal tersebut, kami mengajukan permohonan blanko ijazah dan blanko transkrip pendamping ijazah untuk mahasiswa sebagaimana data terlampir.',
    closing: 'Demikian surat pengajuan ini kami sampaikan untuk dapat dipergunakan sebagaimana mestinya. Atas perhatian dan kerja samanya kami ucapkan terima kasih.',
};

// Standar teks narasi untuk Surat Rekomendasi Pinjaman BJB
const defaultRekomendasiPinjamanBjbNarration = {
    opening: 'Yang bertandatangan dibawah ini :',
    content: 'Adapun sebagai staf Fakultas Ilmu Komputer Universitas Subang, dan oleh karenanya dapat dipertimbangkan untuk diberikan kredit dari {bank_name} {loan_purpose}.\n\nUntuk kelancaran pengambilan kredit tersebut, maka kami mendukung/tidak berkeberatan untuk dipotong langsung dari gaji yang bersangkutan oleh {bank_name}.',
    closing: 'Demikian surat rekomendasi ini di buat, untuk dipergunakan sebagaimana mestinya.',
};

// Standar teks narasi untuk Surat Rekomendasi Kegiatan Pembelajaran di Luar Program Studi
const defaultRekomendasiLuarProdiNarration = {
    opening: 'Yang bertanda tangan di bawah ini:',
    content: 'Untuk menjadi peserta di {program_name} pada {activity_period} dengan pertimbangan bahwa mahasiswa tersebut sanggup mengikuti {program_name} secara penuh dan bertanggung jawab hingga akhir program selesai.\n\nSebagai bentuk dukungan, kami selaku pihak perwakilan kampus menyatakan kesediaan untuk hal berikut:\n1. Memberikan dukungan dan bertanggung jawab atas mahasiswa selama periode pelaksanaan {program_name}.\n2. Memberikan pengakuan keikutsertaan siswa/mahasiswa di {program_name} melalui konversi SKS atau hal lainnya yang dapat diakui dan menunjang kegiatan akademik di kampus.\n3. Mampu menjadi jembatan untuk berkoordinasi antara Tim {program_name} dan mahasiswa apabila terjadi masalah/kendala di kemudian hari.',
    closing: 'Demikian surat rekomendasi ini kami sampaikan untuk dipergunakan sebagaimana mestinya.',
};

// Standar teks narasi untuk Surat Keterangan Lulus (SKL)
const defaultKeteranganLulusNarration = {
    opening: '{pejabat_jabatan}, menerangkan bahwa :',
    content: 'Berdasarkan Surat Keputusan Dekan Tentang Kelulusan Ujian Sidang Sarjana Semester {semester_tipe} Tahun Akademik {tahun_akademik} Program Studi {program_studi} Fakultas Ilmu Komputer Universitas Subang, dengan Nomor : {sk_dekan_nomor}, tanggal {sk_dekan_tanggal}, kami menyatakan :\n\nSehingga yang bersangkutan berhak memakai gelar {gelar_akademik}. Adapun Ijazah dan Transkrip Nilai akan kami proses lebih lanjut.',
    closing: 'Demikian keterangan ini kami buat, untuk dipergunakan sebagaimana mestinya.',
};

const initialType = props.letterTypes[0] || {};
const initialCode = initialType.code || '';

const form = useForm({
    letter_type_id: initialType.id || '',
    student_name: '',
    npm: '',
    study_program: '',
    birth_place: '',
    birth_date: '',
    address: '',
    semester: '',
    academic_year: '',
    purpose: '',
    signer_id: props.signers[0]?.id || 'kaprodi',
    user_id: null,
    extra_data: getDefaultDataForType(initialCode),
    direct_sign: false,
    // Custom templates overrides (optional)
    opening_template: initialCode === 'pakta_integritas'
        ? defaultPaktaNarration.opening
        : (initialCode === 'pengajuan_dana'
            ? defaultPengajuanDanaNarration.opening
            : (initialCode === 'pengadaan_sarpras'
                ? defaultPengadaanSarprasNarration.opening
                : (initialCode === 'perbaikan_sarpras'
                    ? defaultPerbaikanSarprasNarration.opening
                    : (initialCode === 'pemberitahuan_ujian_dosen'
                        ? defaultPemberitahuanUjianDosenNarration.opening
                        : (initialCode === 'pengumuman_ujian_mahasiswa'
                            ? defaultPengumumanUjianMahasiswaNarration.opening
                            : (initialCode === 'perbaikan_data_pddikti'
                                ? defaultPerbaikanDataPddiktiNarration.opening
                                : (initialCode === 'pengajuan_ttd_ijazah'
                                    ? defaultPengajuanTtdIjazahNarration.opening
                                    : (initialCode === 'pengajuan_blanko_ijazah_transkrip'
                                        ? defaultPengajuanBlankoNarration.opening
                                        : (initialCode === 'rekomendasi_pinjaman_bjb'
                                            ? defaultRekomendasiPinjamanBjbNarration.opening
                                            : (initialCode === 'rekomendasi_luar_prodi'
                                                ? defaultRekomendasiLuarProdiNarration.opening
                                                : (initialCode === 'keterangan_yudisium'
                                                    ? defaultKeteranganLulusNarration.opening
                                                    : (initialType.opening_template || '')))))))))))),
    content_template: initialCode === 'pakta_integritas'
        ? defaultPaktaNarration.content
        : (initialCode === 'pengajuan_dana'
            ? defaultPengajuanDanaNarration.content
            : (initialCode === 'pengadaan_sarpras'
                ? defaultPengadaanSarprasNarration.content
                : (initialCode === 'perbaikan_sarpras'
                    ? defaultPerbaikanSarprasNarration.content
                    : (initialCode === 'pemberitahuan_ujian_dosen'
                        ? defaultPemberitahuanUjianDosenNarration.content
                        : (initialCode === 'pengumuman_ujian_mahasiswa'
                            ? defaultPengumumanUjianMahasiswaNarration.content
                            : (initialCode === 'perbaikan_data_pddikti'
                                ? defaultPerbaikanDataPddiktiNarration.content
                                : (initialCode === 'pengajuan_ttd_ijazah'
                                    ? defaultPengajuanTtdIjazahNarration.content
                                    : (initialCode === 'pengajuan_blanko_ijazah_transkrip'
                                        ? defaultPengajuanBlankoNarration.content
                                        : (initialCode === 'rekomendasi_pinjaman_bjb'
                                            ? defaultRekomendasiPinjamanBjbNarration.content
                                            : (initialCode === 'rekomendasi_luar_prodi'
                                                ? defaultRekomendasiLuarProdiNarration.content
                                                : (initialCode === 'keterangan_yudisium'
                                                    ? defaultKeteranganLulusNarration.content
                                                    : (initialType.content_template || '')))))))))))),
    closing_template: initialCode === 'pakta_integritas'
        ? defaultPaktaNarration.closing
        : (initialCode === 'pengajuan_dana'
            ? defaultPengajuanDanaNarration.closing
            : (initialCode === 'pengadaan_sarpras'
                ? defaultPengadaanSarprasNarration.closing
                : (initialCode === 'perbaikan_sarpras'
                    ? defaultPerbaikanSarprasNarration.closing
                    : (initialCode === 'pemberitahuan_ujian_dosen'
                        ? defaultPemberitahuanUjianDosenNarration.closing
                        : (initialCode === 'pengumuman_ujian_mahasiswa'
                            ? defaultPengumumanUjianMahasiswaNarration.closing
                            : (initialCode === 'perbaikan_data_pddikti'
                                ? defaultPerbaikanDataPddiktiNarration.closing
                                : (initialCode === 'pengajuan_ttd_ijazah'
                                    ? defaultPengajuanTtdIjazahNarration.closing
                                    : (initialCode === 'pengajuan_blanko_ijazah_transkrip'
                                        ? defaultPengajuanBlankoNarration.closing
                                        : (initialCode === 'rekomendasi_pinjaman_bjb'
                                            ? defaultRekomendasiPinjamanBjbNarration.closing
                                            : (initialCode === 'rekomendasi_luar_prodi'
                                                ? defaultRekomendasiLuarProdiNarration.closing
                                                : (initialCode === 'keterangan_yudisium'
                                                    ? defaultKeteranganLulusNarration.closing
                                                    : (initialType.closing_template || '')))))))))))),
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

// UI State
const activeTab = ref('form');
const activeStep = ref('type'); // 'type' | 'applicant' | 'details' | 'sign' | 'narration'
const isLecturerType = computed(() => {
    const code = selectedLetterType.value?.code;
    return ['tugas_mengajar', 'tugas_pembimbing_mbkm', 'tugas_pembimbing_ta', 'undangan_rapat_akademik', 'kesanggupan_mengajar', 'pakta_integritas', 'rekomendasi_pinjaman_bjb'].includes(code);
});

// Jenis pengumuman terbuka kampus / prodi (tanpa pemohon mahasiswa atau dosen spesifik)
const isAnnouncementType = computed(() => {
    const code = selectedLetterType.value?.code;
    return ['pengumuman_ujian_mahasiswa'].includes(code);
});

// Surat/Dokumen yang diajukan oleh Fakultas ke Rektor / Eksternal (bukan surat perorangan mahasiswa atau dosen)
const isFacultyType = computed(() => {
    const code = selectedLetterType.value?.code;
    return ['pengajuan_dana', 'pengadaan_sarpras', 'perbaikan_sarpras', 'perbaikan_data_pddikti', 'pembukaan_periode', 'pengajuan_ttd_ijazah', 'pengajuan_blanko_ijazah_transkrip', 'pemberitahuan_ujian_dosen'].includes(code);
});

// Jenis surat/dokumen tanpa nomor surat resmi (Surat Pernyataan / Fakta Integritas)
const isNoNumberType = (typeOrCode) => {
    const code = typeof typeOrCode === 'string' ? typeOrCode : typeOrCode?.code;
    return ['kesanggupan_mengajar', 'pakta_integritas'].includes(code);
};
const isLiveSyncing = ref(false);
const previewPdfBase64 = ref(null);
const previewErrorMessage = ref(null);
const lastSyncedAt = ref(null);

const showLetterTypeDropdown = ref(false);
const letterTypeSearchQuery = ref('');
const showStudentSearch = ref(false);
const studentSearchQuery = ref('');
const showLecturerSearch = ref(false);
const lecturerSearchQuery = ref('');
const showCourseSearch = ref(false);
const courseSearchQuery = ref('');
const activeTeachingCourseIdx = ref(null);
const teachingCourseSearchQuery = ref('');
const activeExamCourseIdx = ref(null);
const examCourseSearchQuery = ref('');
const activeMbkmStudentIdx = ref(null);
const mbkmStudentSearchQuery = ref('');
let syncTimeout = null;

const selectedLetterType = computed(() => {
    return props.letterTypes.find((t) => t.id === form.letter_type_id) || props.letterTypes[0] || {};
});

const currentSigner = computed(() => {
    return props.signers?.find((s) => s.id === form.signer_id || s.code === form.signer_id) || props.signers?.[0] || {
        name: 'BAGUS ALI AKBAR, S.SI.,M.Kom',
        signature_title: 'Ketua Program Studi Sistem Informasi',
    };
});

// Fetch debounced preview from backend
const fetchLivePreview = async () => {
    if (!form.letter_type_id) return;

    isLiveSyncing.value = true;
    previewErrorMessage.value = null;

    try {
        const res = await window.axios.post(route('letters.live-studio.preview'), {
            letter_type_id: form.letter_type_id,
            student_name: form.student_name,
            npm: form.npm,
            study_program: form.study_program,
            birth_place: form.birth_place,
            birth_date: form.birth_date,
            address: form.address,
            semester: form.semester,
            academic_year: form.academic_year,
            purpose: form.purpose,
            signer_id: form.signer_id,
            extra_data: form.extra_data,
            opening_template: form.opening_template,
            content_template: form.content_template,
            closing_template: form.closing_template,
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
        console.error('Error fetching studio preview:', e);
        previewErrorMessage.value = e.response?.data?.message || 'Terjadi kesalahan sistem saat merender simulasi surat.';
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

// Watch form changes to automatically update preview
watch(
    [
        () => form.letter_type_id,
        () => form.student_name,
        () => form.npm,
        () => form.study_program,
        () => form.birth_place,
        () => form.birth_date,
        () => form.address,
        () => form.semester,
        () => form.academic_year,
        () => form.purpose,
        () => form.signer_id,
        () => form.extra_data,
        () => form.opening_template,
        () => form.content_template,
        () => form.closing_template,
    ],
    () => {
        triggerDebouncedSync();
    },
    { deep: true }
);

const formatFieldLabel = (key) => {
    const labels = {
        target_agency: 'Nama Instansi / Perusahaan Tujuan',
        target_recipient: 'Penerima Surat (Yth. Pimpinan/Pejabat)',
        target_address: 'Alamat Instansi / Perusahaan',
        internship_duration: 'Durasi Magang / Kegiatan',
        course_name: 'Nama Mata Kuliah',
        lecturer_name: 'Nama Dosen Pengampu / Pembimbing',
        thesis_title: 'Judul Skripsi / Karya Ilmiah',
        activity_name: 'Nama Kegiatan / Lomba / Seminar',
        event_day: 'Hari / Tanggal Pelaksanaan',
        parent_name: 'Nama Orang Tua / Wali',
        parent_nip: 'NIP Orang Tua',
        parent_rank: 'Pangkat / Golongan Orang Tua',
        parent_institution: 'Instansi / Unit Kerja Orang Tua',
        custom_subject: 'Perihal / Judul Keterangan Surat',
        purpose: 'Keperluan Pembuatan Surat',
        target_city: 'Kota / Lokasi Tujuan',
        defense_date_text: 'Hari & Tanggal Sidang Skripsi',
        yudisium_date_text: 'Hari & Tanggal Yudisium',
        ipk: 'IPK Kelulusan',
        study_duration: 'Masa Studi (Tahun)',
        defense_grade: 'Nilai Mutu Sidang',
        defense_result: 'Hasil Keputusan Sidang',
        yudisium_predicate: 'Predikat Kelulusan',
        meeting_day_date: 'Hari & Tanggal Rapat',
        meeting_time: 'Waktu Pelaksanaan',
        meeting_type: 'Format Rapat (Tatap Muka / Online)',
        meeting_location: 'Ruangan / Tempat Rapat',
        lampiran_count: 'Jumlah Lembar Lampiran',
        start_date: 'Tanggal Mulai',
        end_date: 'Tanggal Selesai',
    };
    return labels[key] || key.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
};

// Filtered letter types by search query
const filteredLetterTypes = computed(() => {
    if (!letterTypeSearchQuery.value.trim()) return props.letterTypes;
    const q = letterTypeSearchQuery.value.toLowerCase().trim();
    return props.letterTypes.filter((t) => {
        const nameMatch = t.name?.toLowerCase().includes(q);
        const codeMatch = t.letter_code?.toLowerCase().includes(q);
        const descMatch = t.description?.toLowerCase().includes(q);
        return nameMatch || codeMatch || descMatch;
    });
});

const selectLetterType = (t) => {
    form.letter_type_id = t.id;
    showLetterTypeDropdown.value = false;
    letterTypeSearchQuery.value = '';
    onLetterTypeChange();
};

// When letter type changes, load its default template & sample extra data
const onLetterTypeChange = () => {
    const t = selectedLetterType.value;
    form.extra_data = getDefaultDataForType(t.code);
    if (t.code === 'rekomendasi_pinjaman_bjb') {
        const dekanSigner = props.signers.find((s) => s.code === 'dekan' || s.id === 'dekan');
        if (dekanSigner) {
            form.signer_id = dekanSigner.id;
        }
    } else if (t.code === 'rekomendasi_luar_prodi') {
        const wadekSigner = props.signers.find((s) => s.code === 'wadek' || s.id === 'wadek');
        if (wadekSigner) {
            form.signer_id = wadekSigner.id;
        }
    } else if (t.letter_code === 'D/FIK-US' || t.code === 'pengajuan_dana') {
        const dekanSigner = props.signers.find((s) => s.code === 'dekan' || s.id === 'dekan');
        if (dekanSigner) {
            form.signer_id = dekanSigner.id;
        }
    }
    if (isFacultyType.value || isAnnouncementType.value) {
        form.student_name = 'Fakultas Ilmu Komputer';
        form.npm = 'FAK-IK';
        form.study_program = 'Fakultas Ilmu Komputer';
    }
    if (Array.isArray(t.required_fields)) {
        t.required_fields.forEach((k) => {
            if (form.extra_data[k] === undefined) {
                form.extra_data[k] = '';
            }
        });
    }
    if (t.code === 'pakta_integritas') {
        form.opening_template = t.opening_template || defaultPaktaNarration.opening;
        form.content_template = t.content_template || defaultPaktaNarration.content;
        form.closing_template = t.closing_template || defaultPaktaNarration.closing;
    } else if (t.code === 'pengajuan_dana') {
        form.opening_template = t.opening_template || defaultPengajuanDanaNarration.opening;
        form.content_template = t.content_template || defaultPengajuanDanaNarration.content;
        form.closing_template = t.closing_template || defaultPengajuanDanaNarration.closing;
    } else if (t.code === 'pengadaan_sarpras') {
        form.opening_template = t.opening_template || defaultPengadaanSarprasNarration.opening;
        form.content_template = t.content_template || defaultPengadaanSarprasNarration.content;
        form.closing_template = t.closing_template || defaultPengadaanSarprasNarration.closing;
    } else if (t.code === 'perbaikan_sarpras') {
        form.opening_template = t.opening_template || defaultPerbaikanSarprasNarration.opening;
        form.content_template = t.content_template || defaultPerbaikanSarprasNarration.content;
        form.closing_template = t.closing_template || defaultPerbaikanSarprasNarration.closing;
    } else if (t.code === 'pemberitahuan_ujian_dosen') {
        form.opening_template = t.opening_template || defaultPemberitahuanUjianDosenNarration.opening;
        form.content_template = t.content_template || defaultPemberitahuanUjianDosenNarration.content;
        form.closing_template = t.closing_template || defaultPemberitahuanUjianDosenNarration.closing;
    } else if (t.code === 'pengumuman_ujian_mahasiswa') {
        form.opening_template = t.opening_template || defaultPengumumanUjianMahasiswaNarration.opening;
        form.content_template = t.content_template || defaultPengumumanUjianMahasiswaNarration.content;
        form.closing_template = t.closing_template || defaultPengumumanUjianMahasiswaNarration.closing;
    } else if (t.code === 'perbaikan_data_pddikti') {
        form.opening_template = t.opening_template || defaultPerbaikanDataPddiktiNarration.opening;
        form.content_template = t.content_template || defaultPerbaikanDataPddiktiNarration.content;
        form.closing_template = t.closing_template || defaultPerbaikanDataPddiktiNarration.closing;
    } else if (t.code === 'pengajuan_ttd_ijazah') {
        form.opening_template = t.opening_template || defaultPengajuanTtdIjazahNarration.opening;
        form.content_template = t.content_template || defaultPengajuanTtdIjazahNarration.content;
        form.closing_template = t.closing_template || defaultPengajuanTtdIjazahNarration.closing;
    } else if (t.code === 'pengajuan_blanko_ijazah_transkrip') {
        form.opening_template = t.opening_template || defaultPengajuanBlankoNarration.opening;
        form.content_template = t.content_template || defaultPengajuanBlankoNarration.content;
        form.closing_template = t.closing_template || defaultPengajuanBlankoNarration.closing;
    } else if (t.code === 'rekomendasi_pinjaman_bjb') {
        form.opening_template = defaultRekomendasiPinjamanBjbNarration.opening;
        form.content_template = defaultRekomendasiPinjamanBjbNarration.content;
        form.closing_template = defaultRekomendasiPinjamanBjbNarration.closing;
    } else if (t.code === 'rekomendasi_luar_prodi') {
        form.opening_template = defaultRekomendasiLuarProdiNarration.opening;
        form.content_template = defaultRekomendasiLuarProdiNarration.content;
        form.closing_template = defaultRekomendasiLuarProdiNarration.closing;
    } else if (t.code === 'keterangan_yudisium') {
        form.opening_template = defaultKeteranganLulusNarration.opening;
        form.content_template = defaultKeteranganLulusNarration.content;
        form.closing_template = defaultKeteranganLulusNarration.closing;
    } else {
        form.opening_template = t.opening_template || '';
        form.content_template = t.content_template || '';
        form.closing_template = t.closing_template || '';
    }
    triggerDebouncedSync();
};

onMounted(() => {
    const t = selectedLetterType.value;
    if (t) {
        if (t.code === 'pakta_integritas') {
            form.opening_template = form.opening_template || t.opening_template || defaultPaktaNarration.opening;
            form.content_template = form.content_template || t.content_template || defaultPaktaNarration.content;
            form.closing_template = form.closing_template || t.closing_template || defaultPaktaNarration.closing;
        } else if (t.code === 'pengajuan_dana') {
            form.opening_template = form.opening_template || t.opening_template || defaultPengajuanDanaNarration.opening;
            form.content_template = form.content_template || t.content_template || defaultPengajuanDanaNarration.content;
            form.closing_template = form.closing_template || t.closing_template || defaultPengajuanDanaNarration.closing;
        } else if (t.code === 'pengadaan_sarpras') {
            form.opening_template = form.opening_template || t.opening_template || defaultPengadaanSarprasNarration.opening;
            form.content_template = form.content_template || t.content_template || defaultPengadaanSarprasNarration.content;
            form.closing_template = form.closing_template || t.closing_template || defaultPengadaanSarprasNarration.closing;
        } else if (t.code === 'perbaikan_sarpras') {
            form.opening_template = form.opening_template || t.opening_template || defaultPerbaikanSarprasNarration.opening;
            form.content_template = form.content_template || t.content_template || defaultPerbaikanSarprasNarration.content;
            form.closing_template = form.closing_template || t.closing_template || defaultPerbaikanSarprasNarration.closing;
        } else if (t.code === 'pemberitahuan_ujian_dosen') {
            form.opening_template = form.opening_template || t.opening_template || defaultPemberitahuanUjianDosenNarration.opening;
            form.content_template = form.content_template || t.content_template || defaultPemberitahuanUjianDosenNarration.content;
            form.closing_template = form.closing_template || t.closing_template || defaultPemberitahuanUjianDosenNarration.closing;
        } else if (t.code === 'pengumuman_ujian_mahasiswa') {
            form.opening_template = form.opening_template || t.opening_template || defaultPengumumanUjianMahasiswaNarration.opening;
            form.content_template = form.content_template || t.content_template || defaultPengumumanUjianMahasiswaNarration.content;
            form.closing_template = form.closing_template || t.closing_template || defaultPengumumanUjianMahasiswaNarration.closing;
        } else if (t.code === 'perbaikan_data_pddikti') {
            form.opening_template = form.opening_template || t.opening_template || defaultPerbaikanDataPddiktiNarration.opening;
            form.content_template = form.content_template || t.content_template || defaultPerbaikanDataPddiktiNarration.content;
            form.closing_template = form.closing_template || t.closing_template || defaultPerbaikanDataPddiktiNarration.closing;
        } else if (t.code === 'pengajuan_ttd_ijazah') {
            form.opening_template = form.opening_template || t.opening_template || defaultPengajuanTtdIjazahNarration.opening;
            form.content_template = form.content_template || t.content_template || defaultPengajuanTtdIjazahNarration.content;
            form.closing_template = form.closing_template || t.closing_template || defaultPengajuanTtdIjazahNarration.closing;
        } else if (t.code === 'pengajuan_blanko_ijazah_transkrip') {
            form.opening_template = form.opening_template || t.opening_template || defaultPengajuanBlankoNarration.opening;
            form.content_template = form.content_template || t.content_template || defaultPengajuanBlankoNarration.content;
            form.closing_template = form.closing_template || t.closing_template || defaultPengajuanBlankoNarration.closing;
        } else if (t.code === 'rekomendasi_pinjaman_bjb') {
            form.opening_template = defaultRekomendasiPinjamanBjbNarration.opening;
            form.content_template = defaultRekomendasiPinjamanBjbNarration.content;
            form.closing_template = defaultRekomendasiPinjamanBjbNarration.closing;
        } else if (t.code === 'rekomendasi_luar_prodi') {
            form.opening_template = defaultRekomendasiLuarProdiNarration.opening;
            form.content_template = defaultRekomendasiLuarProdiNarration.content;
            form.closing_template = defaultRekomendasiLuarProdiNarration.closing;
        } else if (t.code === 'keterangan_yudisium') {
            form.opening_template = defaultKeteranganLulusNarration.opening;
            form.content_template = defaultKeteranganLulusNarration.content;
            form.closing_template = defaultKeteranganLulusNarration.closing;
        } else {
            form.opening_template = form.opening_template || t.opening_template || '';
            form.content_template = form.content_template || t.content_template || '';
            form.closing_template = form.closing_template || t.closing_template || '';
        }
    }
    fetchLivePreview();
});

// Auto-fill student data helper
const selectStudent = (student) => {
    form.user_id = student.id;
    form.student_name = student.name;
    const extractedNpm = student.email?.split('@')[0]?.toUpperCase();
    if (student.npm) {
        form.npm = student.npm;
    } else if (extractedNpm && extractedNpm.startsWith('D1A')) {
        form.npm = extractedNpm;
    }
    if (student.study_program) form.study_program = student.study_program;
    if (student.birth_place) form.birth_place = student.birth_place;
    if (student.birth_date) form.birth_date = student.birth_date;
    if (student.address) form.address = student.address;
    if (student.semester) form.semester = student.semester;
    if (student.academic_year) form.academic_year = student.academic_year;

    showStudentSearch.value = false;
};

// Filtered student list
const filteredStudents = computed(() => {
    if (!studentSearchQuery.value) return props.students.slice(0, 10);
    const q = studentSearchQuery.value.toLowerCase();
    return props.students.filter(
        (s) => s.name?.toLowerCase().includes(q) || s.email?.toLowerCase().includes(q)
    ).slice(0, 15);
});

// Auto-fill lecturer data helper
const selectLecturer = (lecturer) => {
    form.student_name = lecturer.name;
    form.npm = lecturer.nidn || lecturer.nip || '';
    if (lecturer.study_program) {
        form.study_program = lecturer.study_program;
    }
    if (form.extra_data) {
        form.extra_data.lecturer_name = lecturer.name;
        form.extra_data.nidn = lecturer.nidn || lecturer.nip || '';
        form.extra_data.nip = lecturer.nip || '';
        form.extra_data.email = lecturer.email || '';
        form.extra_data.lecturer_email = lecturer.email || '';
        if (lecturer.functional_position) {
            form.extra_data.functional_position = lecturer.functional_position;
        }
        if (lecturer.academic_rank) {
            form.extra_data.academic_rank = lecturer.academic_rank;
        }
    }
    showLecturerSearch.value = false;
};

// Filtered lecturer list
const filteredLecturers = computed(() => {
    if (!lecturerSearchQuery.value) return props.lecturers.slice(0, 10);
    const q = lecturerSearchQuery.value.toLowerCase();
    return props.lecturers.filter(
        (l) => l.name?.toLowerCase().includes(q) ||
               (l.nidn && l.nidn.toLowerCase().includes(q)) ||
               (l.functional_position && l.functional_position.toLowerCase().includes(q))
    ).slice(0, 15);
});

// Auto-fill course data helper (single course fields)
const selectCourse = (course) => {
    if (form.extra_data) {
        form.extra_data.course_name = course.name;
        form.extra_data.course_code = course.code;
        form.extra_data.sks = course.sks;
    }
    showCourseSearch.value = false;
};

// Course selector for Teaching Schedules (Tugas Mengajar)
const selectTeachingCourse = (idx, course) => {
    if (form.extra_data?.teaching_schedules?.[idx]) {
        const ts = form.extra_data.teaching_schedules[idx];
        ts.course_name = course.name;
        ts.code = course.code;
        ts.sks = course.sks;
    }
    activeTeachingCourseIdx.value = null;
    teachingCourseSearchQuery.value = '';
};

// Course selector for Kesanggupan Mengajar
const activeKesanggupanCourseIdx = ref(null);
const kesanggupanCourseSearchQuery = ref('');
const updateKesanggupanTotalSks = (tc) => {
    const sks = parseInt(tc.sks) || 0;
    const kelasCount = parseInt(tc.class_name) || 1;
    tc.total_sks = sks * kelasCount;
};
const selectKesanggupanCourse = (idx, course) => {
    if (form.extra_data?.teaching_courses?.[idx]) {
        const tc = form.extra_data.teaching_courses[idx];
        tc.name = course.name;
        tc.code = course.code;
        tc.sks = course.sks;
        updateKesanggupanTotalSks(tc);
    }
    activeKesanggupanCourseIdx.value = null;
    kesanggupanCourseSearchQuery.value = '';
};
const filteredKesanggupanCourses = computed(() => {
    if (!props.courses) return [];
    if (!kesanggupanCourseSearchQuery.value) return props.courses.slice(0, 10);
    const q = kesanggupanCourseSearchQuery.value.toLowerCase();
    return props.courses.filter(
        (c) => c.name?.toLowerCase().includes(q) || c.code?.toLowerCase().includes(q)
    ).slice(0, 15);
});

const filteredTeachingCourses = computed(() => {
    if (!props.courses) return [];
    if (!teachingCourseSearchQuery.value) return props.courses.slice(0, 10);
    const q = teachingCourseSearchQuery.value.toLowerCase();
    return props.courses.filter(
        (c) => c.name?.toLowerCase().includes(q) || c.code?.toLowerCase().includes(q)
    ).slice(0, 15);
});

// Course selector for Exam Schedules (Izin Mengikuti Ujian)
const selectExamCourse = (idx, course) => {
    if (form.extra_data?.exam_schedules?.[idx]) {
        const sched = form.extra_data.exam_schedules[idx];
        sched.course_name = course.name;
        sched.sks = course.sks;
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

// Filtered course list
const filteredCourses = computed(() => {
    if (!props.courses) return [];
    if (!courseSearchQuery.value) return props.courses.slice(0, 10);
    const q = courseSearchQuery.value.toLowerCase();
    return props.courses.filter(
        (c) => c.name?.toLowerCase().includes(q) ||
               c.code?.toLowerCase().includes(q)
    ).slice(0, 15);
});

// Group member helpers
const addGroupMember = () => {
    if (!Array.isArray(form.extra_data.group_members)) {
        form.extra_data.group_members = [];
    }
    const defaultActivity = selectedLetterType.value?.code === 'izin_seminar_up'
        ? 'Seminar Usulan Penelitian'
        : (selectedLetterType.value?.code === 'izin_sidang_ta' ? 'Sidang Tugas Akhir' : '');
    form.extra_data.group_members.push({
        name: '',
        npm: '',
        address: 'SUBANG',
        activity: defaultActivity,
        day: form.extra_data.event_day || 'Kamis',
    });
};
const removeGroupMember = (idx) => {
    form.extra_data.group_members.splice(idx, 1);
};

// Exam schedule helpers
const addExamSchedule = () => {
    if (!Array.isArray(form.extra_data.exam_schedules)) {
        form.extra_data.exam_schedules = [];
    }
    form.extra_data.exam_schedules.push({
        course_name: '',
        class_name: '',
        sks: '',
        date: '',
        time: '',
        room: '',
    });
};
const removeExamSchedule = (idx) => {
    form.extra_data.exam_schedules.splice(idx, 1);
};

// Teaching schedule helpers
const addTeachingSchedule = () => {
    if (!Array.isArray(form.extra_data.teaching_schedules)) {
        form.extra_data.teaching_schedules = [];
    }
    form.extra_data.teaching_schedules.push({
        course_name: '',
        code: '',
        sks: '',
        class_name: '',
        day: '',
        time: '',
        room: '',
    });
};
const removeTeachingSchedule = (idx) => {
    form.extra_data.teaching_schedules.splice(idx, 1);
};

// MBKM student helpers
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
const removeMbkmStudent = (idx) => {
    form.extra_data.mbkm_students.splice(idx, 1);
};

const selectMbkmStudent = (idx, student) => {
    if (form.extra_data?.mbkm_students?.[idx]) {
        const item = form.extra_data.mbkm_students[idx];
        item.name = student.name;
        item.npm = student.npm || student.identifier || '';
    }
    activeMbkmStudentIdx.value = null;
    mbkmStudentSearchQuery.value = '';
};

const filteredMbkmStudents = computed(() => {
    if (!props.students) return [];
    if (!mbkmStudentSearchQuery.value) return props.students.slice(0, 10);
    const q = mbkmStudentSearchQuery.value.toLowerCase();
    return props.students.filter(
        (s) => s.name?.toLowerCase().includes(q) || s.npm?.toLowerCase().includes(q) || s.email?.toLowerCase().includes(q)
    ).slice(0, 15);
});

// TA student helpers
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
const removeTaStudent = (idx) => {
    form.extra_data.ta_students.splice(idx, 1);
};

// Kesanggupan Mengajar Course Helpers
const addKesanggupanCourse = () => {
    if (!Array.isArray(form.extra_data.teaching_courses)) {
        form.extra_data.teaching_courses = [];
    }
    form.extra_data.teaching_courses.push({
        code: '',
        name: '',
        teaching_type: '',
        class_name: '',
        sks: 2,
        total_sks: 2,
    });
};
const removeKesanggupanCourse = (idx) => {
    form.extra_data.teaching_courses.splice(idx, 1);
};

// Pengadaan Sarpras Considerations Helpers
const addConsiderationPoint = () => {
    if (!Array.isArray(form.extra_data.considerations)) {
        form.extra_data.considerations = [];
    }
    form.extra_data.considerations.push('');
};
const removeConsiderationPoint = (idx) => {
    form.extra_data.considerations.splice(idx, 1);
};

// Perbaikan Sarpras Items Helpers
const addSarprasItem = () => {
    if (!Array.isArray(form.extra_data.sarpras_items)) {
        form.extra_data.sarpras_items = [];
    }
    form.extra_data.sarpras_items.push('');
};
const removeSarprasItem = (idx) => {
    form.extra_data.sarpras_items.splice(idx, 1);
};

// Pemberitahuan Ujian Dosen Points Helpers
const addProvisionPoint = () => {
    if (!Array.isArray(form.extra_data.provision_points)) {
        form.extra_data.provision_points = [];
    }
    form.extra_data.provision_points.push('');
};
const removeProvisionPoint = (idx) => {
    form.extra_data.provision_points.splice(idx, 1);
};

// Perbaikan Data PDDIKTI Items Helpers
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
const removeCorrectionItem = (idx) => {
    form.extra_data.correction_items.splice(idx, 1);
};

// Pengajuan Penandatanganan Ijazah Session Summary Helpers
const addSessionSummary = () => {
    if (!Array.isArray(form.extra_data.session_summary_table)) {
        form.extra_data.session_summary_table = [];
    }
    form.extra_data.session_summary_table.push({
        session_date: '',
        count_text: '',
    });
};
const removeSessionSummary = (idx) => {
    form.extra_data.session_summary_table.splice(idx, 1);
};

// Pakta Integritas Points Helpers
const defaultPaktaPoints = [
    'Bersedia hadir dan melaksanakan perkuliahan tatap muka pada kelas yang telah dijadwalkan oleh Prodi 14 kali pertemuan secara online di LMS (Learning Management Sistem) Universitas Subang Komputer maupun offline di lingkungan Fakultas Ilmu Komputer, bilamana dalam pelaksanaan perkuliahan tidak melaksanakan kewajiban mengajar dalam jangka waktu 3 kali berturut-turut tanpa konfirmasi kepada Kaprodi, maka Kaprodi berhak mengambil alih matakuliah dan mengganti Dosen Pengampu Mata Kuliah.',
    'Bersedia membuat perangkat pembelajaran baik berupa RPS dan materi pembelajaran.',
    'Bersedia memberikan pelayanan terkait dengan perkuliahan melalui media komunikasi online maupun offline dengan mahasiswa yang mengontrak mata kuliah yang diampu (dapat merespon pertanyaan mahasiswa terkait dengan perkuliahan).',
    'Bersedia mengisi Berita Acara Perkuliahan pada setiap pertemuan melalui SIAKAD UNSUB.',
    'Bersedia mengisi nilai hasil Ujian Tengah Semester (UTS) dan Ujian Akhir Semester (UAS) pada SIAKAD maksimal dua minggu setelah pelaksanaan Ujian. Bilamana hingga batas waktu yang telah ditentukan belum melakukan pengumpulan nilai maka Kaprodi berhak mengambil alih dalam pemberian nilai mahasiswa.',
];

const addIntegrityPoint = () => {
    if (!Array.isArray(form.extra_data.integrity_points)) {
        form.extra_data.integrity_points = [...defaultPaktaPoints];
    }
    form.extra_data.integrity_points.push('');
};

const removeIntegrityPoint = (idx) => {
    if (Array.isArray(form.extra_data.integrity_points)) {
        form.extra_data.integrity_points.splice(idx, 1);
    }
};

const resetIntegrityPoints = () => {
    form.extra_data.integrity_points = [...defaultPaktaPoints];
};

// Submit form
const submitLetter = (directSign = false) => {
    form.direct_sign = directSign;
    form.post(route('letters.live-studio.store'), {
        preserveScroll: true,
        onError: (errors) => {
            console.error('Validation errors:', errors);
        },
    });
};
</script>

<template>
    <Head title="Studio Surat" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 text-xs font-medium text-slate-500 dark:text-slate-400">
                        <Link :href="route('letters.index')" class="hover:text-blue-600 transition-colors">
                            Layanan Persuratan
                        </Link>
                        <span>/</span>
                        <span class="text-blue-600 dark:text-blue-400 font-semibold">Studio Surat</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div>
                            <h2 class="text-xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
                                Studio Surat Live
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
                                    ? 'bg-white dark:bg-slate-900 text-blue-600 dark:text-blue-400 shadow-xs'
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
                                    ? 'bg-white dark:bg-slate-900 text-blue-600 dark:text-blue-400 shadow-xs'
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
                                    ? 'bg-white dark:bg-slate-900 text-blue-600 dark:text-blue-400 shadow-xs'
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
                        @click="submitLetter(false)"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-950/60 border border-blue-200 dark:border-blue-800 hover:bg-blue-100 dark:hover:bg-blue-900/60 transition-colors"
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
                    <!-- LEFT COLUMN: INTERACTIVE FORM -->
                    <div :class="[editorColClass, 'space-y-4 transition-all duration-300']">
                        <!-- Validation Error Alert -->
                        <div
                            v-if="Object.keys(form.errors).length > 0"
                            class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-900/60 text-xs text-rose-700 dark:text-rose-300 space-y-1 shadow-xs"
                        >
                            <div class="font-bold flex items-center gap-1.5 text-rose-800 dark:text-rose-200">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Terdapat data yang belum lengkap atau tidak valid:</span>
                            </div>
                            <ul class="list-disc list-inside pl-1 space-y-0.5">
                                <li v-for="(errMsg, errKey) in form.errors" :key="errKey">
                                    {{ errMsg }}
                                </li>
                            </ul>
                        </div>

                        <!-- TAB NAVIGATION PILLS -->
                        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-xs p-1.5 flex flex-wrap gap-1">
                            <button
                                type="button"
                                @click="activeStep = 'type'"
                                :class="[
                                    'flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold transition-all',
                                    activeStep === 'type'
                                        ? 'bg-blue-600 text-white shadow-sm'
                                        : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
                                ]"
                            >
                                <span>1. Format Surat</span>
                            </button>
                            <button
                                v-if="selectedLetterType.code !== 'pembukaan_periode' && selectedLetterType.code !== 'rekomendasi_pinjaman_bjb'"
                                type="button"
                                @click="activeStep = 'applicant'"
                                :class="[
                                    'flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold transition-all',
                                    activeStep === 'applicant'
                                        ? 'bg-blue-600 text-white shadow-sm'
                                        : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
                                ]"
                            >
                                <span>2. Identitas</span>
                            </button>
                            <button
                                type="button"
                                @click="activeStep = 'details'"
                                :class="[
                                    'flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold transition-all',
                                    activeStep === 'details'
                                        ? 'bg-blue-600 text-white shadow-sm'
                                        : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
                                ]"
                            >
                                <span>{{ (selectedLetterType.code === 'pembukaan_periode' || selectedLetterType.code === 'rekomendasi_pinjaman_bjb') ? '2. Rincian & Identitas Surat' : '3. Rincian Surat' }}</span>
                            </button>
                            <button
                                type="button"
                                @click="activeStep = 'sign'"
                                :class="[
                                    'flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold transition-all',
                                    activeStep === 'sign'
                                        ? 'bg-blue-600 text-white shadow-sm'
                                        : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
                                ]"
                            >
                                <span>{{ (selectedLetterType.code === 'pembukaan_periode' || selectedLetterType.code === 'rekomendasi_pinjaman_bjb') ? '3. Penandatangan' : '4. Penandatangan' }}</span>
                            </button>
                            <button
                                type="button"
                                @click="activeStep = 'narration'"
                                :class="[
                                    'flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold transition-all',
                                    activeStep === 'narration'
                                        ? 'bg-blue-600 text-white shadow-sm'
                                        : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
                                ]"
                            >
                                <span>{{ (selectedLetterType.code === 'pembukaan_periode' || selectedLetterType.code === 'rekomendasi_pinjaman_bjb') ? '4. Narasi' : '5. Narasi' }}</span>
                            </button>
                        </div>

                        <!-- Form Card Container -->
                        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 p-5 shadow-xs space-y-5">
                            
                            <!-- ============================================== -->
                            <!-- STEP 1: PILIH JENIS SURAT                      -->
                            <!-- ============================================== -->
                            <div v-show="activeStep === 'type'" class="space-y-4">
                                <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                                    <div>
                                        <h2 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">
                                            1. Pilih Format / Jenis Surat Fasilkom
                                        </h2>
                                        <p class="text-[11px] text-slate-500">Pilih salah satu template surat resmi yang tersedia</p>
                                    </div>
                                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-md bg-blue-50 dark:bg-blue-950 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-800">
                                        {{ letterTypes.length }} Format
                                    </span>
                                </div>

                                <!-- Searchable Custom Dropdown Trigger -->
                                <div class="relative">
                                    <button
                                        type="button"
                                        @click="showLetterTypeDropdown = !showLetterTypeDropdown"
                                        :class="[
                                            'w-full text-left rounded-xl border p-3 transition flex items-center justify-between gap-2 shadow-2xs',
                                            showLetterTypeDropdown
                                                ? 'border-blue-600 ring-2 ring-blue-600/20 bg-blue-50/20 dark:bg-blue-950/20'
                                                : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-slate-300 dark:hover:border-slate-600'
                                        ]"
                                    >
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <span
                                                :class="[
                                                    'shrink-0 px-2 py-0.5 rounded-md text-[10px] font-mono font-bold border',
                                                    isNoNumberType(selectedLetterType)
                                                        ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/80 dark:text-amber-300 border-amber-200/70 dark:border-amber-800/70'
                                                        : 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300 border border-blue-200/60 dark:border-blue-800/60'
                                                ]"
                                            >
                                                {{ isNoNumberType(selectedLetterType) ? 'DOKUMEN' : (selectedLetterType.letter_code || 'SURAT') }}
                                            </span>
                                            <span class="text-xs sm:text-sm font-semibold text-slate-900 dark:text-white truncate">
                                                {{ selectedLetterType.name || 'Pilih Format Surat' }}
                                            </span>
                                        </div>
                                        <svg
                                            :class="['w-4 h-4 text-slate-400 shrink-0 transition-transform duration-200', showLetterTypeDropdown ? 'rotate-180 text-blue-600' : '']"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>

                                    <!-- Backdrop Overlay for Outside Click -->
                                    <div
                                        v-if="showLetterTypeDropdown"
                                        @click="showLetterTypeDropdown = false"
                                        class="fixed inset-0 z-30"
                                    ></div>

                                    <!-- Dropdown Menu with Search Input -->
                                    <div
                                        v-if="showLetterTypeDropdown"
                                        class="absolute left-0 right-0 top-full mt-1.5 z-40 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xl overflow-hidden animate-in fade-in zoom-in-95 duration-150"
                                    >
                                        <!-- Search Input Header -->
                                        <div class="p-2.5 border-b border-slate-100 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-900">
                                            <div class="relative">
                                                <input
                                                    v-model="letterTypeSearchQuery"
                                                    type="text"
                                                    placeholder="Cari jenis surat (misal: PKL, UTS, Magang, Rekomendasi)..."
                                                    class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white pl-8 pr-3 py-2 focus:ring-2 focus:ring-blue-600 focus:border-blue-600 placeholder-slate-400"
                                                    autofocus
                                                    @keydown.esc="showLetterTypeDropdown = false"
                                                />
                                                <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                </svg>
                                            </div>
                                        </div>

                                        <!-- Results List -->
                                        <div class="max-h-64 overflow-y-auto p-1.5 space-y-0.5 divide-y divide-slate-100 dark:divide-slate-800 bg-white dark:bg-slate-900">
                                            <div
                                                v-for="t in filteredLetterTypes"
                                                :key="t.id"
                                                @click="selectLetterType(t)"
                                                :class="[
                                                    'p-2.5 rounded-xl cursor-pointer transition flex items-center justify-between gap-3 text-xs',
                                                    t.id === form.letter_type_id
                                                        ? 'bg-blue-50 dark:bg-blue-950/70 text-blue-900 dark:text-blue-200 font-semibold'
                                                        : 'hover:bg-slate-50 dark:hover:bg-slate-800/80 text-slate-700 dark:text-slate-300'
                                                ]"
                                            >
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <span
                                                        :class="[
                                                            'shrink-0 px-2 py-0.5 rounded text-[10px] font-mono font-bold border',
                                                            isNoNumberType(t)
                                                                ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/80 dark:text-amber-300 border-amber-200/70 dark:border-amber-800/70'
                                                                : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700'
                                                        ]"
                                                    >
                                                        {{ isNoNumberType(t) ? 'DOKUMEN' : (t.letter_code || '-') }}
                                                    </span>
                                                    <span class="truncate">{{ t.name }}</span>
                                                </div>
                                                <svg
                                                    v-if="t.id === form.letter_type_id"
                                                    class="w-4 h-4 text-blue-600 dark:text-blue-400 shrink-0"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>

                                            <!-- Empty State -->
                                            <div
                                                v-if="filteredLetterTypes.length === 0"
                                                class="p-6 text-center text-xs text-slate-400 space-y-1"
                                            >
                                                <p class="font-medium">Format surat tidak ditemukan</p>
                                                <p class="text-[11px] text-slate-500">Coba kata kunci pencarian yang lain.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Selected Type Information Card -->
                                <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 space-y-2 text-xs">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400">
                                            {{ isNoNumberType(selectedLetterType) ? 'Format Penomoran:' : 'Kode Klasifikasi:' }}
                                        </span>
                                        <span
                                            :class="[
                                                'font-mono font-bold',
                                                isNoNumberType(selectedLetterType)
                                                    ? 'text-amber-600 dark:text-amber-400 font-sans text-[11px]'
                                                    : 'text-blue-600 dark:text-blue-400'
                                            ]"
                                        >
                                            {{ isNoNumberType(selectedLetterType) ? 'Tidak ada Nomer Surat' : (selectedLetterType.letter_code || '-') }}
                                        </span>
                                    </div>
                                    <div v-if="selectedLetterType.description" class="pt-1.5 border-t border-slate-200 dark:border-slate-700/80 text-[11px] text-slate-500">
                                        {{ selectedLetterType.description }}
                                    </div>
                                </div>

                                <div class="flex justify-end pt-2">
                                    <button
                                        type="button"
                                        @click="activeStep = ((selectedLetterType.code === 'pembukaan_periode' || selectedLetterType.code === 'rekomendasi_pinjaman_bjb') ? 'details' : 'applicant')"
                                        class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-500 flex items-center gap-1.5 transition"
                                    >
                                        <span>{{ (selectedLetterType.code === 'pembukaan_periode' || selectedLetterType.code === 'rekomendasi_pinjaman_bjb') ? 'Lanjut ke Rincian & Identitas' : 'Lanjut ke Data Identitas' }}</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- ============================================== -->
                            <!-- STEP 2: DATA PEMOHON / PENGIRIM DOKUMEN        -->
                            <!-- ============================================== -->
                            <div v-show="activeStep === 'applicant'" class="space-y-4">
                                <!-- A. KHUSUS SURAT DINAS FAKULTAS (KE REKTOR / PIHAK EKSTERNAL) -->
                                <div v-if="isFacultyType" class="space-y-4">
                                    <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                                        <div>
                                            <h2 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">
                                                2. Pengirim & Tujuan Surat (Fakultas ke Rektor)
                                            </h2>
                                            <p class="text-[11px] text-slate-500">Surat pengajuan/dinas resmi dari pimpinan Fakultas Ilmu Komputer</p>
                                        </div>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            </svg>
                                            Surat Tingkat Fakultas
                                        </span>
                                    </div>

                                    <!-- Banner Info Pengirim Fakultas -->
                                    <div class="p-3.5 rounded-xl bg-blue-50/60 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800/60 flex items-start gap-3">
                                        <div class="p-2 rounded-lg bg-blue-600 text-white shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
                                            </svg>
                                        </div>
                                        <div class="text-xs space-y-0.5">
                                            <div class="font-bold text-blue-900 dark:text-blue-300">Pengirim: Fakultas Ilmu Komputer Universitas Subang</div>
                                            <div class="text-[11px] text-blue-700 dark:text-blue-400">
                                                Dokumen resmi ini diterbitkan atas nama Fakultas Ilmu Komputer dan ditujukan langsung kepada Rektor Universitas Subang.
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Input Form Tujuan & Keterangan Surat Dinas -->
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                            Perihal Surat <span class="text-rose-500">*</span>
                                        </label>
                                        <input
                                            v-model="form.extra_data.subject"
                                            type="text"
                                            :placeholder="selectedLetterType.name || 'Perihal Surat Resmi...'"
                                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-bold text-slate-900 dark:text-white"
                                            @input="triggerDebouncedSync"
                                        />
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Tujuan Surat (Kepada Yth) <span class="text-rose-500">*</span>
                                            </label>
                                            <input
                                                v-model="form.extra_data.target_recipient"
                                                type="text"
                                                placeholder="Rektor Universitas Subang"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-bold text-slate-800 dark:text-slate-200"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Kota / Tempat Tujuan <span class="text-rose-500">*</span>
                                            </label>
                                            <input
                                                v-model="form.extra_data.target_city"
                                                type="text"
                                                placeholder="Subang / Tempat"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                                            />
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 gap-3" :class="selectedLetterType.code === 'pengajuan_dana' ? 'sm:grid-cols-2' : ''">
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Jumlah Lampiran
                                            </label>
                                            <input
                                                v-model="form.extra_data.lampiran_count"
                                                type="text"
                                                placeholder="1 (Satu) Berkas"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                                            />
                                        </div>
                                        <div v-if="selectedLetterType.code === 'pengajuan_dana'">
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Periode / Bulan Pengajuan
                                            </label>
                                            <input
                                                v-model="form.extra_data.period_month_year"
                                                type="text"
                                                placeholder="November 2026"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- B. KHUSUS PENGUMUMAN TERBUKA (KE MAHASISWA) -->
                                <div v-else-if="isAnnouncementType" class="space-y-4">
                                    <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                                        <div>
                                            <h2 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">
                                                2. Pengumuman Resmi Fasilkom
                                            </h2>
                                            <p class="text-[11px] text-slate-500">Pengumuman umum terbuka yang ditujukan kepada seluruh Mahasiswa Fasilkom</p>
                                        </div>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                                            </svg>
                                            Pengumuman Terbuka
                                        </span>
                                    </div>

                                    <div class="p-4 rounded-xl bg-blue-50/60 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800/60 flex items-start gap-3">
                                        <div class="p-2.5 rounded-lg bg-blue-600 text-white shrink-0">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div class="text-xs space-y-1">
                                            <div class="font-bold text-blue-900 dark:text-blue-300">Format Pengumuman Terbuka untuk Mahasiswa</div>
                                            <div class="text-[11px] text-blue-700 dark:text-blue-400 leading-relaxed">
                                                Surat Pengumuman ini berformat edaran umum resmi bertajuk <strong>"PENGUMUMAN"</strong> dan tidak memerlukan pengisian data pemohon perseorangan, instansi tujuan, maupun perihal surat. Anda dapat langsung melanjutkan ke pengisian <strong>Rincian Ketentuan Surat</strong> dan <strong>Penyesuaian Narasi</strong>.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- C. SURAT REGULER MAHASISWA ATAU DOSEN -->
                                <div v-else class="space-y-4">
                                <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                                    <div>
                                        <h2 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">
                                            2. {{ isLecturerType ? 'Data Dosen / Pengajar' : 'Data Pemohon Mahasiswa' }}
                                        </h2>
                                        <p class="text-[11px] text-slate-500">Isi data identitas pemohon atau gunakan pencarian cepat</p>
                                    </div>

                                    <!-- Quick Auto-Fill Dropdowns -->
                                    <div class="relative">
                                        <!-- Mahasiswa Search Trigger -->
                                        <button
                                            v-if="!isLecturerType"
                                            type="button"
                                            @click="showStudentSearch = !showStudentSearch"
                                            class="text-[11px] font-semibold text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                            </svg>
                                            <span>Pilih dari Master Mahasiswa</span>
                                        </button>

                                        <!-- Dosen Search Trigger -->
                                        <button
                                            v-else
                                            type="button"
                                            @click="showLecturerSearch = !showLecturerSearch"
                                            class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                            </svg>
                                            <span>Pilih dari Master Dosen</span>
                                        </button>

                                        <!-- Dropdown Panel Mahasiswa -->
                                        <div
                                            v-if="showStudentSearch && !isLecturerType"
                                            class="absolute right-0 top-6 z-40 w-80 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-xl p-3 space-y-2 animate-in fade-in zoom-in-95"
                                        >
                                            <input
                                                v-model="studentSearchQuery"
                                                type="text"
                                                placeholder="Cari nama mahasiswa / NPM..."
                                                class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 p-2 bg-slate-50 dark:bg-slate-800"
                                            />
                                            <div class="max-h-52 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                                                <div
                                                    v-for="s in filteredStudents"
                                                    :key="s.id"
                                                    @click="selectStudent(s)"
                                                    class="p-2 hover:bg-blue-50 dark:hover:bg-slate-800 cursor-pointer rounded-lg flex flex-col transition-colors"
                                                >
                                                    <span class="font-bold text-slate-800 dark:text-slate-200">{{ s.name }}</span>
                                                    <span class="text-[10px] text-slate-500 font-mono">{{ s.npm || s.email }} • {{ s.study_program || 'Sistem Informasi' }}</span>
                                                </div>
                                                <div v-if="filteredStudents.length === 0" class="p-3 text-center text-slate-400 text-xs">
                                                    Mahasiswa tidak ditemukan
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Dropdown Panel Dosen -->
                                        <div
                                            v-if="showLecturerSearch && isLecturerType"
                                            class="absolute right-0 top-6 z-40 w-80 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-xl p-3 space-y-2 animate-in fade-in zoom-in-95"
                                        >
                                            <input
                                                v-model="lecturerSearchQuery"
                                                type="text"
                                                placeholder="Cari nama dosen / NIDN..."
                                                class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 p-2 bg-slate-50 dark:bg-slate-800"
                                            />
                                            <div class="max-h-52 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                                                <div
                                                    v-for="l in filteredLecturers"
                                                    :key="l.id"
                                                    @click="selectLecturer(l)"
                                                    class="p-2 hover:bg-indigo-50 dark:hover:bg-slate-800 cursor-pointer rounded-lg flex flex-col transition-colors"
                                                >
                                                    <span class="font-bold text-slate-800 dark:text-slate-200">{{ l.name }}</span>
                                                    <div class="flex items-center gap-2 mt-0.5 text-[10px] text-slate-500 dark:text-slate-400">
                                                        <span v-if="l.nidn">NIDN: {{ l.nidn }}</span>
                                                        <span v-if="l.functional_position" class="text-indigo-600 dark:text-indigo-400 font-semibold">• {{ l.functional_position }}</span>
                                                    </div>
                                                </div>
                                                <div v-if="filteredLecturers.length === 0" class="p-3 text-center text-slate-400 text-xs">
                                                    Dosen tidak ditemukan
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Form Inputs Identitas -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                            {{ isLecturerType ? 'Nama Lengkap Dosen + Gelar' : 'Nama Lengkap Mahasiswa' }} <span class="text-rose-500">*</span>
                                        </label>
                                        <input
                                            v-model="form.student_name"
                                            type="text"
                                            :placeholder="isLecturerType ? 'Masukkan nama dosen...' : 'Masukkan nama lengkap mahasiswa...'"
                                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-bold uppercase"
                                            @input="form.extra_data.lecturer_name = form.student_name"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                            {{ isLecturerType ? 'NIDN / NIP Dosen' : 'Nomor Pokok Mahasiswa (NPM)' }} <span class="text-rose-500">*</span>
                                        </label>
                                        <input
                                            v-model="form.npm"
                                            type="text"
                                            :placeholder="isLecturerType ? 'Masukkan NIDN/NIP...' : 'Masukkan NPM...'"
                                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-mono uppercase"
                                            @input="form.extra_data.nidn = form.npm"
                                        />
                                    </div>
                                </div>

                                <div :class="['grid grid-cols-1 gap-3', selectedLetterType.code !== 'pakta_integritas' && selectedLetterType.code !== 'keterangan_kuliah_non_pns' && selectedLetterType.code !== 'keterangan_kuliah_pns' && selectedLetterType.code !== 'pengantar_pkl' ? 'sm:grid-cols-2' : '']">
                                    <div v-if="selectedLetterType.code !== 'pakta_integritas'">
                                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                            Program Studi <span class="text-rose-500">*</span>
                                        </label>
                                        <select
                                            v-model="form.study_program"
                                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                                        >
                                            <option value="Sistem Informasi">Sistem Informasi</option>
                                        </select>
                                    </div>
                                    <div v-if="selectedLetterType.code !== 'pakta_integritas' && selectedLetterType.code !== 'keterangan_kuliah_non_pns' && selectedLetterType.code !== 'keterangan_kuliah_pns' && selectedLetterType.code !== 'pengantar_pkl'">
                                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                            Tahun Akademik
                                        </label>
                                        <input
                                            v-model="form.academic_year"
                                            type="text"
                                            placeholder="2025/2026"
                                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                                        />
                                    </div>
                                </div>

                                <div v-if="!isLecturerType && selectedLetterType.code !== 'pengantar_pkl'" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                            Semester Aktif
                                        </label>
                                        <select
                                            v-model="form.semester"
                                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                                        >
                                            <option value="">Pilih</option>
                                            <option v-for="s in 8" :key="s" :value="s">Semester {{ s }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                            Tempat Lahir
                                        </label>
                                        <input
                                            v-model="form.birth_place"
                                            type="text"
                                            placeholder="Subang"
                                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                            Tanggal Lahir
                                        </label>
                                        <input
                                            v-model="form.birth_date"
                                            type="date"
                                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                                        />
                                    </div>
                                </div>

                                <div v-if="!isLecturerType && selectedLetterType.code !== 'pengantar_pkl'">
                                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                        Keperluan Pembuatan Surat
                                    </label>
                                    <input
                                        v-model="form.purpose"
                                        type="text"
                                        placeholder="Syarat Beasiswa / Tunjangan Gaji Orang Tua / Administrasi"
                                        class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                                    />
                                </div>

                                <div v-if="['tugas_mengajar', 'tugas_pembimbing_mbkm', 'tugas_pembimbing_ta'].includes(selectedLetterType.code)" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                            Status Tenaga Pengajar
                                        </label>
                                        <select
                                            v-model="form.extra_data.lecturer_status"
                                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-medium"
                                        >
                                            <option value="Dosen Tetap">Dosen Tetap</option>
                                            <option value="Dosen Luar Biasa">Dosen Luar Biasa</option>
                                            <option value="Dosen Praktisi">Dosen Praktisi / Mitra</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                            Semester (Ganjil/Genap)
                                        </label>
                                        <select
                                            v-model="form.extra_data.semester_type"
                                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-semibold"
                                        >
                                            <option value="">Pilih Semester</option>
                                            <option value="Ganjil">Ganjil</option>
                                            <option value="Genap">Genap</option>
                                        </select>
                                    </div>
                                </div>
                                <div v-else-if="selectedLetterType.code !== 'pakta_integritas'">
                                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                        Alamat Lengkap (Domisili)
                                    </label>
                                    <textarea
                                        v-model="form.address"
                                        rows="2"
                                        placeholder="Alamat tempat tinggal saat ini..."
                                        class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                                        @input="form.extra_data.address = form.address"
                                    ></textarea>
                                </div>
                                </div>

                                <div class="flex items-center justify-between pt-2">
                                    <button
                                        type="button"
                                        @click="activeStep = 'type'"
                                        class="px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                                    >
                                        ← Kembali
                                    </button>
                                    <button
                                        type="button"
                                        @click="activeStep = 'details'"
                                        class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-500 flex items-center gap-1.5 transition"
                                    >
                                        <span>Lanjut ke Rincian Surat</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- ============================================== -->
                            <!-- STEP 3: RINCIAN KHUSUS SURAT                   -->
                            <!-- ============================================== -->
                            <div v-show="activeStep === 'details'" class="space-y-4">
                                <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                                    <div>
                                        <h2 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">
                                            3. Rincian Khusus Surat: {{ selectedLetterType.name }}
                                        </h2>
                                        <p class="text-[11px] text-slate-500">Parameter spesifik sesuai format surat yang dipilih</p>
                                    </div>
                                    <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                        {{ selectedLetterType.code }}
                                    </span>
                                </div>

                                <!-- 1. KETERANGAN KULIAH PNS -->
                                <div v-if="selectedLetterType.code === 'keterangan_kuliah_pns'" class="space-y-3">
                                    <div class="text-xs font-bold text-slate-700 dark:text-slate-300 pb-1 border-b border-slate-100 dark:border-slate-800">
                                        Data Orang Tua / Wali (PNS / ASN)
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Nama Orang Tua / Wali <span class="text-rose-500">*</span>
                                            </label>
                                            <input
                                                v-model="form.extra_data.parent_name"
                                                type="text"
                                                placeholder="Nama lengkap orang tua"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-bold uppercase"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                NIP / NIK Orang Tua <span class="text-rose-500">*</span>
                                            </label>
                                            <input
                                                v-model="form.extra_data.parent_nip"
                                                type="text"
                                                placeholder="19750101 200003 1 001"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-mono"
                                            />
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Pangkat / Golongan
                                            </label>
                                            <input
                                                v-model="form.extra_data.parent_rank"
                                                type="text"
                                                placeholder="Pembina / IV.a"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Instansi / Unit Kerja
                                            </label>
                                            <input
                                                v-model="form.extra_data.parent_institution"
                                                type="text"
                                                placeholder="Dinas Pendidikan Kab. Subang"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- 2. PENGANTAR MAGANG & PKL -->
                                <div v-else-if="selectedLetterType.code === 'pengantar_magang' || selectedLetterType.code === 'pengantar_pkl'" class="space-y-3">
                                    <div class="text-xs font-bold text-slate-700 dark:text-slate-300 pb-1 border-b border-slate-100 dark:border-slate-800">
                                        Informasi Instansi / Perusahaan Tujuan
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Nama Instansi / Perusahaan <span class="text-rose-500">*</span>
                                            </label>
                                            <input
                                                v-model="form.extra_data.target_agency"
                                                type="text"
                                                placeholder="PT. Dahana (Persero) Subang"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-bold"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Penerima Surat (Yth. Pejabat/Pimpinan)
                                            </label>
                                            <input
                                                v-model="form.extra_data.target_recipient"
                                                type="text"
                                                placeholder="Kepala Divisi SDM & Umum"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                                            />
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Kota Instansi Tujuan
                                            </label>
                                            <input
                                                v-model="form.extra_data.target_city"
                                                type="text"
                                                placeholder="Subang"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Durasi Magang / PKL
                                            </label>
                                            <input
                                                v-model="form.extra_data.internship_duration"
                                                type="text"
                                                placeholder="1 (Satu) Bulan / 3 (Tiga) Bulan"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                                            />
                                        </div>
                                    </div>
                                    <!-- Rentang Tanggal Pelaksanaan (Magang & PKL) -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Tanggal Mulai Pelaksanaan <span class="text-rose-500">*</span>
                                            </label>
                                            <input
                                                v-model="form.extra_data.start_date"
                                                type="text"
                                                :placeholder="selectedLetterType.code === 'pengantar_pkl' ? '06 Oktober 2025' : '14 September 2026'"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-medium"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Tanggal Selesai Pelaksanaan <span class="text-rose-500">*</span>
                                            </label>
                                            <input
                                                v-model="form.extra_data.end_date"
                                                type="text"
                                                :placeholder="selectedLetterType.code === 'pengantar_pkl' ? '28 November 2025' : '09 Januari 2027'"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-medium"
                                            />
                                        </div>
                                    </div>

                                    <!-- Lampiran Anggota Kelompok -->
                                    <div class="space-y-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                                        <div class="flex items-center justify-between">
                                            <span class="text-[11px] font-bold text-slate-700 dark:text-slate-300">
                                                Anggota Kelompok Tambahan (Lampiran):
                                            </span>
                                            <button
                                                type="button"
                                                @click="addGroupMember"
                                                class="text-[11px] font-bold text-blue-600 hover:underline"
                                            >
                                                + Tambah Anggota
                                            </button>
                                        </div>
                                        <div v-if="!form.extra_data.group_members?.length" class="text-[11px] text-slate-400 dark:text-slate-500 italic py-1">
                                            Belum ada anggota tambahan. Pemohon utama otomatis menjadi No. 1.
                                        </div>
                                        <div
                                            v-for="(member, idx) in form.extra_data.group_members"
                                            :key="idx"
                                            class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex flex-wrap items-center gap-2 text-xs"
                                        >
                                            <input
                                                v-model="member.name"
                                                type="text"
                                                placeholder="Nama Mahasiswa"
                                                class="flex-1 min-w-[120px] text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900"
                                            />
                                            <input
                                                v-model="member.npm"
                                                type="text"
                                                placeholder="NPM"
                                                class="w-28 text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 font-mono"
                                            />
                                            <input
                                                v-if="selectedLetterType.code === 'pengantar_pkl'"
                                                v-model="member.address"
                                                type="text"
                                                placeholder="Alamat (Subang)"
                                                class="w-32 text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900"
                                            />
                                            <button
                                                type="button"
                                                @click="removeGroupMember(idx)"
                                                class="text-rose-500 hover:text-rose-700 p-1 font-bold"
                                            >
                                                ✕
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- 3. OBSERVASI MATA KULIAH -->
                                <div v-else-if="selectedLetterType.code === 'observasi_matkul'" class="space-y-3">
                                    <div class="text-xs font-bold text-slate-700 dark:text-slate-300 pb-1 border-b border-slate-100 dark:border-slate-800">
                                        Data Observasi Tugas Mata Kuliah
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Instansi / Perusahaan Tujuan <span class="text-rose-500">*</span>
                                            </label>
                                            <input
                                                v-model="form.extra_data.target_agency"
                                                type="text"
                                                placeholder="Kantor Samsat Kabupaten Subang"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-bold"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Penerima Surat (Yth. Pejabat)
                                            </label>
                                            <input
                                                v-model="form.extra_data.target_recipient"
                                                type="text"
                                                placeholder="Kepala Kantor Samsat Kab. Subang"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                                            />
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Mata Kuliah Yang Dosen Tugaskan <span class="text-rose-500">*</span>
                                            </label>
                                            <input
                                                v-model="form.extra_data.course_name"
                                                type="text"
                                                placeholder="Rekayasa Perangkat Lunak"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-bold"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Dosen Pengampu Mata Kuliah <span class="text-rose-500">*</span>
                                            </label>
                                            <input
                                                v-model="form.extra_data.lecturer_name"
                                                type="text"
                                                placeholder="BAMBANG TJAHJO UTOMO, MT"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 uppercase font-bold"
                                            />
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                            Alamat / Lokasi Instansi Tujuan
                                        </label>
                                        <input
                                            v-model="form.extra_data.target_address"
                                            type="text"
                                            placeholder="Jl. Mayjen Sutoyo No. 3 Subang"
                                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                                        />
                                    </div>

                                    <!-- Lampiran Anggota Kelompok Observasi -->
                                    <div class="space-y-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                                        <div class="flex items-center justify-between">
                                            <span class="text-[11px] font-bold text-slate-700 dark:text-slate-300">
                                                Anggota Kelompok Observasi (Tabel Lampiran):
                                            </span>
                                            <button
                                                type="button"
                                                @click="addGroupMember"
                                                class="text-[11px] font-bold text-blue-600 hover:underline"
                                            >
                                                + Tambah Anggota
                                            </button>
                                        </div>
                                        <div v-if="!form.extra_data.group_members?.length" class="text-[11px] text-slate-400 dark:text-slate-500 italic py-1">
                                            Belum ada anggota tambahan. Pemohon utama otomatis menjadi No. 1.
                                        </div>
                                        <div
                                            v-for="(member, idx) in form.extra_data.group_members"
                                            :key="idx"
                                            class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex flex-wrap items-center gap-2 text-xs"
                                        >
                                            <input
                                                v-model="member.name"
                                                type="text"
                                                placeholder="Nama Mahasiswa"
                                                class="flex-1 min-w-[120px] text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900"
                                            />
                                            <input
                                                v-model="member.npm"
                                                type="text"
                                                placeholder="NPM"
                                                class="w-28 text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 font-mono"
                                            />
                                            <input
                                                v-model="member.address"
                                                type="text"
                                                placeholder="Alamat"
                                                class="w-24 text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900"
                                            />
                                            <button
                                                type="button"
                                                @click="removeGroupMember(idx)"
                                                class="text-rose-500 hover:text-rose-700 p-1 font-bold"
                                            >
                                                ✕
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- 4. IZIN PENELITIAN SKRIPSI -->
                                <div v-else-if="selectedLetterType.code === 'izin_penelitian_skripsi'" class="space-y-3">
                                    <div class="text-xs font-bold text-slate-700 dark:text-slate-300 pb-1 border-b border-slate-100 dark:border-slate-800">
                                        Data Penelitian Skripsi / Tugas Akhir
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Instansi / Lokasi Penelitian <span class="text-rose-500">*</span>
                                            </label>
                                            <input
                                                v-model="form.extra_data.target_agency"
                                                type="text"
                                                placeholder="Diskominfo Kabupaten Subang"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-bold"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Penerima Surat (Yth. Pejabat)
                                            </label>
                                            <input
                                                v-model="form.extra_data.target_recipient"
                                                type="text"
                                                placeholder="Kepala Dinas Komunikasi dan Informatika"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                                            />
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                            Kota Instansi
                                        </label>
                                        <input
                                            v-model="form.extra_data.target_city"
                                            type="text"
                                            placeholder="Subang"
                                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                                        />
                                    </div>
                                </div>

                                <!-- 4.b SEMINAR USULAN PENELITIAN & SIDANG TUGAS AKHIR -->
                                <div v-else-if="selectedLetterType.code === 'izin_seminar_up' || selectedLetterType.code === 'izin_sidang_ta'" class="space-y-3">
                                    <div class="text-xs font-bold text-slate-700 dark:text-slate-300 pb-1 border-b border-slate-100 dark:border-slate-800">
                                        Data Perihal, Tujuan & Jadwal Pelaksanaan
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                            Perihal Surat <span class="text-rose-500">*</span>
                                        </label>
                                        <input
                                            v-model="form.extra_data.subject"
                                            type="text"
                                            :placeholder="selectedLetterType.code === 'izin_sidang_ta' ? 'Permohonan Izin Mengikuti Sidang Tugas Akhir' : 'Permohonan Izin Mengikuti Seminar Usulan Penelitian'"
                                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-bold"
                                        />
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Tujuan Penerima Surat (Kepada Yth.) <span class="text-rose-500">*</span>
                                            </label>
                                            <input
                                                v-model="form.extra_data.target_recipient"
                                                type="text"
                                                placeholder="HRD PT. Uwu Jump Indonesia"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-bold"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Kota / Alamat Tujuan
                                            </label>
                                            <input
                                                v-model="form.extra_data.target_city"
                                                type="text"
                                                placeholder="Tempat"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                                            />
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Nama Kegiatan / Agenda <span class="text-rose-500">*</span>
                                            </label>
                                            <input
                                                v-model="form.extra_data.activity_name"
                                                type="text"
                                                :placeholder="selectedLetterType.code === 'izin_sidang_ta' ? 'Sidang Tugas Akhir' : 'Seminar Usulan Penelitian'"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-semibold"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Hari Pelaksanaan <span class="text-rose-500">*</span>
                                            </label>
                                            <select
                                                v-model="form.extra_data.event_day"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-medium"
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

                                    <!-- Tabel Peserta Seminar / Sidang -->
                                    <div class="space-y-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <span class="text-[11px] font-bold text-slate-700 dark:text-slate-300">
                                                    {{ selectedLetterType.code === 'izin_sidang_ta' ? 'Daftar Mahasiswa Sidang Tugas Akhir:' : 'Daftar Mahasiswa Peserta Seminar:' }}
                                                </span>
                                                <p class="text-[10px] text-slate-400">
                                                    Pemohon utama otomatis menjadi <strong>No. 1</strong>. Tambah peserta jika rombongan/kelompok.
                                                </p>
                                            </div>
                                            <button
                                                type="button"
                                                @click="addGroupMember"
                                                class="text-[11px] font-bold text-blue-600 hover:underline"
                                            >
                                                + Tambah Peserta
                                            </button>
                                        </div>

                                        <!-- Peserta 1 (Pemohon Utama) Preview Row -->
                                        <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 flex items-center justify-between text-xs">
                                            <div class="flex items-center gap-2">
                                                <span class="w-5 h-5 rounded-full bg-blue-600 text-white font-bold flex items-center justify-center text-[10px] shrink-0">1</span>
                                                <span class="font-bold text-slate-800 dark:text-white uppercase">{{ form.student_name || 'Nama Pemohon' }}</span>
                                                <span class="text-slate-400 font-mono">({{ form.npm || 'NPM' }})</span>
                                                <span class="text-slate-400">•</span>
                                                <span class="text-slate-500 font-medium">{{ form.extra_data.activity_name || (selectedLetterType.code === 'izin_sidang_ta' ? 'Sidang Tugas Akhir' : 'Seminar Usulan Penelitian') }}</span>
                                                <span class="text-slate-400">•</span>
                                                <span class="text-slate-500">{{ form.extra_data.event_day || 'Kamis' }}</span>
                                            </div>
                                            <span class="text-[10px] px-2 py-0.5 rounded bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 font-bold shrink-0">
                                                Peserta Utama
                                            </span>
                                        </div>

                                        <!-- Peserta Tambahan Rows -->
                                        <div
                                            v-for="(member, idx) in form.extra_data.group_members"
                                            :key="idx"
                                            class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex flex-wrap items-center gap-2 text-xs"
                                        >
                                            <span class="w-5 h-5 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold flex items-center justify-center text-[10px] shrink-0">
                                                {{ idx + 2 }}
                                            </span>
                                            <input
                                                v-model="member.name"
                                                type="text"
                                                placeholder="Nama Mahasiswa"
                                                class="flex-1 min-w-[130px] text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 font-bold uppercase"
                                            />
                                            <input
                                                v-model="member.npm"
                                                type="text"
                                                placeholder="NPM"
                                                class="w-28 text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 font-mono"
                                            />
                                            <input
                                                v-model="member.activity"
                                                type="text"
                                                :placeholder="form.extra_data.activity_name || (selectedLetterType.code === 'izin_sidang_ta' ? 'Sidang Tugas Akhir' : 'Seminar Usulan Penelitian')"
                                                class="flex-1 min-w-[120px] text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900"
                                            />
                                            <input
                                                v-model="member.day"
                                                type="text"
                                                :placeholder="form.extra_data.event_day || 'Kamis'"
                                                class="w-24 text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900"
                                            />
                                            <button
                                                type="button"
                                                @click="removeGroupMember(idx)"
                                                class="text-rose-500 hover:text-rose-700 p-1 font-bold"
                                                title="Hapus Peserta"
                                            >
                                                ✕
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- 5. IZIN MENGIKUTI UJIAN -->
                                <div v-else-if="selectedLetterType.code === 'izin_mengikuti_ujian'" class="space-y-3">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Perihal Surat <span class="text-rose-500">*</span>
                                            </label>
                                            <input
                                                v-model="form.extra_data.subject"
                                                type="text"
                                                placeholder="Permohonan Izin Mengikuti UAS"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-bold"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Jumlah Lampiran
                                            </label>
                                            <input
                                                v-model="form.extra_data.lampiran_count"
                                                type="text"
                                                placeholder="2 (dua) lembar"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                                            />
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Judul Ujian Lampiran
                                            </label>
                                            <input
                                                v-model="form.extra_data.lampiran_exam_title"
                                                type="text"
                                                placeholder="UJIAN AKHIR SEMESTER"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-semibold"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Semester Lampiran
                                            </label>
                                            <input
                                                v-model="form.extra_data.semester_type"
                                                type="text"
                                                placeholder="GANJIL"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Tahun Akademik Lampiran
                                            </label>
                                            <input
                                                v-model="form.extra_data.academic_year"
                                                type="text"
                                                placeholder="2026/2027"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                                            />
                                        </div>
                                    </div>
                                    <div class="text-xs font-bold text-slate-700 dark:text-slate-300 pb-1 border-b border-slate-100 dark:border-slate-800">
                                        Jadwal Mata Kuliah Ujian
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-[11px] font-bold text-slate-700 dark:text-slate-300">
                                            Daftar Jadwal Ujian:
                                        </span>
                                        <button
                                            type="button"
                                            @click="addExamSchedule"
                                            class="text-[11px] font-bold text-blue-600 hover:underline"
                                        >
                                            + Tambah Jadwal MK
                                        </button>
                                    </div>
                                    <div
                                        v-for="(sched, idx) in form.extra_data.exam_schedules"
                                        :key="idx"
                                        class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 space-y-2 text-xs"
                                    >
                                        <div class="grid grid-cols-12 gap-2">
                                            <div class="col-span-12 sm:col-span-7 relative">
                                                <div class="flex items-center justify-between mb-0.5">
                                                    <label class="block text-[10px] font-semibold text-slate-500">Nama Mata Kuliah</label>
                                                    <button
                                                        type="button"
                                                        @click="activeExamCourseIdx = activeExamCourseIdx === idx ? null : idx"
                                                        class="text-[10px] font-bold text-blue-600 hover:text-blue-700 dark:text-blue-400 inline-flex items-center gap-1"
                                                    >
                                                        <span>📚 Pilih dari Master MK</span>
                                                    </button>
                                                </div>

                                                <!-- DROPDOWN QUICK PICKER MATA KULIAH UNTUK ROW UJIAN -->
                                                <div
                                                    v-if="activeExamCourseIdx === idx"
                                                    class="absolute left-0 top-full mt-1 w-80 sm:w-96 z-40 p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-blue-200 dark:border-blue-900 shadow-xl space-y-2"
                                                >
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-[11px] font-bold text-blue-900 dark:text-blue-300">Pilih Mata Kuliah:</span>
                                                        <button type="button" @click="activeExamCourseIdx = null" class="text-slate-400 hover:text-slate-600 text-xs font-bold">✕</button>
                                                    </div>
                                                    <input
                                                        v-model="examCourseSearchQuery"
                                                        type="text"
                                                        placeholder="Ketik kode / nama MK..."
                                                        class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 py-1.5 px-2.5"
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
                                                    v-model="sched.course_name"
                                                    type="text"
                                                    placeholder="Audit Sistem Informasi"
                                                    class="w-full text-xs p-2 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900"
                                                />
                                            </div>
                                            <div class="col-span-6 sm:col-span-3">
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Kelas</label>
                                                <input v-model="sched.class_name" type="text" placeholder="4A" class="w-full text-xs p-2 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 uppercase font-mono" />
                                            </div>
                                            <div class="col-span-6 sm:col-span-2">
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">SKS</label>
                                                <input v-model.number="sched.sks" type="number" min="1" max="6" placeholder="3" class="w-full text-xs p-2 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 font-mono" />
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-12 gap-2">
                                            <div class="col-span-12 sm:col-span-5">
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Hari / Tanggal Ujian</label>
                                                <input v-model="sched.date" type="text" placeholder="21-03-2025" class="w-full text-xs p-2 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 font-mono" />
                                            </div>
                                            <div class="col-span-6 sm:col-span-4">
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Waktu / Jam</label>
                                                <input v-model="sched.time" type="text" placeholder="08.00 - 09.30" class="w-full text-xs p-2 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 font-mono" />
                                            </div>
                                            <div class="col-span-6 sm:col-span-3 flex items-end gap-1">
                                                <div class="flex-1">
                                                    <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Ruang</label>
                                                    <input v-model="sched.room" type="text" placeholder="Lab II" class="w-full text-xs p-2 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900" />
                                                </div>
                                                <button
                                                    v-if="form.extra_data.exam_schedules?.length > 1"
                                                    type="button"
                                                    @click="removeExamSchedule(idx)"
                                                    class="p-2 text-rose-500 hover:text-rose-700 font-bold"
                                                >
                                                    ✕
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 6. UJIAN SUSULAN -->
                                <div v-else-if="selectedLetterType.code === 'ujian_susulan'" class="space-y-3">
                                    <div class="text-xs font-bold text-slate-700 dark:text-slate-300 pb-1 border-b border-slate-100 dark:border-slate-800">
                                        Data Ujian Susulan (UTS / UAS)
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Mata Kuliah <span class="text-rose-500">*</span>
                                            </label>
                                            <input
                                                v-model="form.extra_data.course_name"
                                                type="text"
                                                placeholder="Jaringan Komputer"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-bold"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Dosen Pengampu Mata Kuliah <span class="text-rose-500">*</span>
                                            </label>
                                            <input
                                                v-model="form.extra_data.lecturer_name"
                                                type="text"
                                                placeholder="ACHMAD SYAFA'AT, S.Kom., MT"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 uppercase font-bold"
                                            />
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Jenis Ujian
                                            </label>
                                            <select
                                                v-model="form.extra_data.exam_type"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-bold"
                                            >
                                                <option value="UTS">UTS (Tengah Semester)</option>
                                                <option value="UAS">UAS (Akhir Semester)</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Nilai Asal
                                            </label>
                                            <input v-model="form.extra_data.original_score" type="text" class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-mono" placeholder="-" />
                                        </div>
                                    </div>
                                </div>

                                <!-- 7. PERBAIKAN NILAI -->
                                <div v-else-if="selectedLetterType.code === 'perbaikan_nilai'" class="space-y-3">
                                    <div class="text-xs font-bold text-slate-700 dark:text-slate-300 pb-1 border-b border-slate-100 dark:border-slate-800">
                                        Data Perbaikan Nilai Mata Kuliah
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                            Perihal Surat <span class="text-rose-500">*</span>
                                        </label>
                                        <input
                                            v-model="form.extra_data.subject"
                                            type="text"
                                            placeholder="Perbaikan Nilai"
                                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-bold"
                                        />
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Nama Mata Kuliah <span class="text-rose-500">*</span>
                                            </label>
                                            <input
                                                v-model="form.extra_data.course_name"
                                                type="text"
                                                placeholder="Analisis dan Perancangan SI"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-bold"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Dosen Pengampu Mata Kuliah <span class="text-rose-500">*</span>
                                            </label>
                                            <input
                                                v-model="form.extra_data.lecturer_name"
                                                type="text"
                                                placeholder="Ir. Syarif Hidayat, MT"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 uppercase font-bold"
                                            />
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                            Nilai Asal (Sebelum Perbaikan)
                                        </label>
                                        <input
                                            v-model="form.extra_data.original_score"
                                            type="text"
                                            placeholder="T / E / D"
                                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-mono uppercase font-bold"
                                        />
                                    </div>
                                </div>

                                <!-- 8. KETERANGAN KELULUSAN (SKL) -->
                                <div v-else-if="selectedLetterType.code === 'keterangan_yudisium'" class="space-y-3">
                                    <div class="text-xs font-bold text-slate-700 dark:text-slate-300 pb-1 border-b border-slate-100 dark:border-slate-800">
                                        Data Keputusan Dekan & Penetapan Kelulusan
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Nomor SK Dekan Kelulusan <span class="text-rose-500">*</span>
                                            </label>
                                            <input
                                                v-model="form.extra_data.sk_number"
                                                type="text"
                                                placeholder="270 /D/FIK-US/VI/2026"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-medium"
                                                @input="triggerDebouncedSync"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Tanggal Penetapan SK Dekan <span class="text-rose-500">*</span>
                                            </label>
                                            <input
                                                v-model="form.extra_data.sk_date"
                                                type="text"
                                                placeholder="26 Juni 2026"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-medium"
                                                @input="triggerDebouncedSync"
                                            />
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Semester Kelulusan <span class="text-rose-500">*</span>
                                            </label>
                                            <select
                                                v-model="form.extra_data.semester_type"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-medium"
                                                @change="triggerDebouncedSync"
                                            >
                                                <option value="Genap">Genap</option>
                                                <option value="Ganjil">Ganjil</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Tahun Akademik Kelulusan <span class="text-rose-500">*</span>
                                            </label>
                                            <input
                                                v-model="form.academic_year"
                                                type="text"
                                                placeholder="2025/2026"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-medium"
                                                @input="triggerDebouncedSync"
                                            />
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Indek Prestasi Komulatif (IPK) <span class="text-rose-500">*</span>
                                            </label>
                                            <input
                                                v-model="form.extra_data.ipk"
                                                type="text"
                                                placeholder="3.19"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-mono font-bold"
                                                @input="triggerDebouncedSync"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Predikat Kelulusan Yudisium <span class="text-rose-500">*</span>
                                            </label>
                                            <input
                                                v-model="form.extra_data.yudisium_predicate"
                                                type="text"
                                                placeholder="SANGAT MEMUASKAN"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-bold uppercase"
                                                @input="triggerDebouncedSync"
                                            />
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                            Gelar Akademik yang Diberikan <span class="text-rose-500">*</span>
                                        </label>
                                        <input
                                            v-model="form.extra_data.degree_title"
                                            type="text"
                                            placeholder="Sarjana Komputer (S.Kom)"
                                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-bold"
                                            @input="triggerDebouncedSync"
                                        />
                                    </div>
                                </div>

                                <!-- 9. REKOMENDASI BEASISWA -->
                                <div v-else-if="selectedLetterType.code === 'rekomendasi_beasiswa'" class="space-y-4">
                                    <div class="p-3 rounded-xl bg-blue-50/80 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800 text-xs text-blue-900 dark:text-blue-200 flex items-start gap-2">
                                        <span class="text-base">🎓</span>
                                        <div>
                                            <strong>Surat Rekomendasi Beasiswa:</strong>
                                            Surat rekomendasi resmi dari Program Studi / Fakultas bagi mahasiswa untuk mendapatkan beasiswa pendidikan.
                                        </div>
                                    </div>

                                    <!-- 1. Pejabat Yang Bertanda Tangan (Sesuai Bagian 1 Live Surat) -->
                                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 space-y-3">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                                1. Data Pejabat Yang Merekomendasikan:
                                            </span>
                                            <button
                                                type="button"
                                                @click="activeStep = 'sign'"
                                                class="text-[11px] font-semibold text-blue-600 hover:underline"
                                            >
                                                Ubah Pejabat di Langkah 4 →
                                            </button>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                            <div>
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-1">Nama Pejabat</label>
                                                <input
                                                    :value="currentSigner.name"
                                                    disabled
                                                    type="text"
                                                    class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-900/60 p-2.5 font-bold text-slate-700 dark:text-slate-300 cursor-not-allowed"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-1">Jabatan Pejabat</label>
                                                <input
                                                    :value="currentSigner.signature_title"
                                                    disabled
                                                    type="text"
                                                    class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-900/60 p-2.5 text-slate-700 dark:text-slate-300 cursor-not-allowed"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-1">Fakultas / Unit</label>
                                                <input
                                                    v-model="form.extra_data.pejabat_fakultas"
                                                    type="text"
                                                    placeholder="Ilmu Komputer Universitas Subang"
                                                    class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-medium"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 2. Program & Lembaga Beasiswa (Sesuai Bagian 2 Paragraf Rekomendasi Live Surat) -->
                                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 space-y-3">
                                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                            2. Detail Program & Lembaga Penyelenggara Beasiswa:
                                        </span>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    Nama Program Beasiswa <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="form.extra_data.scholarship_name"
                                                    type="text"
                                                    placeholder="Beasiswa Pendidikan Pemimpin Indonesia"
                                                    class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-bold"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    Penyelenggara / Lembaga Beasiswa <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="form.extra_data.scholarship_provider"
                                                    type="text"
                                                    placeholder="LEADERS.ID"
                                                    class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-bold"
                                                />
                                            </div>
                                        </div>

                                        <!-- Live Preview Narasi Rekomendasi -->
                                        <div class="p-3 rounded-lg bg-blue-50/50 dark:bg-blue-950/20 border border-blue-200/60 dark:border-blue-900/40 text-[11px] text-slate-600 dark:text-slate-300 leading-relaxed">
                                            <span class="font-bold text-blue-900 dark:text-blue-300 block mb-1">Tinjauan Kalimat Rekomendasi pada Surat:</span>
                                            "Merekomendasikan kepada mahasiswa atas nama <strong class="text-slate-900 dark:text-white">{{ form.student_name || 'Nama Mahasiswa' }}</strong> dengan nomor pokok mahasiswa <strong class="text-slate-900 dark:text-white font-mono">{{ form.npm || 'NPM' }}</strong> untuk mendapatkan <strong class="text-blue-700 dark:text-blue-300">{{ form.extra_data.scholarship_name || 'Nama Beasiswa' }}</strong> oleh <strong class="text-blue-700 dark:text-blue-300">{{ form.extra_data.scholarship_provider || 'Penyelenggara Beasiswa' }}</strong>."
                                        </div>
                                    </div>
                                </div>

                                <!-- 10. REKOMENDASI PEMBELAJARAN LUAR PRODI -->
                                <div v-else-if="selectedLetterType.code === 'rekomendasi_luar_prodi'" class="space-y-4">
                                    <div class="p-3 rounded-xl bg-indigo-50/80 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800 text-xs text-indigo-900 dark:text-indigo-200 flex items-start gap-2">
                                        <span class="text-base">🚀</span>
                                        <div>
                                            <strong>Rekomendasi Pembelajaran di Luar Program Studi:</strong>
                                            Surat rekomendasi resmi dari Pimpinan Fakultas (Wakil Dekan I) bagi mahasiswa untuk program MBKM, Coding Camp, Magang Bersertifikat, Studi Independen, dll.
                                        </div>
                                    </div>

                                    <!-- Bagian 1: Data Pejabat Yang Bertanda Tangan (Pemberi Rekomendasi) -->
                                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 space-y-3">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                                1. Data Pejabat Pemberi Rekomendasi (Wakil Dekan I):
                                            </span>
                                            <button
                                                type="button"
                                                @click="activeStep = 'sign'"
                                                class="text-[11px] font-semibold text-indigo-600 hover:underline"
                                            >
                                                Pilih Pejabat di Langkah 4 →
                                            </button>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                                            <div>
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-1">Nama Pejabat</label>
                                                <input
                                                    :value="currentSigner.name"
                                                    disabled
                                                    type="text"
                                                    class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-900/60 p-2.5 font-bold text-slate-700 dark:text-slate-300 cursor-not-allowed"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-1">Jabatan Pejabat</label>
                                                <input
                                                    :value="currentSigner.signature_title"
                                                    disabled
                                                    type="text"
                                                    class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-900/60 p-2.5 text-slate-700 dark:text-slate-300 cursor-not-allowed"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-1">Program Studi Pejabat</label>
                                                <input
                                                    v-model="form.extra_data.pejabat_prodi"
                                                    type="text"
                                                    placeholder="Sistem Informasi"
                                                    class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-medium"
                                                    @input="triggerDebouncedSync"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-1">Fakultas Pejabat</label>
                                                <input
                                                    v-model="form.extra_data.pejabat_fakultas"
                                                    type="text"
                                                    placeholder="Ilmu Komputer Universitas Subang"
                                                    class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-medium"
                                                    @input="triggerDebouncedSync"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Bagian 2: Data Fakultas Mahasiswa Penerima Rekomendasi -->
                                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 space-y-3">
                                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                            2. Keterangan Fakultas Mahasiswa:
                                        </span>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    Fakultas Mahasiswa
                                                </label>
                                                <input
                                                    v-model="form.extra_data.fakultas_mahasiswa"
                                                    type="text"
                                                    placeholder="Ilmu Komputer Universitas Subang"
                                                    class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-medium"
                                                    @input="triggerDebouncedSync"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    Program Studi Mahasiswa
                                                </label>
                                                <input
                                                    :value="form.study_program || 'Sistem Informasi'"
                                                    disabled
                                                    type="text"
                                                    class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-900/60 p-2.5 text-slate-700 dark:text-slate-300 cursor-not-allowed"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Bagian 3: Detail Kegiatan & Periode -->
                                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 space-y-3">
                                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                            3. Program Kegiatan & Periode Pelaksanaan:
                                        </span>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    Nama Program Kegiatan / Pelatihan <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="form.extra_data.program_name"
                                                    type="text"
                                                    placeholder="Program Coding Camp 2025 powered by DBS Foundation"
                                                    class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-bold"
                                                    @input="triggerDebouncedSync"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    Periode Pelaksanaan Kegiatan <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="form.extra_data.activity_period"
                                                    type="text"
                                                    placeholder="periode Februari - Juli 2025"
                                                    class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                                                    @input="triggerDebouncedSync"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 11. REKOMENDASI PINJAMAN BJB -->
                                <div v-else-if="selectedLetterType.code === 'rekomendasi_pinjaman_bjb'" class="space-y-4">
                                    <div class="p-3.5 rounded-xl bg-amber-50/80 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-xs text-amber-900 dark:text-amber-200 flex items-start justify-between gap-2.5">
                                        <div class="flex items-start gap-2.5">
                                            <span class="text-base">💳</span>
                                            <div>
                                                <strong>Rekomendasi Pinjaman Pegawai:</strong>
                                                Surat rekomendasi pemotongan gaji langsung bagi dosen/pegawai yang mengajukan pinjaman kredit ke Bank Rekanan.
                                            </div>
                                        </div>

                                        <!-- Quick Master Dosen/Pegawai Search Trigger -->
                                        <div class="relative shrink-0">
                                            <button
                                                type="button"
                                                @click="showLecturerSearch = !showLecturerSearch"
                                                class="px-2.5 py-1 rounded-lg bg-amber-100 hover:bg-amber-200 dark:bg-amber-900/50 dark:hover:bg-amber-800/60 text-amber-900 dark:text-amber-200 text-[11px] font-bold flex items-center gap-1 transition"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                </svg>
                                                <span>Cari dari Master Dosen</span>
                                            </button>

                                            <!-- Dropdown Panel Dosen -->
                                            <div
                                                v-if="showLecturerSearch"
                                                class="absolute right-0 top-7 z-40 w-80 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-xl p-3 space-y-2 animate-in fade-in zoom-in-95"
                                            >
                                                <input
                                                    v-model="lecturerSearchQuery"
                                                    type="text"
                                                    placeholder="Cari nama dosen / NIDN..."
                                                    class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 p-2 bg-slate-50 dark:bg-slate-800"
                                                />
                                                <div class="max-h-52 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                                                    <div
                                                        v-for="l in filteredLecturers"
                                                        :key="l.id"
                                                        @click="
                                                            selectLecturer(l);
                                                            form.extra_data.employee_name = form.student_name;
                                                            form.extra_data.employee_nik = form.npm;
                                                            if (l.functional_position) form.extra_data.employee_position = l.functional_position;
                                                            triggerDebouncedSync();
                                                        "
                                                        class="p-2 hover:bg-amber-50 dark:hover:bg-slate-800 cursor-pointer rounded-lg flex flex-col transition-colors text-left"
                                                    >
                                                        <span class="font-bold text-slate-800 dark:text-slate-200">{{ l.name }}</span>
                                                        <div class="flex items-center gap-2 mt-0.5 text-[10px] text-slate-500 dark:text-slate-400">
                                                            <span v-if="l.nidn">NIDN: {{ l.nidn }}</span>
                                                            <span v-if="l.functional_position" class="text-amber-700 dark:text-amber-400 font-semibold">• {{ l.functional_position }}</span>
                                                        </div>
                                                    </div>
                                                    <div v-if="filteredLecturers.length === 0" class="p-3 text-center text-slate-400 text-xs">
                                                        Dosen tidak ditemukan
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Bagian 1: Data Pegawai / Dosen yang Direkomendasikan -->
                                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 space-y-3">
                                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                            Data Pegawai / Dosen Penerima Rekomendasi:
                                        </span>
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                            <div>
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    Nama Pegawai / Dosen <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="form.student_name"
                                                    type="text"
                                                    placeholder="Nama Lengkap Pegawai..."
                                                    class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-bold"
                                                    @input="form.extra_data.employee_name = form.student_name; triggerDebouncedSync();"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    NIK / NIDN / NIP <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="form.npm"
                                                    type="text"
                                                    placeholder="NIK Pegawai..."
                                                    class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-mono"
                                                    @input="form.extra_data.employee_nik = form.npm; triggerDebouncedSync();"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    Jabatan / Posisi Kerja <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="form.extra_data.employee_position"
                                                    type="text"
                                                    placeholder="Staf Fakultas Ilmu Komputer Universitas Subang"
                                                    class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                                                    @input="triggerDebouncedSync"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Bagian 2: Informasi Bank & Tujuan Kredit -->
                                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 space-y-3">
                                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                            Tujuan Pinjaman & Bank Rekanan:
                                        </span>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    Nama Bank Rekanan
                                                </label>
                                                <input
                                                    v-model="form.extra_data.bank_name"
                                                    type="text"
                                                    placeholder="Bank BJB"
                                                    class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-bold"
                                                    @input="triggerDebouncedSync"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    Tujuan Pengambilan Kredit
                                                </label>
                                                <input
                                                    v-model="form.extra_data.loan_purpose"
                                                    type="text"
                                                    placeholder="kebutuhan renovasi rumah"
                                                    class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                                                    @input="triggerDebouncedSync"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 12. SURAT TUGAS MENGAJAR -->
                                <div v-else-if="selectedLetterType.code === 'tugas_mengajar'" class="space-y-3">
                                    <div class="flex items-center justify-between pb-1 border-b border-slate-100 dark:border-slate-800">
                                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                            Jadwal Mata Kuliah Yang Diampu:
                                        </span>
                                        <button
                                            type="button"
                                            @click="addTeachingSchedule"
                                            class="text-[11px] font-bold text-blue-600 hover:underline"
                                        >
                                            + Tambah Jadwal MK
                                        </button>
                                    </div>
                                    <div
                                        v-for="(ts, idx) in form.extra_data.teaching_schedules"
                                        :key="idx"
                                        class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 space-y-2 text-xs"
                                    >
                                        <div class="grid grid-cols-12 gap-2">
                                            <div class="col-span-12 sm:col-span-5 relative">
                                                <div class="flex items-center justify-between mb-0.5">
                                                    <label class="block text-[10px] font-semibold text-slate-500">Nama Mata Kuliah</label>
                                                    <button
                                                        type="button"
                                                        @click="activeTeachingCourseIdx = activeTeachingCourseIdx === idx ? null : idx"
                                                        class="text-[10px] font-bold text-blue-600 hover:text-blue-700 dark:text-blue-400 inline-flex items-center gap-1"
                                                    >
                                                        <span>📚 Pilih dari Master MK</span>
                                                    </button>
                                                </div>

                                                <!-- DROPDOWN QUICK PICKER MATA KULIAH UNTUK ROW TUGAS MENGAJAR -->
                                                <div
                                                    v-if="activeTeachingCourseIdx === idx"
                                                    class="absolute left-0 top-full mt-1 w-80 sm:w-96 z-40 p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-blue-200 dark:border-blue-900 shadow-xl space-y-2"
                                                >
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-[11px] font-bold text-blue-900 dark:text-blue-300">Pilih Mata Kuliah Kurikulum:</span>
                                                        <button type="button" @click="activeTeachingCourseIdx = null" class="text-slate-400 hover:text-slate-600 text-xs font-bold">✕</button>
                                                    </div>
                                                    <input
                                                        v-model="teachingCourseSearchQuery"
                                                        type="text"
                                                        placeholder="Ketik kode / nama MK..."
                                                        class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 py-1.5 px-2.5"
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

                                                <input v-model="ts.course_name" type="text" placeholder="Desain Web 1C" class="w-full text-xs p-2 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 font-bold" />
                                            </div>
                                            <div class="col-span-4 sm:col-span-3">
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Kode MK</label>
                                                <input v-model="ts.code" type="text" placeholder="D1A12004" class="w-full text-xs p-2 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 font-mono uppercase" />
                                            </div>
                                            <div class="col-span-4 sm:col-span-2">
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">SKS</label>
                                                <input v-model.number="ts.sks" type="number" min="1" max="6" placeholder="2" class="w-full text-xs p-2 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 font-mono" />
                                            </div>
                                            <div class="col-span-4 sm:col-span-2">
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Kelas</label>
                                                <input v-model="ts.class_name" type="text" placeholder="1C" class="w-full text-xs p-2 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 uppercase font-bold" />
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-12 gap-2">
                                            <div class="col-span-12 sm:col-span-4">
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Hari</label>
                                                <input v-model="ts.day" type="text" placeholder="Kamis" class="w-full text-xs p-2 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900" />
                                            </div>
                                            <div class="col-span-6 sm:col-span-5">
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Waktu / Jam</label>
                                                <input v-model="ts.time" type="text" placeholder="18.30 - 20.00" class="w-full text-xs p-2 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 font-mono" />
                                            </div>
                                            <div class="col-span-6 sm:col-span-3 flex items-end gap-1">
                                                <div class="flex-1">
                                                    <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Ruang</label>
                                                    <input v-model="ts.room" type="text" placeholder="Lab II" class="w-full text-xs p-2 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900" />
                                                </div>
                                                <button
                                                    v-if="form.extra_data.teaching_schedules?.length > 1"
                                                    type="button"
                                                    @click="removeTeachingSchedule(idx)"
                                                    class="p-2 text-rose-500 hover:text-rose-700 font-bold"
                                                >
                                                    ✕
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 13. TUGAS PEMBIMBING MBKM -->
                                <div v-else-if="selectedLetterType.code === 'tugas_pembimbing_mbkm'" class="space-y-3">
                                    <div class="flex items-center justify-between pb-1 border-b border-slate-100 dark:border-slate-800">
                                        <div>
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                                Daftar Mahasiswa Bimbingan MBKM:
                                            </span>
                                            <p class="text-[10px] text-slate-400">
                                                Tabel mahasiswa, mitra/proyek, dan peran pembimbing sesuai surat penugasan
                                            </p>
                                        </div>
                                        <button
                                            type="button"
                                            @click="addMbkmStudent"
                                            class="text-[11px] font-bold text-blue-600 hover:underline"
                                        >
                                            + Tambah Mahasiswa
                                        </button>
                                    </div>
                                    <div
                                        v-for="(st, idx) in form.extra_data.mbkm_students"
                                        :key="idx"
                                        class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 space-y-2 text-xs"
                                    >
                                        <div class="flex items-center justify-between pb-1 border-b border-slate-200/60 dark:border-slate-700/60">
                                            <span class="font-bold text-slate-800 dark:text-slate-200 text-[11px]">
                                                Mahasiswa #{{ idx + 1 }}
                                            </span>
                                            <button
                                                v-if="form.extra_data.mbkm_students?.length > 1"
                                                type="button"
                                                @click="removeMbkmStudent(idx)"
                                                class="text-rose-500 hover:text-rose-700 text-xs font-bold"
                                                title="Hapus baris ini"
                                            >
                                                ✕ Hapus
                                            </button>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-2">
                                            <div class="sm:col-span-5 relative">
                                                <div class="flex items-center justify-between mb-0.5">
                                                    <label class="block text-[10px] font-semibold text-slate-500">Nama Mahasiswa</label>
                                                    <button
                                                        type="button"
                                                        @click="activeMbkmStudentIdx = activeMbkmStudentIdx === idx ? null : idx"
                                                        class="text-[10px] font-bold text-blue-600 hover:text-blue-700 dark:text-blue-400 inline-flex items-center gap-1"
                                                    >
                                                        <span>🔍 Pilih Mahasiswa</span>
                                                    </button>
                                                </div>

                                                <!-- DROPDOWN QUICK PICKER MAHASISWA MBKM -->
                                                <div
                                                    v-if="activeMbkmStudentIdx === idx"
                                                    class="absolute left-0 top-full mt-1 w-80 sm:w-96 z-40 p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-blue-200 dark:border-blue-900 shadow-xl space-y-2"
                                                >
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-[11px] font-bold text-blue-900 dark:text-blue-300">Pilih Data Mahasiswa:</span>
                                                        <button type="button" @click="activeMbkmStudentIdx = null" class="text-slate-400 hover:text-slate-600 text-xs font-bold">✕</button>
                                                    </div>
                                                    <input
                                                        v-model="mbkmStudentSearchQuery"
                                                        type="text"
                                                        placeholder="Cari nama atau NPM..."
                                                        class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 py-1.5 px-2.5"
                                                        autofocus
                                                    />
                                                    <div class="max-h-48 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700">
                                                        <button
                                                            v-for="s in filteredMbkmStudents"
                                                            :key="s.id"
                                                            type="button"
                                                            @click="selectMbkmStudent(idx, s)"
                                                            class="w-full text-left py-1.5 px-2 hover:bg-blue-50 dark:hover:bg-slate-700/60 rounded transition flex items-center justify-between text-xs"
                                                        >
                                                            <div>
                                                                <div class="font-bold text-slate-800 dark:text-white uppercase">{{ s.name }}</div>
                                                                <div class="text-[10px] text-slate-500 font-mono">{{ s.npm || s.identifier || '-' }} • {{ s.study_program || 'Sistem Informasi' }}</div>
                                                            </div>
                                                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-blue-100 text-blue-800 font-bold shrink-0">Pilih</span>
                                                        </button>
                                                        <div v-if="filteredMbkmStudents.length === 0" class="p-3 text-center text-slate-400 text-xs">
                                                            Mahasiswa tidak ditemukan.
                                                        </div>
                                                    </div>
                                                </div>

                                                <input
                                                    v-model="st.name"
                                                    type="text"
                                                    placeholder="LIA KARTIKA"
                                                    class="w-full text-xs p-2 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 font-bold uppercase"
                                                />
                                            </div>
                                            <div class="sm:col-span-3">
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">NPM</label>
                                                <input
                                                    v-model="st.npm"
                                                    type="text"
                                                    placeholder="D1A230409"
                                                    class="w-full text-xs p-2 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 font-mono uppercase"
                                                />
                                            </div>
                                            <div class="sm:col-span-4">
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Mitra / Project MBKM</label>
                                                <input
                                                    v-model="st.mitra_project"
                                                    type="text"
                                                    placeholder="Dicoding (Studi Independen)"
                                                    class="w-full text-xs p-2 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900"
                                                />
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-2">
                                            <div class="sm:col-span-6">
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Peran Pembimbing</label>
                                                <select
                                                    v-model="st.role"
                                                    class="w-full text-xs p-2 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 font-medium"
                                                >
                                                    <option value="Pembimbing I">Pembimbing I</option>
                                                    <option value="Pembimbing II">Pembimbing II</option>
                                                    <option value="Pembimbing Lapangan">Pembimbing Lapangan</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 14. TUGAS PEMBIMBING TA -->
                                <div v-else-if="selectedLetterType.code === 'tugas_pembimbing_ta'" class="space-y-3">
                                    <div class="flex items-center justify-between pb-1 border-b border-slate-100 dark:border-slate-800">
                                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                            Mahasiswa Bimbingan Tugas Akhir:
                                        </span>
                                        <button
                                            type="button"
                                            @click="addTaStudent"
                                            class="text-[11px] font-bold text-blue-600 hover:underline"
                                        >
                                            + Tambah Mahasiswa TA
                                        </button>
                                    </div>
                                    <div
                                        v-for="(st, idx) in form.extra_data.ta_students"
                                        :key="idx"
                                        class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex flex-wrap items-center gap-2 text-xs"
                                    >
                                        <input v-model="st.name" type="text" placeholder="Nama Mahasiswa" class="flex-1 min-w-[120px] text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900" />
                                        <input v-model="st.npm" type="text" placeholder="NPM" class="w-28 text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 font-mono" />
                                        <input v-model="st.role" type="text" placeholder="Pembimbing I / II" class="w-28 text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900" />
                                        <button
                                            type="button"
                                            @click="removeTaStudent(idx)"
                                            class="text-rose-500 hover:text-rose-700 p-1 font-bold"
                                        >
                                            ✕
                                        </button>
                                    </div>
                                </div>

                                <!-- 15. KESANGGUPAN MENGAJAR -->
                                <div v-else-if="selectedLetterType.code === 'kesanggupan_mengajar'" class="space-y-4">
                                    <!-- Konteks Semester & Thn Akademik Narasi -->
                                    <div class="p-3 rounded-xl bg-blue-50/60 dark:bg-blue-950/20 border border-blue-200/60 dark:border-blue-900/40 space-y-3">
                                        <div class="text-[11px] font-bold text-blue-900 dark:text-blue-300 flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span>Periode Kesanggupan Mengajar</span>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    Semester (Ganjil/Genap)
                                                </label>
                                                <select
                                                    v-model="form.extra_data.semester_type"
                                                    class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-semibold"
                                                >
                                                    <option value="Ganjil">Semester Ganjil</option>
                                                    <option value="Genap">Semester Genap</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    Tahun Akademik
                                                </label>
                                                <input
                                                    v-model="form.academic_year"
                                                    type="text"
                                                    placeholder="2026/2027"
                                                    class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-semibold"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Data Akademik & Dosen Pelengkap -->
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Jabatan Akademik
                                            </label>
                                            <input
                                                v-model="form.extra_data.academic_rank"
                                                type="text"
                                                placeholder="Tenaga Pengajar / Asisten Ahli / Lektor"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Email Dosen
                                            </label>
                                            <input
                                                v-model="form.extra_data.email"
                                                type="email"
                                                placeholder="dosen@unsub.ac.id"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-mono"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Bidang Keahlian
                                            </label>
                                            <input
                                                v-model="form.extra_data.expertise"
                                                type="text"
                                                placeholder="Pemrograman Web & Sistem Informasi"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                                            />
                                        </div>
                                    </div>

                                    <!-- Tabel Mata Kuliah Kesanggupan Mengajar -->
                                    <div class="space-y-3 pt-2 border-t border-slate-100 dark:border-slate-800">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                                    Tabel Mata Kuliah Kesanggupan Mengajar:
                                                </span>
                                                <p class="text-[10px] text-slate-400">Sesuai kolom tabel pada live surat resmi</p>
                                            </div>
                                            <button
                                                type="button"
                                                @click="addKesanggupanCourse"
                                                class="px-2.5 py-1 text-[11px] font-bold text-blue-600 hover:text-white bg-blue-50 hover:bg-blue-600 border border-blue-200 hover:border-transparent rounded-lg transition"
                                            >
                                                + Tambah MK
                                            </button>
                                        </div>

                                        <div
                                            v-for="(tc, idx) in form.extra_data.teaching_courses"
                                            :key="idx"
                                            class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 space-y-2.5 text-xs relative"
                                        >
                                            <!-- Baris 1: Nama MK + Master MK picker & Kode MK -->
                                            <div class="grid grid-cols-12 gap-2">
                                                <div class="col-span-12 sm:col-span-8 relative">
                                                    <div class="flex items-center justify-between mb-0.5">
                                                        <label class="block text-[10px] font-semibold text-slate-500">
                                                            {{ idx + 1 }}. Nama Mata Kuliah
                                                        </label>
                                                        <button
                                                            type="button"
                                                            @click="activeKesanggupanCourseIdx = activeKesanggupanCourseIdx === idx ? null : idx"
                                                            class="text-[10px] font-bold text-blue-600 hover:text-blue-700 dark:text-blue-400 inline-flex items-center gap-1"
                                                        >
                                                            <span>📚 Pilih dari Master MK</span>
                                                        </button>
                                                    </div>

                                                    <!-- DROPDOWN QUICK PICKER MASTER MK -->
                                                    <div
                                                        v-if="activeKesanggupanCourseIdx === idx"
                                                        class="absolute left-0 top-full mt-1 w-80 sm:w-96 z-40 p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-blue-200 dark:border-blue-900 shadow-xl space-y-2"
                                                    >
                                                        <div class="flex items-center justify-between">
                                                            <span class="text-[11px] font-bold text-blue-900 dark:text-blue-300">Pilih Mata Kuliah Kurikulum:</span>
                                                            <button type="button" @click="activeKesanggupanCourseIdx = null" class="text-slate-400 hover:text-slate-600 text-xs font-bold">✕</button>
                                                        </div>
                                                        <input
                                                            v-model="kesanggupanCourseSearchQuery"
                                                            type="text"
                                                            placeholder="Ketik kode / nama MK..."
                                                            class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 py-1.5 px-2.5"
                                                            autofocus
                                                        />
                                                        <div class="max-h-48 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700">
                                                            <button
                                                                v-for="c in filteredKesanggupanCourses"
                                                                :key="c.id"
                                                                type="button"
                                                                @click="selectKesanggupanCourse(idx, c)"
                                                                class="w-full text-left py-1.5 px-2 hover:bg-blue-50 dark:hover:bg-slate-700/60 rounded transition flex items-center justify-between text-xs"
                                                            >
                                                                <div>
                                                                    <div class="font-bold text-slate-800 dark:text-white">{{ c.name }}</div>
                                                                    <div class="text-[10px] text-slate-500 font-mono">{{ c.code }} • {{ c.sks }} SKS • Sem {{ c.semester || '-' }}</div>
                                                                </div>
                                                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-blue-100 text-blue-800 font-bold shrink-0">Pilih</span>
                                                            </button>
                                                            <div v-if="filteredKesanggupanCourses.length === 0" class="p-3 text-center text-slate-400 text-xs">
                                                                Mata kuliah tidak ditemukan.
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <input
                                                        v-model="tc.name"
                                                        type="text"
                                                        placeholder="Desain Web"
                                                        class="w-full text-xs p-2 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 font-bold"
                                                    />
                                                </div>

                                                <div class="col-span-12 sm:col-span-4">
                                                    <div class="flex items-center justify-between mb-0.5">
                                                        <label class="block text-[10px] font-semibold text-slate-500">Kode MK</label>
                                                        <button
                                                            v-if="form.extra_data.teaching_courses.length > 1"
                                                            type="button"
                                                            @click="removeKesanggupanCourse(idx)"
                                                            class="text-rose-500 hover:text-rose-700 text-[10px] font-bold"
                                                            title="Hapus baris ini"
                                                        >
                                                            ✕ Hapus Baris
                                                        </button>
                                                    </div>
                                                    <input
                                                        v-model="tc.code"
                                                        type="text"
                                                        placeholder="D1A12004"
                                                        class="w-full text-xs p-2 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 font-mono uppercase"
                                                    />
                                                </div>
                                            </div>

                                            <!-- Baris 2: Jenis Pengajaran, Kelas, SKS, Total SKS -->
                                            <div class="grid grid-cols-12 gap-2">
                                                <div class="col-span-12 sm:col-span-5">
                                                    <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Jenis Pengajaran</label>
                                                    <input
                                                        v-model="tc.teaching_type"
                                                        type="text"
                                                        placeholder="Teori / Praktikum / Mandiri (opsional)"
                                                        class="w-full text-xs p-2 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900"
                                                    />
                                                </div>
                                                <div class="col-span-4 sm:col-span-2">
                                                    <label class="block text-[10px] font-semibold text-slate-500 mb-0.5" title="Jumlah rombel atau label kelas">Jml Kelas</label>
                                                    <input
                                                        v-model="tc.class_name"
                                                        type="text"
                                                        placeholder="3"
                                                        @input="updateKesanggupanTotalSks(tc)"
                                                        class="w-full text-xs p-2 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 text-center font-bold"
                                                    />
                                                </div>
                                                <div class="col-span-4 sm:col-span-2">
                                                    <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">SKS</label>
                                                    <input
                                                        v-model.number="tc.sks"
                                                        type="number"
                                                        min="1"
                                                        max="6"
                                                        placeholder="2"
                                                        @input="updateKesanggupanTotalSks(tc)"
                                                        class="w-full text-xs p-2 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 font-mono text-center"
                                                    />
                                                </div>
                                                <div class="col-span-4 sm:col-span-3">
                                                    <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Total SKS</label>
                                                    <input
                                                        v-model="tc.total_sks"
                                                        type="text"
                                                        placeholder="6"
                                                        class="w-full text-xs p-2 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 font-mono text-center font-bold text-blue-600 dark:text-blue-400"
                                                    />
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Summary Total Jumlah SKS Keseluruhan -->
                                        <div class="p-3 rounded-xl bg-slate-100 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 flex items-center justify-between text-xs">
                                            <span class="font-bold text-slate-700 dark:text-slate-300">
                                                Jumlah Total SKS (Baris Bawah Tabel):
                                            </span>
                                            <div class="flex items-center gap-2">
                                                <input
                                                    v-model="form.extra_data.total_sks_sum"
                                                    type="text"
                                                    placeholder="Otomatis dihitung jika kosong"
                                                    class="w-36 text-xs p-1.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 font-mono font-bold text-center"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 16. PAKTA INTEGRITAS -->
                                <div v-else-if="selectedLetterType.code === 'undangan_rapat_akademik'" class="space-y-3">
                                    <div class="text-xs font-bold text-slate-700 dark:text-slate-300 pb-1 border-b border-slate-100 dark:border-slate-800">
                                        Data Undangan & Jadwal Rapat Akademik
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Agenda / Perihal Rapat <span class="text-rose-500">*</span>
                                            </label>
                                            <input
                                                v-model="form.extra_data.agenda"
                                                type="text"
                                                placeholder="Rapat Akademik / Koordinasi Perkuliahan"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-bold"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Jumlah Lampiran
                                            </label>
                                            <input
                                                v-model="form.extra_data.lampiran_count"
                                                type="text"
                                                placeholder="-"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-medium"
                                            />
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Hari / Tanggal Rapat <span class="text-rose-500">*</span>
                                            </label>
                                            <input
                                                v-model="form.extra_data.meeting_day_date"
                                                type="text"
                                                placeholder="Selasa, 8 September 2026"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-medium"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Waktu Pelaksanaan <span class="text-rose-500">*</span>
                                            </label>
                                            <input
                                                v-model="form.extra_data.meeting_time"
                                                type="text"
                                                placeholder="09.00 WIB s/d Selesai"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-medium"
                                            />
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                            Format Pertemuan <span class="text-rose-500">*</span>
                                        </label>
                                        <div class="grid grid-cols-2 gap-2">
                                            <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 cursor-pointer">
                                                <input
                                                    type="radio"
                                                    value="offline"
                                                    v-model="form.extra_data.meeting_type"
                                                    class="text-blue-600 focus:ring-blue-500"
                                                />
                                                <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Offline (Tatap Muka)</span>
                                            </label>
                                            <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 cursor-pointer">
                                                <input
                                                    type="radio"
                                                    value="online"
                                                    v-model="form.extra_data.meeting_type"
                                                    class="text-blue-600 focus:ring-blue-500"
                                                />
                                                <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Online (Google Meet / Zoom)</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Lokasi Offline -->
                                    <div v-if="form.extra_data.meeting_type !== 'online'">
                                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                            Tempat / Ruangan Rapat <span class="text-rose-500">*</span>
                                        </label>
                                        <input
                                            v-model="form.extra_data.meeting_location"
                                            type="text"
                                            placeholder="Ruang 12 Fakultas Ilmu Komputer"
                                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-medium"
                                        />
                                    </div>

                                    <!-- Lokasi Online -->
                                    <div v-else class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-3 rounded-xl border border-blue-200 dark:border-blue-900/50 bg-blue-50/40 dark:bg-blue-950/20">
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Platform
                                            </label>
                                            <select
                                                v-model="form.extra_data.meeting_platform"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-medium"
                                            >
                                                <option value="Google Meet">Google Meet</option>
                                                <option value="Zoom Meeting">Zoom Meeting</option>
                                            </select>
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Link Pertemuan Online <span class="text-rose-500">*</span>
                                            </label>
                                            <input
                                                v-model="form.extra_data.meeting_link"
                                                type="url"
                                                placeholder="https://meet.google.com/abc-defg-hij"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-mono"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- 16. PAKTA INTEGRITAS -->
                                <div v-else-if="selectedLetterType.code === 'pakta_integritas'" class="space-y-4">
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                            Mata Kuliah Yang Diampu <span class="text-rose-500">*</span>
                                        </label>
                                        <textarea
                                            v-model="form.extra_data.courses_taught"
                                            rows="2"
                                            placeholder="Pemrograman Web, Sistem Basis Data..."
                                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                                        ></textarea>
                                    </div>

                                    <!-- Butir-Butir Ketentuan Fakta Integritas (Dinamis: Tambah / Hapus / Edit) -->
                                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 space-y-3">
                                        <div class="flex items-center justify-between pb-2 border-b border-slate-200 dark:border-slate-700">
                                            <div>
                                                <div class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                                    Butir-Butir Ketentuan Fakta Integritas ({{ (form.extra_data.integrity_points || []).length }} Butir)
                                                </div>
                                                <p class="text-[10px] text-slate-500 dark:text-slate-400">
                                                    Teks butir dapat diedit, ditambah, atau dihapus secara dinamis sesuai kebutuhan
                                                </p>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <button
                                                    type="button"
                                                    @click="resetIntegrityPoints"
                                                    class="text-[10px] font-semibold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300"
                                                    title="Kembalikan ke 5 butir standar Fasilkom"
                                                >
                                                    Reset Standar
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="addIntegrityPoint"
                                                    class="px-2.5 py-1 rounded-lg text-[11px] font-bold text-white bg-blue-600 hover:bg-blue-500 transition shadow-xs"
                                                >
                                                    + Tambah Butir
                                                </button>
                                            </div>
                                        </div>

                                        <div class="space-y-2.5">
                                            <div
                                                v-for="(point, idx) in (form.extra_data.integrity_points || [])"
                                                :key="idx"
                                                class="flex items-start gap-2 group"
                                            >
                                                <span class="w-5 pt-1 text-[11px] font-bold text-blue-600 dark:text-blue-400 shrink-0 text-right">
                                                    {{ idx + 1 }}.
                                                </span>
                                                <textarea
                                                    v-model="form.extra_data.integrity_points[idx]"
                                                    rows="2"
                                                    class="flex-1 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500"
                                                    placeholder="Tuliskan butir ketentuan pakta integritas..."
                                                ></textarea>
                                                <button
                                                    v-if="(form.extra_data.integrity_points || []).length > 1"
                                                    type="button"
                                                    @click="removeIntegrityPoint(idx)"
                                                    class="p-1 text-slate-400 hover:text-rose-500 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/30 transition shrink-0 mt-1"
                                                    title="Hapus butir ini"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 17. PENGAJUAN DANA FAKULTAS -->
                                <div v-else-if="selectedLetterType.code === 'pengajuan_dana'" class="space-y-4">
                                    <div class="p-3 rounded-xl bg-blue-50/50 dark:bg-blue-950/20 border border-blue-100 dark:border-blue-900/40 text-xs text-blue-900 dark:text-blue-300">
                                        <span class="font-bold">Format Pengajuan Kebutuhan Dana Fakultas ke Rektor:</span>
                                        Rincian Pagu Anggaran, Pencairan Bulan Sebelumnya, serta Nominal Pengajuan Bulan Berjalan.
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Pagu Anggaran Tahun Berjalan (Rp)
                                            </label>
                                            <input
                                                v-model="form.extra_data.pagu_amount"
                                                type="text"
                                                placeholder="165.200.000"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-mono font-bold"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Sisa Pagu Anggaran (Rp)
                                            </label>
                                            <input
                                                v-model="form.extra_data.remaining_pagu_amount"
                                                type="text"
                                                placeholder="145.046.500"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-mono"
                                            />
                                        </div>
                                    </div>

                                    <!-- Pencairan Sebelumnya -->
                                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700 space-y-3">
                                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Rincian Pencairan Bulan Sebelumnya:</span>
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                                            <div class="sm:col-span-2">
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-1">Keterangan Pencairan 1</label>
                                                <input
                                                    v-model="form.extra_data.disbursement_1_label"
                                                    type="text"
                                                    placeholder="Pencairan bulan September 2026"
                                                    class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-1">Nominal (Rp)</label>
                                                <input
                                                    v-model="form.extra_data.disbursement_1_amount"
                                                    type="text"
                                                    placeholder="6.950.000"
                                                    class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 font-mono"
                                                />
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                                            <div class="sm:col-span-2">
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-1">Keterangan Pencairan 2</label>
                                                <input
                                                    v-model="form.extra_data.disbursement_2_label"
                                                    type="text"
                                                    placeholder="Pencairan bulan Oktober 2026"
                                                    class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-1">Nominal (Rp)</label>
                                                <input
                                                    v-model="form.extra_data.disbursement_2_amount"
                                                    type="text"
                                                    placeholder="13.203.500"
                                                    class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 font-mono"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Pengajuan Bulan Berjalan & Saldo Lalu -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700 space-y-2">
                                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300">Pengajuan Bulan Ini</label>
                                            <input
                                                v-model="form.extra_data.submitted_label"
                                                type="text"
                                                placeholder="Pengajuan November 2026"
                                                class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 mb-1"
                                            />
                                            <input
                                                v-model="form.extra_data.submitted_amount"
                                                type="text"
                                                placeholder="26.101.509"
                                                class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 font-mono"
                                            />
                                        </div>
                                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700 space-y-2">
                                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300">Saldo Bulan Lalu</label>
                                            <input
                                                v-model="form.extra_data.last_balance_label"
                                                type="text"
                                                placeholder="Saldo Penggunaan bulan lalu"
                                                class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 mb-1"
                                            />
                                            <input
                                                v-model="form.extra_data.last_balance_amount"
                                                type="text"
                                                placeholder="1.439.509"
                                                class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 font-mono"
                                            />
                                        </div>
                                    </div>

                                    <!-- Total Bersih & Terbilang -->
                                    <div class="p-3.5 rounded-xl bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800/50 space-y-3">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-[11px] font-bold text-emerald-800 dark:text-emerald-300 mb-1">
                                                    Jumlah Bersih Kebutuhan Dana (Rp) *
                                                </label>
                                                <input
                                                    v-model="form.extra_data.final_net_amount"
                                                    type="text"
                                                    placeholder="24.662.000"
                                                    class="w-full text-xs rounded-xl border border-emerald-300 dark:border-emerald-700 bg-white dark:bg-slate-800 p-2.5 font-mono font-bold text-emerald-700 dark:text-emerald-400"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-bold text-emerald-800 dark:text-emerald-300 mb-1">
                                                    Tanggal Surat Resmi
                                                </label>
                                                <input
                                                    v-model="form.extra_data.letter_date_text"
                                                    type="text"
                                                    placeholder="Subang, 2 November 2026"
                                                    class="w-full text-xs rounded-xl border border-emerald-300 dark:border-emerald-700 bg-white dark:bg-slate-800 p-2.5"
                                                />
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-bold text-emerald-800 dark:text-emerald-300 mb-1">
                                                Terbilang
                                            </label>
                                            <input
                                                v-model="form.extra_data.terbilang"
                                                type="text"
                                                placeholder="Dua Puluh Empat Juta Enam Ratus Enam Puluh Dua Ribu Rupiah"
                                                class="w-full text-xs rounded-xl border border-emerald-300 dark:border-emerald-700 bg-white dark:bg-slate-800 p-2.5 italic"
                                            />
                                        </div>
                                    </div>

                                    <!-- Dual Signature Identitas Penandatangan -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Nama Dekan (Mengetahui)
                                            </label>
                                            <input
                                                v-model="form.extra_data.dekan_name"
                                                type="text"
                                                placeholder="Dr. Tepi Peirisal,S.Sos, M.Si"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-bold"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Nama Bendahara Fakultas (Pengaju)
                                            </label>
                                            <input
                                                v-model="form.extra_data.treasurer_name"
                                                type="text"
                                                placeholder="Fina Widya Ramdhani, S.AN"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-bold"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- 18. PENGADAAN & PERBAIKAN SARPRAS -->
                                <div v-else-if="selectedLetterType.code === 'pengadaan_sarpras' || selectedLetterType.code === 'perbaikan_sarpras'" class="space-y-4">
                                    <div class="space-y-3">
                                        <div class="flex items-center justify-between pb-1 border-b border-slate-100 dark:border-slate-800">
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                                Daftar Item Sarana & Prasarana:
                                            </span>
                                            <button
                                                type="button"
                                                @click="addSarprasItem"
                                                class="text-[11px] font-bold text-blue-600 hover:underline"
                                            >
                                                + Tambah Item
                                            </button>
                                        </div>
                                        <div
                                            v-for="(item, idx) in form.extra_data.sarpras_items"
                                            :key="idx"
                                            class="flex items-center gap-2"
                                        >
                                            <span class="text-xs font-bold text-slate-400 w-4">{{ idx + 1 }}.</span>
                                            <input
                                                v-model="form.extra_data.sarpras_items[idx]"
                                                type="text"
                                                placeholder="Contoh: 1 Unit Printer Epson L3250..."
                                                class="flex-1 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                                                @input="triggerDebouncedSync"
                                            />
                                            <button
                                                v-if="form.extra_data.sarpras_items?.length > 1"
                                                type="button"
                                                @click="removeSarprasItem(idx)"
                                                class="p-2 text-rose-500 hover:text-rose-700 font-bold"
                                                title="Hapus Item"
                                            >
                                                ✕
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Keterangan Unit Kerja & Pertimbangan Khusus Pengadaan Sarpras -->
                                    <div v-if="selectedLetterType.code === 'pengadaan_sarpras'" class="space-y-3 pt-2 border-t border-slate-100 dark:border-slate-800">
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                Unit Kerja Pemohon
                                            </label>
                                            <input
                                                v-model="form.extra_data.unit_kerja"
                                                type="text"
                                                placeholder="BAAK FAKULTAS ILMU KOMPUTER"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 uppercase font-bold"
                                                @input="triggerDebouncedSync"
                                            />
                                        </div>

                                        <div class="space-y-2">
                                            <div class="flex items-center justify-between">
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400">
                                                    Poin Bahan Pertimbangan:
                                                </label>
                                                <button
                                                    type="button"
                                                    @click="addConsiderationPoint"
                                                    class="text-[11px] font-bold text-blue-600 hover:underline"
                                                >
                                                    + Tambah Poin
                                                </button>
                                            </div>
                                            <div
                                                v-for="(point, pIdx) in form.extra_data.considerations"
                                                :key="pIdx"
                                                class="flex items-center gap-2"
                                            >
                                                <span class="text-xs font-bold text-slate-400 w-4">{{ pIdx + 1 }}.</span>
                                                <input
                                                    v-model="form.extra_data.considerations[pIdx]"
                                                    type="text"
                                                    placeholder="Tuliskan alasan / pertimbangan pengadaan..."
                                                    class="flex-1 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2"
                                                    @input="triggerDebouncedSync"
                                                />
                                                <button
                                                    v-if="form.extra_data.considerations?.length > 1"
                                                    type="button"
                                                    @click="removeConsiderationPoint(pIdx)"
                                                    class="p-2 text-rose-500 hover:text-rose-700 font-bold"
                                                    title="Hapus Poin"
                                                >
                                                    ✕
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 19. PEMBERITAHUAN UTS/UAS KE DOSEN & PENGUMUMAN UTS/UAS KE MAHASISWA -->
                                <div v-else-if="selectedLetterType.code === 'pemberitahuan_ujian_dosen' || selectedLetterType.code === 'pengumuman_ujian_mahasiswa'" class="space-y-4">
                                    <div class="space-y-2.5">
                                        <div class="flex items-center justify-between">
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400">
                                                Daftar Butir Ketentuan Ujian:
                                            </label>
                                            <button
                                                type="button"
                                                @click="addProvisionPoint"
                                                class="text-[11px] font-bold text-blue-600 hover:underline"
                                            >
                                                + Tambah Poin
                                            </button>
                                        </div>
                                        <div
                                            v-for="(point, ptIdx) in form.extra_data.provision_points"
                                            :key="ptIdx"
                                            class="flex items-start gap-2"
                                        >
                                            <span class="text-xs font-bold text-slate-400 w-5 pt-2 text-right">{{ ptIdx + 1 }}.</span>
                                            <textarea
                                                v-model="form.extra_data.provision_points[ptIdx]"
                                                rows="2"
                                                placeholder="Isi butir ketentuan pelaksanaan ujian..."
                                                class="flex-1 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2"
                                                @input="triggerDebouncedSync"
                                            ></textarea>
                                            <button
                                                v-if="form.extra_data.provision_points?.length > 1"
                                                type="button"
                                                @click="removeProvisionPoint(ptIdx)"
                                                class="p-2 text-rose-500 hover:text-rose-700 font-bold"
                                                title="Hapus Butir"
                                            >
                                                ✕
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- 20. PERBAIKAN DATA PDDIKTI -->
                                <div v-else-if="selectedLetterType.code === 'perbaikan_data_pddikti'" class="space-y-3">
                                    <div class="flex items-center justify-between pb-1 border-b border-slate-100 dark:border-slate-800">
                                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                            Tabel Perubahan Data PDDIKTI:
                                        </span>
                                        <button
                                            type="button"
                                            @click="addCorrectionItem"
                                            class="text-[11px] font-bold text-blue-600 hover:underline"
                                        >
                                            + Tambah Baris
                                        </button>
                                    </div>
                                    <div
                                        v-for="(item, idx) in form.extra_data.correction_items"
                                        :key="idx"
                                        class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 space-y-2 text-xs"
                                    >
                                        <div class="grid grid-cols-12 gap-2">
                                            <div class="col-span-5">
                                                <input v-model="item.npm" type="text" placeholder="NPM" class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 font-mono" @input="triggerDebouncedSync" />
                                            </div>
                                            <div class="col-span-6">
                                                <input v-model="item.name" type="text" placeholder="Nama Mahasiswa" class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900" @input="triggerDebouncedSync" />
                                            </div>
                                            <div class="col-span-1 flex items-center justify-end">
                                                <button type="button" @click="removeCorrectionItem(idx); triggerDebouncedSync();" class="text-rose-500 font-bold">✕</button>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <input v-model="item.initial_data" type="text" placeholder="Data Awal" class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900" @input="triggerDebouncedSync" />
                                            <input v-model="item.proposed_correction" type="text" placeholder="Usulan Perbaikan" class="w-full text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 font-bold text-blue-600" @input="triggerDebouncedSync" />
                                        </div>
                                    </div>
                                </div>

                                <!-- 21. PEMBUKAAN PERIODE -->
                                <div v-else-if="selectedLetterType.code === 'pembukaan_periode'" class="space-y-4">
                                    <div class="p-3 rounded-xl bg-blue-50/80 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800 text-xs text-blue-900 dark:text-blue-200 flex items-start gap-2">
                                        <span class="text-base">📅</span>
                                        <div>
                                            <strong>Surat Pengantar Pembukaan Periode:</strong>
                                            Surat resmi pengantar permohonan pembukaan periode Feeder PDDIKTI ke Rektor Universitas Subang.
                                        </div>
                                    </div>

                                    <!-- Informasi Header Memo Resmi -->
                                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 space-y-3">
                                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                            1. Header Memo Resmi (Perihal, Lampiran, & Tujuan Surat):
                                        </span>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    Perihal Surat <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="form.extra_data.subject"
                                                    type="text"
                                                    placeholder="Pengajuan Pembukaan Periode Tipe 2"
                                                    class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-bold"
                                                    @input="triggerDebouncedSync"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    Jumlah Lampiran
                                                </label>
                                                <input
                                                    v-model="form.extra_data.lampiran_count"
                                                    type="text"
                                                    placeholder="1 (satu) berkas"
                                                    class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                                                    @input="triggerDebouncedSync"
                                                />
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    Penerima Surat (Kepada Yth.)
                                                </label>
                                                <textarea
                                                    v-model="form.extra_data.target_recipient"
                                                    rows="3"
                                                    placeholder="Rektor Universitas Subang&#10;u.p. Wakil Rektor I Bid. Akademik&#10;dan kemahasiswaan"
                                                    class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                                                    @input="triggerDebouncedSync"
                                                ></textarea>
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    Kota / Lokasi Tujuan (di ...)
                                                </label>
                                                <input
                                                    v-model="form.extra_data.target_city"
                                                    type="text"
                                                    placeholder="Tempat"
                                                    class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                                                    @input="triggerDebouncedSync"
                                                />
                                                <p class="text-[10px] text-slate-400 mt-1">Default tercetak: 'di Tempat'</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Isi / Narasi Pokok Permohonan Pembukaan Periode -->
                                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 space-y-2.5">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                                2. Isi Paragraf Permohonan Pembukaan Periode:
                                            </span>
                                            <button
                                                type="button"
                                                @click="
                                                    form.extra_data.purpose_description = 'Sehubungan dengan kebutuhan perbaikan data PIN mahasiswa yang akan lulus, dengan ini kami mengajukan permohonan Pembukaan Periode Tipe 2 melalui Feeder PDDIKTI untuk memperbaiki data pada Program Studi Sistem Informasi (57201). Adapun data yang kami ajukan sebagaimana data terlampir.';
                                                    triggerDebouncedSync();
                                                "
                                                class="text-[10px] font-bold text-blue-600 hover:underline"
                                            >
                                                ↺ Reset Default
                                            </button>
                                        </div>
                                        <textarea
                                            v-model="form.extra_data.purpose_description"
                                            rows="5"
                                            placeholder="Sehubungan dengan kebutuhan perbaikan data PIN mahasiswa yang akan lulus, dengan ini kami mengajukan permohonan Pembukaan Periode Tipe 2 melalui Feeder PDDIKTI untuk memperbaiki data pada Program Studi Sistem Informasi (57201). Adapun data yang kami ajukan sebagaimana data terlampir."
                                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-3 leading-relaxed"
                                            @input="triggerDebouncedSync"
                                        ></textarea>
                                        <p class="text-[10px] text-slate-400">
                                            Paragraf di atas akan dicetak tepat di bawah salam pembuka "Dengan Hormat," pada live surat resmi.
                                        </p>
                                    </div>
                                </div>

                                <!-- 22. PENGAJUAN PENANDATANGANAN IJAZAH & PENGAJUAN BLANKO IJAZAH TRANSKRIP -->
                                <div v-else-if="selectedLetterType.code === 'pengajuan_ttd_ijazah' || selectedLetterType.code === 'pengajuan_blanko_ijazah_transkrip'" class="space-y-4">
                                    <div class="p-3 rounded-xl bg-blue-50/80 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800 text-xs text-blue-900 dark:text-blue-200 flex items-start gap-2">
                                        <span class="text-base">🎓</span>
                                        <div>
                                            <strong>{{ selectedLetterType.name }}:</strong>
                                            Sesuaikan perihal, lampiran, serta tabel rekap pelaksanaan sidang dan jumlah mahasiswa. Narasi pengantar dapat disesuaikan di Langkah 5 (Narasi).
                                        </div>
                                    </div>

                                    <!-- Kolom Perihal & Lampiran Khusus Pengajuan Blanko Ijazah dan Blanko Transkrip -->
                                    <div
                                        v-if="selectedLetterType.code === 'pengajuan_blanko_ijazah_transkrip'"
                                        class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 space-y-3"
                                    >
                                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                            Perihal & Lampiran Surat:
                                        </span>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    Perihal Surat <span class="text-rose-500">*</span>
                                                </label>
                                                <input
                                                    v-model="form.extra_data.subject"
                                                    type="text"
                                                    placeholder="Pengajuan Blanko Ijazah dan Blanko Transkrip"
                                                    class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-bold"
                                                    @input="triggerDebouncedSync"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                    Jumlah Lampiran
                                                </label>
                                                <input
                                                    v-model="form.extra_data.lampiran_count"
                                                    type="text"
                                                    placeholder="1 (satu) berkas"
                                                    class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                                                    @input="triggerDebouncedSync"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Tabel Rekap Pelaksanaan Sidang & Jumlah Mahasiswa -->
                                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 space-y-3">
                                        <div class="flex items-center justify-between pb-1 border-b border-slate-200 dark:border-slate-700">
                                            <div>
                                                <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                                    Tabel Rekap Pelaksanaan Sidang:
                                                </span>
                                                <p class="text-[10px] text-slate-400">Kolom No, Tanggal Sidang, dan Jumlah Mahasiswa sesuai live surat</p>
                                            </div>
                                            <button
                                                type="button"
                                                @click="addSessionSummary(); triggerDebouncedSync();"
                                                class="px-2.5 py-1 text-[11px] font-bold text-blue-600 hover:text-white bg-blue-50 hover:bg-blue-600 border border-blue-200 hover:border-transparent rounded-lg transition"
                                            >
                                                + Tambah Baris Sidang
                                            </button>
                                        </div>

                                        <div
                                            v-for="(session, sIdx) in form.extra_data.session_summary_table"
                                            :key="sIdx"
                                            class="p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 space-y-2 text-xs"
                                        >
                                            <div class="flex items-center justify-between">
                                                <span class="text-[10px] font-bold text-slate-400">Baris ke-{{ sIdx + 1 }}</span>
                                                <button
                                                    v-if="form.extra_data.session_summary_table.length > 1"
                                                    type="button"
                                                    @click="removeSessionSummary(sIdx); triggerDebouncedSync();"
                                                    class="text-rose-500 hover:text-rose-700 text-[10px] font-bold"
                                                >
                                                    ✕ Hapus Baris
                                                </button>
                                            </div>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                                <div>
                                                    <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Tanggal Sidang</label>
                                                    <input
                                                        v-model="session.session_date"
                                                        type="text"
                                                        :placeholder="selectedLetterType.code === 'pengajuan_blanko_ijazah_transkrip' ? '26 Juni 2026' : '27 Agustus 2026'"
                                                        class="w-full text-xs p-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 font-medium"
                                                        @input="triggerDebouncedSync"
                                                    />
                                                </div>
                                                <div>
                                                    <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Jumlah Mahasiswa</label>
                                                    <input
                                                        v-model="session.count_text"
                                                        type="text"
                                                        :placeholder="selectedLetterType.code === 'pengajuan_blanko_ijazah_transkrip' ? '12 ( Dua belas ) mahasiswa' : '32 (Tiga Puluh Dua ) mahasiswa'"
                                                        class="w-full text-xs p-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 font-medium"
                                                        @input="triggerDebouncedSync"
                                                    />
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Opsi Tampilkan Baris Total Khusus Pengajuan Blanko jika 1 baris -->
                                        <div
                                            v-if="selectedLetterType.code === 'pengajuan_blanko_ijazah_transkrip'"
                                            class="flex items-center gap-2 pt-1 pb-1"
                                        >
                                            <input
                                                id="show_total_row_blanko"
                                                v-model="form.extra_data.show_total_row"
                                                type="checkbox"
                                                class="rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                                @change="triggerDebouncedSync"
                                            />
                                            <label for="show_total_row_blanko" class="text-xs font-semibold text-slate-600 dark:text-slate-400 cursor-pointer">
                                                Tampilkan Baris Total (otomatis tampil jika sidang > 1 baris)
                                            </label>
                                        </div>

                                        <!-- Baris Total Tabel -->
                                        <div
                                            v-if="selectedLetterType.code === 'pengajuan_ttd_ijazah' || form.extra_data.show_total_row || (form.extra_data.session_summary_table && form.extra_data.session_summary_table.length > 1)"
                                            class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs"
                                        >
                                            <span class="font-bold text-slate-700 dark:text-slate-300">
                                                Keterangan Baris Total Tabel:
                                            </span>
                                            <input
                                                v-model="form.extra_data.total_count_text"
                                                type="text"
                                                :placeholder="selectedLetterType.code === 'pengajuan_blanko_ijazah_transkrip' ? '12 ( Dua belas ) mahasiswa' : '32 (Tiga Puluh Dua ) mahasiswa'"
                                                class="w-full sm:w-72 text-xs p-1.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 font-bold text-blue-600 dark:text-blue-400"
                                                @input="triggerDebouncedSync"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- 23. FALLBACK DEFAULT UNTUK JENIS SURAT LAINNYA -->
                                <div v-else class="space-y-3">
                                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs text-slate-600 dark:text-slate-400">
                                        <div class="font-bold text-slate-800 dark:text-slate-200 mb-1">Form Standar Surat Resmi Fasilkom:</div>
                                        Format ini menggunakan tata letak baku Fasilkom UNSUB dengan data identitas pemohon yang telah diisi pada Langkah 2.
                                    </div>
                                    <div
                                        v-if="selectedLetterType.required_fields && selectedLetterType.required_fields.length > 0"
                                        class="space-y-2.5 pt-2"
                                    >
                                        <div v-for="f in selectedLetterType.required_fields" :key="f">
                                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                                {{ formatFieldLabel(f) }}
                                            </label>
                                            <input
                                                v-model="form.extra_data[f]"
                                                type="text"
                                                class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between pt-2">
                                    <button
                                        type="button"
                                        @click="activeStep = ((selectedLetterType.code === 'pembukaan_periode' || selectedLetterType.code === 'rekomendasi_pinjaman_bjb') ? 'type' : 'applicant')"
                                        class="px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                                    >
                                        {{ (selectedLetterType.code === 'pembukaan_periode' || selectedLetterType.code === 'rekomendasi_pinjaman_bjb') ? '← Format Surat' : '← Data Identitas' }}
                                    </button>
                                    <button
                                        type="button"
                                        @click="activeStep = 'sign'"
                                        class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-500 flex items-center gap-1.5 transition"
                                    >
                                        <span>Lanjut ke Penandatangan</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- ============================================== -->
                            <!-- STEP 4: PENANDATANGAN & TTE                    -->
                            <!-- ============================================== -->
                            <div v-show="activeStep === 'sign'" class="space-y-4">
                                <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                                    <div>
                                        <h2 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">
                                            {{ ['kesanggupan_mengajar', 'pakta_integritas'].includes(selectedLetterType.code) ? '4. Penandatangan Pernyataan (Dosen ybs)' : '4. Pejabat Penandatangan Surat' }}
                                        </h2>
                                        <p class="text-[11px] text-slate-500">
                                            {{ ['kesanggupan_mengajar', 'pakta_integritas'].includes(selectedLetterType.code) ? 'Penandatangan otomatis adalah Dosen yang bersangkutan (Yang Membuat Pernyataan)' : 'Pilih pejabat yang bertandatangan dengan segel kriptografis' }}
                                        </p>
                                    </div>
                                </div>

                                <!-- KHUSUS SURAT KESANGGUPAN MENGAJAR: TAMPILKAN KARTU DOSEN YBS (Dosen ybs + Materai 10000) -->
                                <div v-if="selectedLetterType.code === 'kesanggupan_mengajar'" class="p-4 rounded-xl bg-blue-50/50 dark:bg-blue-950/30 border border-blue-200/80 dark:border-blue-800/80 space-y-3">
                                    <div class="flex items-center gap-2 text-xs font-bold text-blue-900 dark:text-blue-200">
                                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        <span>Penandatangan: Dosen Yang Bersangkutan (Yang Membuat Pernyataan)</span>
                                    </div>
                                    <div class="p-3 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs space-y-1.5">
                                        <div class="flex items-center justify-between">
                                            <div class="text-[10px] uppercase font-bold text-slate-400">Nama Dosen Penandatangan:</div>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                                Dilengkapi Materai 10.000
                                            </span>
                                        </div>
                                        <div class="font-bold text-slate-900 dark:text-white text-sm">
                                            {{ form.student_name || 'Nama Dosen Belum Diisi' }}
                                        </div>
                                        <div class="text-[11px] text-slate-500 font-mono">
                                            NIDN: {{ form.npm || '-' }} • Program Studi {{ form.study_program || 'Sistem Informasi' }}
                                        </div>
                                    </div>
                                    <p class="text-[11px] text-slate-600 dark:text-slate-400 leading-relaxed">
                                        Surat Pernyataan Kesanggupan Mengajar ini ditandatangani langsung oleh <strong>Dosen yang bersangkutan</strong> di atas materai Rp10.000. Penandatangan pada dokumen otomatis tercantum atas nama dosen di atas tanpa memerlukan pemilihan pejabat fakultas.
                                    </p>
                                </div>

                                <!-- KHUSUS FAKTA INTEGRITAS: TAMPILKAN KARTU DOSEN YBS (TANPA PILIH PEJABAT) -->
                                <div v-else-if="selectedLetterType.code === 'pakta_integritas'" class="p-4 rounded-xl bg-blue-50/50 dark:bg-blue-950/30 border border-blue-200/80 dark:border-blue-800/80 space-y-3">
                                    <div class="flex items-center gap-2 text-xs font-bold text-blue-900 dark:text-blue-200">
                                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        <span>Penandatangan: Yang Membuat Pernyataan (Dosen Pengampu)</span>
                                    </div>
                                    <div class="p-3 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs space-y-1">
                                        <div class="text-[10px] uppercase font-bold text-slate-400">Nama Dosen Penandatangan:</div>
                                        <div class="font-bold text-slate-900 dark:text-white text-sm">
                                            {{ form.student_name || 'Nama Dosen Belum Diisi' }}
                                        </div>
                                        <div class="text-[11px] text-slate-500 font-mono">
                                            NIDN: {{ form.npm || '-' }} • Program Studi {{ form.study_program || 'Sistem Informasi' }}
                                        </div>
                                    </div>
                                    <p class="text-[11px] text-slate-600 dark:text-slate-400 leading-relaxed">
                                        Dokumen Fakta Integritas ini merupakan pernyataan individu dosen secara mandiri. Penandatangan pada dokumen otomatis tercantum atas nama dosen di atas dengan kolom tanda tangan resmi.
                                    </p>
                                </div>

                                <!-- KHUSUS PENGAJUAN DANA FAKULTAS: DUAL SIGNATURE (DEKAN & BENDAHARA) -->
                                <div v-else-if="selectedLetterType.code === 'pengajuan_dana'" class="p-4 rounded-xl bg-blue-50/50 dark:bg-blue-950/30 border border-blue-200/80 dark:border-blue-800/80 space-y-3">
                                    <div class="flex items-center gap-2 text-xs font-bold text-blue-900 dark:text-blue-200">
                                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>Penandatangan Ganda (Dekan & Bendahara Fakultas)</span>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                        <div class="p-3 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs space-y-1">
                                            <div class="text-[10px] uppercase font-bold text-slate-400">Mengetahui:</div>
                                            <div class="font-bold text-slate-900 dark:text-white">
                                                {{ form.extra_data.dekan_name || 'Dr. Tepi Peirisal,S.Sos, M.Si' }}
                                            </div>
                                            <div class="text-[11px] text-slate-500">
                                                Dekan Fakultas Ilmu Komputer
                                            </div>
                                        </div>
                                        <div class="p-3 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs space-y-1">
                                            <div class="text-[10px] uppercase font-bold text-slate-400">Pembuat / Pengaju:</div>
                                            <div class="font-bold text-slate-900 dark:text-white">
                                                {{ form.extra_data.treasurer_name || 'Fina Widya Ramdhani, S.AN' }}
                                            </div>
                                            <div class="text-[11px] text-slate-500">
                                                Bendahara Fakultas Ilmu Komputer
                                            </div>
                                        </div>
                                    </div>
                                    <p class="text-[11px] text-slate-600 dark:text-slate-400 leading-relaxed">
                                        Surat Pengajuan Kebutuhan Dana otomatis mencantumkan tanda tangan Mengetahui Dekan dan Bendahara Fakultas di bagian penutup.
                                    </p>
                                </div>

                                <!-- KHUSUS SURAT REKOMENDASI PINJAMAN BJB: PENANDATANGAN DEKAN DENGAN FORM IDENTITAS DEKAN -->
                                <div v-else-if="selectedLetterType.code === 'rekomendasi_pinjaman_bjb'" class="p-4 rounded-xl bg-blue-50/50 dark:bg-blue-950/30 border border-blue-200/80 dark:border-blue-800/80 space-y-3">
                                    <div class="flex items-center gap-2 text-xs font-bold text-blue-900 dark:text-blue-200">
                                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        <span>Penandatangan Surat: Dekan Fakultas Ilmu Komputer</span>
                                    </div>
                                    <div class="p-3 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-2 text-xs">
                                        <div class="text-[10px] uppercase font-bold text-slate-400">Identitas Pejabat Penandatangan (Dekan):</div>
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                                            <div>
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Nama Dekan</label>
                                                <input
                                                    v-model="form.extra_data.dekan_name"
                                                    type="text"
                                                    placeholder="Dr. Tepi Peirisal, S.Sos, M.Si"
                                                    class="w-full text-xs p-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 font-bold"
                                                    @input="triggerDebouncedSync"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">NIK Dekan</label>
                                                <input
                                                    v-model="form.extra_data.dekan_nik"
                                                    type="text"
                                                    placeholder="0410420142"
                                                    class="w-full text-xs p-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 font-mono"
                                                    @input="triggerDebouncedSync"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Jabatan Dekan</label>
                                                <input
                                                    v-model="form.extra_data.dekan_position"
                                                    type="text"
                                                    placeholder="Dekan Fakultas Ilmu Komputer"
                                                    class="w-full text-xs p-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 font-medium"
                                                    @input="triggerDebouncedSync"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    <p class="text-[11px] text-slate-600 dark:text-slate-400 leading-relaxed">
                                        Surat Rekomendasi Pinjaman ditandatangani resmi oleh Dekan Fakultas Ilmu Komputer. Data di atas otomatis tercetak pada bagian pertama surat <em>"Yang bertandatangan dibawah ini"</em> dan kolom penandatanganan.
                                    </p>
                                </div>

                                <!-- DEFAULT: PILIH PEJABAT PENANDATANGAN UNTUK SURAT RESMI LAINNYA -->
                                <div v-else class="space-y-4">
                                    <!-- Signer Radio Options Grid -->
                                    <div class="space-y-2">
                                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                            Pilih Pejabat Penandatangan:
                                        </label>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                            <label
                                                v-for="signer in signers"
                                                :key="signer.id"
                                                :class="[
                                                    'p-3 rounded-xl border cursor-pointer transition flex items-start gap-2.5',
                                                    form.signer_id === signer.id
                                                        ? 'border-blue-600 bg-blue-50/40 dark:bg-blue-950/40 ring-1 ring-blue-600'
                                                        : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-slate-300'
                                                ]"
                                            >
                                                <input
                                                    type="radio"
                                                    :value="signer.id"
                                                    v-model="form.signer_id"
                                                    @change="fetchLivePreview"
                                                    class="mt-0.5 text-blue-600 focus:ring-blue-500"
                                                />
                                                <div class="min-w-0">
                                                    <div class="text-xs font-bold text-slate-900 dark:text-white truncate">
                                                        {{ signer.name }}
                                                    </div>
                                                    <div class="text-[10px] text-slate-500 dark:text-slate-400">
                                                        {{ signer.position || signer.title || signer.code }}
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between pt-2">
                                    <button
                                        type="button"
                                        @click="activeStep = 'details'"
                                        class="px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                                    >
                                        ← Rincian Surat
                                    </button>
                                    <button
                                        type="button"
                                        @click="activeStep = 'narration'"
                                        class="px-4 py-2 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-200 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-300 dark:border-slate-600 transition"
                                    >
                                        <span>Kustomisasi Narasi (Opsional) →</span>
                                    </button>
                                </div>
                            </div>

                            <!-- ============================================== -->
                            <!-- STEP 5: KUSTOMISASI REDAKSI NARASI (OPSIONAL)  -->
                            <!-- ============================================== -->
                            <div v-show="activeStep === 'narration'" class="space-y-4">
                                <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                                    <div>
                                        <h2 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">
                                            {{ (selectedLetterType.code === 'pembukaan_periode' || selectedLetterType.code === 'rekomendasi_pinjaman_bjb') ? '4. Penyesuaian Redaksi Narasi (Opsional)' : '5. Penyesuaian Redaksi Narasi (Opsional)' }}
                                        </h2>
                                        <p class="text-[11px] text-slate-500">Kosongkan kolom ini jika ingin menggunakan redaksi baku standar Fasilkom</p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="
                                            if (selectedLetterType.code === 'pakta_integritas') {
                                                form.opening_template = defaultPaktaNarration.opening;
                                                form.content_template = defaultPaktaNarration.content;
                                                form.closing_template = defaultPaktaNarration.closing;
                                            } else if (selectedLetterType.code === 'pengajuan_dana') {
                                                form.opening_template = defaultPengajuanDanaNarration.opening;
                                                form.content_template = defaultPengajuanDanaNarration.content;
                                                form.closing_template = defaultPengajuanDanaNarration.closing;
                                            } else if (selectedLetterType.code === 'pengadaan_sarpras') {
                                                form.opening_template = defaultPengadaanSarprasNarration.opening;
                                                form.content_template = defaultPengadaanSarprasNarration.content;
                                                form.closing_template = defaultPengadaanSarprasNarration.closing;
                                            } else if (selectedLetterType.code === 'perbaikan_sarpras') {
                                                form.opening_template = defaultPerbaikanSarprasNarration.opening;
                                                form.content_template = defaultPerbaikanSarprasNarration.content;
                                                form.closing_template = defaultPerbaikanSarprasNarration.closing;
                                            } else if (selectedLetterType.code === 'pemberitahuan_ujian_dosen') {
                                                form.opening_template = defaultPemberitahuanUjianDosenNarration.opening;
                                                form.content_template = defaultPemberitahuanUjianDosenNarration.content;
                                                form.closing_template = defaultPemberitahuanUjianDosenNarration.closing;
                                            } else if (selectedLetterType.code === 'pengumuman_ujian_mahasiswa') {
                                                form.opening_template = defaultPengumumanUjianMahasiswaNarration.opening;
                                                form.content_template = defaultPengumumanUjianMahasiswaNarration.content;
                                                form.closing_template = defaultPengumumanUjianMahasiswaNarration.closing;
                                            } else if (selectedLetterType.code === 'perbaikan_data_pddikti') {
                                                form.opening_template = defaultPerbaikanDataPddiktiNarration.opening;
                                                form.content_template = defaultPerbaikanDataPddiktiNarration.content;
                                                form.closing_template = defaultPerbaikanDataPddiktiNarration.closing;
                                            } else if (selectedLetterType.code === 'pengajuan_ttd_ijazah') {
                                                form.opening_template = defaultPengajuanTtdIjazahNarration.opening;
                                                form.content_template = defaultPengajuanTtdIjazahNarration.content;
                                                form.closing_template = defaultPengajuanTtdIjazahNarration.closing;
                                            } else if (selectedLetterType.code === 'pengajuan_blanko_ijazah_transkrip') {
                                                form.opening_template = defaultPengajuanBlankoNarration.opening;
                                                form.content_template = defaultPengajuanBlankoNarration.content;
                                                form.closing_template = defaultPengajuanBlankoNarration.closing;
                                            } else if (selectedLetterType.code === 'rekomendasi_pinjaman_bjb') {
                                                form.opening_template = defaultRekomendasiPinjamanBjbNarration.opening;
                                                form.content_template = defaultRekomendasiPinjamanBjbNarration.content;
                                                form.closing_template = defaultRekomendasiPinjamanBjbNarration.closing;
                                            } else if (selectedLetterType.code === 'rekomendasi_luar_prodi') {
                                                form.opening_template = defaultRekomendasiLuarProdiNarration.opening;
                                                form.content_template = defaultRekomendasiLuarProdiNarration.content;
                                                form.closing_template = defaultRekomendasiLuarProdiNarration.closing;
                                            } else if (selectedLetterType.code === 'keterangan_yudisium') {
                                                form.opening_template = defaultKeteranganLulusNarration.opening;
                                                form.content_template = defaultKeteranganLulusNarration.content;
                                                form.closing_template = defaultKeteranganLulusNarration.closing;
                                            } else {
                                                form.opening_template = selectedLetterType.opening_template || '';
                                                form.content_template = selectedLetterType.content_template || '';
                                                form.closing_template = selectedLetterType.closing_template || '';
                                            }
                                            triggerDebouncedSync();
                                        "
                                        class="text-[11px] font-semibold text-rose-500 hover:underline"
                                    >
                                        Reset ke Baku
                                    </button>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                        {{
                                            selectedLetterType.code === 'pakta_integritas'
                                                ? 'Paragraf Pembuka (Sebelum Data Dosen)'
                                                : (selectedLetterType.code === 'pengajuan_dana'
                                                    ? 'Kalimat Pengantar Kebutuhan Dana (Sebelum Rincian Angka)'
                                                    : (selectedLetterType.code === 'pengadaan_sarpras'
                                                        ? 'Paragraf Pembuka Pengadaan (Sebelum Daftar Item)'
                                                        : (selectedLetterType.code === 'perbaikan_sarpras'
                                                            ? 'Paragraf Pembuka Permohonan Pergantian Sarpras'
                                                            : (selectedLetterType.code === 'pemberitahuan_ujian_dosen'
                                                                ? 'Paragraf Pembuka Pemberitahuan Ujian (Sebelum Butir Ketentuan)'
                                                                : (selectedLetterType.code === 'pengumuman_ujian_mahasiswa'
                                                                    ? 'Paragraf Pembuka Pengumuman Ujian (Sebelum Butir Ketentuan)'
                                                                    : (selectedLetterType.code === 'perbaikan_data_pddikti'
                                                                        ? 'Paragraf Pembuka Permohonan Perubahan Data (Sebelum Tabel)'
                                                                        : (selectedLetterType.code === 'pengajuan_ttd_ijazah' || selectedLetterType.code === 'pengajuan_blanko_ijazah_transkrip'
                                                                            ? 'Paragraf Pembuka Pelaksanaan Sidang (Sebelum Tabel)'
                                                                            : (selectedLetterType.code === 'rekomendasi_pinjaman_bjb'
                                                                                ? 'Kalimat Pengantar Pihak Pemberi Rekomendasi'
                                                                                : (selectedLetterType.code === 'rekomendasi_luar_prodi'
                                                                                    ? 'Kalimat Pengantar Pihak Pemberi Rekomendasi (Sebelum Data Pejabat)'
                                                                                    : (selectedLetterType.code === 'keterangan_yudisium'
                                                                                        ? 'Kalimat Pengantar Penandatangan (Sebelum Data Mahasiswa)'
                                                                                        : 'Paragraf Pembuka Surat'))))))))))
                                        }}
                                    </label>
                                    <textarea
                                        v-model="form.opening_template"
                                        rows="2"
                                        :placeholder="
                                            selectedLetterType.code === 'pakta_integritas'
                                                ? 'Saya yang bertanda tangan di bawah ini:'
                                                : (selectedLetterType.code === 'pengajuan_dana'
                                                    ? 'Bersama ini kami sampaikan dengan hormat kebutuhan dana bulan November 2026 sebesar :'
                                                    : (selectedLetterType.code === 'pengadaan_sarpras'
                                                        ? 'Sehubungan dengan meningkatnya aktivitas dan kebutuhan perangkat kerja...'
                                                        : (selectedLetterType.code === 'perbaikan_sarpras'
                                                            ? 'Bersama ini kami sampaikan surat permohonan pergantian Sarana dan Prasarana...'
                                                            : (selectedLetterType.code === 'pemberitahuan_ujian_dosen'
                                                                ? 'Diberitahukan sesuai dengan kalender Akademik Fakultas Ilmu Komputer, Bahwa pelaksanaan Ujian Tengah Semester Ganjil TA. 2026/2027...'
                                                                : (selectedLetterType.code === 'pengumuman_ujian_mahasiswa'
                                                                    ? 'Diberitahukan sesuai dengan surat edaran dari Wakil Rektor I Bidang Akademik dan kemahasiswaan, Bahwa pelaksanaan Ujian Tengah Semester...'
                                                                    : (selectedLetterType.code === 'perbaikan_data_pddikti'
                                                                        ? 'Sehubungan dengan kesalahan pada data mahasiswa di bawah ini, dengan ini kami mengajukan perubahan data mahasiswa sesuai dengan tabel berikut ini:'
                                                                        : (selectedLetterType.code === 'pengajuan_ttd_ijazah' || selectedLetterType.code === 'pengajuan_blanko_ijazah_transkrip'
                                                                            ? 'Bersama surat ini kami sampaikan bahwa Fakultas Ilmu Komputer Universitas Subang telah melaksanakan sidang komprehensif pada:'
                                                                            : (selectedLetterType.code === 'rekomendasi_pinjaman_bjb'
                                                                                ? 'Yang bertandatangan dibawah ini :'
                                                                                : (selectedLetterType.code === 'rekomendasi_luar_prodi'
                                                                                    ? 'Yang bertanda tangan di bawah ini:'
                                                                                    : (selectedLetterType.code === 'keterangan_yudisium'
                                                                                        ? '{pejabat_jabatan}, menerangkan bahwa :'
                                                                                        : 'Yang bertanda tangan di bawah ini...'))))))))))
                                        "
                                        class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                                    ></textarea>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                        {{
                                            selectedLetterType.code === 'pakta_integritas'
                                                ? 'Kalimat Pengantar Butir Komitmen'
                                                : (selectedLetterType.code === 'pengadaan_sarpras'
                                                    ? 'Paragraf Harapan / Dukungan Operasional'
                                                    : (selectedLetterType.code === 'perbaikan_sarpras'
                                                        ? 'Kalimat Pengantar Daftar Barang (Sebelum Daftar Item)'
                                                        : (selectedLetterType.code === 'perbaikan_data_pddikti'
                                                            ? 'Kalimat Penegasan Bukti Fakta Dokumen (Setelah Tabel)'
                                                            : (selectedLetterType.code === 'pengajuan_ttd_ijazah'
                                                                ? 'Paragraf Pengajuan Penandatanganan Ijazah (Setelah Tabel)'
                                                                : (selectedLetterType.code === 'pengajuan_blanko_ijazah_transkrip'
                                                                    ? 'Paragraf Permohonan Blanko Ijazah & Transkrip (Setelah Tabel)'
                                                                    : (selectedLetterType.code === 'rekomendasi_pinjaman_bjb'
                                                                        ? 'Paragraf Pertimbangan Kredit & Potong Gaji'
                                                                        : (selectedLetterType.code === 'rekomendasi_luar_prodi'
                                                                            ? 'Paragraf Rekomendasi Partisipasi & Butir Komitmen Dukungan'
                                                                            : (selectedLetterType.code === 'keterangan_yudisium'
                                                                                ? 'Paragraf Penetapan Kelulusan Berdasarkan SK Dekan'
                                                                                : 'Paragraf Isi / Rekomendasi Inti'))))))))
                                        }}
                                    </label>
                                    <textarea
                                        v-model="form.content_template"
                                        :rows="(selectedLetterType.code === 'rekomendasi_pinjaman_bjb' || selectedLetterType.code === 'rekomendasi_luar_prodi' || selectedLetterType.code === 'keterangan_yudisium') ? 5 : 2"
                                        :placeholder="
                                            selectedLetterType.code === 'pakta_integritas'
                                                ? 'Menyatakan bahwa saya akan melaksanakan ketentuan sebagai berikut:'
                                                : (selectedLetterType.code === 'pengadaan_sarpras'
                                                    ? 'Kami berharap permohonan ini dapat dipenuhi guna memenuhi kelancaran operasional harian dan pelayanan akademik bagi mahasiswa.'
                                                    : (selectedLetterType.code === 'perbaikan_sarpras'
                                                        ? 'Adapun sarana dan prasarana yang kami mohonkan yaitu :'
                                                        : (selectedLetterType.code === 'perbaikan_data_pddikti'
                                                            ? 'Data yang diubah sesuai dengan fakta dan dibuktikan dengan dokumen terlampir.'
                                                            : (selectedLetterType.code === 'pengajuan_ttd_ijazah'
                                                                ? 'bersamaan dengan ini kami mengajukan penandatanganan ijazah untuk mahasiswa tersebut diatas, data suplemen pengajuan terlampir.'
                                                                : (selectedLetterType.code === 'pengajuan_blanko_ijazah_transkrip'
                                                                    ? 'Sehubungan dengan hal tersebut, kami mengajukan permohonan blanko ijazah dan blanko transkrip pendamping ijazah untuk mahasiswa sebagaimana data terlampir.'
                                                                    : (selectedLetterType.code === 'rekomendasi_pinjaman_bjb'
                                                                        ? 'Adapun sebagai staf Fakultas Ilmu Komputer Universitas Subang, dan oleh karenanya dapat dipertimbangkan untuk diberikan kredit dari Bank BJB untuk pengajuan pinjaman.\n\nUntuk kelancaran pengambilan kredit tersebut, maka kami mendukung/tidak berkeberatan untuk dipotong langsung dari gaji yang bersangkutan oleh Bank BJB.'
                                                                        : (selectedLetterType.code === 'rekomendasi_luar_prodi'
                                                                            ? 'Untuk menjadi peserta di {program_name} pada {activity_period} dengan pertimbangan bahwa mahasiswa tersebut sanggup mengikuti {program_name} secara penuh dan bertanggung jawab hingga akhir program selesai.\n\nSebagai bentuk dukungan, kami selaku pihak perwakilan kampus menyatakan kesediaan untuk hal berikut:\n1. Memberikan dukungan dan bertanggung jawab atas mahasiswa selama periode pelaksanaan {program_name}.\n2. Memberikan pengakuan keikutsertaan siswa/mahasiswa di {program_name} melalui konversi SKS atau hal lainnya yang dapat diakui dan menunjang kegiatan akademik di kampus.\n3. Mampu menjadi jembatan untuk berkoordinasi antara Tim {program_name} dan mahasiswa apabila terjadi masalah/kendala di kemudian hari.'
                                                                            : (selectedLetterType.code === 'keterangan_yudisium'
                                                                                ? 'Berdasarkan Surat Keputusan Dekan Tentang Kelulusan Ujian Sidang Sarjana Semester {semester_tipe} Tahun Akademik {tahun_akademik} Program Studi {program_studi} Fakultas Ilmu Komputer Universitas Subang, dengan Nomor : {sk_dekan_nomor}, tanggal {sk_dekan_tanggal}, kami menyatakan :\n\nSehingga yang bersangkutan berhak memakai gelar {gelar_akademik}. Adapun Ijazah dan Transkrip Nilai akan kami proses lebih lanjut.'
                                                                                : 'Menerangkan bahwa mahasiswa tersebut di atas adalah benar...'))))))))
                                        "
                                        class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                                    ></textarea>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                        {{
                                            selectedLetterType.code === 'pakta_integritas'
                                                ? 'Paragraf Penutup & Kesediaan Sanksi'
                                                : (selectedLetterType.code === 'pengajuan_dana'
                                                    ? 'Paragraf Penutup Surat Permohonan Dana (Setelah Terbilang)'
                                                    : (selectedLetterType.code === 'perbaikan_sarpras'
                                                        ? 'Paragraf Penutup Surat Permohonan Pergantian'
                                                        : (selectedLetterType.code === 'pemberitahuan_ujian_dosen'
                                                            ? 'Paragraf Penutup Pemberitahuan Ujian'
                                                            : (selectedLetterType.code === 'pengumuman_ujian_mahasiswa'
                                                                ? 'Paragraf Penutup Pengumuman Ujian'
                                                                : (selectedLetterType.code === 'perbaikan_data_pddikti'
                                                                    ? 'Paragraf Penutup Surat Permohonan Perubahan Data'
                                                                    : (selectedLetterType.code === 'pengajuan_ttd_ijazah' || selectedLetterType.code === 'pengajuan_blanko_ijazah_transkrip'
                                                                        ? 'Paragraf Penutup Surat Pengajuan'
                                                                        : (selectedLetterType.code === 'rekomendasi_pinjaman_bjb' || selectedLetterType.code === 'rekomendasi_luar_prodi'
                                                                            ? 'Paragraf Penutup Surat Rekomendasi'
                                                                            : (selectedLetterType.code === 'keterangan_yudisium'
                                                                                ? 'Paragraf Penutup Surat Keterangan Lulus'
                                                                                : 'Paragraf Penutup Surat'))))))))
                                        }}
                                    </label>
                                    <textarea
                                        v-model="form.closing_template"
                                        rows="2"
                                        :placeholder="
                                            selectedLetterType.code === 'pakta_integritas'
                                                ? 'Demikian pernyataan ini saya buat, apabila di kemudian hari saya tidak memenuhi ketentuan di atas, saya bersedia menerima sanksi baik berupa teguran lisan, teguran tertulis, serta tidak diberi kesempatan mengampu mata kuliah pada semester berikutnya.'
                                                : (selectedLetterType.code === 'pengajuan_dana'
                                                    ? 'Demikian surat permohonan pengajuan kebutuhan dana ini kami sampaikan, atas perhatian dan kebijaksanaan Bapak Rektor kami ucapkan terima kasih.'
                                                    : (selectedLetterType.code === 'perbaikan_sarpras'
                                                        ? 'Demikian surat permohonan ini kami sampaikan, atas perhatiannya kami ucapkan terimakasih.'
                                                        : (selectedLetterType.code === 'pemberitahuan_ujian_dosen'
                                                            ? 'Demikian agar maklum, atas perhatiannya kami ucapkan terimakasih.'
                                                            : (selectedLetterType.code === 'pengumuman_ujian_mahasiswa'
                                                                ? 'Demikian agar maklum, atas perhatiannya kami ucapkan terimakasih.'
                                                                : (selectedLetterType.code === 'perbaikan_data_pddikti'
                                                                    ? 'Demikian surat ini kami buat untuk dapat ditindaklanjuti. Atas perhatiannya kami ucapkan terimakasih.'
                                                                    : (selectedLetterType.code === 'pengajuan_ttd_ijazah'
                                                                        ? 'Demikian surat pengajuan ini dibuat untuk dapat dipergunakan sebagaimana mestinya.'
                                                                        : (selectedLetterType.code === 'pengajuan_blanko_ijazah_transkrip'
                                                                            ? 'Demikian surat pengajuan ini kami sampaikan untuk dapat dipergunakan sebagaimana mestinya. Atas perhatian dan kerja samanya kami ucapkan terima kasih.'
                                                                            : (selectedLetterType.code === 'rekomendasi_pinjaman_bjb'
                                                                                ? 'Demikian surat rekomendasi ini di buat, untuk dipergunakan sebagaimana mestinya.'
                                                                                : (selectedLetterType.code === 'rekomendasi_luar_prodi'
                                                                                    ? 'Demikian surat rekomendasi ini kami sampaikan untuk dipergunakan sebagaimana mestinya.'
                                                                                    : (selectedLetterType.code === 'keterangan_yudisium'
                                                                                        ? 'Demikian keterangan ini kami buat, untuk dipergunakan sebagaimana mestinya.'
                                                                                        : 'Demikian surat keterangan ini dibuat dengan sebenarnya...'))))))))))
                                        "
                                        class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                                    ></textarea>
                                </div>

                                <div class="flex items-center justify-between pt-2">
                                    <button
                                        type="button"
                                        @click="activeStep = 'sign'"
                                        class="px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                                    >
                                        ← Penandatangan
                                    </button>
                                    <button
                                        type="button"
                                        @click="submitLetter(false)"
                                        :disabled="form.processing"
                                        class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-500 shadow-md shadow-blue-950/20 transition"
                                    >
                                        Simpan Surat
                                    </button>
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
                                        Pratinjau Live Dokumen Surat
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
