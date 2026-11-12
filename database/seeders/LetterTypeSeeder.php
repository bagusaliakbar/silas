<?php

namespace Database\Seeders;

use App\Models\LetterRequest;
use App\Models\LetterType;
use App\Models\User;
use App\Services\AcademicLetterService;
use App\Services\CertificateService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LetterTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'code' => 'keterangan_kuliah_non_pns',
                'name' => 'Surat Keterangan Masih Kuliah Non PNS',
                'description' => 'Surat keterangan aktif kuliah untuk keperluan umum, beasiswa, atau instansi swasta.',
                'letter_code' => 'PD.1.2/FIK-US',
                'opening_template' => '{pejabat_jabatan} Fakultas Ilmu Komputer Universitas Subang, menerangkan bahwa :',
                'content_template' => 'Nama tersebut di atas adalah benar tercatat sebagai Mahasiswa aktif Fakultas Ilmu Komputer Universitas Subang Program Studi {program_studi} Jenjang Sarjana (S1) Semester {semester} ({semester_huruf}) pada Tahun Akademik {tahun_akademik}. Surat keterangan ini diterbitkan untuk keperluan {keperluan}.',
                'closing_template' => 'Demikian surat keterangan ini dibuat dengan sebenarnya atas permintaan yang bersangkutan untuk dipergunakan sebagaimana mestinya.',
                'required_fields' => [],
                'order' => 1,
            ],
            [
                'code' => 'keterangan_kuliah_pns',
                'name' => 'Surat Keterangan Masih Kuliah PNS',
                'description' => 'Surat keterangan aktif kuliah khusus untuk lampiran tunjangan gaji orang tua PNS/TNI/POLRI.',
                'letter_code' => 'PD.1.2/FIK-US',
                'opening_template' => 'Yang bertanda tangan dibawah ini :',
                'content_template' => 'Nama tersebut tercatat sebagai mahasiswa Fakultas Ilmu Komputer Universitas Subang Program Studi {program_studi} Jenjang Sarjana (S1) Semester {semester_romawi} ({semester_huruf}) pada Tahun Akademik {tahun_akademik}.',
                'closing_template' => 'Demikian keterangan ini dibuat atas permintaan yang bersangkutan untuk dipergunakan seperlunya.',
                'required_fields' => ['parent_name', 'parent_nip', 'parent_rank', 'parent_institution'],
                'order' => 2,
            ],
            [
                'code' => 'pengantar_magang',
                'name' => 'Surat Pengantar Magang',
                'description' => 'Surat permohonan izin pelaksanaan Magang MBKM mahasiswa ke instansi / perusahaan dilengkapi lembar lampiran daftar mahasiswa.',
                'letter_code' => 'PD.1.2/FIK-US',
                'opening_template' => "Dengan Hormat,\n\nSehubungan dengan adanya Kegiatan Magang MBKM pada semester ganjil TA {tahun_akademik}. Fakultas Ilmu Komputer yang bertujuan untuk mendorong Mahasiswa untuk mampu menguasai kompetensi berbagai keilmuan sebelum memasuki dunia kerja.",
                'content_template' => 'Sehubungan dengan hal itu kami mohon kiranya Bapak/Ibu dapat memberikan bantuan kepada mahasiswa dan mengijinkan pegawai Bapak/Ibu memberikan pendampingan untuk pelaksanaan Magang MBKM selama {durasi_magang} mulai dari tanggal {tanggal_mulai_magang} s/d {tanggal_selesai_magang} di **{tujuan_instansi}**. Terlampir daftar mahasiswa yang mengikuti program Magang MBKM di **{tujuan_instansi}**.',
                'closing_template' => 'Demikian surat permohonan ini kami sampaikan. Atas kesedianaan dan kerjasamanya Bapak/Ibu, kami ucapkan terima kasih.',
                'required_fields' => ['target_recipient', 'target_agency', 'target_city', 'internship_duration', 'start_date', 'end_date', 'group_members'],
                'order' => 3,
            ],
            [
                'code' => 'pengantar_pkl',
                'name' => 'Surat Pengantar Izin PKL',
                'description' => 'Surat permohonan pelaksanaan Praktik Kerja Lapangan (PKL) mahasiswa.',
                'letter_code' => 'PD.1.2/FIK-US',
                'opening_template' => "Dengan hormat,\n\n{pejabat_jabatan}, menerangkan bahwa :",
                'content_template' => "Bermaksud untuk melaksanakan Praktek Kerja Lapangan di instansi/Perusahaan yang Bapak/Ibu pimpin selama {durasi_pkl} dimulai tanggal {tanggal_mulai_pkl} – {tanggal_selesai_pkl} pada **{tujuan_instansi}**.\n\nSehubungan dengan hal itu kami mohon kiranya Bapak/Ibu dapat memberikan izin dan memberikan bantuan kepada mahasiswa tersebut di atas,serta menugaskan pegawai Bapak/Ibu untuk menjadi pembimbing.",
                'closing_template' => 'Demikian atas perhatian dan kerjasamanya kami ucapkan terimakasih.',
                'required_fields' => ['target_recipient', 'target_agency', 'target_city', 'internship_duration', 'start_date', 'end_date', 'group_members'],
                'order' => 4,
            ],
            [
                'code' => 'observasi_matkul',
                'name' => 'Surat Pengantar Observasi Tugas Mata Kuliah',
                'description' => 'Surat izin observasi lapangan / wawancara untuk pemenuhan tugas mata kuliah (mandiri atau berkelompok).',
                'letter_code' => 'PD.1.2/FIK-US',
                'opening_template' => '{pejabat_jabatan} Fakultas Ilmu Komputer Universitas Subang, menerangkan bahwa :',
                'content_template' => "Bermaksud akan mengadakan observasi Matakuliah {mata_kuliah} untuk penulisan tugas matakuliah yang diikuti oleh mahasiswa tersebut.\n\nSehubungan dengan hal itu kami mohon kiranya Bapak/Ibu dapat memberikan bantuan kepada mahasiswa tersebut diatas, dan mengijinkan pegawai Bapak/Ibu memberikan keterangan/penjelasan untuk penyelesaian tugas matakuliah yang bersangkutan.",
                'closing_template' => 'Demikian permohonan ini kami sampaikan. Atas bantuan dan kesediaannya kami ucapkan terima kasih.',
                'required_fields' => ['target_recipient', 'course_name', 'target_city', 'group_members'],
                'order' => 5,
            ],
            [
                'code' => 'izin_penelitian_skripsi',
                'name' => 'Surat Pengantar Izin Penelitian Skripsi',
                'description' => 'Surat izin pengambilan data / penelitian skripsi mahasiswa di objek penelitian.',
                'letter_code' => 'PD.1.2/FIK-US',
                'opening_template' => 'Bersama ini kami {pejabat_jabatan}, dengan ini memberi pengantar kepada mahasiswa :',
                'content_template' => "Bermaksud akan mengadakan penelitian pada **{tujuan_instansi}** untuk penulisan Skripsi yang diikuti oleh mahasiswa tersebut.\n\nSehubungan dengan hal itu kami mohon kiranya Bapak/Ibu dapat memberikan bantuan kepada mahasiswa tersebut diatas, dan mengijinkan pegawai Bapak/Ibu memberikan keterangan/penjelasan untuk penyelesaian Skripsi yang bersangkutan.",
                'closing_template' => 'Atas bantuan dan kerjasamanya kami ucapkan terima kasih.',
                'required_fields' => ['target_recipient', 'target_agency', 'target_city', 'group_members'],
                'order' => 6,
            ],
            [
                'code' => 'ujian_susulan',
                'name' => 'Surat Pengantar Ujian Susulan (UTS/UAS)',
                'description' => 'Surat pengantar permohonan ujian susulan (UTS / UAS) kepada dosen pengampu mata kuliah.',
                'letter_code' => 'PD.1.1/FIK-US',
                'opening_template' => 'Bersama ini kami hadapkan Mahasiswa Fakultas Ilmu Komputer Universitas Subang Tahun Akademik {tahun_akademik} :',
                'content_template' => 'Dengan ini kami mohon kesediaan Bapak / Ibu memberikan kesempatan untuk {jenis_ujian} susulan. Penilaian diberikan dengan angka mutu sesuai dengan komponen Nilai yang tidak lengkap.',
                'closing_template' => 'Demikian atas kesediaanya kami ucapkan terima kasih',
                'required_fields' => ['exam_type', 'lecturer_name', 'course_name', 'original_score'],
                'order' => 7,
            ],
            [
                'code' => 'izin_mengikuti_ujian',
                'name' => 'Surat Izin Mengikuti Ujian (UTS/UAS)',
                'description' => 'Surat permohonan izin dispensasi mengikuti ujian (UTS / UAS) ke instansi/perusahaan tempat bekerja mahasiswa dilengkapi lampiran jadwal ujian.',
                'letter_code' => 'PD.1.2/FIK-US',
                'opening_template' => 'Berdasarkan Kalender Akademik Universitas Subang bahwa akan dilaksanakannya {nama_ujian_lengkap} {semester_tipe} Tahun Akademik {tahun_akademik} pada tanggal {tanggal_ujian}, kami yang bertanda tangan di bawah ini, {pejabat_jabatan}, menerangkan bahwa :',
                'content_template' => 'Sehubungan dengan hal itu kami mohon kiranya Bapak/Ibu dapat memberikan **Izin Mengikuti {nama_ujian_lengkap}** kepada mahasiswa yang bersangkutan dengan jadwal terlampir.',
                'closing_template' => 'Atas bantuan dan kerjasamanya kami ucapkan terima kasih.',
                'required_fields' => ['target_recipient', 'target_city', 'exam_type', 'semester_type', 'exam_dates', 'exam_schedules'],
                'order' => 8,
            ],
            [
                'code' => 'keterangan_lainnya',
                'name' => 'Surat Keterangan Akademik Lainnya',
                'description' => 'Permohonan surat keterangan resmi lainnya dari Program Studi Sistem Informasi.',
                'letter_code' => 'UM.1.1/FIK-US',
                'opening_template' => '{pejabat_jabatan} Fakultas Ilmu Komputer Universitas Subang, dengan ini menerangkan bahwa :',
                'content_template' => 'Mahasiswa tersebut di atas tercatat aktif pada Program Studi {program_studi} Fakultas Ilmu Komputer Universitas Subang Semester {semester} Tahun Akademik {tahun_akademik}, dan surat ini diterbitkan untuk keperluan {keperluan}.',
                'closing_template' => 'Demikian surat keterangan ini kami sampaikan untuk dapat dipergunakan seperlunya.',
                'required_fields' => ['custom_subject', 'purpose'],
                'order' => 9,
            ],
            [
                'code' => 'tugas_mengajar',
                'name' => 'Surat Tugas Mengajar',
                'description' => 'Surat tugas mengajar bagi dosen pengampu mata kuliah pada semester ganjil / genap.',
                'letter_code' => 'PD.1.2/FIK-US',
                'opening_template' => 'Yang bertanda tangan di bawah ini {pejabat_jabatan} dengan ini menugaskan :',
                'content_template' => 'Demikian Surat Tugas ini agar dilaksanakan dengan berpedoman pada Kurikulum dan Silabus Jurusan serta Satuan Acara Perkuliahan (SAP) yang dibuat oleh masing-masing Dosen.',
                'closing_template' => '',
                'required_fields' => ['lecturer_status', 'nidn', 'semester_type', 'teaching_schedules'],
                'order' => 10,
            ],
            [
                'code' => 'izin_seminar_up',
                'name' => 'Surat Keterangan Mengikuti Seminar Usulan Penelitian',
                'description' => 'Surat permohonan izin dan dispensasi mahasiswa untuk mengikuti Seminar Usulan Penelitian tatap muka / offline.',
                'letter_code' => 'PD.1.2/FIK-US',
                'opening_template' => 'Berdasarkan jadwal Seminar Usulan Penelitian yang dilaksanakan secara offline (Tatap muka) Mahasiswa bertepatan dengan kegiatan Seminar Usulan Penelitian yang sedang berjalan di Fakultas Ilmu Komputer, maka dengan ini kami menerangkan bahwa:',
                'content_template' => 'Sehubungan dengan hal itu kami mohon kiranya Bapak/Ibu dapat memberikan izin kepada mahasiswa yang bersangkutan untuk **Mengikuti Seminar Usulan Penelitian**.',
                'closing_template' => 'Demikian surat ini dibuat, atas bantuan dan kerjasamanya kami ucapkan terima kasih.',
                'required_fields' => ['target_recipient', 'target_city', 'activity_name', 'event_day', 'group_members'],
                'order' => 11,
            ],
            [
                'code' => 'tugas_pembimbing_mbkm',
                'name' => 'Surat Tugas Pembimbing MBKM',
                'description' => 'Surat penugasan dosen sebagai pembimbing mahasiswa program MBKM (Studi Independen, Magang, Proyek Independen).',
                'letter_code' => 'PD.1.2/FIK-US',
                'opening_template' => 'Yang bertanda tangan di bawah ini {pejabat_jabatan} dengan ini menugaskan :',
                'content_template' => 'Demikian Surat Tugas ini kami sampaikan atas perhatiannya kami ucapkan terimakasih.',
                'closing_template' => '',
                'required_fields' => ['lecturer_status', 'nidn', 'semester_type', 'mbkm_students'],
                'order' => 12,
            ],
            [
                'code' => 'tugas_pembimbing_ta',
                'name' => 'Surat Tugas Pembimbing Tugas Akhir',
                'description' => 'Surat penugasan dosen sebagai pembimbing Tugas Akhir / Skripsi mahasiswa.',
                'letter_code' => 'PD.1.2/FIK-US',
                'opening_template' => 'Yang bertanda tangan di bawah ini {pejabat_jabatan} dengan ini menugaskan :',
                'content_template' => 'Demikian Surat Tugas ini kami sampaikan atas perhatiannya kami ucapkan terimakasih.',
                'closing_template' => '',
                'required_fields' => ['lecturer_status', 'nidn', 'semester_type', 'ta_students'],
                'order' => 13,
            ],
            [
                'code' => 'izin_sidang_ta',
                'name' => 'Surat Keterangan Mengikuti Sidang Tugas Akhir',
                'description' => 'Surat permohonan izin dan dispensasi mahasiswa untuk mengikuti Sidang Tugas Akhir tatap muka / offline.',
                'letter_code' => 'PD.1.2/FIK-US',
                'opening_template' => 'Berdasarkan jadwal Sidang Tugas Akhir yang dilaksanakan secara offline (Tatap muka) Mahasiswa bertepatan dengan kegiatan Sidang Tugas Akhir yang sedang berjalan di Fakultas Ilmu Komputer, maka dengan ini kami menerangkan bahwa:',
                'content_template' => 'Sehubungan dengan hal itu kami mohon kiranya Bapak/Ibu dapat memberikan izin kepada mahasiswa yang bersangkutan untuk **Mengikuti Sidang Tugas Akhir**.',
                'closing_template' => 'Demikian surat ini dibuat, atas bantuan dan kerjasamanya kami ucapkan terima kasih.',
                'required_fields' => ['target_recipient', 'target_city', 'activity_name', 'event_day', 'group_members'],
                'order' => 14,
            ],
            [
                'code' => 'perbaikan_nilai',
                'name' => 'Surat Pengantar Perbaikan Nilai',
                'description' => 'Surat pengantar permohonan perbaikan nilai kepada dosen pengampu mata kuliah.',
                'letter_code' => 'PD.1.1/FIK-US',
                'opening_template' => 'Bersama ini kami hadapkan Mahasiswa Fakultas Ilmu Komputer Universitas Subang Tahun Akademik {tahun_akademik} :',
                'content_template' => 'Dengan ini kami mohon kesediaan Bapak / Ibu memberikan kesempatan untuk perbaikan Nilai. Penilaian diberikan dengan angka mutu sesuai dengan komponen Nilai yang tidak lengkap.',
                'closing_template' => 'Demikian atas kesediaanya kami ucapkan terima kasih',
                'required_fields' => ['lecturer_name', 'course_name', 'original_score'],
                'order' => 15,
            ],
            [
                'code' => 'keterangan_yudisium',
                'name' => 'Surat Keterangan Lulus',
                'description' => 'Surat Keterangan Lulus (SKL) resmi Fakultas Ilmu Komputer yang menerangkan kelulusan mahasiswa berdasarkan SK Dekan, memuat predikat yudisium, IPK, hak pemakaian gelar, dan proses ijazah/transkrip.',
                'letter_code' => 'D/FIK-US',
                'opening_template' => '{pejabat_jabatan}, menerangkan bahwa :',
                'content_template' => "Berdasarkan Surat Keputusan Dekan Tentang Kelulusan Ujian Sidang Sarjana Semester {semester_tipe} Tahun Akademik {tahun_akademik} Program Studi {program_studi} Fakultas Ilmu Komputer Universitas Subang, dengan Nomor : {sk_dekan_nomor}, tanggal {sk_dekan_tanggal}, kami menyatakan :\n\nSehingga yang bersangkutan berhak memakai gelar {gelar_akademik}. Adapun Ijazah dan Transkrip Nilai akan kami proses lebih lanjut.",
                'closing_template' => 'Demikian keterangan ini kami buat, untuk dipergunakan sebagaimana mestinya.',
                'required_fields' => ['sk_number', 'sk_date', 'degree_title', 'yudisium_predicate', 'ipk'],
                'order' => 16,
            ],
            [
                'code' => 'undangan_rapat_akademik',
                'name' => 'Surat Undangan Rapat Akademik',
                'description' => 'Surat undangan resmi rapat akademik bagi dosen di lingkungan Fakultas Ilmu Komputer.',
                'letter_code' => 'PD.1.1/FIK-US',
                'opening_template' => 'Bersama ini kami mengundang kepada Bapak/Ibu Dosen Fakultas Ilmu Komputer Universitas Subang, dalam rangka {agenda} yang akan diselenggarakan pada:',
                'content_template' => '',
                'closing_template' => 'Demikian undangan kami sampaikan, atas perhatiannya diucapkan terimakasih.',
                'required_fields' => ['meeting_day_date', 'meeting_time', 'meeting_type', 'meeting_location'],
                'order' => 17,
            ],
            [
                'code' => 'kesanggupan_mengajar',
                'name' => 'Surat Pernyataan Kesanggupan Mengajar',
                'description' => 'Surat pernyataan kesanggupan dosen menjadi pengampu mata kuliah pada semester berjalan dilengkapi tabel beban mengajar dan tanda tangan dosen yang bersangkutan.',
                'letter_code' => 'PD.1.2/FIK-US',
                'opening_template' => "Saya yang bertandatangan di bawah ini :\n\nNama : {nama_dosen}\nNIP/NIDN : {nip_nidn}\nJabatan Akademik : {jabatan_akademik}\nAlamat : {alamat}\nEmail : {email}\nKeahlian : {keahlian}",
                'content_template' => 'Menyatakan BERSEDIA DAN SANGGUP menjadi dosen pengampu pada Semester {semester_tipe} Tahun Akademik {tahun_akademik}, dengan MATA KULIAH sebagaimana tersebut dalam tabel di bawah ini.',
                'closing_template' => 'Demikian pernyataan ini saya sampaikan, untuk dapat dipergunakan sebagaimana mestinya.',
                'required_fields' => ['lecturer_id', 'teaching_courses', 'materai'],
                'order' => 18,
            ],
            [
                'code' => 'rekomendasi_pinjaman_bjb',
                'name' => 'Surat Rekomendasi Pinjaman BJB',
                'description' => 'Surat Rekomendasi Dekan kepada Dosen/Pegawai/Staf untuk pengajuan pinjaman/kredit Bank BJB dengan pemotongan gaji langsung.',
                'letter_code' => 'D/FIK-US',
                'opening_template' => "Yang bertandatangan dibawah ini :\n\nNama : {dekan_name}\nNIK : {dekan_nik}\nJabatan : {dekan_position}",
                'content_template' => "Dengan ini merekomendasikan kepada :\n\nNama : {employee_name}\nNIK : {employee_nik}\nJabatan : {employee_position}\n\nAdapun sebagai staf Fakultas Ilmu Komputer Universitas Subang, dan oleh karenanya dapat dipertimbangkan untuk diberikan kredit dari Bank BJB untuk {loan_purpose}.\n\nUntuk kelancaran pengambilan kredit tersebut, maka kami mendukung/tidak berkeberatan untuk dipotong langsung dari gaji yang bersangkutan oleh Bank BJB.",
                'closing_template' => 'Demikian surat rekomendasi ini di buat, untuk dipergunakan sebagaimana mestinya.',
                'required_fields' => ['employee_name', 'employee_nik', 'employee_position', 'loan_purpose', 'bank_name'],
                'order' => 23,
            ],
            [
                'code' => 'rekomendasi_beasiswa',
                'name' => 'Surat Rekomendasi Beasiswa',
                'description' => 'Surat Rekomendasi Ketua Program Studi / Dekan bagi mahasiswa untuk mendapatkan beasiswa dari yayasan, lembaga, atau instansi penyedia beasiswa.',
                'letter_code' => 'D/FIK-US',
                'opening_template' => "Yang bertanda tangan di bawah ini:\n\nNama : {pejabat_nama}\nJabatan : {pejabat_jabatan}\nFakultas : {pejabat_fakultas}",
                'content_template' => 'Merekomendasikan kepada mahasiswa atas nama {student_name} dengan nomor pokok mahasiswa {npm} untuk mendapatkan {scholarship_name} oleh {scholarship_provider}.',
                'closing_template' => 'Demikian surat rekomendasi ini dibuat untuk dipergunakan sebagaimana mestinya. Atas perhatiannya, kami ucapkan terima kasih',
                'required_fields' => ['scholarship_name', 'scholarship_provider'],
                'order' => 19,
            ],
            [
                'code' => 'rekomendasi_luar_prodi',
                'name' => 'Surat Rekomendasi Kegiatan Pembelajaran di Luar Program Studi',
                'description' => 'Surat Rekomendasi Fakultas / Program Studi untuk mahasiswa yang mengikuti program pembelajaran di luar prodi / MBKM / Coding Camp / Studi Independen dengan komitmen dukungan dan konversi SKS.',
                'letter_code' => 'PD.1.6/FIK-US',
                'opening_template' => 'Yang bertanda tangan di bawah ini:',
                'content_template' => "Untuk menjadi peserta di {program_name} pada {activity_period} dengan pertimbangan bahwa mahasiswa tersebut sanggup mengikuti {program_name} secara penuh dan bertanggung jawab hingga akhir program selesai.\n\nSebagai bentuk dukungan, kami selaku pihak perwakilan kampus menyatakan kesediaan untuk hal berikut:\n1. Memberikan dukungan dan bertanggung jawab atas mahasiswa selama periode pelaksanaan {program_name}.\n2. Memberikan pengakuan keikutsertaan siswa/mahasiswa di {program_name} melalui konversi SKS atau hal lainnya yang dapat diakui dan menunjang kegiatan akademik di kampus.\n3. Mampu menjadi jembatan untuk berkoordinasi antara Tim {program_name} dan mahasiswa apabila terjadi masalah/kendala di kemudian hari.",
                'closing_template' => 'Demikian surat rekomendasi ini kami sampaikan untuk dipergunakan sebagaimana mestinya.',
                'required_fields' => ['program_name', 'activity_period'],
                'order' => 24,
            ],
        ];

        foreach ($types as $type) {
            LetterType::updateOrCreate(['code' => $type['code']], $type);
        }

        $officials = [
            [
                'name' => 'Bagus Ali Akbar, S.SI., M.Kom',
                'email' => 'bagusaliakbar@unsub.ac.id',
                'title' => 'Ketua Program Studi Sistem Informasi',
            ],
            [
                'name' => 'Dr. Tepi Peirisal, M.Si',
                'email' => 'tepipeirisal@unsub.ac.id',
                'title' => 'Dekan Fakultas Ilmu Komputer',
            ],
            [
                'name' => 'Bambang Tjahjo Utomo, MT',
                'email' => 'bambangtjahjoutomo@unsub.ac.id',
                'title' => 'Wakil Dekan Fakultas Ilmu Komputer',
            ],
        ];

        foreach ($officials as $official) {
            $offUser = User::firstOrCreate(
                ['email' => $official['email']],
                [
                    'name' => $official['name'],
                    'role' => 'pejabat',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
            $offUser->update(['role' => 'pejabat']);

            if (! $offUser->digitalCertificates()->exists()) {
                app(CertificateService::class)->generateSelfSignedCertificate($offUser, [
                    'name' => 'Fasilkom UNSUB / '.$offUser->name,
                    'organization' => 'Fakultas Ilmu Komputer Universitas Subang',
                    'organizational_unit' => $official['title'],
                    'email' => $offUser->email,
                    'country' => 'ID',
                    'days_valid' => 365,
                    'passphrase' => 'secret123',
                ]);
            }
        }

        $mahasiswaUser = User::where('email', 'mahasiswa@unsub.ac.id')->first() ?? User::first();
        $sampleType = LetterType::where('code', 'keterangan_kuliah_non_pns')->first();
        if ($mahasiswaUser && $sampleType && ! LetterRequest::where('npm', 'D1A260029')->exists()) {
            $letter = LetterRequest::create([
                'user_id' => $mahasiswaUser->id,
                'letter_type_id' => $sampleType->id,
                'student_name' => 'LUNA AMELLA WARDHANI',
                'npm' => 'D1A260029',
                'study_program' => 'Sistem Informasi',
                'birth_place' => 'Wonogiri',
                'birth_date' => '2007-01-08',
                'address' => 'Mutiara Jaya Regency C 4/1 RT 041/RW 004 Desa Kondangjaya, Kecamatan Karawang Timur',
                'semester' => 1,
                'academic_year' => '2026/2027',
                'purpose' => 'Persyaratan Administrasi',
                'signer_name' => 'BAGUS ALI AKBAR, S.SI., M.Kom',
                'signer_title' => 'Ka. Program Studi',
                'status' => 'submitted',
            ]);

            app(AcademicLetterService::class)->generatePdf($letter);
        }
    }
}
