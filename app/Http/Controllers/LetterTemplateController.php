<?php

namespace App\Http\Controllers;

use App\Models\LetterClassification;
use App\Models\LetterRequest;
use App\Models\LetterType;
use App\Services\AcademicLetterService;
use App\Services\MouService;
use App\Services\SkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class LetterTemplateController extends Controller
{
    public function __construct(
        protected AcademicLetterService $letterService,
        protected SkService $skService,
        protected MouService $mouService
    ) {}

    /**
     * Provide comprehensive available placeholder variables for admin reference.
     *
     * @return array<int, array{tag: string, label: string, example: string, category: string}>
     */
    public static function getAvailablePlaceholders(): array
    {
        return [
            ['tag' => '{nama_mahasiswa}', 'label' => 'Nama Lengkap Mahasiswa', 'example' => 'Budi Santoso', 'category' => 'Mahasiswa'],
            ['tag' => '{npm}', 'label' => 'Nomor Pokok Mahasiswa', 'example' => 'D1A210001', 'category' => 'Mahasiswa'],
            ['tag' => '{program_studi}', 'label' => 'Program Studi', 'example' => 'Sistem Informasi', 'category' => 'Mahasiswa'],
            ['tag' => '{tempat_lahir}', 'label' => 'Tempat Lahir', 'example' => 'Subang', 'category' => 'Mahasiswa'],
            ['tag' => '{tanggal_lahir}', 'label' => 'Tanggal Lahir', 'example' => '14 Mei 2003', 'category' => 'Mahasiswa'],
            ['tag' => '{ttl}', 'label' => 'Tempat & Tanggal Lahir (Lengkap)', 'example' => 'Subang, 14 Mei 2003', 'category' => 'Mahasiswa'],
            ['tag' => '{alamat}', 'label' => 'Alamat Domisili', 'example' => 'Jl. R.A. Kartini No. 12 Subang', 'category' => 'Mahasiswa'],
            ['tag' => '{semester}', 'label' => 'Semester (Angka)', 'example' => '6', 'category' => 'Akademik'],
            ['tag' => '{semester_huruf}', 'label' => 'Semester (Huruf/Kata)', 'example' => 'Enam', 'category' => 'Akademik'],
            ['tag' => '{semester_romawi}', 'label' => 'Semester (Angka Romawi)', 'example' => 'VII', 'category' => 'Akademik'],
            ['tag' => '{jenjang}', 'label' => 'Jenjang Program Pendidikan', 'example' => 'Sarjana / S.1', 'category' => 'Akademik'],
            ['tag' => '{tahun_akademik}', 'label' => 'Tahun Akademik', 'example' => '2026/2027', 'category' => 'Akademik'],
            ['tag' => '{keperluan}', 'label' => 'Keperluan Surat', 'example' => 'Pengajuan Beasiswa Unggulan', 'category' => 'Akademik'],
            ['tag' => '{nomor_surat}', 'label' => 'Nomor Surat Resmi', 'example' => '024/PK.1.1/FIK-US/X/2026', 'category' => 'Pejabat & Sistem'],
            ['tag' => '{pejabat_nama}', 'label' => 'Nama Pejabat Penandatangan', 'example' => 'BAGUS ALI AKBAR, S.Si., M.Kom', 'category' => 'Pejabat & Sistem'],
            ['tag' => '{pejabat_jabatan}', 'label' => 'Jabatan Resmi Pejabat', 'example' => 'Ketua Program Studi Sistem Informasi Fakultas Ilmu Komputer Universitas Subang', 'category' => 'Pejabat & Sistem'],
            ['tag' => '{pejabat_title}', 'label' => 'Singkatan Gelar Jabatan', 'example' => 'Ka. Program Studi', 'category' => 'Pejabat & Sistem'],

            // Kolom Isian Khusus (Extra Data)
            ['tag' => '{tujuan_instansi}', 'label' => 'Nama Perusahaan / Instansi Tujuan', 'example' => 'PT. Dahana (Persero) Subang', 'category' => 'Instansi / Mitra'],
            ['tag' => '{alamat_instansi}', 'label' => 'Alamat Perusahaan / Instansi', 'example' => 'Jl. Raya Subang KM 15', 'category' => 'Instansi / Mitra'],
            ['tag' => '{periode_magang}', 'label' => 'Periode / Rentang Waktu Magang/PKL', 'example' => '1 Juli 2026 s.d. 31 Agustus 2026', 'category' => 'Instansi / Mitra'],
            ['tag' => '{durasi_magang}', 'label' => 'Durasi Magang MBKM', 'example' => '5 bulan', 'category' => 'PKL & Magang'],
            ['tag' => '{tanggal_mulai_magang}', 'label' => 'Tanggal Mulai Magang', 'example' => '14 September 2026', 'category' => 'PKL & Magang'],
            ['tag' => '{tanggal_selesai_magang}', 'label' => 'Tanggal Selesai Magang', 'example' => '09 Januari 2027', 'category' => 'PKL & Magang'],
            ['tag' => '{durasi_pkl}', 'label' => 'Durasi PKL (contoh: 40 hari)', 'example' => '40 hari', 'category' => 'PKL & Magang'],
            ['tag' => '{tanggal_mulai_pkl}', 'label' => 'Tanggal Mulai PKL', 'example' => '06 Oktober 2025', 'category' => 'PKL & Magang'],
            ['tag' => '{tanggal_selesai_pkl}', 'label' => 'Tanggal Selesai PKL', 'example' => '28 November 2025', 'category' => 'PKL & Magang'],
            ['tag' => '{mata_kuliah}', 'label' => 'Nama Mata Kuliah', 'example' => 'Rekayasa Perangkat Lunak', 'category' => 'Perkuliahan'],
            ['tag' => '{dosen_pengampu}', 'label' => 'Dosen Pengampu', 'example' => 'Bambang Tjahjo Utomo, MT', 'category' => 'Perkuliahan'],
            ['tag' => '{judul_skripsi}', 'label' => 'Judul Skripsi / Penelitian', 'example' => 'Sistem Verifikasi Ijazah Digital Berbasis TTE', 'category' => 'Penelitian'],
            ['tag' => '{kegiatan}', 'label' => 'Nama Kegiatan Seminar / Ujian', 'example' => 'Seminar Usulan Penelitian', 'category' => 'Seminar & Tugas Akhir'],
            ['tag' => '{hari_pelaksanaan}', 'label' => 'Hari Pelaksanaan Kegiatan', 'example' => 'Kamis', 'category' => 'Seminar & Tugas Akhir'],
            ['tag' => '{jenis_ujian}', 'label' => 'Jenis Ujian (UTS / UAS)', 'example' => 'UAS', 'category' => 'Ujian & Jadwal'],
            ['tag' => '{nama_ujian_lengkap}', 'label' => 'Nama Ujian Lengkap (Ujian Tengah/Akhir Semester)', 'example' => 'Ujian Akhir Semester', 'category' => 'Ujian & Jadwal'],
            ['tag' => '{tanggal_ujian}', 'label' => 'Rentang Tanggal Pelaksanaan Ujian', 'example' => '12-19 Januari 2026', 'category' => 'Ujian & Jadwal'],
            ['tag' => '{lampiran_count}', 'label' => 'Jumlah Lembar Lampiran', 'example' => '2 (dua) lembar', 'category' => 'Pejabat & Sistem'],
            ['tag' => '{nilai_asal}', 'label' => 'Nilai Asal Sebelum Ujian Susulan', 'example' => '-', 'category' => 'Ujian & Jadwal'],
            ['tag' => '{alasan}', 'label' => 'Alasan Dispensasi / Ujian Susulan', 'example' => 'Sedang dalam penugasan dinas kampus', 'category' => 'Perkuliahan'],
            ['tag' => '{nama_orang_tua}', 'label' => 'Nama Orang Tua / Wali', 'example' => 'H. Maman Sulaeman', 'category' => 'Orang Tua (PNS)'],
            ['tag' => '{nip_orang_tua}', 'label' => 'NIP / NRP Orang Tua', 'example' => '19750812 200212 1 003', 'category' => 'Orang Tua (PNS)'],
            ['tag' => '{pangkat_orang_tua}', 'label' => 'Pangkat / Golongan Orang Tua', 'example' => 'Pembina / IV a', 'category' => 'Orang Tua (PNS)'],
            ['tag' => '{instansi_orang_tua}', 'label' => 'Instansi / Unit Kerja Orang Tua', 'example' => 'Dinas Pendidikan Kabupaten Subang', 'category' => 'Orang Tua (PNS)'],
            ['tag' => '{nama_dosen}', 'label' => 'Nama Dosen Pengampu', 'example' => 'BAGUS ALI AKBAR, S.Si., M.Kom', 'category' => 'Dosen & Mengajar'],
            ['tag' => '{status_tenaga_pengajar}', 'label' => 'Status Tenaga Pengajar', 'example' => 'Dosen Tetap', 'category' => 'Dosen & Mengajar'],
            ['tag' => '{nidn}', 'label' => 'NIDN / NIP Dosen', 'example' => '0410019202', 'category' => 'Dosen & Mengajar'],
            ['tag' => '{semester_tipe}', 'label' => 'Tipe Semester (Ganjil/Genap)', 'example' => 'Ganjil', 'category' => 'Dosen & Mengajar'],

            // Keterangan Kelulusan & Yudisium
            ['tag' => '{sk_dekan_nomor}', 'label' => 'Nomor SK Dekan Kelulusan', 'example' => '270 /D/FIK-US/VI/2026', 'category' => 'Kelulusan / SKL'],
            ['tag' => '{sk_dekan_tanggal}', 'label' => 'Tanggal Penetapan SK Dekan', 'example' => '26 Juni 2026', 'category' => 'Kelulusan / SKL'],
            ['tag' => '{gelar_akademik}', 'label' => 'Gelar Akademik Kelulusan', 'example' => 'Sarjana Komputer (S.Kom)', 'category' => 'Kelulusan / SKL'],
            ['tag' => '{predikat_kelulusan}', 'label' => 'Predikat Yudisium Kelulusan', 'example' => 'SANGAT MEMUASKAN', 'category' => 'Kelulusan / SKL'],
            ['tag' => '{tanggal_sidang}', 'label' => 'Hari dan Tanggal Sidang Tugas Akhir', 'example' => 'Kamis, 27 Agustus 2026', 'category' => 'Kelulusan / SKL'],
            ['tag' => '{tanggal_yudisium}', 'label' => 'Hari dan Tanggal Sidang Yudisium', 'example' => 'Kamis, 27 Agustus 2026', 'category' => 'Kelulusan / SKL'],
            ['tag' => '{ipk}', 'label' => 'Indeks Prestasi Kumulatif (IPK)', 'example' => '3.19', 'category' => 'Kelulusan / SKL'],
            ['tag' => '{masa_studi}', 'label' => 'Masa Studi Mahasiswa', 'example' => '4 (Empat) Tahun', 'category' => 'Kelulusan / SKL'],
            ['tag' => '{nilai_sidang}', 'label' => 'Nilai Sidang Tugas Akhir', 'example' => 'A', 'category' => 'Kelulusan / SKL'],
            ['tag' => '{hasil_sidang}', 'label' => 'Hasil Sidang Tugas Akhir', 'example' => 'Lulus', 'category' => 'Kelulusan / SKL'],
            ['tag' => '{predikat_yudisium}', 'label' => 'Predikat / Hasil Yudisium', 'example' => 'Dengan Pujian', 'category' => 'Kelulusan / SKL'],

            // Undangan Rapat Akademik
            ['tag' => '{agenda}', 'label' => 'Agenda / Hal Rapat', 'example' => 'Rapat Akademik', 'category' => 'Undangan Rapat'],
            ['tag' => '{hari_tanggal_rapat}', 'label' => 'Hari dan Tanggal Rapat', 'example' => 'Selasa, 8 September 2026', 'category' => 'Undangan Rapat'],
            ['tag' => '{waktu_rapat}', 'label' => 'Waktu Pelaksanaan Rapat', 'example' => '09.00 WIB', 'category' => 'Undangan Rapat'],
            ['tag' => '{tempat_rapat}', 'label' => 'Tempat / Lokasi Rapat', 'example' => 'Ruang 12 Fakultas Ilmu Komputer', 'category' => 'Undangan Rapat'],
            ['tag' => '{link_rapat}', 'label' => 'Tautan / Link Google Meet (Jika Online)', 'example' => 'https://meet.google.com/abc-defg-hij', 'category' => 'Undangan Rapat'],
            ['tag' => '{penerima_undangan}', 'label' => 'Nama Penerima Undangan / Dosen', 'example' => 'Bapak Bagus Ali Akbar, S.SI,. M.Kom', 'category' => 'Undangan Rapat'],

            // Studio SK (Surat Keputusan Dekan)
            ['tag' => '{about_title}', 'label' => 'Pokok Perihal Keputusan Dekan (TENTANG)', 'example' => 'PENETAPAN DOSEN PEMBIMBING BKP MBKM', 'category' => 'Studio SK Dekan'],
            ['tag' => '{dekan_name}', 'label' => 'Nama Dekan Fasilkom', 'example' => 'Dr. TEPI PEIRISAL, M.Si', 'category' => 'Studio SK Dekan'],
            ['tag' => '{dekan_nik}', 'label' => 'NIK Dekan Fasilkom', 'example' => '0410420142', 'category' => 'Studio SK Dekan'],
            ['tag' => '{decision_city}', 'label' => 'Kota Penetapan SK', 'example' => 'Subang', 'category' => 'Studio SK Dekan'],
            ['tag' => '{decision_date_text}', 'label' => 'Tanggal Penetapan SK', 'example' => '01 September 2025', 'category' => 'Studio SK Dekan'],

            // Studio MOU (Nota Kesepahaman)
            ['tag' => '{mou_title}', 'label' => 'Judul Nota Kesepahaman Kerjasama', 'example' => 'PENYELENGGARAAN PROGRAM MAGANG BERSERTIFIKAT - MBKM', 'category' => 'Studio MoU Kerjasama'],
            ['tag' => '{partner_name}', 'label' => 'Nama Mitra / Instansi / Perusahaan (Pihak 1)', 'example' => 'PT. Dahana (Persero) Subang', 'category' => 'Studio MoU Kerjasama'],
            ['tag' => '{partner_official_name}', 'label' => 'Nama Pejabat Pihak Mitra', 'example' => 'Ir. Hendra Wijaya, MM', 'category' => 'Studio MoU Kerjasama'],
            ['tag' => '{partner_official_position}', 'label' => 'Jabatan Pejabat Pihak Mitra', 'example' => 'Direktur SDM & Hukum', 'category' => 'Studio MoU Kerjasama'],
            ['tag' => '{partner_mou_number}', 'label' => 'Nomor Surat Perjanjian Pihak Mitra', 'example' => '012/HRD-MOU/XI/2026', 'category' => 'Studio MoU Kerjasama'],
            ['tag' => '{faculty_mou_number}', 'label' => 'Nomor Perjanjian Fasilkom UNSUB', 'example' => '001/MOU/FIK-US/XI/2026', 'category' => 'Studio MoU Kerjasama'],
            ['tag' => '{signing_city}', 'label' => 'Kota Penandatanganan MoU', 'example' => 'Subang', 'category' => 'Studio MoU Kerjasama'],
            ['tag' => '{signing_date_words}', 'label' => 'Hari dan Tanggal Terbilang (MoU)', 'example' => 'Sabtu tanggal tujuh bulan November tahun dua ribu dua puluh enam', 'category' => 'Studio MoU Kerjasama'],
        ];
    }

    /**
     * Predefined customizable input fields available for letter templates.
     *
     * @return array<int, array{key: string, label: string, category: string, tag: string, default: string}>
     */
    public static function getPredefinedFields(): array
    {
        return [
            ['key' => 'target_agency', 'label' => 'Nama Instansi / Perusahaan Tujuan', 'category' => 'Mitra & Instansi', 'tag' => '{tujuan_instansi}', 'default' => 'PT. Dahana (Persero) Subang'],
            ['key' => 'target_recipient', 'label' => 'Penerima Surat (Yth. Pimpinan/Pejabat)', 'category' => 'Mitra & Instansi', 'tag' => '{penerima_surat}', 'default' => 'Pimpinan PT. Dahana (Persero)'],
            ['key' => 'target_address', 'label' => 'Alamat Instansi / Perusahaan', 'category' => 'Mitra & Instansi', 'tag' => '{alamat_instansi}', 'default' => 'Jl. Raya Subang KM 15, Cibogo'],
            ['key' => 'internship_duration', 'label' => 'Durasi Magang / Kegiatan', 'category' => 'Mitra & Instansi', 'tag' => '{durasi_magang}', 'default' => '40 hari'],
            ['key' => 'course_name', 'label' => 'Nama Mata Kuliah', 'category' => 'Akademik & Perkuliahan', 'tag' => '{mata_kuliah}', 'default' => 'Rekayasa Perangkat Lunak'],
            ['key' => 'lecturer_name', 'label' => 'Nama Dosen Pengampu / Pembimbing', 'category' => 'Akademik & Perkuliahan', 'tag' => '{dosen_pengampu}', 'default' => 'BAMBANG TJAHJO UTOMO, MT'],
            ['key' => 'thesis_title', 'label' => 'Judul Skripsi / Karya Ilmiah', 'category' => 'Penelitian & Skripsi', 'tag' => '{judul_skripsi}', 'default' => 'Sistem Informasi Pelayanan Desa'],
            ['key' => 'activity_name', 'label' => 'Nama Kegiatan / Lomba / Seminar', 'category' => 'Kegiatan & Acara', 'tag' => '{kegiatan}', 'default' => 'Lomba Karya Inovasi Teknologi'],
            ['key' => 'event_day', 'label' => 'Hari / Tanggal Pelaksanaan', 'category' => 'Kegiatan & Acara', 'tag' => '{hari_pelaksanaan}', 'default' => 'Kamis, 15 Oktober 2026'],
            ['key' => 'parent_name', 'label' => 'Nama Orang Tua / Wali', 'category' => 'Orang Tua (PNS/Dinas)', 'tag' => '{nama_orang_tua}', 'default' => 'H. MAMAN SULAEMAN, S.Sos'],
            ['key' => 'parent_nip', 'label' => 'NIP Orang Tua', 'category' => 'Orang Tua (PNS/Dinas)', 'tag' => '{nip_orang_tua}', 'default' => '19750812 200212 1 003'],
            ['key' => 'parent_rank', 'label' => 'Pangkat / Golongan Orang Tua', 'category' => 'Orang Tua (PNS/Dinas)', 'tag' => '{pangkat_orang_tua}', 'default' => 'Pembina / IV a'],
            ['key' => 'parent_institution', 'label' => 'Instansi / Unit Kerja Orang Tua', 'category' => 'Orang Tua (PNS/Dinas)', 'tag' => '{instansi_orang_tua}', 'default' => 'Dinas Pendidikan Kabupaten Subang'],
        ];
    }

    /**
     * Display a listing of all letter templates.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        if ($user->isMahasiswa()) {
            abort(403, 'Akses terbatas untuk Administrator dan Pejabat Fasilkom.');
        }

        $letterTypes = LetterType::withCount('letterRequests')
            ->orderBy('order')
            ->get();

        return Inertia::render('LetterTemplates/Index', [
            'letterTypes' => $letterTypes,
            'placeholders' => self::getAvailablePlaceholders(),
            'predefinedFields' => self::getPredefinedFields(),
        ]);
    }

    /**
     * Show form for creating a new custom letter template.
     */
    public function create(Request $request): Response
    {
        $user = $request->user();
        if ($user->isMahasiswa()) {
            abort(403, 'Akses terbatas untuk Administrator dan Pejabat Fasilkom.');
        }

        return Inertia::render('LetterTemplates/Create', [
            'placeholders' => self::getAvailablePlaceholders(),
            'predefinedFields' => self::getPredefinedFields(),
            'signers' => AcademicLetterService::getAvailableSigners(),
            'classifications' => LetterClassification::active()->ordered()->get(),
        ]);
    }

    /**
     * Store a newly created custom letter template.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user->isMahasiswa()) {
            abort(403, 'Akses terbatas untuk Administrator dan Pejabat Fasilkom.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'letter_code' => ['required', 'string', 'max:50'],
            'code' => ['nullable', 'string', 'max:100'],
            'opening_template' => ['nullable', 'string'],
            'content_template' => ['required', 'string'],
            'closing_template' => ['nullable', 'string'],
            'required_fields' => ['nullable', 'array'],
            'is_active' => ['boolean'],
        ]);

        $baseCode = ! empty($validated['code']) ? $validated['code'] : Str::slug($validated['name'], '_');
        $code = $baseCode;
        $counter = 1;
        while (LetterType::where('code', $code)->exists()) {
            $code = $baseCode.'_'.$counter++;
        }

        $maxOrder = LetterType::max('order') ?: 0;

        $letterType = LetterType::create([
            'name' => $validated['name'],
            'code' => $code,
            'description' => $validated['description'] ?? null,
            'letter_code' => $validated['letter_code'],
            'opening_template' => $validated['opening_template'] ?? null,
            'content_template' => $validated['content_template'],
            'closing_template' => $validated['closing_template'] ?? null,
            'required_fields' => $validated['required_fields'] ?? [],
            'is_active' => $validated['is_active'] ?? true,
            'order' => $maxOrder + 1,
        ]);

        return redirect()->route('letter-templates.index')
            ->with('success', "Format surat baru '{$letterType->name}' berhasil ditambahkan dan siap digunakan.");
    }

    /**
     * Delete or deactivate a letter template.
     */
    public function destroy(LetterType $letterType, Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user->isMahasiswa()) {
            abort(403, 'Akses terbatas untuk Administrator dan Pejabat Fasilkom.');
        }

        $force = $request->boolean('force');
        $name = $letterType->name;

        if ($letterType->letterRequests()->exists() && ! $force) {
            $letterType->update(['is_active' => false]);

            return redirect()->route('letter-templates.index')
                ->with('success', "Format surat '{$name}' telah dinonaktifkan karena sudah terdapat riwayat permohonan surat.");
        }

        $letterType->delete();

        return redirect()->route('letter-templates.index')
            ->with('success', "Format surat '{$name}' berhasil dihapus secara permanen.");
    }

    /**
     * Live preview for an unsaved new template draft.
     */
    public function previewNew(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->isMahasiswa()) {
            abort(403, 'Akses terbatas untuk Administrator dan Pejabat Fasilkom.');
        }

        $tempType = new LetterType([
            'name' => $request->input('name') ?: 'Surat Keterangan Kustom',
            'code' => 'custom_preview',
            'letter_code' => $request->input('letter_code') ?: 'PD.1.2/FIK-US',
            'opening_template' => $request->input('opening_template'),
            'content_template' => $request->input('content_template') ?: 'Menerangkan bahwa mahasiswa tersebut aktif perkuliahan pada Fakultas Ilmu Komputer.',
            'closing_template' => $request->input('closing_template'),
            'required_fields' => (array) $request->input('required_fields', []),
        ]);

        $extraData = [];
        foreach (self::getPredefinedFields() as $field) {
            $extraData[$field['key']] = $field['default'] ?? 'Contoh Isian';
        }

        // Merge any custom fields
        $customFields = $request->input('custom_fields', []);
        if (is_array($customFields)) {
            foreach ($customFields as $cf) {
                if (! empty($cf['key'])) {
                    $extraData[$cf['key']] = $cf['default'] ?? 'Contoh Isian';
                }
            }
        }

        $sampleLetter = new LetterRequest([
            'user_id' => $user->id,
            'student_name' => 'Budi Santoso',
            'npm' => 'D1A210001',
            'study_program' => 'Sistem Informasi',
            'birth_place' => 'Subang',
            'birth_date' => '2003-05-14',
            'address' => 'Jl. R.A. Kartini No. 12 Subang',
            'semester' => 6,
            'academic_year' => '2026/2027',
            'purpose' => 'Keperluan Administrasi',
            'extra_data' => $extraData,
            'signer_name' => 'BAGUS ALI AKBAR, S.Si., M.Kom',
            'signer_title' => 'Ka. Program Studi',
            'status' => 'draft',
        ]);
        $sampleLetter->setRelation('letterType', $tempType);

        $code = Str::slug($request->input('code') ?: $request->input('name') ?: 'custom_preview', '_');

        if (str_starts_with($code, 'sk_') || str_contains(strtolower($request->input('name') ?? ''), 'sk ') || str_contains(strtolower($request->input('name') ?? ''), 'keputusan')) {
            $sampleLetter = $this->buildSampleSkLetter($tempType, $user);
            try {
                $pdfContent = $this->skService->renderSkPdf($sampleLetter, true);

                return response()->json([
                    'success' => true,
                    'pdfBase64' => base64_encode($pdfContent),
                    'engine' => 'sk',
                ]);
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal merender simulasi SK Dekan: '.$e->getMessage(),
                ], 500);
            }
        }

        if (str_starts_with($code, 'mou_') || str_contains(strtolower($request->input('name') ?? ''), 'mou') || str_contains(strtolower($request->input('name') ?? ''), 'kesepahaman')) {
            $sampleLetter = $this->buildSampleMouLetter($tempType, $user);
            try {
                $pdfContent = $this->mouService->renderMouPdf($sampleLetter, true);

                return response()->json([
                    'success' => true,
                    'pdfBase64' => base64_encode($pdfContent),
                    'engine' => 'mou',
                ]);
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal merender simulasi MoU: '.$e->getMessage(),
                ], 500);
            }
        }

        try {
            $pdfContent = $this->letterService->renderPreviewPdf($sampleLetter, true);

            return response()->json([
                'success' => true,
                'pdfBase64' => base64_encode($pdfContent),
                'engine' => 'surat',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal merender simulasi: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show form for editing specific letter template narration.
     */
    public function edit(LetterType $letterType, Request $request): Response
    {
        $user = $request->user();
        if ($user->isMahasiswa()) {
            abort(403, 'Akses terbatas untuk Administrator dan Pejabat Fasilkom.');
        }

        $letterType->loadCount('letterRequests');

        return Inertia::render('LetterTemplates/Edit', [
            'letterType' => $letterType,
            'placeholders' => self::getAvailablePlaceholders(),
            'signers' => AcademicLetterService::getAvailableSigners(),
            'classifications' => LetterClassification::active()->ordered()->get(),
        ]);
    }

    /**
     * Update letter template narration and configuration.
     */
    public function update(LetterType $letterType, Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user->isMahasiswa()) {
            abort(403, 'Akses terbatas untuk Administrator dan Pejabat Fasilkom.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'letter_code' => ['nullable', 'string', 'max:50'],
            'opening_template' => ['nullable', 'string'],
            'content_template' => ['nullable', 'string'],
            'closing_template' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $letterType->update($validated);

        return redirect()->route('letter-templates.index')
            ->with('success', "Template narasi untuk '{$letterType->name}' berhasil diperbarui.");
    }

    /**
     * Live preview generated letter PDF based on the custom template.
     */
    public function preview(LetterType $letterType, Request $request): HttpResponse|JsonResponse
    {
        $user = $request->user();
        if ($user->isMahasiswa()) {
            abort(403, 'Akses terbatas untuk Administrator dan Pejabat Fasilkom.');
        }

        // Allow previewing either current saved model or request draft inputs
        $opening = $request->input('opening_template', $letterType->opening_template);
        $content = $request->input('content_template', $letterType->content_template);
        $closing = $request->input('closing_template', $letterType->closing_template);

        // Temporarily clone or set on letterType instance
        $previewType = clone $letterType;
        $previewType->opening_template = $opening;
        $previewType->content_template = $content;
        $previewType->closing_template = $closing;

        $code = $letterType->code ?? '';

        // 1. ROUTE TO STUDIO SK: Surat Keputusan Dekan
        if (str_starts_with($code, 'sk_')) {
            $sampleLetter = $this->buildSampleSkLetter($previewType, $user);
            try {
                $pdfContent = $this->skService->renderSkPdf($sampleLetter, true);

                if ($request->wantsJson() || $request->input('return_base64')) {
                    return response()->json([
                        'success' => true,
                        'pdfBase64' => base64_encode($pdfContent),
                        'engine' => 'sk',
                        'letterType' => [
                            'id' => $letterType->id,
                            'name' => $letterType->name,
                            'letter_code' => $letterType->letter_code,
                        ],
                    ]);
                }

                return response($pdfContent, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="preview_sk_'.$letterType->code.'.pdf"',
                ]);
            } catch (\Throwable $e) {
                if ($request->wantsJson() || $request->input('return_base64')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Gagal merender simulasi SK Dekan: '.$e->getMessage(),
                    ], 500);
                }
                throw $e;
            }
        }

        // 2. ROUTE TO STUDIO MOU: Nota Kesepahaman Bilateral
        if (str_starts_with($code, 'mou_')) {
            $sampleLetter = $this->buildSampleMouLetter($previewType, $user);
            try {
                $pdfContent = $this->mouService->renderMouPdf($sampleLetter, true);

                if ($request->wantsJson() || $request->input('return_base64')) {
                    return response()->json([
                        'success' => true,
                        'pdfBase64' => base64_encode($pdfContent),
                        'engine' => 'mou',
                        'letterType' => [
                            'id' => $letterType->id,
                            'name' => $letterType->name,
                            'letter_code' => $letterType->letter_code,
                        ],
                    ]);
                }

                return response($pdfContent, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="preview_mou_'.$letterType->code.'.pdf"',
                ]);
            } catch (\Throwable $e) {
                if ($request->wantsJson() || $request->input('return_base64')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Gagal merender simulasi MoU: '.$e->getMessage(),
                    ], 500);
                }
                throw $e;
            }
        }

        // 3. ROUTE TO STUDIO SURAT: Academic & Operational Letters
        $sampleLetter = $this->buildSampleAcademicLetter($previewType, $user);
        try {
            $pdfContent = $this->letterService->renderPreviewPdf($sampleLetter, true);

            if ($request->wantsJson() || $request->input('return_base64')) {
                return response()->json([
                    'success' => true,
                    'pdfBase64' => base64_encode($pdfContent),
                    'engine' => 'surat',
                    'letterType' => [
                        'id' => $letterType->id,
                        'name' => $letterType->name,
                        'letter_code' => $letterType->letter_code,
                    ],
                ]);
            }

            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="preview_template_'.$letterType->code.'.pdf"',
            ]);
        } catch (\Throwable $e) {
            if ($request->wantsJson() || $request->input('return_base64')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal merender simulasi surat: '.$e->getMessage(),
                ], 500);
            }
            throw $e;
        }
    }

    /**
     * Build sample LetterRequest for Studio SK with complete rich data.
     */
    protected function buildSampleSkLetter(LetterType $previewType, $user): LetterRequest
    {
        $code = $previewType->code;

        $extraData = [
            'about_title' => $this->getDefaultSkAboutTitle($code),
            'study_program' => 'Program Studi Sistem Informasi S1',
            'semester_type' => 'Ganjil',
            'academic_year' => '2025/2026',
            'decision_city' => 'Subang',
            'decision_date_text' => '01 September 2025',
            'dekan_name' => 'Dr. TEPI PEIRISAL, M.Si',
            'dekan_nik' => '0410420142',
            'menetapkan_text' => 'Keputusan Dekan Fakultas Ilmu Komputer Universitas Subang',
            'menimbang' => [
                'Bahwa untuk kelancaran pelaksanaan kegiatan akademik pada Fakultas Ilmu Komputer Universitas Subang, perlu ditetapkan keputusan secara resmi;',
                'Bahwa tenaga pendidik dan mahasiswa yang namanya tercantum dalam lampiran keputusan ini dipandang mampu dan memenuhi syarat;',
                'Bahwa berdasarkan pertimbangan sebagaimana dimaksud pada huruf a dan b, perlu menetapkan Keputusan Dekan Fakultas Ilmu Komputer.',
            ],
            'mengingat' => [
                'Undang-Undang Nomor 20 Tahun 2003 tentang Sistem Pendidikan Nasional;',
                'Undang-Undang Nomor 14 Tahun 2005 tentang Guru dan Dosen;',
                'Undang-Undang Nomor 12 Tahun 2012 tentang Perguruan Tinggi;',
                'Peraturan Pemerintah Nomor 4 Tahun 2014 tentang Penyelenggaraan Pendidikan dan Pengelolaan Perguruan Tinggi;',
                'Statuta Universitas Subang.',
            ],
            'memperhatikan' => [],
            'dictums' => [
                [
                    'label' => 'Pertama',
                    'text' => 'Menetapkan keputusan dan daftar terlampir sebagai bagian yang tidak terpisahkan dari surat keputusan ini.',
                ],
                [
                    'label' => 'Kedua',
                    'text' => 'Keputusan ini berlaku sejak tanggal ditetapkan dengan ketentuan apabila dikemudian hari ternyata terdapat kekeliruan dalam keputusan ini akan diperbaiki sebagaimana mestinya.',
                ],
            ],
            'tembusan_list' => [
                'Yayasan Kutawaringin Subang',
                'Rektor Universitas Subang',
                'Arsip',
            ],
            'mbkm_matrix' => [
                [
                    'mitra_name' => 'PT. DAHANA (PERSERO)',
                    'lecturer_name' => 'Bambang Tjahjo Utomo, MT',
                    'lecturer_nidn' => '0410019202',
                    'students' => [
                        ['name' => 'LIA KARTIKA', 'npm' => 'D1A230409'],
                        ['name' => 'NIKMAH AZIZAH', 'npm' => 'D1A230406'],
                    ],
                ],
            ],
            'teaching_lecturers' => [
                [
                    'name' => 'Bambang Tjahjo Utomo, MT',
                    'courses' => [
                        ['name' => 'APSI II', 'sks' => '3', 'semester' => '5', 'class' => 'A'],
                        ['name' => 'Bisnis Online', 'sks' => '3', 'semester' => '7', 'class' => 'A'],
                    ],
                ],
                [
                    'name' => 'Bagus Ali Akbar, S.Si., M.Kom',
                    'courses' => [
                        ['name' => 'Pemrograman Web II', 'sks' => '3', 'semester' => '4', 'class' => 'A'],
                        ['name' => 'Rekayasa Perangkat Lunak', 'sks' => '3', 'semester' => '4', 'class' => 'B'],
                    ],
                ],
            ],
            'ta_matrix' => [
                [
                    'name' => 'Bambang Tjahjo Utomo, MT',
                    'students' => [
                        ['npm' => 'D1A210001', 'name' => 'Budi Santoso', 'title' => 'Sistem Verifikasi Dokumen Digital Berbasis TTE'],
                    ],
                ],
            ],
            'wali_matrix' => [
                ['class' => 'Tingkat I / Semester 1', 'lecturer' => 'Bagus Ali Akbar, S.Si., M.Kom', 'students_count' => '42 Mahasiswa'],
                ['class' => 'Tingkat II / Semester 3', 'lecturer' => 'Bambang Tjahjo Utomo, MT', 'students_count' => '38 Mahasiswa'],
            ],
            'yudisium_matrix' => [
                ['no' => 1, 'npm' => 'D1A210001', 'name' => 'Budi Santoso', 'ipk' => '3.82', 'predicate' => 'Dengan Pujian'],
                ['no' => 2, 'npm' => 'D1A210005', 'name' => 'Siti Aminah', 'ipk' => '3.75', 'predicate' => 'Sangat Memuaskan'],
            ],
            'seminar_matrix' => [
                ['student_name' => 'Budi Santoso', 'npm' => 'D1A210001', 'examiner_1' => 'Dr. Tepi Peirisal, M.Si', 'examiner_2' => 'Bagus Ali Akbar, M.Kom', 'examiner_3' => 'Bambang Tjahjo Utomo, MT'],
            ],
            'sidang_matrix' => [
                ['student_name' => 'Budi Santoso', 'npm' => 'D1A210001', 'examiner_1' => 'Dr. Tepi Peirisal, M.Si', 'examiner_2' => 'Bagus Ali Akbar, M.Kom', 'examiner_3' => 'Bambang Tjahjo Utomo, MT'],
            ],
            'resignation_matrix' => [
                ['no' => 1, 'npm' => 'D1A210099', 'name' => 'Ahmad Fauzi', 'study_program' => 'Sistem Informasi', 'reason' => 'Pindah Bekerja ke Luar Daerah'],
            ],
            'gkm_chairman_name' => 'MAYA DESTRIANI, S.Kom., M.Kom',
            'ukm_chairman_name' => 'CACA ARIF HERDIAN, S.Kom., M.Kom',
        ];

        // Terapkan kustomisasi narasi konsiderans & diktum dari template jika ada
        if (! empty($previewType->opening_template)) {
            $lines = array_values(array_filter(array_map('trim', preg_split("/\r\n|\n|\r/", $previewType->opening_template))));
            if (! empty($lines)) {
                $extraData['menimbang'] = $lines;
            }
        }

        if (! empty($previewType->content_template)) {
            $dictumLines = array_values(array_filter(array_map('trim', preg_split("/\r\n|\n|\r/", $previewType->content_template))));
            if (! empty($dictumLines)) {
                $labels = ['Pertama', 'Kedua', 'Ketiga', 'Keempat', 'Kelima', 'Keenam'];
                $dictums = [];
                foreach ($dictumLines as $idx => $line) {
                    $lbl = $labels[$idx] ?? 'Ke-'.($idx + 1);
                    $dictums[] = [
                        'label' => $lbl,
                        'text' => $line,
                    ];
                }
                if (! empty($previewType->closing_template)) {
                    $dictums[] = [
                        'label' => $labels[count($dictums)] ?? 'Penutup',
                        'text' => trim($previewType->closing_template),
                    ];
                }
                $extraData['dictums'] = $dictums;
            }
        } elseif (! empty($previewType->closing_template)) {
            $extraData['dictums'][] = [
                'label' => 'Terakhir',
                'text' => trim($previewType->closing_template),
            ];
        }

        $letter = new LetterRequest([
            'user_id' => $user->id,
            'letter_type_id' => $previewType->id,
            'student_name' => 'Dr. TEPI PEIRISAL, M.Si',
            'npm' => 'SK-DEKAN',
            'study_program' => 'Sistem Informasi',
            'semester' => 1,
            'academic_year' => '2025/2026',
            'letter_number' => '027/D/FIK-US/X/2026',
            'signer_name' => 'Dr. TEPI PEIRISAL, M.Si',
            'signer_title' => 'Dekan',
            'status' => 'draft',
            'extra_data' => $extraData,
        ]);

        $letter->setRelation('letterType', $previewType);

        return $letter;
    }

    /**
     * Resolve default about_title for Studio SK.
     */
    protected function getDefaultSkAboutTitle(string $code): string
    {
        return match ($code) {
            'sk_dekan_dosen_mengajar' => "PENUNJUKAN DOSEN PENGAJAR SEMESTER GANJIL TAHUN AKADEMIK\n2025/2026\nPROGRAM STUDI SISTEM INFORMASI S1",
            'sk_dekan_pembimbing_ta' => "DOSEN PEMBIMBING TUGAS AKHIR\nFAKULTAS ILMU KOMPUTER UNIVERSITAS SUBANG\nSEMESTER GANJIL TAHUN AKADEMIK 2025/2026",
            'sk_dekan_pembimbing_mbkm' => "PENYELENGGARAAN DAN PENETAPAN DOSEN PEMBIMBING BKP MBKM\nSEMESTER GANJIL\nFAKULTAS ILMU KOMPUTER UNIVERSITAS SUBANG\nTAHUN AKADEMIK 2025/2026",
            'sk_dekan_dosen_wali' => "PENUNJUKAN DAN PENETAPAN DOSEN WALI AKADEMIK\nFAKULTAS ILMU KOMPUTER UNIVERSITAS SUBANG\nTAHUN AKADEMIK 2025/2026",
            'sk_dekan_yudisium' => "KELULUSAN UJIAN SIDANG SARJANA DAN PESERTA YUDISIUM\nPROGRAM STUDI SISTEM INFORMASI\nSEMESTER GENAP TAHUN AKADEMIK 2025/2026",
            'sk_dekan_penguji_sidang_ta' => "PELAKSANAAN PENGUJIAN SEMINAR TUGAS AKHIR\nFAKULTAS ILMU KOMPUTER UNIVERSITAS SUBANG\nTAHUN AKADEMIK 2025/2026",
            'sk_dekan_pengujian_sidang_skripsi' => "PELAKSANAAN PENGUJIAN SIDANG TUGAS AKHIR/SKRIPSI\nFAKULTAS ILMU KOMPUTER UNIVERSITAS SUBANG\nTAHUN AKADEMIK 2025/2026",
            'sk_dekan_pengunduran_diri' => "PENGUNDURAN DIRI MAHASISWA\nFAKULTAS ILMU KOMPUTER UNIVERSITAS SUBANG\nTAHUN AKADEMIK 2025/2026",
            'sk_dekan_gkm_ukm' => "PENUNJUKAN DAN PENGANGKATAN KETUA\nGUGUS KENDALI MUTU (GKM) PADA FAKULTAS DAN UNIT KENDALI MUTU\n(UKM) PADA PROGRAM STUDI SISTEM INFORMASI UNIVERSITAS SUBANG",
            default => "PENYELENGGARAAN KEGIATAN AKADEMIK\nFAKULTAS ILMU KOMPUTER UNIVERSITAS SUBANG\nTAHUN AKADEMIK 2025/2026",
        };
    }

    /**
     * Build sample LetterRequest for Studio MOU with complete bilateral data.
     */
    protected function buildSampleMouLetter(LetterType $previewType, $user): LetterRequest
    {
        $extraData = [
            'partner_name' => 'PT. Dahana (Persero) Subang',
            'partner_address' => 'Jl. Raya Subang KM 15, Cibogo, Subang',
            'partner_business_field' => 'Industri Manufaktur Bahan Berenergi Tinggi',
            'partner_official_name' => 'Ir. Hendra Wijaya, MM',
            'partner_official_position' => 'Direktur Sumber Daya Manusia',
            'partner_phone' => '(0260) 412345',
            'partner_email' => 'sdm@dahana.id',
            'faculty_official_name' => 'Dr. Tepi Peirisal, S.Sos, M.Si',
            'faculty_official_position' => 'Dekan',
            'faculty_nik' => '0410420142',
            'mou_title' => 'PENYELENGGARAAN PROGRAM MAGANG BERSERTIFIKAT - MBKM',
            'partner_mou_number' => '012/HRD-MOU/XI/2026',
            'faculty_mou_number' => '001/MOU/FIK-US/XI/2026',
            'signing_city' => 'Subang',
            'signing_date' => '2026-11-07',
            'signing_day_words' => 'Sabtu',
            'signing_date_words' => 'tujuh',
            'signing_month_words' => 'November',
            'signing_year_words' => 'dua ribu dua puluh enam',
            'signing_date_formatted' => '07-11-2026',
        ];

        // Terapkan kustomisasi narasi klausul & penutup MoU dari template jika ada
        if (! empty($previewType->content_template)) {
            $scopeLines = array_values(array_filter(array_map('trim', preg_split("/\r\n|\n|\r/", $previewType->content_template))));
            if (! empty($scopeLines)) {
                $extraData['pasal_3_scope'] = $scopeLines;
            }
        }
        if (! empty($previewType->closing_template)) {
            $extraData['pasal_10_text'] = trim($previewType->closing_template);
            $extraData['pasal_11_text2'] = trim($previewType->closing_template);
        }

        $letter = new LetterRequest([
            'user_id' => $user->id,
            'letter_type_id' => $previewType->id,
            'student_name' => 'Ir. Hendra Wijaya, MM (PT. Dahana Persero)',
            'npm' => 'MOU-MITRA',
            'study_program' => 'Fakultas Ilmu Komputer',
            'semester' => 1,
            'academic_year' => '2025/2026',
            'letter_number' => '001/MOU/FIK-US/XI/2026',
            'signer_name' => 'Dr. Tepi Peirisal, S.Sos, M.Si',
            'signer_title' => 'Dekan',
            'status' => 'draft',
            'extra_data' => $extraData,
        ]);

        $letter->setRelation('letterType', $previewType);

        return $letter;
    }

    /**
     * Build sample LetterRequest for Studio Surat with complete rich data.
     */
    protected function buildSampleAcademicLetter(LetterType $previewType, $user): LetterRequest
    {
        $code = $previewType->code;

        $isPns = ($code === 'keterangan_kuliah_pns');
        $isObservasi = ($code === 'observasi_matkul');
        $isSkripsi = ($code === 'izin_penelitian_skripsi');
        $isTugasMengajar = ($code === 'tugas_mengajar');
        $isMbkm = ($code === 'tugas_pembimbing_mbkm');
        $isTa = ($code === 'tugas_pembimbing_ta');
        $isTugas = ($isTugasMengajar || $isMbkm || $isTa);
        $isSeminarUp = ($code === 'izin_seminar_up');
        $isSidangTa = ($code === 'izin_sidang_ta');
        $isMagang = ($code === 'pengantar_magang');
        $isPkl = ($code === 'pengantar_pkl');
        $isIzinUjian = ($code === 'izin_mengikuti_ujian');
        $isUjianSusulan = ($code === 'ujian_susulan');
        $isPerbaikanNilai = ($code === 'perbaikan_nilai');
        $isYudisium = ($code === 'keterangan_yudisium');
        $isUndanganRapat = ($code === 'undangan_rapat_akademik');
        $isSarpras = in_array($code, ['pengadaan_sarpras', 'perbaikan_sarpras']);
        $isDinasFakultas = in_array($code, [
            'pengajuan_dana', 'pengadaan_sarpras', 'perbaikan_sarpras',
            'pemberitahuan_ujian_dosen', 'pengumuman_ujian_mahasiswa',
            'perbaikan_data_pddikti', 'pembukaan_periode', 'pengajuan_ttd_ijazah',
            'pengajuan_blanko_ijazah_transkrip', 'rekomendasi_pinjaman_bjb',
        ]);

        $signerName = $isDinasFakultas ? 'Dr. TEPI PEIRISAL, M.Si' : ($isTugas ? 'BAMBANG TJAHJO UTOMO, MT' : 'BAGUS ALI AKBAR, S.Si., M.Kom');
        $signerTitle = $isDinasFakultas ? 'Dekan' : ($isTugas ? 'Wakil Dekan' : 'Ka. Program Studi');

        $extraData = [
            'company_name' => 'PT. Dahana (Persero) Subang',
            'company_address' => 'Jl. Raya Subang KM 15, Cibogo, Subang',
            'target_recipient' => $isDinasFakultas
                ? 'Rektor Universitas Subang'
                : ($isIzinUjian
                    ? 'HRD Klinik Pratama Rawat Inap Larasati'
                    : ($isPkl
                        ? 'Kepala Sekolah SMP Negeri 1 Binong Kabupaten Subang'
                        : ($isMagang
                            ? 'Kepala Kantor Urusan Agama (KUA) Kecamatan Ciater Kabupaten Subang'
                            : ($isSidangTa || $isSeminarUp
                                ? 'HRD PT. Uwu Jump Indonesia'
                                : ($isSkripsi
                                    ? 'Kepala Kantor Desa Panyingkiran Kecamatan Purwadadi Kabupaten Subang'
                                    : 'Kepala Kantor Samsat Kabupaten Subang'))))),
            'target_agency' => $isDinasFakultas ? 'Universitas Subang' : 'PT. Dahana (Persero) Subang',
            'target_city' => 'Subang',
            'target_address' => 'Jl. R.A. Kartini KM 3 Subang',
            'internship_duration' => $isPkl ? '40 hari' : '5 bulan',
            'start_date' => $isPkl ? '06 Oktober 2025' : '14 September 2026',
            'end_date' => $isPkl ? '28 November 2025' : '09 Januari 2027',
            'internship_period' => '1 Juli 2026 s.d. 31 Agustus 2026',
            'course_name' => $isPerbaikanNilai ? 'APSI I' : ($isUjianSusulan ? 'Jaringan Komputer' : 'Konsep Sistem Informasi'),
            'lecturer_name' => $isPerbaikanNilai ? 'Ir. Syarif Hidayat, MT' : ($isUjianSusulan ? "ACHMAD SYAFA'AT, S.Kom., MT" : 'BAGUS ALI AKBAR, S.Si., M.Kom'),
            'lecturer_status' => 'Dosen Tetap',
            'nidn' => '0410019202',
            'exam_type' => $isUjianSusulan ? 'UTS' : 'UAS',
            'original_score' => $isPerbaikanNilai ? 'T' : '-',
            'semester_type' => 'Ganjil',
            'exam_dates' => '12-19 Januari 2026',
            'lampiran_count' => ($isIzinUjian ? '2 (dua) lembar' : ($isMagang ? '1 Lembar' : '-')),
            'activity_name' => $isSidangTa ? 'Sidang Tugas Akhir' : 'Seminar Usulan Penelitian',
            'event_day' => 'Kamis',
            'exam_schedules' => [
                ['course_name' => 'Audit Sistem Informasi', 'class_name' => '4A', 'sks' => 3, 'date' => '21-03-2025', 'time' => '08.00', 'room' => '8'],
            ],
            'teaching_schedules' => [
                ['course_name' => 'Desain WEB 1C', 'code' => 'D1A12004', 'sks' => 2, 'day' => 'Kamis', 'time' => '18.30-20.00', 'room' => 'Lab II'],
                ['course_name' => 'Kerja Praktek', 'code' => 'D1A73001', 'sks' => 3, 'day' => 'Sabtu', 'time' => '09.00-10.30', 'room' => '-'],
            ],
            'mbkm_students' => [
                ['name' => 'LIA KARTIKA', 'npm' => 'D1A230409', 'mitra_project' => 'Dicoding (Studi Independen)', 'role' => 'Pembimbing I'],
                ['name' => 'NIKMAH AZIZAH', 'npm' => 'D1A230406', 'mitra_project' => 'Dicoding (Studi Independen)', 'role' => 'Pembimbing I'],
            ],
            'ta_students' => [
                ['name' => 'Tiara Maharani Ar', 'npm' => 'D1A200403', 'role' => 'Pembimbing I'],
                ['name' => 'Riska Nuril Anwar', 'npm' => 'D1A230062', 'role' => 'Pembimbing I'],
            ],
            'group_members' => ($isSkripsi || $isTugas || $isSeminarUp || $isSidangTa) ? [] : [
                ['name' => 'ALVITO BAGASWARA', 'npm' => 'D1A260009', 'address' => 'SUBANG'],
                ['name' => 'M.FADHIL AZIZ', 'npm' => 'D1A260007', 'address' => 'SUBANG'],
            ],
            'thesis_title' => 'Rancang Bangun Sistem Informasi Pelayanan Desa Berbasis Web',
            'agenda' => 'Rapat Akademik',
            'meeting_day_date' => 'Selasa, 8 September 2026',
            'meeting_time' => '09.00 WIB',
            'meeting_type' => 'offline',
            'meeting_location' => 'Ruang 12 Fakultas Ilmu Komputer',
            'meeting_platform' => 'Google Meet',
            'meeting_link' => 'https://meet.google.com/abc-defg-hij',
            'recipient_name' => 'Bapak Bagus Ali Akbar, S.SI,. M.Kom',
            'defense_date_text' => 'Kamis, 27 Agustus 2026',
            'ipk' => $isYudisium ? '3.19' : '3.73',
            'study_duration' => '4 (Empat) Tahun',
            'defense_grade' => 'A',
            'defense_result' => 'Lulus',
            'yudisium_date_text' => 'Kamis, 27 Agustus 2026',
            'yudisium_predicate' => $isYudisium ? 'SANGAT MEMUASKAN' : 'Dengan Pujian',
            'sk_number' => '270 /D/FIK-US/VI/2026',
            'sk_date' => '26 Juni 2026',
            'degree_title' => 'Sarjana Komputer (S.Kom)',
            'parent_name' => $isPns ? 'NANA SUTRISNA, A.Md.P' : 'H. Maman Sulaeman, S.Sos',
            'parent_nip' => $isPns ? '3213052705580002' : '19750812 200212 1 003',
            'parent_rank' => $isPns ? 'Pembina Utama Muda IV/c' : 'Pembina / IV a',
            'parent_institution' => $isPns ? 'Dinas Pertanian Kabupaten Subang' : 'Dinas Pendidikan Kabupaten Subang',

            // Sarpras & Operasional Fields
            'unit_kerja' => 'FAKULTAS ILMU KOMPUTER',
            'sarpras_items' => [
                '1 Unit Proyektor EPSON EB-E500 Ruang Kuliah 8',
                '2 Unit Pendingin Ruangan (AC) Daikin 1.5 PK Lab Komputer II',
                '1 Set Kursi Kuliah Lipat (50 unit)',
            ],
            'considerations' => [
                'Mendukung kelancaran perkuliahan dan praktikum mahasiswa semester ganjil;',
                'Sarana sebelumnya telah mengalami kerusakan teknis berat dan tidak ekonomis diperbaiki.',
            ],
            'period_month_year' => 'November 2026',
            'nominal' => '15.000.000',
            'nominal_terbilang' => 'Lima Belas Juta Rupiah',
            'balance' => '2.500.000',
            'employee_name' => 'Bambang Tjahjo Utomo, MT',
            'employee_nik' => '0410019202',
            'employee_position' => 'Dosen Tetap',
            'loan_purpose' => 'Kredit Renovasi Rumah Tinggal',
            'bank_name' => 'Bank BJB Cabang Subang',
            'program_name' => 'Studi Independen Bersertifikat (SIB) Kampus Merdeka',
            'activity_period' => 'September 2026 - Januari 2027',
            'scholarship_name' => 'Beasiswa Prestasi Akademik Unggulan',
            'scholarship_provider' => 'Yayasan Kutawaringin Subang',
        ];

        // Ensure user custom template overrides are also applied into extra_data
        if (! empty($previewType->opening_template)) {
            $extraData['opening_template'] = $previewType->opening_template;
        }
        if (! empty($previewType->content_template)) {
            $extraData['content_template'] = $previewType->content_template;
        }
        if (! empty($previewType->closing_template)) {
            $extraData['closing_template'] = $previewType->closing_template;
        }

        $studentName = $isUndanganRapat
            ? 'Bapak Bagus Ali Akbar, S.SI,. M.Kom'
            : ($isYudisium
                ? 'HAFIDZ ARIMANSYAH'
                : ($isPerbaikanNilai
                    ? 'MOCHAMAD ALKASYAH'
                    : ($isUjianSusulan
                        ? 'NURUL FATURROHMA D'
                        : ($isIzinUjian
                            ? 'ANGGI ARIYANTI'
                            : ($isPkl
                                ? 'FUTYK CHUSNUL CHOTIMAH'
                                : ($isMagang
                                    ? 'SYAHRUL MUHAIMIN'
                                    : ($isSidangTa
                                        ? 'SITI MASITOH'
                                        : ($isPns
                                            ? 'TRISNALDI ELANGGA'
                                            : ($isSeminarUp
                                                ? 'SITI MASITOH'
                                                : ($isSkripsi
                                                    ? 'AGUS CAKRA DINATA'
                                                    : ($isTugas
                                                        ? 'BAGUS ALI AKBAR, S.Si., M.Kom'
                                                        : ($isObservasi
                                                            ? 'REDZY ANUGERAH'
                                                            : 'Budi Santoso, S.Kom'))))))))))));

        $npm = $isUndanganRapat
            ? '-'
            : ($isYudisium
                ? 'D1A220028'
                : ($isPerbaikanNilai
                    ? 'DIA.18.0040'
                    : ($isUjianSusulan
                        ? 'D1A.220016'
                        : ($isIzinUjian
                            ? 'D1A252001'
                            : ($isPkl
                                ? 'D1A220013'
                                : ($isMagang
                                    ? 'D1A230080'
                                    : ($isSidangTa
                                        ? 'D1A230421'
                                        : ($isPns
                                            ? 'D1A231018'
                                            : ($isSeminarUp
                                                ? 'D1A230421'
                                                : ($isSkripsi
                                                    ? 'D1A2300036'
                                                    : ($isTugas
                                                        ? '0410019202'
                                                        : ($isObservasi
                                                            ? 'D1A260008'
                                                            : 'D1A210001'))))))))))));

        $sampleLetter = new LetterRequest([
            'user_id' => $user->id,
            'letter_type_id' => $previewType->id,
            'student_name' => $studentName,
            'npm' => $npm,
            'study_program' => 'Sistem Informasi',
            'birth_place' => 'Subang',
            'birth_date' => $isPns ? '2004-01-12' : '2003-05-14',
            'address' => ($isObservasi || $isSkripsi || $isSeminarUp || $isSidangTa || $isMagang || $isPkl || $isIzinUjian || $isUjianSusulan || $isPerbaikanNilai || $isYudisium || $isUndanganRapat) ? 'SUBANG' : 'Jl. R.A. Kartini No. 12 Subang, Jawa Barat',
            'semester' => ($isPns || $isSeminarUp || $isSidangTa || $isSkripsi || $isMagang || $isPkl || $isPerbaikanNilai || $isYudisium || $isUndanganRapat) ? 7 : ($isTugas ? 1 : ($isObservasi ? 1 : ($isIzinUjian ? 1 : ($isUjianSusulan ? 3 : 6)))),
            'academic_year' => '2025/2026',
            'purpose' => 'Kelengkapan Berkas Administrasi Akademik',
            'letter_number' => '024/'.($previewType->letter_code ?: 'PD.1.2/FIK-US').'/X/'.now()->year,
            'signer_name' => $signerName,
            'signer_title' => $signerTitle,
            'status' => 'draft',
            'extra_data' => $extraData,
        ]);

        $sampleLetter->setRelation('letterType', $previewType);

        return $sampleLetter;
    }
}
