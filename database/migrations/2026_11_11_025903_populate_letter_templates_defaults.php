<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $defaults = [
            // 1. Dokumen Pakta Integritas
            'pakta_integritas' => [
                'opening_template' => 'Saya yang bertanda tangan di bawah ini:',
                'content_template' => 'Menyatakan bahwa saya akan melaksanakan ketentuan sebagai berikut:',
                'closing_template' => 'Demikian pernyataan ini saya buat, apabila di kemudian hari saya tidak memenuhi ketentuan di atas, saya bersedia menerima sanksi baik berupa teguran lisan, teguran tertulis, serta tidak diberi kesempatan mengampu mata kuliah pada semester berikutnya.',
            ],

            // 2. Pengajuan Kebutuhan Dana
            'pengajuan_dana' => [
                'opening_template' => 'Dengan hormat, sehubungan dengan pelaksanaan kegiatan operasional pada Fakultas Ilmu Komputer Universitas Subang, bersama ini kami sampaikan rincian pengajuan kebutuhan dana.',
                'content_template' => 'Adapun rincian alokasi anggaran dan kebutuhan dana operasional sebagaimana tercantum dalam tabel pengajuan terlampir.',
                'closing_template' => 'Demikian pengajuan kebutuhan dana ini kami sampaikan, atas perhatian dan realisasinya diucapkan terima kasih.',
            ],

            // 3. Pengadaan Sarpras
            'pengadaan_sarpras' => [
                'opening_template' => 'Sehubungan dengan meningkatnya aktivitas dan kebutuhan perangkat kerja di {unit_kerja} di Tahun akademik {tahun_akademik}, bersama ini kami mengajukan Permohonan Pengadaan Sarana dan Prasarana dengan spesifikasi terlampir guna menunjang pekerjaan harian.',
                'content_template' => 'Kami berharap permohonan ini dapat dipenuhi guna memenuhi kelancaran operasional harian dan pelayanan akademik bagi mahasiswa.',
                'closing_template' => 'Demikian surat permohonan ini kami sampaikan. Atas perhatian dan kerjasamanya, kami ucapkan terima kasih.',
            ],

            // 4. Perbaikan Sarpras
            'perbaikan_sarpras' => [
                'opening_template' => 'Bersama ini kami sampaikan surat permohonan pergantian Sarana dan Prasarana Fakultas Ilmu Komputer Universitas Subang yang memerlukan pergantian.',
                'content_template' => 'Adapun sarana dan prasarana yang kami mohonkan yaitu :',
                'closing_template' => 'Demikian surat permohonan ini kami sampaikan, atas perhatiannya kami ucapkan terimakasih.',
            ],

            // 5. Pemberitahuan Ujian Dosen
            'pemberitahuan_ujian_dosen' => [
                'opening_template' => 'Diberitahukan sesuai dengan kalender Akademik Fakultas Ilmu Komputer, Bahwa pelaksanaan Ujian Tengah Semester Ganjil TA. {tahun_akademik} akan dilaksanakan secara Daring dan atau Luring dengan ketentuan sebagai berikut :',
                'content_template' => 'Pelaksanaan ujian wajib dikoordinasikan oleh seluruh Dosen Pengampu mata kuliah sesuai jadwal kalender akademik yang telah ditetapkan.',
                'closing_template' => 'Demikian agar maklum, atas perhatiannya kami ucapkan terimakasih.',
            ],

            // 6. Pengumuman Ujian Mahasiswa
            'pengumuman_ujian_mahasiswa' => [
                'opening_template' => 'Diberitahukan sesuai dengan surat edaran dari Wakil Rektor I Bidang Akademik dan kemahasiswaan, Bahwa pelaksanaan Ujian Tengah Semester Ganjil TA. {tahun_akademik} akan dilaksanakan secara Daring dan atau Luring dengan ketentuan sebagai berikut :',
                'content_template' => 'Seluruh mahasiswa diwajibkan mematuhi tata tertib ujian, mengenakan pakaian rapi hitam putih beralmamater, dan membawa kartu ujian yang sah.',
                'closing_template' => 'Demikian pengumuman ini disampaikan untuk diketahui dan dilaksanakan sebagaimana mestinya.',
            ],

            // 7. Perbaikan Data PDDIKTI
            'perbaikan_data_pddikti' => [
                'opening_template' => 'Sehubungan dengan kesalahan pada data mahasiswa di bawah ini, dengan ini kami mengajukan perubahan data mahasiswa sesuai dengan tabel berikut ini:',
                'content_template' => 'Data yang diubah telah diverifikasi sesuai dengan fakta dan dibuktikan dengan dokumen administrasi terlampir.',
                'closing_template' => 'Demikian surat ini kami buat untuk dapat ditindaklanjuti. Atas perhatiannya kami ucapkan terimakasih.',
            ],

            // 8. Undangan Rapat Akademik
            'undangan_rapat_akademik' => [
                'content_template' => 'Mengingat pentingnya agenda rapat ini demi peningkatan mutu akademik, dimohon kehadiran Bapak/Ibu Dosen tepat pada waktu yang telah ditentukan.',
            ],

            // 9. Pembukaan Periode
            'pembukaan_periode' => [
                'content_template' => 'Sehubungan dengan kebutuhan perbaikan data PIN mahasiswa yang akan lulus, dengan ini kami mengajukan permohonan Pembukaan Periode Tipe 2 melalui Feeder PDDIKTI untuk memperbaiki data pada Program Studi Sistem Informasi (57201). Adapun data yang kami ajukan sebagaimana data terlampir.',
            ],

            // 10. Pengajuan TTD Ijazah
            'pengajuan_ttd_ijazah' => [
                'content_template' => 'Bersamaan dengan ini kami mengajukan penandatanganan ijazah untuk mahasiswa tersebut diatas, data suplemen pengajuan terlampir.',
            ],

            // 11. Pengajuan Blanko Ijazah Transkrip
            'pengajuan_blanko_ijazah_transkrip' => [
                'content_template' => 'Sehubungan dengan hal tersebut, kami mengajukan permohonan blanko ijazah dan blanko transkrip pendamping ijazah untuk mahasiswa sebagaimana data terlampir.',
            ],

            // 12. SK Dekan Dosen Pembimbing BKP MBKM
            'sk_dekan_pembimbing_mbkm' => [
                'opening_template' => "Bahwa dalam rangka kebijakan merdeka belajar yang tertuang dalam Permendikbud No. 3 Tahun 2020 memberikan hak kepada mahasiswa untuk 3 semester belajar di luar program studinya;\nBahwa untuk mengimplementasikan kebijakan tersebut Fakultas Ilmu Komputer Universitas Subang telah menjalin kerjasama dengan Mitra Program Magang Bersertifikat Kampus Merdeka (MBKM);\nBahwa nama-nama dosen yang tercantum dalam lampiran keputusan ini dipandang mampu dan memenuhi syarat untuk ditunjuk sebagai Dosen Pembimbing BKP MBKM;\nBahwa berdasarkan pertimbangan sebagaimana dimaksud, perlu menetapkan Keputusan Dekan Fakultas Ilmu Komputer Universitas Subang.",
                'content_template' => "Menunjuk nama-nama dosen yang tercantum dalam lampiran keputusan ini sebagai Dosen Pembimbing BKP MBKM Fakultas Ilmu Komputer Universitas Subang;\nMemberikan tugas kepada pembimbing untuk mengarahkan, membimbing, dan memonitor aktivitas mahasiswa selama pelaksanaan program MBKM di instansi mitra.",
                'closing_template' => 'Keputusan ini berlaku sejak tanggal ditetapkan dengan ketentuan apabila di kemudian hari terdapat kekeliruan akan diperbaiki sebagaimana mestinya.',
            ],

            // 13. SK Dosen Pembimbing Tugas Akhir
            'sk_dekan_pembimbing_ta' => [
                'opening_template' => "Bahwa mahasiswa mempunyai hak untuk mendapat bimbingan dari dosen yang bertanggungjawab atas program studi yang diikutinya dalam penyelesaian studi;\nBahwa mahasiswa sebagai bagian dari civitas akademika perlu lebih ditingkatkan kualitasnya serta diarahkan agar dapat menyelesaikan studi tepat pada waktunya;\nBahwa untuk mewujudkan hal tersebut di atas, maka kepada mahasiswa sebagai peserta didik diberikan bimbingan akademik;\nBahwa sesuai dengan hal-hal tersebut di atas, maka perlu ditetapkan persyaratan, tugas, wewenang Pembimbing Akademik dengan Keputusan Dekan Fakultas Ilmu Komputer Universitas Subang.",
                'content_template' => "Menunjuk nama-nama dosen sebagai pembimbing tugas akhir pada Fakultas Ilmu Komputer Universitas Subang sebagaimana tercantum pada lampiran Surat Keputusan ini;\nProsedur bimbingan tugas akhir sesuai dengan pedoman penyusunan tugas akhir di Fakultas Ilmu Komputer Universitas Subang.",
                'closing_template' => 'Keputusan ini mulai berlaku sejak tanggal ditetapkan dan apabila terdapat kekeliruan dalam keputusan ini akan diperbaiki sebagaimana mestinya.',
            ],

            // 14. SK Penguji Seminar Tugas Akhir
            'sk_dekan_penguji_sidang_ta' => [
                'opening_template' => "Bahwa untuk Kelancaran pelaksanaan Kegiatan Pengujian Tugas Akhir Semester Ganjil Tahun Akademik {tahun_akademik} bagi mahasiswa Program Studi S1 Sistem Informasi Pada Fakultas Ilmu Komputer Universitas Subang perlu ditunjukkan penguji seminar tugas Akhir/skripsi;\nBahwa saudara-saudara yang namanya tercantum dalam daftar lampiran keputusan ini, dipandang mampu dan memenuhi syarat untuk ditunjukkan sebagai penguji seminar tugas Akhir/skripsi;\nBahwa untuk memenuhi poin a dan b di atas, maka perlu ditetapkan melalui Keputusan Dekan Fakultas Ilmu Komputer.",
                'content_template' => "Menyetujui pelaksanaan Pengujian Seminar Tugas Akhir/Skripsi yang diselenggarakan oleh Program Studi Sistem Informasi;\nMenetapkan peserta ujian dan penguji sebagaimana tercantum dalam lampiran Surat Keputusan yang merupakan bagian tak terpisahkan dari keputusan ini.",
                'closing_template' => 'Keputusan ini berlaku sejak tanggal ditetapkan dengan ketentuan, apabila dikemudian hari ternyata terdapat kekeliruan dalam Keputusan ini maka akan diperbaiki sebagaimana mestinya.',
            ],

            // 15. SK Dosen Wali Akademik
            'sk_dekan_dosen_wali' => [
                'opening_template' => "Bahwa untuk kelancaran proses bimbingan akademik dan pengawasan studi mahasiswa Fakultas Ilmu Komputer Universitas Subang perlu ditunjuk Dosen Wali Akademik;\nBahwa dosen yang namanya tercantum dalam lampiran keputusan ini dipandang cakap dan memenuhi syarat untuk ditetapkan sebagai Dosen Wali Akademik;\nBahwa berdasarkan pertimbangan tersebut perlu diterbitkan Keputusan Dekan Fakultas Ilmu Komputer.",
                'content_template' => "Menunjuk nama-nama dosen yang tercantum dalam lampiran keputusan ini sebagai Dosen Wali Akademik Fakultas Ilmu Komputer Universitas Subang;\nDosen Wali Akademik berkewajiban membimbing mahasiswa dalam pengisian KRS, memantau perkembangan akademik, dan memberikan arahan studi.",
                'closing_template' => 'Keputusan ini mulai berlaku sejak tanggal ditetapkan dan apabila di kemudian hari terdapat kekeliruan akan diadakan perbaikan sebagaimana mestinya.',
            ],

            // 16. SK Kelulusan / Yudisium
            'sk_dekan_yudisium' => [
                'opening_template' => "Bahwa mahasiswa yang namanya tercantum dalam lampiran keputusan ini telah menyelesaikan seluruh beban studi dan dinyatakan lulus pada ujian sidang sarjana;\nBahwa berdasarkan hasil rapat yudisium kelulusan Fakultas Ilmu Komputer Universitas Subang, mahasiswa tersebut berhak menyandang gelar Sarjana Komputer (S.Kom);\nBahwa berdasarkan pertimbangan tersebut, perlu ditetapkan Keputusan Dekan tentang Yudisium Kelulusan Sarjana.",
                'content_template' => "Menetapkan Kelulusan Ujian Sidang Sarjana dan Peserta Yudisium Program Studi Sistem Informasi Fakultas Ilmu Komputer Universitas Subang sebagaimana tercantum dalam lampiran keputusan ini;\nMemberikan hak kepada lulusan untuk menggunakan gelar akademik Sarjana Komputer (S.Kom) beserta hak dan kewajiban yang melekat padanya.",
                'closing_template' => 'Keputusan ini berlaku sejak tanggal ditetapkan dengan ketentuan apabila dikemudian hari terdapat kekeliruan akan diperbaiki sebagaimana mestinya.',
            ],

            // 17. SK Dosen Mengajar
            'sk_dekan_dosen_mengajar' => [
                'opening_template' => "Bahwa untuk pelaksanaan proses belajar mengajar Semester Ganjil Tahun Akademik {tahun_akademik}, pada Fakultas Ilmu Komputer perlu ditunjukkan tenaga pengajar;\nBahwa saudara-saudara yang namanya tercantum dalam daftar lampiran keputusan ini, mampu dan memenuhi syarat untuk ditunjukkan sebagai dosen pengajar Semester Ganjil Tahun Akademik {tahun_akademik}, pada Fakultas Ilmu Komputer Universitas Subang;\nBahwa untuk memenuhi poin a dan b di atas, maka perlu ditetapkan melalui Keputusan Dekan Fakultas Ilmu Komputer.",
                'content_template' => 'Menunjukkan Saudara-saudara yang namanya tercantum dalam daftar lampiran surat keputusan ini, sebagai dosen pengajar Semester Ganjil Tahun Akademik {tahun_akademik}, dalam mata kuliah masing-masing sebagaimana tercantum dalam lampiran keputusan ini.',
                'closing_template' => 'Keputusan ini berlaku sejak tanggal ditetapkan dengan ketentuan apabila dikemudian hari ternyata terdapat kekeliruan dalam Keputusan ini akan diperbaiki sebagaimana mestinya.',
            ],

            // 18. SK Pelaksanaan Pengujian Sidang Tugas Akhir / Skripsi
            'sk_dekan_pengujian_sidang_skripsi' => [
                'opening_template' => "Bahwa untuk Kelancaran pelaksanaan Kegiatan Pengujian Sidang Tugas Akhir/Skripsi Semester Ganjil Tahun Akademik {tahun_akademik} bagi mahasiswa Program Studi S1 Sistem Informasi Pada Fakultas Ilmu Komputer Universitas Subang perlu ditunjukkan penguji sidang tugas Akhir/skripsi;\nBahwa saudara-saudara yang namanya tercantum dalam daftar lampiran keputusan ini, dipandang mampu dan memenuhi syarat untuk ditunjukkan sebagai penguji sidang tugas Akhir/skripsi;\nBahwa untuk memenuhi poin a dan b di atas, maka perlu ditetapkan melalui Keputusan Dekan Fakultas Ilmu Komputer.",
                'content_template' => "Menyetujui pelaksanaan Pengujian Sidang Tugas Akhir/Skripsi Semester Ganjil Tahun Akademik {tahun_akademik} yang diselenggarakan oleh Program Studi Sistem Informasi;\nMenetapkan peserta sidang dan dosen penguji sebagaimana tercantum dalam lampiran Surat Keputusan yang merupakan bagian tak terpisahkan dari keputusan ini.",
                'closing_template' => 'Keputusan ini berlaku sejak tanggal ditetapkan dengan ketentuan apabila dikemudian hari ternyata terdapat kekeliruan dalam Keputusan ini maka akan diperbaiki sebagaimana mestinya.',
            ],

            // 19. SK Pengunduran Diri Mahasiswa
            'sk_dekan_pengunduran_diri' => [
                'opening_template' => "Bahwa mahasiswa yang namanya tercantum dalam keputusan ini telah mengajukan permohonan pengunduran diri secara tertulis atas kehendak sendiri;\nBahwa mahasiswa yang bersangkutan telah menyelesaikan segala kewajiban administratif dan keuangan di lingkungan Fakultas Ilmu Komputer Universitas Subang;\nBahwa berdasarkan pertimbangan tersebut, perlu diterbitkan Keputusan Dekan tentang Pengunduran Diri Mahasiswa.",
                'content_template' => "Menerima dan menyetujui pengunduran diri mahasiswa sebagaimana tercantum dalam keputusan ini;\nMenyatakan sejak berlakunya keputusan ini yang bersangkutan kehilangan hak dan statusnya sebagai mahasiswa Fakultas Ilmu Komputer Universitas Subang.",
                'closing_template' => 'Keputusan ini berlaku sejak tanggal ditetapkan dengan ketentuan apabila di kemudian hari terdapat kekeliruan akan diperbaiki sebagaimana mestinya.',
            ],

            // 20. SK Pengangkatan GKM dan UKM
            'sk_dekan_gkm_ukm' => [
                'opening_template' => "Bahwa dalam rangka mencapai Standar Nasional Pendidikan Tinggi diperlukan sistem penjaminan mutu pendidikan tinggi di Fakultas Ilmu Komputer Universitas Subang;\nBahwa untuk meningkatkan mutu akademik dan tata kelola program studi perlu dibentuk Gugus Kendali Mutu (GKM) Fakultas dan Unit Kendali Mutu (UKM) Program Studi;\nBahwa saudara yang namanya tercantum dalam keputusan ini dipandang mampu dan memenuhi syarat untuk mengemban amanah tersebut.",
                'content_template' => "Menunjuk dan mengangkat Saudara yang namanya tercantum dalam keputusan ini sebagai Ketua Gugus Kendali Mutu (GKM) Fakultas dan Ketua Unit Kendali Mutu (UKM) Program Studi Sistem Informasi;\nMemberikan tugas dan wewenang untuk melaksanakan monitoring dan evaluasi penjaminan mutu internal secara berkelanjutan.",
                'closing_template' => 'Keputusan ini berlaku sejak tanggal ditetapkan dan apabila di kemudian hari terdapat kekeliruan akan diperbaiki sebagaimana mestinya.',
            ],

            // 21. Nota Kesepahaman (MoU) Magang MBKM
            'mou_magang_mbkm' => [
                'opening_template' => 'Pada hari ini bertempat di Subang, kami yang bertandatangan di bawah ini PIHAK KESATU dan PIHAK KEDUA telah bersepakat mengadakan Nota Kesepahaman Bersama.',
                'content_template' => "Penyelenggaraan Program Magang Bersertifikat dan Pembelajaran MBKM;\nPembinaan dan pengembangan kompetensi praktis mahasiswa di dunia industri dan dunia kerja;\nPenyediaan fasilitas pendampingan dan bimbingan teknis bagi peserta program.",
                'closing_template' => 'Perjanjian ini dibuat tanpa paksaan dari siapapun, bermaterai cukup serta masing-masing mempunyai kekuatan hukum yang sama.',
            ],
        ];

        foreach ($defaults as $code => $tpl) {
            $updateData = [];
            if (isset($tpl['opening_template'])) {
                $updateData['opening_template'] = DB::raw("COALESCE(NULLIF(opening_template, ''), ".DB::getPdo()->quote($tpl['opening_template']).')');
            }
            if (isset($tpl['content_template'])) {
                $updateData['content_template'] = DB::raw("COALESCE(NULLIF(content_template, ''), ".DB::getPdo()->quote($tpl['content_template']).')');
            }
            if (isset($tpl['closing_template'])) {
                $updateData['closing_template'] = DB::raw("COALESCE(NULLIF(closing_template, ''), ".DB::getPdo()->quote($tpl['closing_template']).')');
            }

            if (! empty($updateData)) {
                DB::table('letter_types')->where('code', $code)->update($updateData);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to clear templates on rollback
    }
};
