<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Lecturer;
use App\Models\LetterRequest;
use App\Models\LetterType;
use App\Models\SignatureField;
use App\Models\SigningOfficial;
use FPDF;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AcademicLetterService
{
    protected float $lastSignatureY = 175;

    protected float $pageOneSignatureY = 175;

    /**
     * Official Signers of Fakultas Ilmu Komputer Universitas Subang.
     *
     * @return array<int, array{id: string, code: string, role_title: string, signature_title: string, name: string, display_name: string, nip: ?string, email: ?string, description: ?string, is_active: bool, order: int}>
     */
    public static function getAvailableSigners(bool $activeOnly = true): array
    {
        try {
            if (Schema::hasTable('signing_officials')) {
                $query = SigningOfficial::query();
                if ($activeOnly) {
                    $query->active();
                }
                $officials = $query->ordered()->get();

                if ($officials->isNotEmpty()) {
                    return $officials->map(fn ($official) => [
                        'id' => $official->code,
                        'code' => $official->code,
                        'db_id' => $official->id,
                        'role_title' => $official->role_title,
                        'signature_title' => $official->signature_title,
                        'name' => $official->name,
                        'display_name' => $official->display_name ?: $official->name,
                        'nip' => $official->nip,
                        'email' => $official->email,
                        'description' => $official->description,
                        'is_active' => (bool) $official->is_active,
                        'order' => (int) $official->order,
                    ])->values()->all();
                }
            }
        } catch (\Throwable) {
            // Fallback to static defaults
        }

        return [
            [
                'id' => 'kaprodi',
                'code' => 'kaprodi',
                'role_title' => 'Ketua Program Studi',
                'signature_title' => 'Ka. Program Studi',
                'name' => 'BAGUS ALI AKBAR, S.Si., M.Kom',
                'display_name' => 'Bagus Ali Akbar, S.Si., M.Kom',
                'nip' => '0410019202',
                'email' => 'bagusaliakbar@unsub.ac.id',
                'description' => 'Ketua Program Studi Sistem Informasi',
                'is_active' => true,
                'order' => 1,
            ],
            [
                'id' => 'dekan',
                'code' => 'dekan',
                'role_title' => 'Dekan',
                'signature_title' => 'Dekan',
                'name' => 'Dr. TEPI PEIRISAL, M.Si',
                'display_name' => 'Dr. Tepi Peirisal, M.Si',
                'nip' => '0415057001',
                'email' => 'tepipeirisal@unsub.ac.id',
                'description' => 'Dekan Fakultas Ilmu Komputer',
                'is_active' => true,
                'order' => 2,
            ],
            [
                'id' => 'wadek',
                'code' => 'wadek',
                'role_title' => 'Wakil Dekan',
                'signature_title' => 'Wakil Dekan',
                'name' => 'BAMBANG TJAHJO UTOMO, MT',
                'display_name' => 'Bambang Tjahjo Utomo, MT',
                'nip' => '0408086801',
                'email' => 'bambangtjahjoutomo@unsub.ac.id',
                'description' => 'Wakil Dekan Fakultas Ilmu Komputer',
                'is_active' => true,
                'order' => 3,
            ],
        ];
    }

    /**
     * Resolve email for the given letter's signer.
     */
    public function resolveSignerEmail(LetterRequest $letter): string
    {
        // Khusus Surat Pernyataan Kesanggupan Mengajar & Fakta Integritas: penandatangan adalah Dosen yang bersangkutan
        if (in_array($letter->letterType?->code, ['kesanggupan_mengajar', 'pakta_integritas'])) {
            $extra = $letter->extra_data ?? [];
            if (! empty($extra['lecturer_email'])) {
                return $extra['lecturer_email'];
            }
            if (! empty($extra['email'])) {
                return $extra['email'];
            }
            // Check lecturer model by nidn or name
            if (! empty($extra['nidn'])) {
                $lecturer = Lecturer::where('nidn', $extra['nidn'])->first();
                if ($lecturer && $lecturer->email) {
                    return $lecturer->email;
                }
            }
        }

        $title = strtolower($letter->signer_title ?? '');
        $name = strtolower($letter->signer_name ?? '');

        // 1. Try to find matched official in database
        try {
            if (Schema::hasTable('signing_officials')) {
                $officials = SigningOfficial::query()->active()->get();
                foreach ($officials as $official) {
                    $offName = strtolower($official->name ?? '');
                    $offDisp = strtolower($official->display_name ?? '');
                    $offRole = strtolower($official->role_title ?? '');
                    $offSig = strtolower($official->signature_title ?? '');

                    if ($name && ($name === $offName || $name === $offDisp || str_contains($name, $offName) || str_contains($offName, $name))) {
                        return $official->email;
                    }

                    if ($title && ($title === $offRole || $title === $offSig || str_contains($title, $offRole) || str_contains($offRole, $title))) {
                        return $official->email;
                    }
                }
            }
        } catch (\Throwable) {
            // fallback below
        }

        if (str_contains($title, 'wakil') || str_contains($name, 'bambang')) {
            return 'bambangtjahjoutomo@unsub.ac.id';
        }

        if (str_contains($title, 'dekan') || str_contains($name, 'tepi')) {
            return 'tepipeirisal@unsub.ac.id';
        }

        return 'bagusaliakbar@unsub.ac.id';
    }

    public function __construct(
        protected PdfSignerService $pdfSignerService
    ) {}

    /**
     * Generate the next official letter number.
     */
    public function generateLetterNumber(LetterRequest $letter): string
    {
        $year = now()->year;
        $month = now()->month;
        $romanMonths = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
        ];
        $romanMonth = $romanMonths[$month] ?? 'X';

        $letterCode = $letter->letterType->letter_code ?? 'PD.1.2/FIK-US';

        // Count existing letters of this year
        $count = LetterRequest::whereYear('created_at', $year)
            ->whereNotNull('letter_number')
            ->count() + 1;

        $paddedNumber = str_pad((string) $count, 3, '0', STR_PAD_LEFT);

        return "{$paddedNumber}/{$letterCode}/{$romanMonth}/{$year}";
    }

    /**
     * Generate standard Fasilkom UNSUB PDF for a letter request.
     */
    public function generatePdf(LetterRequest $letter): string
    {
        $noLetterNumberTypes = ['kesanggupan_mengajar', 'pakta_integritas'];
        $isNoNumber = in_array($letter->letterType?->code, $noLetterNumberTypes);

        if ($isNoNumber) {
            $letter->letter_number = null;
            $letter->save();
        } elseif (empty($letter->letter_number)) {
            $letter->letter_number = $this->generateLetterNumber($letter);
            $letter->save();
        }

        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->SetMargins(20, 15, 20);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();

        // 1. KOP SURAT RESMI FASILKOM UNIVERSITAS SUBANG
        $this->renderHeader($pdf);

        // 2. JUDUL SURAT & NOMOR
        $this->renderTitle($pdf, $letter);

        // 3. KATA PEMBUKA & DATA MAHASISWA
        $this->renderBody($pdf, $letter);

        // 4. BLOK TANDA TANGAN KAPRODI
        $this->renderSignatureBlock($pdf, $letter);
        $this->pageOneSignatureY = $this->lastSignatureY;

        // 5. HALAMAN 2: LAMPIRAN (Jika ada, seperti pada Surat Pengantar Magang MBKM & Surat Izin Mengikuti Ujian)
        $this->renderAttachmentPageIfNeeded($pdf, $letter);

        // Save PDF to storage
        $directory = 'letters/'.$letter->user_id;
        Storage::disk('local')->makeDirectory($directory);

        $filename = 'SURAT_'.Str::slug($letter->letterType->code).'_'.time().'.pdf';
        $relativePath = $directory.'/'.$filename;
        $absolutePath = Storage::disk('local')->path($relativePath);

        $pdf->Output('F', $absolutePath);

        // Link to or update Document model for E-Sign pipeline
        $sha256 = hash_file('sha256', $absolutePath);
        $document = null;

        $totalPages = $pdf->PageNo();

        if ($letter->document_id && $existingDoc = Document::find($letter->document_id)) {
            $document = $existingDoc;
            if ($document->file_path && $document->file_path !== $relativePath && Storage::disk('local')->exists($document->file_path)) {
                Storage::disk('local')->delete($document->file_path);
            }
            $document->update([
                'original_filename' => $filename,
                'file_path' => $relativePath,
                'file_size' => filesize($absolutePath),
                'total_pages' => $totalPages,
                'sha256_original' => $sha256,
                'metadata' => array_merge($document->metadata ?? [], [
                    'letter_request_id' => $letter->id,
                    'letter_number' => $letter->letter_number,
                    'letter_type' => $letter->letterType->name,
                    'npm' => $letter->npm,
                    'signer_title' => $letter->signer_title,
                    'signer_name' => $letter->signer_name,
                ]),
            ]);
        } else {
            $document = Document::create([
                'user_id' => $letter->user_id,
                'title' => $letter->letterType->name.' - '.$letter->student_name,
                'original_filename' => $filename,
                'file_path' => $relativePath,
                'file_size' => filesize($absolutePath),
                'total_pages' => $totalPages,
                'sha256_original' => $sha256,
                'status' => 'draft',
                'signing_method' => 'visual_and_qr',
                'psre_provider' => 'local',
                'metadata' => [
                    'letter_request_id' => $letter->id,
                    'letter_number' => $letter->letter_number,
                    'letter_type' => $letter->letterType->name,
                    'npm' => $letter->npm,
                    'signer_title' => $letter->signer_title,
                    'signer_name' => $letter->signer_name,
                ],
            ]);

            $letter->update([
                'document_id' => $document->id,
            ]);
        }

        // Place or update automatic QR Code & Signature field in the signature block (Page 1, ~Bottom Right)
        $signerEmail = $this->resolveSignerEmail($letter);
        $posYPercent = round((($this->pageOneSignatureY + 18.5) / 297) * 100, 1);

        $field = SignatureField::where('document_id', $document->id)
            ->where('role', 'signer')
            ->first();

        if ($field) {
            $field->update([
                'signer_name' => $letter->signer_name,
                'signer_email' => $signerEmail,
                'pos_y' => $posYPercent,
            ]);
        } else {
            SignatureField::create([
                'document_id' => $document->id,
                'user_id' => $letter->user_id,
                'signer_name' => $letter->signer_name,
                'signer_email' => $signerEmail,
                'role' => 'signer',
                'page_number' => 1,
                'pos_x' => 58,
                'pos_y' => $posYPercent,
                'width' => 11,
                'height' => 6.5,
                'field_type' => 'qr_code',
                'status' => 'pending',
            ]);
        }

        return $relativePath;
    }

    /**
     * Render Kop Surat Resmi Fasilkom Universitas Subang.
     */
    protected function renderHeader(FPDF $pdf): void
    {
        $kopPath = public_path('images/logos/kop-surat-fasilkom.png');
        if (file_exists($kopPath)) {
            // Lebar printable area A4 = 170mm (margin kiri 20mm, kanan 20mm: 210 - 40 = 170mm)
            // Rasio gambar cropped 993 x 207 = 4.7971 => Tinggi = 170 / 4.7971 = 35.44mm
            $pdf->Image($kopPath, 20, 11, 170, 35.44);
            $pdf->SetY(53);

            return;
        }

        $logoPath = public_path('images/logos/universitas-subang.png');
        if (file_exists($logoPath)) {
            $pdf->Image($logoPath, 20, 10, 26, 26);
        }

        $pdf->SetXY(48, 10);
        $pdf->SetFont('Times', '', 14);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(142, 5.5, 'UNIVERSITAS SUBANG', 0, 1, 'C');

        $pdf->SetX(48);
        $pdf->SetFont('Times', 'B', 17);
        $pdf->Cell(142, 6.8, 'FAKULTAS ILMU KOMPUTER', 0, 1, 'C');

        $pdf->SetX(48);
        $pdf->SetFont('Helvetica', 'B', 9.5);
        $pdf->Cell(142, 4.8, 'Akreditasi BAIK SEKALI No. 110/SK/LAM-INFOKOM/Ak/S/VIII/2025', 0, 1, 'C');

        $pdf->SetX(48);
        $pdf->SetFont('Times', '', 9.5);
        $pdf->Cell(142, 4.2, 'Jalan R.A Kartini KM 3 Telp (0260) 411415 Subang', 0, 1, 'C');

        $pdf->SetX(48);
        $pdf->SetFont('Times', '', 9.5);
        $wPrefix = $pdf->GetStringWidth('E-Mail : ');
        $wEmail = $pdf->GetStringWidth('fasilkom@unsub.ac.id');
        $totalW = $wPrefix + $wEmail;
        $startX = 48 + ((142 - $totalW) / 2);

        $pdf->SetX($startX);
        $pdf->Cell($wPrefix, 4.2, 'E-Mail : ', 0, 0, 'L');
        $pdf->SetTextColor(0, 0, 238);
        $pdf->SetFont('Times', 'U', 9.5);
        $pdf->Cell($wEmail, 4.2, 'fasilkom@unsub.ac.id', 0, 1, 'L');

        // Double Line Kop Surat (Black)
        $y = 39.5;
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.85);
        $pdf->Line(20, $y, 190, $y);

        $pdf->SetLineWidth(0.25);
        $pdf->Line(20, $y + 1.1, 190, $y + 1.1);

        $pdf->SetY(48);
    }

    /**
     * Render Letter Title and Number.
     */
    protected function renderTitle(FPDF $pdf, LetterRequest $letter): void
    {
        // Surat Pengantar Observasi Mata Kuliah, Penelitian Skripsi, Seminar UP, Sidang TA, Pengantar Magang, Pengantar PKL, Izin Mengikuti Ujian, Pengantar Ujian Susulan, & Pengantar Perbaikan Nilai use official memo-style header (Nomor, Lampiran, Perihal, Kepada Yth.)
        if ($letter->letterType && in_array($letter->letterType->code, ['observasi_matkul', 'izin_penelitian_skripsi', 'izin_seminar_up', 'izin_sidang_ta', 'pengantar_magang', 'pengantar_pkl', 'izin_mengikuti_ujian', 'ujian_susulan', 'perbaikan_nilai', 'undangan_rapat_akademik', 'pengajuan_dana', 'pengadaan_sarpras', 'perbaikan_sarpras', 'pemberitahuan_ujian_dosen', 'perbaikan_data_pddikti', 'pembukaan_periode', 'pengajuan_ttd_ijazah', 'pengajuan_blanko_ijazah_transkrip'])) {
            $this->renderObservasiHeaderMeta($pdf, $letter);

            return;
        }

        if ($letter->letterType && in_array($letter->letterType->code, ['tugas_mengajar', 'tugas_pembimbing_mbkm', 'tugas_pembimbing_ta'])) {
            $pdf->SetFont('Helvetica', 'BU', 12);
            $pdf->SetTextColor(15, 23, 42);
            $pdf->Cell(170, 5, 'SURAT TUGAS', 0, 1, 'C');

            $pdf->SetFont('Helvetica', '', 9.5);
            $pdf->SetTextColor(51, 65, 85);
            $pdf->Cell(170, 4.5, 'Nomor : '.$letter->letter_number, 0, 1, 'C');

            $pdf->Ln(6);

            return;
        }

        // Khusus Surat Pernyataan Kesanggupan Mengajar (Judul Huruf Kapital Bold tanpa Nomor di bawah judul, format pernyataan resmi)
        if ($letter->letterType && $letter->letterType->code === 'kesanggupan_mengajar') {
            $pdf->SetFont('Helvetica', 'B', 12);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(170, 6, 'SURAT PERNYATAAN KESANGGUPAN MENGAJAR', 0, 1, 'C');
            $pdf->Ln(6);

            return;
        }

        // Khusus Dokumen Fakta Integritas (Judul Center Bergaris Bawah + Subjudul 3 Baris Bold Kapital)
        if ($letter->letterType && $letter->letterType->code === 'pakta_integritas') {
            $pdf->SetFont('Helvetica', 'BU', 12);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(170, 6, 'FAKTA INTEGRITAS', 0, 1, 'C');
            $pdf->Ln(2);

            $pdf->SetFont('Helvetica', 'B', 11);
            $pdf->Cell(170, 5, 'DOSEN PENGAMPU MATA KULIAH', 0, 1, 'C');
            $prodi = mb_strtoupper($letter->study_program ?: 'Sistem Informasi', 'UTF-8');
            $pdf->Cell(170, 5, 'PROGRAM STUDI '.$prodi, 0, 1, 'C');
            $pdf->Cell(170, 5, 'FAKULTAS ILMU KOMPUTER UNIVERSITAS SUBANG', 0, 1, 'C');
            $pdf->Ln(7);

            return;
        }

        // Khusus Surat Pengumuman UTS/UAS ke Mahasiswa (Judul PENGUMUMAN Center Bergaris Bawah + Nomor di bawahnya)
        if ($letter->letterType && $letter->letterType->code === 'pengumuman_ujian_mahasiswa') {
            $pdf->SetFont('Helvetica', 'BU', 14);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(170, 6, 'PENGUMUMAN', 0, 1, 'C');

            $pdf->SetFont('Helvetica', '', 10.5);
            $pdf->Cell(170, 5, 'Nomor : '.$letter->letter_number, 0, 1, 'C');
            $pdf->Ln(7);

            return;
        }

        // Khusus Surat Rekomendasi (Pinjaman BJB, Rekomendasi Beasiswa, & Pembelajaran Luar Prodi) (Judul "SURAT REKOMENDASI" + Nomor)
        if ($letter->letterType && in_array($letter->letterType->code, ['rekomendasi_pinjaman_bjb', 'rekomendasi_beasiswa', 'rekomendasi_luar_prodi'])) {
            $pdf->SetFont('Helvetica', 'BU', 12);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(170, 6, 'SURAT REKOMENDASI', 0, 1, 'C');

            $pdf->SetFont('Helvetica', '', 10);
            $rawNumber = $letter->letter_number ?: ('/'.($letter->letterType->letter_code ?? 'PD.1.6/FIK-US').'/'.Carbon::now()->format('m/Y'));
            // Remove redundant prefix if already starts with Nomor / No
            $cleanNumber = preg_replace('/^(Nomor|No)\s*:\s*/i', '', $rawNumber);
            $prefix = ($letter->letterType->code === 'rekomendasi_beasiswa') ? 'NOMOR: ' : 'Nomor : ';
            $pdf->Cell(170, 5, $prefix.$cleanNumber, 0, 1, 'C');
            $pdf->Ln(6);

            return;
        }

        // Khusus Surat Keterangan Lulus (Judul "SURAT KETERANGAN LULUS" Bergaris Bawah + Nomor)
        if ($letter->letterType && $letter->letterType->code === 'keterangan_yudisium') {
            $pdf->SetFont('Helvetica', 'BU', 12);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(170, 6, 'SURAT KETERANGAN LULUS', 0, 1, 'C');

            $pdf->SetFont('Helvetica', '', 10);
            $rawNumber = $letter->letter_number ?: ('/'.($letter->letterType->letter_code ?? 'D/FIK-US').'/'.Carbon::now()->format('m/Y'));
            $cleanNumber = preg_replace('/^(Nomor|No)\s*:\s*/i', '', $rawNumber);
            $pdf->Cell(170, 5, 'Nomor : '.$cleanNumber, 0, 1, 'C');
            $pdf->Ln(6);

            return;
        }

        $title = mb_strtoupper($letter->letterType->name, 'UTF-8');

        $pdf->SetFont('Helvetica', 'BU', 11.5);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(170, 5, $title, 0, 1, 'C');

        $pdf->SetFont('Helvetica', '', 9.5);
        $pdf->SetTextColor(51, 65, 85);
        $pdf->Cell(170, 4.5, 'Nomor : '.$letter->letter_number, 0, 1, 'C');

        $pdf->Ln(6);
    }

    /**
     * Render official memo-style metadata (Nomor, Lampiran, Perihal, Kepada Yth.) for Observasi & Skripsi.
     */
    protected function renderObservasiHeaderMeta(FPDF $pdf, LetterRequest $letter): void
    {
        $extra = $letter->extra_data ?? [];

        $pdf->SetX(20);
        $pdf->SetFont('Helvetica', '', 9.5);
        $pdf->SetTextColor(15, 23, 42);

        // Khusus Surat Undangan Rapat Akademik: urutan Nomor, Perihal, Lampiran
        if ($letter->letterType?->code === 'undangan_rapat_akademik') {
            // Nomor
            $pdf->Cell(20, 4.2, 'Nomor', 0, 0, 'L');
            $pdf->Cell(4, 4.2, ':', 0, 0, 'C');
            $pdf->Cell(146, 4.2, $letter->letter_number, 0, 1, 'L');

            // Perihal
            $subject = ! empty($extra['agenda']) ? 'Undangan '.$extra['agenda'] : 'Undangan Rapat Akademik';
            $pdf->Cell(20, 4.2, 'Perihal', 0, 0, 'L');
            $pdf->Cell(4, 4.2, ':', 0, 0, 'C');
            $pdf->Cell(146, 4.2, $subject, 0, 1, 'L');

            // Lampiran
            $lampiranStr = ! empty($extra['lampiran_count']) ? $extra['lampiran_count'] : '-';
            $pdf->Cell(20, 4.2, 'Lampiran', 0, 0, 'L');
            $pdf->Cell(4, 4.2, ':', 0, 0, 'C');
            $pdf->Cell(146, 4.2, $lampiranStr, 0, 1, 'L');

            $pdf->Ln(4.5);

            // Kepada Yth. & Penerima
            $pdf->SetFont('Helvetica', '', 9.5);
            $pdf->Cell(170, 4.2, 'Kepada Yth.', 0, 1, 'L');

            $recipientName = ! empty($extra['recipient_name'])
                ? $extra['recipient_name']
                : (! empty($letter->student_name) ? $letter->student_name : 'Bapak Bagus Ali Akbar, S.SI,. M.Kom');

            $pdf->SetFont('Helvetica', 'B', 9.5);
            $pdf->Cell(170, 4.5, $recipientName, 0, 1, 'L');

            $pdf->SetFont('Helvetica', '', 9.5);
            $pdf->Cell(170, 4.2, 'di', 0, 1, 'L');
            $pdf->Cell(170, 4.2, 'Tempat', 0, 1, 'L');

            $pdf->Ln(5);

            return;
        }

        // Nomor
        $pdf->Cell(20, 4.2, 'Nomor', 0, 0, 'L');
        $pdf->Cell(4, 4.2, ':', 0, 0, 'C');
        $pdf->Cell(146, 4.2, $letter->letter_number, 0, 1, 'L');

        // Lampiran
        $isMagang = ($letter->letterType?->code === 'pengantar_magang');
        $isPkl = ($letter->letterType?->code === 'pengantar_pkl');
        $isIzinUjian = ($letter->letterType?->code === 'izin_mengikuti_ujian');
        $isUjianSusulan = ($letter->letterType?->code === 'ujian_susulan');
        $isPerbaikanNilai = ($letter->letterType?->code === 'perbaikan_nilai');
        $isPengajuanDana = ($letter->letterType?->code === 'pengajuan_dana');
        $isPengadaanSarpras = ($letter->letterType?->code === 'pengadaan_sarpras');
        $isPerbaikanSarpras = ($letter->letterType?->code === 'perbaikan_sarpras');
        $isPemberitahuanUjianDosen = ($letter->letterType?->code === 'pemberitahuan_ujian_dosen');
        $isPerbaikanDataPddikti = ($letter->letterType?->code === 'perbaikan_data_pddikti');
        $isPembukaanPeriode = ($letter->letterType?->code === 'pembukaan_periode');
        $isPengajuanTtdIjazah = ($letter->letterType?->code === 'pengajuan_ttd_ijazah');
        $isPengajuanBlanko = ($letter->letterType?->code === 'pengajuan_blanko_ijazah_transkrip');

        if ($isPengajuanBlanko) {
            $lampiranStr = ! empty($extra['lampiran_count']) ? $extra['lampiran_count'] : '1 (satu) berkas';
        } elseif ($isPengajuanTtdIjazah) {
            $lampiranStr = ! empty($extra['lampiran_count']) ? $extra['lampiran_count'] : '1 (satu) bendel';
        } elseif ($isPembukaanPeriode) {
            $lampiranStr = ! empty($extra['lampiran_count']) ? $extra['lampiran_count'] : '1 (satu) berkas';
        } elseif ($isPerbaikanDataPddikti) {
            $lampiranStr = ! empty($extra['lampiran_count']) ? $extra['lampiran_count'] : '1 Rangkap';
        } elseif ($isPengajuanDana) {
            $lampiranStr = ! empty($extra['lampiran_count']) ? $extra['lampiran_count'] : '1 (Satu) Berkas';
        } elseif ($isPengadaanSarpras) {
            $lampiranStr = ! empty($extra['lampiran_count']) ? $extra['lampiran_count'] : '1 Lembar';
        } elseif ($isPerbaikanSarpras || $isPemberitahuanUjianDosen) {
            $lampiranStr = ! empty($extra['lampiran_count']) ? $extra['lampiran_count'] : '-';
        } elseif ($isMagang) {
            $lampiranStr = '1 Lembar';
        } elseif ($isIzinUjian) {
            $lampiranStr = ! empty($extra['lampiran_count']) ? $extra['lampiran_count'] : '2 (dua) lembar';
        } else {
            $lampiranStr = '-';
        }

        $pdf->Cell(20, 4.2, 'Lampiran', 0, 0, 'L');
        $pdf->Cell(4, 4.2, ':', 0, 0, 'C');
        $pdf->Cell(146, 4.2, $lampiranStr, 0, 1, 'L');

        // Perihal (Bold)
        $isSkripsi = ($letter->letterType?->code === 'izin_penelitian_skripsi');
        $isSeminarUp = ($letter->letterType?->code === 'izin_seminar_up');
        $isSidangTa = ($letter->letterType?->code === 'izin_sidang_ta');

        if ($isPengajuanBlanko) {
            $defaultSubject = 'Pengajuan Blanko Ijazah dan Blanko Transkrip';
            $subject = ! empty($extra['subject']) ? $extra['subject'] : $defaultSubject;
        } elseif ($isPengajuanTtdIjazah) {
            $defaultSubject = 'Pengajuan Penandatanganan Ijazah';
            $subject = ! empty($extra['subject']) ? $extra['subject'] : $defaultSubject;
        } elseif ($isPembukaanPeriode) {
            $defaultSubject = 'Pengajuan Pembukaan Periode Tipe 2';
            $subject = ! empty($extra['subject']) ? $extra['subject'] : $defaultSubject;
        } elseif ($isPerbaikanDataPddikti) {
            $defaultSubject = 'Pengajuan Perubahan Data Mahasiswa';
            $subject = ! empty($extra['subject']) ? $extra['subject'] : $defaultSubject;
        } elseif ($isPemberitahuanUjianDosen) {
            $examType = $extra['exam_type'] ?? 'Ujian Tengah Semester';
            $semType = $extra['semester_type'] ?? 'Ganjil';
            $ta = $extra['academic_year'] ?? '2026/2027';
            $defaultSubject = "Pemberitahuan {$examType} {$semType} TA.{$ta}";
            $subject = ! empty($extra['subject']) ? $extra['subject'] : $defaultSubject;
        } elseif ($isPerbaikanSarpras) {
            $defaultSubject = 'Permohonan Pergantian Sarana dan Prasarana Fakultas Ilmu Komputer';
            $subject = ! empty($extra['subject']) ? $extra['subject'] : $defaultSubject;
        } elseif ($isPengadaanSarpras) {
            $items = $extra['sarpras_items'] ?? [];
            $validItems = [];
            if (is_array($items)) {
                foreach ($items as $it) {
                    $itText = is_array($it) ? ($it['name'] ?? ($it['text'] ?? '')) : (string) $it;
                    if (trim($itText) !== '') {
                        $validItems[] = trim($itText);
                    }
                }
            }
            if (! empty($validItems)) {
                $defaultSubject = 'Permohonan Pengadaan '.implode(', ', $validItems);
            } else {
                $defaultSubject = 'Permohonan Penambahan 2 Unit Komputer dan 1 Unit Printer BAAK Fakultas Ilmu Komputer Universitas Subang';
            }
            $subject = ! empty($extra['subject']) ? $extra['subject'] : $defaultSubject;
        } elseif ($isPengajuanDana) {
            $monthPeriod = $extra['period_month_year'] ?? 'November 2026';
            $subject = 'Pengajuan Kebutuhan bulan '.$monthPeriod;
        } elseif ($isMagang) {
            $subject = 'Surat Pengantar Magang';
        } elseif ($isPkl) {
            $subject = 'Izin Pengantar Praktek Kerja Lapangan';
        } elseif ($isIzinUjian) {
            $examType = strtoupper($extra['exam_type'] ?? 'UAS');
            $defaultSubject = ($examType === 'UTS') ? 'Permohonan Izin Mengikuti UTS' : 'Permohonan Izin Mengikuti UAS';
            $subject = ! empty($extra['subject']) ? $extra['subject'] : $defaultSubject;
        } elseif ($isUjianSusulan) {
            $examType = strtoupper($extra['exam_type'] ?? 'UTS');
            $subject = ($examType === 'UAS') ? 'UAS Susulan' : 'UTS Susulan';
        } elseif ($isPerbaikanNilai) {
            $defaultSubject = 'Perbaikan Nilai';
            $subject = ! empty($extra['subject']) ? $extra['subject'] : $defaultSubject;
        } elseif ($isSidangTa) {
            $defaultSubject = 'Permohonan Izin Mengikuti Sidang Tugas Akhir';
            $subject = ! empty($extra['subject']) ? $extra['subject'] : $defaultSubject;
        } elseif ($isSeminarUp) {
            $defaultSubject = 'Permohonan Izin Mengikuti Seminar Usulan Penelitian';
            $subject = ! empty($extra['subject']) ? $extra['subject'] : $defaultSubject;
        } elseif ($isSkripsi) {
            $subject = 'Permohonan Izin Penelitian Skripsi';
        } else {
            $subject = 'Pengantar Izin Observasi';
        }

        $pdf->Cell(20, 4.2, 'Perihal', 0, 0, 'L');
        $pdf->Cell(4, 4.2, ':', 0, 0, 'C');
        $pdf->SetFont('Helvetica', 'B', 9.5);
        $pdf->Cell(146, 4.2, $subject, 0, 1, 'L');

        $pdf->Ln(3.5);

        // Kepada Yth. & Penerima
        if ($isPengajuanTtdIjazah || $isPengajuanBlanko) {
            $defaultRecipient = 'Rektor Universitas Subang';
            $recipient = ! empty($extra['target_recipient']) ? $extra['target_recipient'] : $defaultRecipient;
            $city = ! empty($extra['target_city']) ? $extra['target_city'] : 'Subang';

            $pdf->SetFont('Helvetica', '', 9.5);
            $pdf->Cell(170, 4.2, 'Kepada Yth.', 0, 1, 'L');
            $pdf->MultiCell(170, 4.5, $recipient, 0, 'L');
            $pdf->Cell(170, 4.2, 'Di', 0, 1, 'L');
            $pdf->Cell(170, 4.2, $city, 0, 1, 'L');
            $pdf->Ln(3.5);

            return;
        }

        if ($isPembukaanPeriode || $isPerbaikanDataPddikti) {
            $defaultRecipient = "Rektor Universitas Subang\nu.p. Wakil Rektor I Bid. Akademik\ndan kemahasiswaan";
            $recipient = ! empty($extra['target_recipient']) ? $extra['target_recipient'] : $defaultRecipient;
            $city = ! empty($extra['target_city']) ? $extra['target_city'] : 'Tempat';

            $pdf->SetFont('Helvetica', '', 9.5);
            $pdf->Cell(170, 4.2, 'Kepada Yth.', 0, 1, 'L');
            $pdf->MultiCell(170, 4.5, $recipient, 0, 'L');
            $pdf->Cell(170, 4.2, 'di '.$city, 0, 1, 'L');
            $pdf->Ln(3.5);

            return;
        }

        if ($isPemberitahuanUjianDosen) {
            $defaultRecipient = "Bapak/Ibu Dosen\nFakultas Ilmu Komputer Universitas Subang";
            $recipient = ! empty($extra['target_recipient']) ? $extra['target_recipient'] : $defaultRecipient;
            $city = ! empty($extra['target_city']) ? $extra['target_city'] : 'Tempat';

            $pdf->SetFont('Helvetica', '', 9.5);
            $pdf->Cell(170, 4.2, 'Kepada Yth.', 0, 1, 'L');
            $pdf->SetFont('Helvetica', '', 9.5);
            $pdf->MultiCell(170, 4.5, $recipient, 0, 'L');
            $pdf->Cell(170, 4.2, 'di', 0, 1, 'L');
            $pdf->Cell(170, 4.2, $city, 0, 1, 'L');
            $pdf->Ln(3.5);

            return;
        }

        // Kepada Yth. & Penerima
        if ($isPengajuanDana || $isPengadaanSarpras || $isPerbaikanSarpras) {
            $defaultRecipient = 'Rektor Universitas Subang';
            $defaultCity = ($isPengadaanSarpras || $isPerbaikanSarpras) ? 'Tempat' : 'Subang';

            $recipient = ! empty($extra['target_recipient']) ? $extra['target_recipient'] : $defaultRecipient;
            $city = ! empty($extra['target_city']) ? $extra['target_city'] : $defaultCity;

            $pdf->SetFont('Helvetica', '', 9.5);
            $pdf->Cell(170, 4.2, 'Kepada Yth.', 0, 1, 'L');
            $pdf->SetFont('Helvetica', '', 9.5);
            $pdf->Cell(170, 4.5, $recipient, 0, 1, 'L');
            $pdf->Cell(170, 4.2, 'di', 0, 1, 'L');
            $pdf->Cell(170, 4.2, $city, 0, 1, 'L');
            $pdf->Ln(3.5);

            return;
        }

        // Kepada Yth. & Penerima
        if ($isUjianSusulan || $isPerbaikanNilai) {
            $defaultLecturer = $isPerbaikanNilai ? 'Ir. Syarif Hidayat, MT' : "ACHMAD SYAFA'AT, S.Kom., MT";
            $defaultCourse = $isPerbaikanNilai ? 'APSI I' : 'Jaringan Komputer';

            $lecturerName = ! empty($extra['lecturer_name']) ? $extra['lecturer_name'] : $defaultLecturer;
            $courseName = ! empty($extra['course_name']) ? $extra['course_name'] : $defaultCourse;

            $pdf->SetFont('Helvetica', '', 9.5);
            $pdf->Cell(170, 4.2, 'Kepada Yth,', 0, 1, 'L');

            $wLabel = 36;
            $wColon = 4;
            $wVal = 130;

            $pdf->Cell($wLabel, 4.5, 'Bapak / Ibu', 0, 0, 'L');
            $pdf->Cell($wColon, 4.5, ':', 0, 0, 'C');
            $pdf->Cell($wVal, 4.5, $lecturerName, 0, 1, 'L');

            $pdf->Cell($wLabel, 4.5, 'Dosen Mata Kuliah', 0, 0, 'L');
            $pdf->Cell($wColon, 4.5, ':', 0, 0, 'C');
            $pdf->Cell($wVal, 4.5, $courseName, 0, 1, 'L');

            $pdf->Cell(170, 4.5, 'Di', 0, 1, 'L');
            $pdf->Ln(2);
            $pdf->Cell(170, 4.5, 'Tempat', 0, 1, 'L');
            $pdf->Ln(4.5);

            return;
        }

        // Kepada Yth. & Penerima
        if ($isMagang) {
            $defaultRecipient = 'Kepala Kantor Urusan Agama (KUA) Kecamatan Ciater Kabupaten Subang';
        } elseif ($isPkl) {
            $defaultRecipient = 'Kepala Sekolah SMP Negeri 1 Binong Kabupaten Subang';
        } elseif ($isIzinUjian) {
            $defaultRecipient = 'HRD Klinik Pratama Rawat Inap Larasati';
        } elseif ($isSeminarUp || $isSidangTa) {
            $defaultRecipient = 'HRD PT. Uwu Jump Indonesia';
        } elseif ($isSkripsi) {
            $defaultRecipient = 'Kepala Kantor Desa Panyingkiran Kecamatan Purwadadi Kabupaten Subang';
        } else {
            $defaultRecipient = 'Kepala Kantor Samsat Kabupaten Subang';
        }

        $recipient = ! empty($extra['target_recipient'])
            ? $extra['target_recipient']
            : (! empty($extra['target_agency']) ? 'Kepala / Pimpinan '.$extra['target_agency'] : $defaultRecipient);
        $city = ! empty($extra['target_city']) ? $extra['target_city'] : 'Tempat';

        $pdf->SetFont('Helvetica', '', 9.5);
        $pdf->Cell(170, 4.2, 'Kepada Yth.', 0, 1, 'L');
        $pdf->SetFont('Helvetica', 'B', 9.5);
        $pdf->MultiCell(170, 4.5, $recipient, 0, 'L');
        $pdf->SetFont('Helvetica', '', 9.5);
        $pdf->Cell(170, 4.2, 'di', 0, 1, 'L');
        $pdf->Cell(170, 4.2, $city, 0, 1, 'L');

        $pdf->Ln(3.5);
    }

    /**
     * Parse template string with letter data variables.
     */
    public function parseTemplate(string $template, LetterRequest $letter): string
    {
        $semesterWords = [
            1 => 'Satu', 2 => 'Dua', 3 => 'Tiga', 4 => 'Empat',
            5 => 'Lima', 6 => 'Enam', 7 => 'Tujuh', 8 => 'Delapan',
            9 => 'Sembilan', 10 => 'Sepuluh', 11 => 'Sebelas', 12 => 'Dua Belas',
            13 => 'Tiga Belas', 14 => 'Empat Belas',
        ];
        $semWord = $semesterWords[$letter->semester] ?? (string) $letter->semester;

        $birthDateStr = $letter->birth_date ? Carbon::parse($letter->birth_date)->translatedFormat('d F Y') : '-';
        $ttl = ($letter->birth_place ? $letter->birth_place.', ' : '').$birthDateStr;

        $title = strtolower($letter->signer_title ?? '');
        $name = strtolower($letter->signer_name ?? '');

        if (str_contains($title, 'wakil') || str_contains($name, 'bambang')) {
            $signerRoleFull = 'Wakil Dekan Fakultas Ilmu Komputer Universitas Subang';
        } elseif (str_contains($title, 'dekan') || str_contains($name, 'tepi')) {
            $signerRoleFull = 'Dekan Fakultas Ilmu Komputer Universitas Subang';
        } else {
            $prodi = $letter->study_program ?: 'Sistem Informasi';
            $signerRoleFull = 'Ketua Program Studi '.$prodi.' Fakultas Ilmu Komputer Universitas Subang';
        }

        $romanMonths = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
            13 => 'XIII', 14 => 'XIV',
        ];
        $semRoman = $romanMonths[$letter->semester] ?? (string) $letter->semester;

        $extra = $letter->extra_data ?? [];

        $agency = ! empty($extra['target_agency'])
            ? $extra['target_agency']
            : (! empty($extra['target_recipient'])
                ? preg_replace('/^(Kepala\s+|Pimpinan\s+)/i', '', $extra['target_recipient'])
                : ($extra['company_name'] ?? '-'));

        $placeholders = [
            '{nama_mahasiswa}' => $letter->student_name ?? '-',
            '{npm}' => $letter->npm ?? '-',
            '{program_studi}' => $letter->study_program ?? 'Sistem Informasi',
            '{jenjang}' => 'Sarjana / S.1',
            '{tempat_lahir}' => $letter->birth_place ?? '-',
            '{tanggal_lahir}' => $birthDateStr,
            '{ttl}' => $ttl,
            '{alamat}' => $letter->address ?? '-',
            '{semester}' => (string) $letter->semester,
            '{semester_huruf}' => $semWord,
            '{semester_romawi}' => $semRoman,
            '{tahun_akademik}' => $letter->academic_year ?? '-',
            '{keperluan}' => $letter->purpose ?? ($extra['purpose'] ?? 'keperluan administrasi tunjangan gaji'),
            '{nomor_surat}' => $letter->letter_number ?? '-',
            '{pejabat_nama}' => $letter->signer_name ?? 'BAGUS ALI AKBAR, S.Si., M.Kom',
            '{pejabat_jabatan}' => $signerRoleFull,
            '{pejabat_title}' => $letter->signer_title ?? 'Ka. Program Studi',

            // Extra data placeholders
            '{tujuan_instansi}' => $agency,
            '{lokasi_penelitian}' => $agency,
            '{penerima_surat}' => $extra['target_recipient'] ?? ($extra['company_name'] ?? '-'),
            '{alamat_instansi}' => $extra['company_address'] ?? ($extra['target_address'] ?? '-'),
            '{periode_magang}' => $extra['internship_period'] ?? '-',
            '{mata_kuliah}' => $extra['course_name'] ?? '-',
            '{dosen_pengampu}' => $extra['lecturer_name'] ?? '-',
            '{judul_skripsi}' => $extra['thesis_title'] ?? '-',
            '{kegiatan}' => $extra['activity_name'] ?? 'Seminar Usulan Penelitian',
            '{hari_pelaksanaan}' => $extra['event_day'] ?? 'Kamis',
            '{jenis_ujian}' => $extra['exam_type'] ?? 'UTS / UAS',
            '{alasan}' => $extra['reason'] ?? '-',
            '{nama_orang_tua}' => $extra['parent_name'] ?? '-',
            '{nip_orang_tua}' => $extra['parent_nip'] ?? '-',
            '{pangkat_orang_tua}' => $extra['parent_rank'] ?? '-',
            '{instansi_orang_tua}' => $extra['parent_institution'] ?? '-',
            '{nama_dosen}' => $letter->student_name ?? ($extra['lecturer_name'] ?? '-'),
            '{status_tenaga_pengajar}' => $extra['lecturer_status'] ?? 'Dosen Tetap',
            '{nidn}' => $extra['nidn'] ?? ($letter->npm ?? '-'),
            '{semester_tipe}' => $extra['semester_type'] ?? ($letter->semester % 2 === 1 ? 'Ganjil' : 'Genap'),
            '{durasi_magang}' => $extra['internship_duration'] ?? '5 bulan',
            '{tanggal_mulai_magang}' => $extra['start_date'] ?? '14 September 2026',
            '{tanggal_selesai_magang}' => $extra['end_date'] ?? '09 Januari 2027',
            '{durasi_pkl}' => $extra['internship_duration'] ?? ($extra['pkl_duration'] ?? '40 hari'),
            '{tanggal_mulai_pkl}' => $extra['start_date'] ?? '06 Oktober 2025',
            '{tanggal_selesai_pkl}' => $extra['end_date'] ?? '28 November 2025',
            '{nama_ujian_lengkap}' => (strtoupper($extra['exam_type'] ?? 'UAS') === 'UTS') ? 'Ujian Tengah Semester' : 'Ujian Akhir Semester',
            '{tanggal_ujian}' => $extra['exam_dates'] ?? ($extra['exam_date'] ?? '12-19 Januari 2026'),
            '{lampiran_count}' => $extra['lampiran_count'] ?? '2 (dua) lembar',
            '{nilai_asal}' => $extra['original_score'] ?? '-',
            '{tanggal_sidang}' => $extra['defense_date_text'] ?? ($extra['defense_date'] ?? 'Kamis, 27 Agustus 2026'),
            '{tanggal_yudisium}' => $extra['yudisium_date_text'] ?? ($extra['yudisium_date'] ?? 'Kamis, 27 Agustus 2026'),
            '{ipk}' => $extra['ipk'] ?? '3.73',
            '{masa_studi}' => $extra['study_duration'] ?? '4 (Empat) Tahun',
            '{nilai_sidang}' => $extra['defense_grade'] ?? 'A',
            '{hasil_sidang}' => $extra['defense_result'] ?? 'Lulus',
            '{predikat_yudisium}' => $extra['yudisium_predicate'] ?? 'Dengan Pujian',
            '{sk_dekan_nomor}' => $extra['sk_number'] ?? '270 /D/FIK-US/VI/2026',
            '{sk_dekan_tanggal}' => $extra['sk_date'] ?? '26 Juni 2026',
            '{gelar_akademik}' => $extra['degree_title'] ?? 'Sarjana Komputer (S.Kom)',
            '{predikat_kelulusan}' => $extra['yudisium_predicate'] ?? 'SANGAT MEMUASKAN',
            '{agenda}' => $extra['agenda'] ?? 'Rapat Akademik',
            '{hari_tanggal_rapat}' => $extra['meeting_day_date'] ?? 'Selasa, 8 September 2026',
            '{waktu_rapat}' => $extra['meeting_time'] ?? '09.00 WIB',
            '{tempat_rapat}' => ($extra['meeting_type'] ?? 'offline') === 'online' ? 'Online ('.($extra['meeting_platform'] ?? 'Google Meet').')' : ($extra['meeting_location'] ?? 'Ruang 12 Fakultas Ilmu Komputer'),
            '{link_rapat}' => $extra['meeting_link'] ?? '-',
            '{penerima_undangan}' => $extra['recipient_name'] ?? ($letter->student_name ?? 'Bapak Bagus Ali Akbar, S.SI,. M.Kom'),
        ];

        // Support any dynamic custom extra fields
        foreach ($extra as $key => $val) {
            if (is_scalar($val) && ! isset($placeholders['{'.$key.'}'])) {
                $placeholders['{'.$key.'}'] = (string) $val;
            }
        }

        return str_replace(array_keys($placeholders), array_values($placeholders), $template);
    }

    /**
     * Render Letter Body & Student Data.
     */
    protected function renderBody(FPDF $pdf, LetterRequest $letter): void
    {
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(15, 23, 42);

        $letterType = $letter->letterType;

        // Specialized layout for Surat Keterangan Masih Kuliah PNS (Format Baku 1-12)
        if ($letterType && $letterType->code === 'keterangan_kuliah_pns') {
            $this->renderBodyKeteranganKuliahPns($pdf, $letter, $letterType);

            return;
        }

        // Specialized layout for Surat Pengantar Observasi Tugas Mata Kuliah (Tabel Kelompok Mahasiswa)
        if ($letterType && $letterType->code === 'observasi_matkul') {
            $this->renderBodyObservasiMatkul($pdf, $letter, $letterType);

            return;
        }

        // Specialized layout for Surat Pengantar Izin Penelitian Skripsi (Tabel Mahasiswa)
        if ($letterType && $letterType->code === 'izin_penelitian_skripsi') {
            $this->renderBodyPenelitianSkripsi($pdf, $letter, $letterType);

            return;
        }

        // Specialized layout for Surat Keterangan Mengikuti Seminar Usulan Penelitian & Sidang Tugas Akhir (Tabel Peserta Seminar/Sidang)
        if ($letterType && in_array($letterType->code, ['izin_seminar_up', 'izin_sidang_ta'])) {
            $this->renderBodySeminarUsulanPenelitian($pdf, $letter, $letterType);

            return;
        }

        // Specialized layout for Surat Pengantar Magang (Surat Pengantar MBKM & Lampiran Daftar Mahasiswa)
        if ($letterType && $letterType->code === 'pengantar_magang') {
            $this->renderBodyPengantarMagang($pdf, $letter, $letterType);

            return;
        }

        // Specialized layout for Surat Pengantar Izin PKL (Tabel Mahasiswa & Narasi PKL)
        if ($letterType && $letterType->code === 'pengantar_pkl') {
            $this->renderBodyPengantarPkl($pdf, $letter, $letterType);

            return;
        }

        // Specialized layout for Surat Izin Mengikuti Ujian (UTS/UAS)
        if ($letterType && $letterType->code === 'izin_mengikuti_ujian') {
            $this->renderBodyIzinMengikutiUjian($pdf, $letter, $letterType);

            return;
        }

        // Specialized layout for Surat Pengantar Ujian Susulan (UTS/UAS)
        if ($letterType && $letterType->code === 'ujian_susulan') {
            $this->renderBodyUjianSusulan($pdf, $letter, $letterType);

            return;
        }

        // Specialized layout for Surat Pengantar Perbaikan Nilai
        if ($letterType && $letterType->code === 'perbaikan_nilai') {
            $this->renderBodyPerbaikanNilai($pdf, $letter, $letterType);

            return;
        }

        // Specialized layout for Surat Keterangan Yudisium
        if ($letterType && $letterType->code === 'keterangan_yudisium') {
            $this->renderBodyKeteranganYudisium($pdf, $letter, $letterType);

            return;
        }

        // Specialized layout for Surat Undangan Rapat Akademik
        if ($letterType && $letterType->code === 'undangan_rapat_akademik') {
            $this->renderBodyUndanganRapatAkademik($pdf, $letter, $letterType);

            return;
        }

        // Specialized layout for Surat Tugas Mengajar
        if ($letterType && $letterType->code === 'tugas_mengajar') {
            $this->renderBodySuratTugasMengajar($pdf, $letter, $letterType);

            return;
        }

        // Specialized layout for Surat Tugas Pembimbing MBKM
        if ($letterType && $letterType->code === 'tugas_pembimbing_mbkm') {
            $this->renderBodySuratTugasPembimbingMbkm($pdf, $letter, $letterType);

            return;
        }

        // Specialized layout for Surat Tugas Pembimbing Tugas Akhir
        if ($letterType && $letterType->code === 'tugas_pembimbing_ta') {
            $this->renderBodySuratTugasPembimbingTa($pdf, $letter, $letterType);

            return;
        }

        // Specialized layout for Surat Pernyataan Kesanggupan Mengajar (Tanda tangan Dosen yang bersangkutan)
        if ($letterType && $letterType->code === 'kesanggupan_mengajar') {
            $this->renderBodyKesanggupanMengajar($pdf, $letter, $letterType);

            return;
        }

        // Specialized layout for Dokumen Fakta Integritas (Dosen Pengampu Mata Kuliah)
        if ($letterType && $letterType->code === 'pakta_integritas') {
            $this->renderBodyPaktaIntegritas($pdf, $letter, $letterType);

            return;
        }

        // Specialized layout for Surat Pengajuan Kebutuhan Dana
        if ($letterType && $letterType->code === 'pengajuan_dana') {
            $this->renderBodyPengajuanDana($pdf, $letter, $letterType);

            return;
        }

        // Specialized layout for Surat Pengajuan Pengadaan Sarana dan Prasarana
        if ($letterType && $letterType->code === 'pengadaan_sarpras') {
            $this->renderBodyPengadaanSarpras($pdf, $letter, $letterType);

            return;
        }

        // Specialized layout for Surat Perbaikan Sarana dan Prasarana
        if ($letterType && $letterType->code === 'perbaikan_sarpras') {
            $this->renderBodyPerbaikanSarpras($pdf, $letter, $letterType);

            return;
        }

        // Specialized layout for Surat Pemberitahuan UTS/UAS ke Dosen
        if ($letterType && $letterType->code === 'pemberitahuan_ujian_dosen') {
            $this->renderBodyPemberitahuanUjianDosen($pdf, $letter, $letterType);

            return;
        }

        // Specialized layout for Surat Pengumuman UTS/UAS ke Mahasiswa
        if ($letterType && $letterType->code === 'pengumuman_ujian_mahasiswa') {
            $this->renderBodyPengumumanUjianMahasiswa($pdf, $letter, $letterType);

            return;
        }

        // Specialized layout for Surat Pengantar Perbaikan Data Mahasiswa PDDIKTI
        if ($letterType && $letterType->code === 'perbaikan_data_pddikti') {
            $this->renderBodyPerbaikanDataPddikti($pdf, $letter, $letterType);

            return;
        }

        // Specialized layout for Surat Pengantar Pembukaan Periode
        if ($letterType && $letterType->code === 'pembukaan_periode') {
            $this->renderBodyPembukaanPeriode($pdf, $letter, $letterType);

            return;
        }

        // Specialized layout for Surat Pengajuan Penandatanganan Ijazah
        if ($letterType && $letterType->code === 'pengajuan_ttd_ijazah') {
            $this->renderBodyPengajuanTtdIjazah($pdf, $letter, $letterType);

            return;
        }

        // Specialized layout for Surat Pengajuan Blanko Ijazah dan Blanko Transkrip
        if ($letterType && $letterType->code === 'pengajuan_blanko_ijazah_transkrip') {
            $this->renderBodyPengajuanBlankoIjazahTranskrip($pdf, $letter, $letterType);

            return;
        }

        // Specialized layout for Surat Rekomendasi Pinjaman BJB
        if ($letterType && $letterType->code === 'rekomendasi_pinjaman_bjb') {
            $this->renderBodyRekomendasiPinjamanBjb($pdf, $letter, $letterType);

            return;
        }

        // Specialized layout for Surat Rekomendasi Beasiswa
        if ($letterType && $letterType->code === 'rekomendasi_beasiswa') {
            $this->renderBodyRekomendasiBeasiswa($pdf, $letter, $letterType);

            return;
        }

        // Specialized layout for Surat Rekomendasi Kegiatan Pembelajaran di Luar Program Studi
        if ($letterType && $letterType->code === 'rekomendasi_luar_prodi') {
            $this->renderBodyRekomendasiLuarProdi($pdf, $letter, $letterType);

            return;
        }

        $title = strtolower($letter->signer_title ?? '');
        $name = strtolower($letter->signer_name ?? '');

        if (str_contains($title, 'wakil') || str_contains($name, 'bambang')) {
            $openingTitle = 'Wakil Dekan Fakultas Ilmu Komputer Universitas Subang';
        } elseif (str_contains($title, 'dekan') || str_contains($name, 'tepi')) {
            $openingTitle = 'Dekan Fakultas Ilmu Komputer Universitas Subang';
        } else {
            $prodi = $letter->study_program ?: 'Sistem Informasi';
            $openingTitle = 'Ketua Program Studi '.$prodi.' Fakultas Ilmu Komputer Universitas Subang';
        }

        // Opening narrative: dynamic template with fallback
        if (! empty($letterType?->opening_template)) {
            $opening = $this->parseTemplate($letterType->opening_template, $letter);
        } else {
            $opening = $openingTitle.', menerangkan bahwa :';
        }

        $pdf->MultiCell(170, 5.5, $opening, 0, 'J');
        $pdf->Ln(2);

        // Table details
        $birthDateStr = $letter->birth_date ? Carbon::parse($letter->birth_date)->translatedFormat('d F Y') : '-';
        $ttl = ($letter->birth_place ? $letter->birth_place.', ' : '').$birthDateStr;

        $this->renderFieldRow($pdf, 'Nama', $letter->student_name);
        $this->renderFieldRow($pdf, 'NPM', $letter->npm);
        $this->renderFieldRow($pdf, 'Tempat, tanggal lahir', $ttl);
        $this->renderFieldRow($pdf, 'Alamat', $letter->address ?: '-');

        // Render Extra fields based on type
        $extra = $letter->extra_data ?? [];
        if ($letterType && $letterType->code === 'keterangan_kuliah_pns') {
            $pdf->Ln(1);
            $this->renderFieldRow($pdf, 'Nama Orang Tua / Wali', $extra['parent_name'] ?? '-');
            $this->renderFieldRow($pdf, 'NIP / NRP', $extra['parent_nip'] ?? '-');
            $this->renderFieldRow($pdf, 'Pangkat / Golongan', $extra['parent_rank'] ?? '-');
            $this->renderFieldRow($pdf, 'Instansi / Unit Kerja', $extra['parent_institution'] ?? '-');
        } elseif ($letterType && $letterType->code === 'pengantar_pkl') {
            $pdf->Ln(1);
            $this->renderFieldRow($pdf, 'Tujuan Instansi/Perusahaan', $extra['company_name'] ?? '-');
            $this->renderFieldRow($pdf, 'Alamat Instansi/Perusahaan', $extra['company_address'] ?? '-');
            $this->renderFieldRow($pdf, 'Periode / Waktu Pelaksanaan', $extra['internship_period'] ?? '-');
        } elseif ($letterType && $letterType->code === 'observasi_matkul') {
            $pdf->Ln(1);
            $this->renderFieldRow($pdf, 'Mata Kuliah', $extra['course_name'] ?? '-');
            $this->renderFieldRow($pdf, 'Dosen Pengampu', $extra['lecturer_name'] ?? '-');
            $this->renderFieldRow($pdf, 'Instansi Objek Observasi', $extra['target_agency'] ?? '-');
        } elseif ($letterType && $letterType->code === 'izin_penelitian_skripsi') {
            $pdf->Ln(1);
            $this->renderFieldRow($pdf, 'Judul Skripsi', $extra['thesis_title'] ?? '-');
            $this->renderFieldRow($pdf, 'Instansi / Objek Penelitian', $extra['target_agency'] ?? '-');
        }

        $pdf->Ln(3);

        // Middle Statement Paragraph: dynamic template with fallback
        if (! empty($letterType?->content_template)) {
            $content = $this->parseTemplate($letterType->content_template, $letter);
        } else {
            $semesterWords = [
                1 => 'Satu', 2 => 'Dua', 3 => 'Tiga', 4 => 'Empat',
                5 => 'Lima', 6 => 'Enam', 7 => 'Tujuh', 8 => 'Delapan',
            ];
            $semWord = $semesterWords[$letter->semester] ?? (string) $letter->semester;
            $content = "Nama tersebut tercatat sebagai Mahasiswa Fakultas Ilmu Komputer Universitas Subang Jurusan {$letter->study_program} Jenjang Sarjana (S1) Semester {$letter->semester} ({$semWord}) pada Tahun Akademik {$letter->academic_year}.";
        }

        $pdf->MultiCell(170, 5.5, $content, 0, 'J');
        $pdf->Ln(2.5);

        // Closing Paragraph: dynamic template with fallback
        if (! empty($letterType?->closing_template)) {
            $closing = $this->parseTemplate($letterType->closing_template, $letter);
        } else {
            $closing = 'Demikian keterangan ini dibuat atas permintaan yang bersangkutan untuk dipergunakan seperlunya.';
        }

        $pdf->MultiCell(170, 5.5, $closing, 0, 'J');
    }

    /**
     * Render specialized 3-tier official BKN format for Surat Keterangan Masih Kuliah PNS (Items 1-12).
     */
    protected function renderBodyKeteranganKuliahPns(FPDF $pdf, LetterRequest $letter, ?LetterType $letterType): void
    {
        $extra = $letter->extra_data ?? [];

        $birthDateStr = $letter->birth_date ? Carbon::parse($letter->birth_date)->translatedFormat('d F Y') : '-';
        $ttl = ($letter->birth_place ? $letter->birth_place.', ' : '').$birthDateStr;

        $semesterWords = [
            1 => 'Satu', 2 => 'Dua', 3 => 'Tiga', 4 => 'Empat',
            5 => 'Lima', 6 => 'Enam', 7 => 'Tujuh', 8 => 'Delapan',
            9 => 'Sembilan', 10 => 'Sepuluh', 11 => 'Sebelas', 12 => 'Dua Belas',
            13 => 'Tiga Belas', 14 => 'Empat Belas',
        ];
        $semWord = $semesterWords[$letter->semester] ?? (string) $letter->semester;

        $romanMonths = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
            13 => 'XIII', 14 => 'XIV',
        ];
        $semRoman = $romanMonths[$letter->semester] ?? (string) $letter->semester;

        // 1. Opening Narrative (Default: "Yang bertanda tangan dibawah ini :")
        if (! empty($letterType?->opening_template)) {
            $opening = $this->parseTemplate($letterType->opening_template, $letter);
        } else {
            $opening = 'Yang bertanda tangan dibawah ini :';
        }

        $pdf->SetFont('Helvetica', '', 10);
        $pdf->MultiCell(170, 5, $opening, 0, 'L');
        $pdf->Ln(2);

        // Bagian I: Data Pejabat (Butir 1 - 3)
        $this->renderNumberedFieldRow($pdf, '1. Nama', $letter->signer_name ?? 'BAGUS ALI AKBAR, S.Si., M.Kom', true);
        $this->renderNumberedFieldRow($pdf, '2. Jabatan', $letter->signer_title ?? 'Ka. Program Studi');
        $this->renderNumberedFieldRow($pdf, '3. Pada Sekolah Tinggi', 'Universitas Subang');
        $pdf->Ln(2.5);

        // Subheader Mahasiswa
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->Cell(170, 5, 'Dengan ini menyatakan dengan sesungguhnya, bahwa :', 0, 1, 'L');
        $pdf->Ln(2);

        // Bagian II: Data Mahasiswa (Butir 4 - 8)
        $this->renderNumberedFieldRow($pdf, '4. Nama Mahasiswa', mb_strtoupper($letter->student_name ?? '-', 'UTF-8'), true);
        $this->renderNumberedFieldRow($pdf, '5. Nomor Pokok Mahasiswa', $letter->npm ?? '-');
        $this->renderNumberedFieldRow($pdf, '6. Tempat Tanggal Lahir', $ttl);
        $this->renderNumberedFieldRow($pdf, '7. Program Studi', $letter->study_program ?? 'Sistem Informasi');
        $this->renderNumberedFieldRow($pdf, '8. Jenjang Program', 'Sarjana /S.1');
        $pdf->Ln(2.5);

        // Subheader Orang Tua
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->Cell(170, 5, 'Nama Orang Tua Mahasiswa Tersebut adalah :', 0, 1, 'L');
        $pdf->Ln(2);

        // Bagian III: Data Orang Tua (Butir 9 - 12)
        $parentName = ! empty($extra['parent_name']) ? mb_strtoupper($extra['parent_name'], 'UTF-8') : '-';
        $this->renderNumberedFieldRow($pdf, '9. Nama', $parentName, true);
        $this->renderNumberedFieldRow($pdf, '10. NIP/NIK', $extra['parent_nip'] ?? '-');
        $this->renderNumberedFieldRow($pdf, '11. Pangkat/Golongan', $extra['parent_rank'] ?? '-');
        $this->renderNumberedFieldRow($pdf, '12. Instansi', $extra['parent_institution'] ?? '-');
        $pdf->Ln(3);

        // Bagian IV: Paragraf Pernyataan Aktif Kuliah (Content Template)
        if (! empty($letterType?->content_template)) {
            $content = $this->parseTemplate($letterType->content_template, $letter);
        } else {
            $content = "Nama tersebut tercatat sebagai mahasiswa Fakultas Ilmu Komputer Universitas Subang Program Studi {$letter->study_program} Jenjang Sarjana (S1) Semester {$semRoman} ({$semWord}) pada Tahun Akademik {$letter->academic_year}.";
        }
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->MultiCell(170, 5.2, $content, 0, 'J');
        $pdf->Ln(3);

        // Bagian V: Paragraf Penutup (Closing Template)
        if (! empty($letterType?->closing_template)) {
            $closing = $this->parseTemplate($letterType->closing_template, $letter);
        } else {
            $closing = 'Demikian keterangan ini dibuat atas permintaan yang bersangkutan untuk dipergunakan seperlunya.';
        }
        $pdf->MultiCell(170, 5.2, $closing, 0, 'J');
    }

    /**
     * Render specialized format for Surat Pengantar Observasi Tugas Mata Kuliah (Tabel Kelompok Mahasiswa).
     */
    protected function renderBodyObservasiMatkul(FPDF $pdf, LetterRequest $letter, ?LetterType $letterType): void
    {
        $extra = $letter->extra_data ?? [];

        // 1. Opening Narrative (Default: "{pejabat_jabatan} Fakultas Ilmu Komputer Universitas Subang, menerangkan bahwa :")
        if (! empty($letterType?->opening_template)) {
            $opening = $this->parseTemplate($letterType->opening_template, $letter);
        } else {
            $prodi = $letter->study_program ?: 'Sistem Informasi';
            $opening = "Ketua Program Studi {$prodi} Fakultas Ilmu Komputer Universitas Subang, menerangkan bahwa :";
        }

        // Indented paragraph like in the reference image
        $pdf->SetFont('Helvetica', '', 9.5);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetX(35);
        $pdf->Write(4.5, $opening);
        $pdf->Ln(5.5);

        // 2. Build Student Group List (Ketua + Anggota Tambahan)
        $members = [];
        $members[] = [
            'name' => mb_strtoupper($letter->student_name ?? '-', 'UTF-8'),
            'npm' => $letter->npm ?? '-',
            'address' => mb_strtoupper($letter->address ?: ($extra['student_city'] ?? 'SUBANG'), 'UTF-8'),
        ];

        if (! empty($extra['group_members']) && is_array($extra['group_members'])) {
            foreach ($extra['group_members'] as $m) {
                if (! empty($m['name'])) {
                    $members[] = [
                        'name' => mb_strtoupper(trim($m['name']), 'UTF-8'),
                        'npm' => ! empty($m['npm']) ? trim($m['npm']) : '-',
                        'address' => ! empty($m['address']) ? mb_strtoupper(trim($m['address']), 'UTF-8') : 'SUBANG',
                    ];
                }
            }
        }

        // 3. Render Table
        // Total printable width: 170mm (Margins: 20mm left, 20mm right)
        // Col widths: NO: 12mm, NAMA MAHASISWA: 78mm, NPM: 45mm, ALAMAT: 35mm
        $wNo = 12;
        $wName = 78;
        $wNpm = 45;
        $wAddr = 35;

        // Header Row
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->Cell($wNo, 6, 'NO', 1, 0, 'C');
        $pdf->Cell($wName, 6, 'NAMA MAHASISWA', 1, 0, 'C');
        $pdf->Cell($wNpm, 6, 'NPM', 1, 0, 'C');
        $pdf->Cell($wAddr, 6, 'ALAMAT', 1, 1, 'C');

        // Data Rows
        $pdf->SetFont('Helvetica', '', 8.5);
        foreach ($members as $index => $member) {
            $noStr = (string) ($index + 1);
            $nameStr = $member['name'];
            $npmStr = $member['npm'];
            $addrStr = $member['address'];

            $nameWidth = $pdf->GetStringWidth($nameStr);
            if ($nameWidth > ($wName - 4)) {
                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $rowH = 8.8;

                $pdf->Cell($wNo, $rowH, $noStr, 1, 0, 'C');

                $pdf->SetXY($x + $wNo, $y);
                $pdf->MultiCell($wName, 4.4, $nameStr, 1, 'L');

                $pdf->SetXY($x + $wNo + $wName, $y);
                $pdf->Cell($wNpm, $rowH, $npmStr, 1, 0, 'C');
                $pdf->Cell($wAddr, $rowH, $addrStr, 1, 1, 'C');
            } else {
                $rowH = 5.6;
                $pdf->Cell($wNo, $rowH, $noStr, 1, 0, 'C');
                $pdf->Cell($wName, $rowH, '  '.$nameStr, 1, 0, 'L');
                $pdf->Cell($wNpm, $rowH, $npmStr, 1, 0, 'C');
                $pdf->Cell($wAddr, $rowH, $addrStr, 1, 1, 'C');
            }
        }

        $pdf->Ln(3);

        // 4. Content / Narrative
        $courseName = ! empty($extra['course_name']) ? $extra['course_name'] : 'Konsep Sistem Informasi';

        if (! empty($letterType?->content_template)) {
            $content = $this->parseTemplate($letterType->content_template, $letter);
            $this->renderMarkdownFormattedText($pdf, $content, 4.6, 9.5);
        } else {
            $pdf->SetFont('Helvetica', '', 9.5);
            $pdf->Write(4.6, 'Bermaksud akan mengadakan observasi ');
            $pdf->SetFont('Helvetica', 'B', 9.5);
            $pdf->Write(4.6, 'Matakuliah '.$courseName);
            $pdf->SetFont('Helvetica', '', 9.5);
            $pdf->Write(4.6, ' untuk penulisan tugas matakuliah yang diikuti oleh mahasiswa tersebut.');
            $pdf->Ln(6.5);

            $pdf->MultiCell(170, 4.6, 'Sehubungan dengan hal itu kami mohon kiranya Bapak/Ibu dapat memberikan bantuan kepada mahasiswa tersebut diatas, dan mengijinkan pegawai Bapak/Ibu memberikan keterangan/penjelasan untuk penyelesaian tugas matakuliah yang bersangkutan.', 0, 'J');
        }

        $pdf->Ln(2.5);

        // 5. Closing Narrative
        if (! empty($letterType?->closing_template)) {
            $closing = $this->parseTemplate($letterType->closing_template, $letter);
        } else {
            $closing = 'Demikian permohonan ini kami sampaikan. Atas bantuan dan kesediaannya kami ucapkan terima kasih.';
        }
        $pdf->SetFont('Helvetica', '', 9.5);
        $pdf->MultiCell(170, 4.6, $closing, 0, 'J');
    }

    /**
     * Render specialized format for Surat Pengantar Izin Penelitian Skripsi (Tabel Mahasiswa).
     */
    protected function renderBodyPenelitianSkripsi(FPDF $pdf, LetterRequest $letter, ?LetterType $letterType): void
    {
        $extra = $letter->extra_data ?? [];

        // 1. Opening Narrative
        if (! empty($letterType?->opening_template)) {
            $opening = $this->parseTemplate($letterType->opening_template, $letter);
        } else {
            $prodi = $letter->study_program ?: 'Sistem Informasi';
            $title = strtolower($letter->signer_title ?? '');
            $name = strtolower($letter->signer_name ?? '');

            if (str_contains($title, 'wakil') || str_contains($name, 'bambang')) {
                $role = 'Wakil Dekan Fakultas Ilmu Komputer Universitas Subang';
            } elseif (str_contains($title, 'dekan') || str_contains($name, 'tepi')) {
                $role = 'Dekan Fakultas Ilmu Komputer Universitas Subang';
            } else {
                $role = "Ketua Program Studi {$prodi} Fakultas Ilmu Komputer Universitas Subang";
            }
            $opening = "Bersama ini kami {$role}, dengan ini memberi pengantar kepada mahasiswa :";
        }

        // Indented opening paragraph like in the reference image
        $pdf->SetFont('Helvetica', '', 9.5);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetX(35);
        $pdf->Write(4.5, $opening);
        $pdf->Ln(5.5);

        // 2. Build Student List (Main applicant is No 1, plus optional additional members)
        $members = [];
        $members[] = [
            'name' => mb_strtoupper($letter->student_name ?? '-', 'UTF-8'),
            'npm' => $letter->npm ?? '-',
            'address' => mb_strtoupper($letter->address ?: ($extra['student_city'] ?? 'SUBANG'), 'UTF-8'),
        ];

        if (! empty($extra['group_members']) && is_array($extra['group_members'])) {
            foreach ($extra['group_members'] as $m) {
                if (! empty($m['name'])) {
                    $members[] = [
                        'name' => mb_strtoupper(trim($m['name']), 'UTF-8'),
                        'npm' => ! empty($m['npm']) ? trim($m['npm']) : '-',
                        'address' => ! empty($m['address']) ? mb_strtoupper(trim($m['address']), 'UTF-8') : 'SUBANG',
                    ];
                }
            }
        }

        // 3. Render Table
        $wNo = 12;
        $wName = 78;
        $wNpm = 45;
        $wAddr = 35;

        // Header Row
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->Cell($wNo, 6, 'NO', 1, 0, 'C');
        $pdf->Cell($wName, 6, 'NAMA MAHASISWA', 1, 0, 'C');
        $pdf->Cell($wNpm, 6, 'NPM', 1, 0, 'C');
        $pdf->Cell($wAddr, 6, 'ALAMAT', 1, 1, 'C');

        // Data Rows
        $pdf->SetFont('Helvetica', '', 8.5);
        foreach ($members as $index => $member) {
            $noStr = ($index + 1).'.';
            $nameStr = $member['name'];
            $npmStr = $member['npm'];
            $addrStr = $member['address'];

            $nameWidth = $pdf->GetStringWidth($nameStr);
            if ($nameWidth > ($wName - 4)) {
                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $rowH = 8.8;

                $pdf->Cell($wNo, $rowH, $noStr, 1, 0, 'C');

                $pdf->SetXY($x + $wNo, $y);
                $pdf->MultiCell($wName, 4.4, $nameStr, 1, 'L');

                $pdf->SetXY($x + $wNo + $wName, $y);
                $pdf->Cell($wNpm, $rowH, $npmStr, 1, 0, 'C');
                $pdf->Cell($wAddr, $rowH, $addrStr, 1, 1, 'C');
            } else {
                $rowH = 5.6;
                $pdf->Cell($wNo, $rowH, $noStr, 1, 0, 'C');
                $pdf->Cell($wName, $rowH, '  '.$nameStr, 1, 0, 'L');
                $pdf->Cell($wNpm, $rowH, $npmStr, 1, 0, 'C');
                $pdf->Cell($wAddr, $rowH, $addrStr, 1, 1, 'C');
            }
        }

        $pdf->Ln(3.5);

        // 4. Content / Narrative
        $agency = ! empty($extra['target_agency'])
            ? $extra['target_agency']
            : (! empty($extra['target_recipient'])
                ? preg_replace('/^(Kepala\s+|Pimpinan\s+)/i', '', $extra['target_recipient'])
                : 'Kantor Desa Panyingkiran Kecamatan Purwadadi Kabupaten Subang');

        if (! empty($letterType?->content_template)) {
            $content = $this->parseTemplate($letterType->content_template, $letter);
            $this->renderMarkdownFormattedText($pdf, $content, 4.6, 9.5);
        } else {
            $pdf->SetFont('Helvetica', '', 9.5);
            $pdf->Write(4.6, 'Bermaksud akan mengadakan penelitian pada ');
            $pdf->SetFont('Helvetica', 'B', 9.5);
            $pdf->Write(4.6, $agency);
            $pdf->SetFont('Helvetica', '', 9.5);
            $pdf->Write(4.6, ' untuk penulisan Skripsi yang diikuti oleh mahasiswa tersebut.');
            $pdf->Ln(6.5);

            $pdf->MultiCell(170, 4.6, 'Sehubungan dengan hal itu kami mohon kiranya Bapak/Ibu dapat memberikan bantuan kepada mahasiswa tersebut diatas, dan mengijinkan pegawai Bapak/Ibu memberikan keterangan/penjelasan untuk penyelesaian Skripsi yang bersangkutan.', 0, 'J');
            $pdf->Ln(2.5);
        }

        // 5. Closing Narrative
        if (! empty($letterType?->closing_template)) {
            $closing = $this->parseTemplate($letterType->closing_template, $letter);
        } else {
            $closing = 'Atas bantuan dan kerjasamanya kami ucapkan terima kasih.';
        }
        $pdf->SetFont('Helvetica', '', 9.5);
        $pdf->MultiCell(170, 4.6, $closing, 0, 'J');
    }

    /**
     * Render specialized format for Surat Pengantar Magang (Surat Pengantar MBKM).
     */
    protected function renderBodyPengantarMagang(FPDF $pdf, LetterRequest $letter, ?LetterType $letterType): void
    {
        $extra = $letter->extra_data ?? [];

        // 1. Opening Narrative (Dengan Hormat, ...)
        if (! empty($letterType?->opening_template)) {
            $opening = $this->parseTemplate($letterType->opening_template, $letter);
        } else {
            $academicYear = $letter->academic_year ?? '2026/2027';
            $opening = "Dengan Hormat,\n\nSehubungan dengan adanya Kegiatan Magang MBKM pada semester ganjil TA {$academicYear}. Fakultas Ilmu Komputer yang bertujuan untuk mendorong Mahasiswa untuk mampu menguasai kompetensi berbagai keilmuan sebelum memasuki dunia kerja.";
        }

        $pdf->SetFont('Helvetica', '', 9.5);
        $pdf->SetTextColor(15, 23, 42);
        $this->renderMarkdownFormattedText($pdf, $opening, 4.8, 9.5);
        $pdf->Ln(1);

        // 2. Middle Request Narrative
        if (! empty($letterType?->content_template)) {
            $content = $this->parseTemplate($letterType->content_template, $letter);
        } else {
            $agency = ! empty($extra['target_agency'])
                ? $extra['target_agency']
                : (! empty($extra['target_recipient'])
                    ? preg_replace('/^(Kepala\s+|Pimpinan\s+)/i', '', $extra['target_recipient'])
                    : 'Kantor Urusan Agama (KUA) Kecamatan Ciater Kabupaten Subang');
            $durasi = $extra['internship_duration'] ?? '5 bulan';
            $startDate = $extra['start_date'] ?? '14 September 2026';
            $endDate = $extra['end_date'] ?? '09 Januari 2027';

            $content = "Sehubungan dengan hal itu kami mohon kiranya Bapak/Ibu dapat memberikan bantuan kepada mahasiswa dan mengijinkan pegawai Bapak/Ibu memberikan pendampingan untuk pelaksanaan Magang MBKM selama {$durasi} mulai dari tanggal {$startDate} s/d {$endDate} di **{$agency}**. Terlampir daftar mahasiswa yang mengikuti program Magang MBKM di **{$agency}**.";
        }

        $this->renderMarkdownFormattedText($pdf, $content, 4.8, 9.5);
        $pdf->Ln(1);

        // 3. Closing Narrative
        if (! empty($letterType?->closing_template)) {
            $closing = $this->parseTemplate($letterType->closing_template, $letter);
        } else {
            $closing = 'Demikian surat permohonan ini kami sampaikan. Atas kesedianaan dan kerjasamanya Bapak/Ibu, kami ucapkan terima kasih.';
        }

        $pdf->SetFont('Helvetica', '', 9.5);
        $pdf->MultiCell(170, 4.8, $closing, 0, 'J');
    }

    /**
     * Render specialized format for Surat Pengantar Izin PKL (Tabel Mahasiswa & Narasi PKL).
     */
    protected function renderBodyPengantarPkl(FPDF $pdf, LetterRequest $letter, ?LetterType $letterType): void
    {
        $extra = $letter->extra_data ?? [];

        // 1. Opening Narrative (Dengan hormat, \n\n Ketua Program Studi ...)
        if (! empty($letterType?->opening_template)) {
            $opening = $this->parseTemplate($letterType->opening_template, $letter);
        } else {
            $prodi = $letter->study_program ?: 'Sistem Informasi';
            $opening = "Dengan hormat,\n\nKetua Program Studi {$prodi} Fakultas Ilmu Komputer Universitas Subang, menerangkan bahwa :";
        }

        $pdf->SetFont('Helvetica', '', 9.5);
        $pdf->SetTextColor(15, 23, 42);
        $this->renderMarkdownFormattedText($pdf, $opening, 4.6, 9.5);
        $pdf->Ln(1);

        // 2. Student Table (NO, NAMA MAHASISWA, NPM, ALAMAT)
        $members = [];
        $members[] = [
            'name' => mb_strtoupper($letter->student_name ?? '-', 'UTF-8'),
            'npm' => $letter->npm ?? '-',
            'address' => mb_strtoupper($letter->address ?: ($extra['target_city'] ?? 'SUBANG'), 'UTF-8'),
        ];

        if (! empty($extra['group_members']) && is_array($extra['group_members'])) {
            foreach ($extra['group_members'] as $m) {
                if (! empty($m['name'])) {
                    $members[] = [
                        'name' => mb_strtoupper(trim($m['name']), 'UTF-8'),
                        'npm' => ! empty($m['npm']) ? trim($m['npm']) : '-',
                        'address' => ! empty($m['address']) ? mb_strtoupper(trim($m['address']), 'UTF-8') : 'SUBANG',
                    ];
                }
            }
        }

        $wNo = 16;
        $wName = 74;
        $wNpm = 40;
        $wAddr = 40;

        // Header Row
        $pdf->SetFont('Helvetica', 'B', 9.5);
        $pdf->Cell($wNo, 6.5, 'NO', 1, 0, 'C');
        $pdf->Cell($wName, 6.5, 'NAMA MAHASISWA', 1, 0, 'C');
        $pdf->Cell($wNpm, 6.5, 'NPM', 1, 0, 'C');
        $pdf->Cell($wAddr, 6.5, 'ALAMAT', 1, 1, 'C');

        // Data Rows
        $pdf->SetFont('Helvetica', '', 9.5);
        foreach ($members as $idx => $member) {
            $noStr = ($idx + 1).'.';
            $nameStr = $member['name'];
            $npmStr = $member['npm'];
            $addrStr = $member['address'];

            $nameLines = ($pdf->GetStringWidth($nameStr) > ($wName - 4)) ? 2 : 1;
            $rowH = $nameLines === 1 ? 6.5 : 9.5;
            $lineH = $nameLines === 1 ? 6.5 : 4.75;

            $x = $pdf->GetX();
            $y = $pdf->GetY();

            $pdf->Cell($wNo, $rowH, $noStr, 1, 0, 'C');

            if ($nameLines > 1) {
                $pdf->SetXY($x + $wNo, $y);
                $pdf->MultiCell($wName, $lineH, $nameStr, 1, 'C');
            } else {
                $pdf->Cell($wName, $rowH, $nameStr, 1, 0, 'C');
            }

            $pdf->SetXY($x + $wNo + $wName, $y);
            $pdf->Cell($wNpm, $rowH, $npmStr, 1, 0, 'C');

            $pdf->SetXY($x + $wNo + $wName + $wNpm, $y);
            $pdf->Cell($wAddr, $rowH, $addrStr, 1, 1, 'C');
        }

        $pdf->Ln(3.5);

        // 3. Middle Request Narrative
        if (! empty($letterType?->content_template)) {
            $content = $this->parseTemplate($letterType->content_template, $letter);
        } else {
            $agency = ! empty($extra['target_agency'])
                ? $extra['target_agency']
                : (! empty($extra['target_recipient'])
                    ? preg_replace('/^(Kepala\s+|Pimpinan\s+)/i', '', $extra['target_recipient'])
                    : 'SMP Negeri 1 Binong Kabupaten Subang');
            $durasi = $extra['internship_duration'] ?? '40 hari';
            $startDate = $extra['start_date'] ?? '06 Oktober 2025';
            $endDate = $extra['end_date'] ?? '28 November 2025';

            $content = "Bermaksud untuk melaksanakan Praktek Kerja Lapangan di instansi/Perusahaan yang Bapak/Ibu pimpin selama {$durasi} dimulai tanggal {$startDate} – {$endDate} pada **{$agency}**.\n\nSehubungan dengan hal itu kami mohon kiranya Bapak/Ibu dapat memberikan izin dan memberikan bantuan kepada mahasiswa tersebut di atas,serta menugaskan pegawai Bapak/Ibu untuk menjadi pembimbing.";
        }

        $this->renderMarkdownFormattedText($pdf, $content, 4.6, 9.5);
        $pdf->Ln(1);

        // 4. Closing Narrative
        if (! empty($letterType?->closing_template)) {
            $closing = $this->parseTemplate($letterType->closing_template, $letter);
        } else {
            $closing = 'Demikian atas perhatian dan kerjasamanya kami ucapkan terimakasih.';
        }

        $pdf->SetFont('Helvetica', '', 9.5);
        $pdf->MultiCell(170, 4.6, $closing, 0, 'J');
    }

    /**
     * Render specialized format for Surat Izin Mengikuti Ujian (UTS/UAS).
     */
    protected function renderBodyIzinMengikutiUjian(FPDF $pdf, LetterRequest $letter, ?LetterType $letterType): void
    {
        $extra = $letter->extra_data ?? [];

        // 1. Opening Narrative (Indented first-line)
        if (! empty($letterType?->opening_template)) {
            $opening = $this->parseTemplate($letterType->opening_template, $letter);
        } else {
            $examTypeUpper = strtoupper($extra['exam_type'] ?? 'UAS');
            $namaUjian = ($examTypeUpper === 'UTS') ? 'Ujian Tengah Semester' : 'Ujian Akhir Semester';
            $semTipe = $extra['semester_type'] ?? ($letter->semester % 2 === 1 ? 'Ganjil' : 'Genap');
            $thnAkademik = $letter->academic_year ?? '2025/2026';
            $tglUjian = $extra['exam_dates'] ?? '12-19 Januari 2026';
            $prodi = $letter->study_program ?: 'Sistem Informasi';
            $title = strtolower($letter->signer_title ?? '');
            $name = strtolower($letter->signer_name ?? '');

            if (str_contains($title, 'wakil') || str_contains($name, 'bambang')) {
                $role = 'Wakil Dekan Fakultas Ilmu Komputer Universitas Subang';
            } elseif (str_contains($title, 'dekan') || str_contains($name, 'tepi')) {
                $role = 'Dekan Fakultas Ilmu Komputer Universitas Subang';
            } else {
                $role = "Ketua Program Studi {$prodi} Fakultas Ilmu Komputer Universitas Subang";
            }

            $opening = "Berdasarkan Kalender Akademik Universitas Subang bahwa akan dilaksanakannya {$namaUjian} {$semTipe} Tahun Akademik {$thnAkademik} pada tanggal {$tglUjian}, kami yang bertanda tangan di bawah ini, {$role}, menerangkan bahwa :";
        }

        $pdf->SetFont('Helvetica', '', 9.5);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetX(35);
        $pdf->Write(4.6, $opening);
        $pdf->Ln(7);

        // 2. Student Table (NO, NPM, NAMA - with Bold NPM and Bold NAMA)
        $wNo = 16;
        $wNpm = 50;
        $wName = 104;

        // Header Row
        $pdf->SetFont('Helvetica', 'B', 9.5);
        $pdf->Cell($wNo, 6.5, 'NO', 1, 0, 'C');
        $pdf->Cell($wNpm, 6.5, 'NPM', 1, 0, 'C');
        $pdf->Cell($wName, 6.5, 'NAMA', 1, 1, 'C');

        // Main Student Row
        $students = [];
        $students[] = [
            'npm' => $letter->npm ?? '-',
            'name' => mb_strtoupper($letter->student_name ?? '-', 'UTF-8'),
        ];

        if (! empty($extra['group_members']) && is_array($extra['group_members'])) {
            foreach ($extra['group_members'] as $m) {
                if (! empty($m['name'])) {
                    $students[] = [
                        'npm' => ! empty($m['npm']) ? trim($m['npm']) : '-',
                        'name' => mb_strtoupper(trim($m['name']), 'UTF-8'),
                    ];
                }
            }
        }

        foreach ($students as $idx => $st) {
            $noStr = (string) ($idx + 1);
            $npmStr = $st['npm'];
            $nameStr = $st['name'];

            $nameLines = ($pdf->GetStringWidth($nameStr) > ($wName - 4)) ? 2 : 1;
            $rowH = $nameLines === 1 ? 7 : 10;
            $lineH = $nameLines === 1 ? 7 : 5;

            $x = $pdf->GetX();
            $y = $pdf->GetY();

            $pdf->SetFont('Helvetica', '', 9.5);
            $pdf->Cell($wNo, $rowH, $noStr, 1, 0, 'C');

            $pdf->SetFont('Helvetica', 'B', 9.5);
            $pdf->Cell($wNpm, $rowH, $npmStr, 1, 0, 'C');

            if ($nameLines > 1) {
                $pdf->SetXY($x + $wNo + $wNpm, $y);
                $pdf->MultiCell($wName, $lineH, $nameStr, 1, 'C');
            } else {
                $pdf->Cell($wName, $rowH, $nameStr, 1, 1, 'C');
            }
        }

        $pdf->Ln(4.5);

        // 3. Middle Request Narrative (Indented first-line)
        if (! empty($letterType?->content_template)) {
            $content = $this->parseTemplate($letterType->content_template, $letter);
        } else {
            $examTypeUpper = strtoupper($extra['exam_type'] ?? 'UAS');
            $namaUjian = ($examTypeUpper === 'UTS') ? 'Ujian Tengah Semester' : 'Ujian Akhir Semester';
            $content = "Sehubungan dengan hal itu kami mohon kiranya Bapak/Ibu dapat memberikan **Izin Mengikuti {$namaUjian}** kepada mahasiswa yang bersangkutan dengan jadwal terlampir.";
        }

        $pdf->SetX(35);
        if (str_contains($content, '**')) {
            $parts = explode('**', $content);
            foreach ($parts as $idx => $part) {
                if ($part === '') {
                    continue;
                }
                $isBold = ($idx % 2 === 1);
                $pdf->SetFont('Helvetica', $isBold ? 'B' : '', 9.5);
                $pdf->Write(4.6, $part);
            }
        } else {
            $pdf->SetFont('Helvetica', '', 9.5);
            $pdf->Write(4.6, $content);
        }
        $pdf->Ln(7.5);

        // 4. Closing Narrative
        if (! empty($letterType?->closing_template)) {
            $closing = $this->parseTemplate($letterType->closing_template, $letter);
        } else {
            $closing = 'Atas bantuan dan kerjasamanya kami ucapkan terima kasih.';
        }

        $pdf->SetFont('Helvetica', '', 9.5);
        $pdf->MultiCell(170, 4.6, $closing, 0, 'L');
    }

    /**
     * Render specialized format for Surat Pengantar Ujian Susulan (UTS/UAS).
     */
    protected function renderBodyUjianSusulan(FPDF $pdf, LetterRequest $letter, ?LetterType $letterType): void
    {
        $extra = $letter->extra_data ?? [];
        $thnAkademik = $letter->academic_year ?? '2025/2026';
        $examTypeUpper = strtoupper($extra['exam_type'] ?? 'UTS');
        $examLabel = ($examTypeUpper === 'UAS') ? 'UAS susulan' : 'UTS susulan';
        $originalScore = ! empty($extra['original_score']) ? $extra['original_score'] : '-';

        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(15, 23, 42);

        // 1. Opening Paragraph
        if (! empty($letterType?->opening_template)) {
            $opening = $this->parseTemplate($letterType->opening_template, $letter);
        } else {
            $opening = "Bersama ini kami hadapkan Mahasiswa Fakultas Ilmu Komputer Universitas Subang Tahun Akademik {$thnAkademik} :";
        }

        $pdf->MultiCell(170, 5.5, $opening, 0, 'J');
        $pdf->Ln(3.5);

        // 2. Student Data Rows
        $wLabel = 38;
        $wColon = 4;
        $wVal = 128;
        $lineH = 5.5;

        // NAMA
        $pdf->Cell($wLabel, $lineH, 'NAMA', 0, 0, 'L');
        $pdf->Cell($wColon, $lineH, ':', 0, 0, 'C');
        $pdf->Cell($wVal, $lineH, mb_strtoupper($letter->student_name ?? '-', 'UTF-8'), 0, 1, 'L');

        // NPM
        $pdf->Cell($wLabel, $lineH, 'NPM', 0, 0, 'L');
        $pdf->Cell($wColon, $lineH, ':', 0, 0, 'C');
        $pdf->Cell($wVal, $lineH, $letter->npm ?? '-', 0, 1, 'L');

        // PROGRAM STUDI
        $pdf->Cell($wLabel, $lineH, 'PROGRAM STUDI', 0, 0, 'L');
        $pdf->Cell($wColon, $lineH, ':', 0, 0, 'C');
        $pdf->Cell($wVal, $lineH, mb_strtoupper($letter->study_program ?? 'SISTEM INFORMASI', 'UTF-8'), 0, 1, 'L');

        // NILAI ASAL
        $pdf->Cell($wLabel, $lineH, 'NILAI ASAL', 0, 0, 'L');
        $pdf->Cell($wColon, $lineH, ':', 0, 0, 'C');
        $pdf->Cell($wVal, $lineH, $originalScore, 0, 1, 'L');

        $pdf->Ln(4.5);

        // 3. Middle Request Paragraph
        if (! empty($letterType?->content_template)) {
            $content = $this->parseTemplate($letterType->content_template, $letter);
        } else {
            $content = "Dengan ini kami mohon kesediaan Bapak / Ibu memberikan kesempatan untuk {$examLabel}. Penilaian diberikan dengan angka mutu sesuai dengan komponen Nilai yang tidak lengkap.";
        }

        $pdf->MultiCell(170, 5.5, $content, 0, 'J');
        $pdf->Ln(4.5);

        // 4. Closing Paragraph
        if (! empty($letterType?->closing_template)) {
            $closing = $this->parseTemplate($letterType->closing_template, $letter);
        } else {
            $closing = 'Demikian atas kesediaanya kami ucapkan terima kasih';
        }

        $pdf->MultiCell(170, 5.5, $closing, 0, 'L');
    }

    /**
     * Render specialized format for Surat Pengantar Perbaikan Nilai.
     */
    protected function renderBodyPerbaikanNilai(FPDF $pdf, LetterRequest $letter, ?LetterType $letterType): void
    {
        $extra = $letter->extra_data ?? [];
        $thnAkademik = $letter->academic_year ?? '2025/2026';
        $originalScore = ! empty($extra['original_score']) ? $extra['original_score'] : 'T';

        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(15, 23, 42);

        // 1. Opening Paragraph
        if (! empty($letterType?->opening_template)) {
            $opening = $this->parseTemplate($letterType->opening_template, $letter);
        } else {
            $opening = "Bersama ini kami hadapkan Mahasiswa Fakultas Ilmu Komputer Universitas Subang Tahun Akademik {$thnAkademik} :";
        }

        $pdf->MultiCell(170, 5.5, $opening, 0, 'J');
        $pdf->Ln(3.5);

        // 2. Student Data Rows
        $wLabel = 38;
        $wColon = 4;
        $wVal = 128;
        $lineH = 5.5;

        // NAMA
        $pdf->Cell($wLabel, $lineH, 'NAMA', 0, 0, 'L');
        $pdf->Cell($wColon, $lineH, ':', 0, 0, 'C');
        $pdf->Cell($wVal, $lineH, mb_strtoupper($letter->student_name ?? '-', 'UTF-8'), 0, 1, 'L');

        // NPM
        $pdf->Cell($wLabel, $lineH, 'NPM', 0, 0, 'L');
        $pdf->Cell($wColon, $lineH, ':', 0, 0, 'C');
        $pdf->Cell($wVal, $lineH, $letter->npm ?? '-', 0, 1, 'L');

        // PROGRAM STUDI
        $pdf->Cell($wLabel, $lineH, 'PROGRAM STUDI', 0, 0, 'L');
        $pdf->Cell($wColon, $lineH, ':', 0, 0, 'C');
        $pdf->Cell($wVal, $lineH, mb_strtoupper($letter->study_program ?? 'SISTEM INFORMASI', 'UTF-8'), 0, 1, 'L');

        // NILAI ASAL
        $pdf->Cell($wLabel, $lineH, 'NILAI ASAL', 0, 0, 'L');
        $pdf->Cell($wColon, $lineH, ':', 0, 0, 'C');
        $pdf->Cell($wVal, $lineH, $originalScore, 0, 1, 'L');

        $pdf->Ln(4.5);

        // 3. Middle Request Paragraph
        if (! empty($letterType?->content_template)) {
            $content = $this->parseTemplate($letterType->content_template, $letter);
        } else {
            $content = 'Dengan ini kami mohon kesediaan Bapak / Ibu memberikan kesempatan untuk perbaikan Nilai. Penilaian diberikan dengan angka mutu sesuai dengan komponen Nilai yang tidak lengkap.';
        }

        $pdf->MultiCell(170, 5.5, $content, 0, 'J');
        $pdf->Ln(4.5);

        // 4. Closing Paragraph
        if (! empty($letterType?->closing_template)) {
            $closing = $this->parseTemplate($letterType->closing_template, $letter);
        } else {
            $closing = 'Demikian atas kesediaanya kami ucapkan terima kasih';
        }

        $pdf->MultiCell(170, 5.5, $closing, 0, 'L');
    }

    /**
     * Render specialized format for Surat Keterangan Lulus (SKL).
     */
    protected function renderBodyKeteranganYudisium(FPDF $pdf, LetterRequest $letter, ?LetterType $letterType): void
    {
        $extra = $letter->extra_data ?? [];

        // Data Akademik Mahasiswa
        $studentName = mb_strtoupper($letter->student_name ?: 'M. RIO SYAHPUTRA', 'UTF-8');
        $npm = $letter->npm ?: 'D1A220090';
        $studyProgram = $letter->study_program ?: 'Sistem Informasi';

        // Format TTL
        $birthDateStr = $letter->birth_date ? Carbon::parse($letter->birth_date)->translatedFormat('d F Y') : '22 Juni 2004';
        $birthPlace = mb_strtoupper($letter->birth_place ?: 'SUBANG', 'UTF-8');
        $ttl = trim($birthPlace.' '.$birthDateStr);

        // Format Alamat
        $address = $letter->address ?: ($extra['address'] ?? 'Kp. Betok RT.05/02, Desa Mulyasari, Kec.Binong, Kab. Subang');

        // Parameter SK Dekan & Kelulusan
        $skNumber = ! empty($extra['sk_number']) ? $extra['sk_number'] : '270 /D/FIK-US/VI/2026';
        $skDate = ! empty($extra['sk_date']) ? $extra['sk_date'] : '26 Juni 2026';
        $semesterType = ! empty($extra['semester_type']) ? $extra['semester_type'] : ($letter->semester % 2 === 1 ? 'Ganjil' : 'Genap');
        $academicYear = $letter->academic_year ?: '2025/2026';

        $ipk = ! empty($extra['ipk']) ? $extra['ipk'] : '3.19';
        $yudisiumPredicate = mb_strtoupper(! empty($extra['yudisium_predicate']) ? $extra['yudisium_predicate'] : 'SANGAT MEMUASKAN', 'UTF-8');
        $degreeTitle = ! empty($extra['degree_title']) ? $extra['degree_title'] : 'Sarjana Komputer (S.Kom)';

        $pdf->SetTextColor(0, 0, 0);

        // 1. Paragraf Pembuka
        $defaultOpeningTemplate = '{pejabat_jabatan}, menerangkan bahwa :';
        $opening = ! empty($extra['opening_template'])
            ? $this->parseTemplate($extra['opening_template'], $letter)
            : ($letterType?->opening_template ? $this->parseTemplate($letterType->opening_template, $letter) : $this->parseTemplate($defaultOpeningTemplate, $letter));

        $pdf->SetFont('Helvetica', '', 10);
        $pdf->MultiCell(170, 5, $opening, 0, 'L');
        $pdf->Ln(2);

        // 2. Tabel Identitas Mahasiswa (Indented)
        $indent = 30;
        $wLabel = 40;
        $wColon = 4;
        $wVal = 170 - $indent - $wLabel - $wColon;
        $lineH = 5;

        // Nama
        $pdf->SetX(20 + $indent);
        $pdf->Cell($wLabel, $lineH, 'Nama', 0, 0, 'L');
        $pdf->Cell($wColon, $lineH, ':', 0, 0, 'L');
        $pdf->Cell($wVal, $lineH, $studentName, 0, 1, 'L');

        // NPM
        $pdf->SetX(20 + $indent);
        $pdf->Cell($wLabel, $lineH, 'NPM', 0, 0, 'L');
        $pdf->Cell($wColon, $lineH, ':', 0, 0, 'L');
        $pdf->Cell($wVal, $lineH, $npm, 0, 1, 'L');

        // Tempat, Tanggal Lahir
        $pdf->SetX(20 + $indent);
        $pdf->Cell($wLabel, $lineH, 'Tempat, Tanggal Lahir', 0, 0, 'L');
        $pdf->Cell($wColon, $lineH, ':', 0, 0, 'L');
        $pdf->Cell($wVal, $lineH, $ttl, 0, 1, 'L');

        // Alamat (bisa multi-baris)
        $pdf->SetX(20 + $indent);
        $pdf->Cell($wLabel, $lineH, 'Alamat', 0, 0, 'L');
        $pdf->Cell($wColon, $lineH, ':', 0, 0, 'L');
        $pdf->MultiCell($wVal, $lineH, $address, 0, 'L');

        $pdf->Ln(4);

        // 3. Paragraf Berdasarkan SK Dekan & Pernyataan
        $customContent = ! empty($extra['content_template'])
            ? $extra['content_template']
            : ($letterType?->content_template ?: '');

        $defaultP1 = "Berdasarkan Surat Keputusan Dekan Tentang Kelulusan Ujian Sidang Sarjana Semester {$semesterType} Tahun Akademik {$academicYear} Program Studi {$studyProgram} Fakultas Ilmu Komputer Universitas Subang, dengan Nomor : {$skNumber}, tanggal {$skDate}, kami menyatakan :";
        $defaultP2 = "Sehingga yang bersangkutan berhak memakai gelar {$degreeTitle}. Adapun Ijazah dan Transkrip Nilai akan kami proses lebih lanjut.";

        $p1 = $defaultP1;
        $p2 = $defaultP2;

        if (! empty($customContent)) {
            $parsedContent = $this->parseTemplate($customContent, $letter);
            $parsedContent = str_replace(
                ['{sk_number}', '{sk_date}', '{degree_title}', '{predikat_yudisium}', '{ipk}'],
                [$skNumber, $skDate, $degreeTitle, $yudisiumPredicate, $ipk],
                $parsedContent
            );

            // Jika template memuat dua paragraf (dipisahkan double newline)
            $parts = preg_split('/\r\n\r\n|\n\n/', trim($parsedContent), 2);
            if (count($parts) >= 2) {
                $p1 = trim($parts[0]);
                $p2 = trim($parts[1]);
            } else {
                $p1 = trim($parsedContent);
                // Jika custom content tidak memuat klausul gelar/ijazah, gunakan defaultP2
                if (str_contains($parsedContent, 'Sehingga yang bersangkutan berhak memakai gelar')) {
                    $p2 = '';
                }
            }
        }

        // Cetak Paragraf 1 (Berdasarkan SK Dekan...)
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->MultiCell(170, 5, $p1, 0, 'J');
        $pdf->Ln(4.5);

        // 4. Blok Pernyataan Kelulusan (LULUS, Predikat Yudisium, IPK)
        $pdf->SetFont('Helvetica', 'B', 14);
        $pdf->Cell(170, 6, 'LULUS', 0, 1, 'C');
        $pdf->Ln(1);

        $pdf->SetFont('Helvetica', '', 11);
        $pdf->Cell(170, 5.5, 'Predikat Yudisium : '.$yudisiumPredicate, 0, 1, 'C');
        $pdf->Cell(170, 5.5, 'Indek Prestasi Komulatif (IPK) : '.$ipk, 0, 1, 'C');
        $pdf->Ln(5);

        // 5. Paragraf 2 (Sehingga yang bersangkutan berhak memakai gelar... Adapun Ijazah dan Transkrip Nilai...)
        if (! empty($p2)) {
            $pdf->SetFont('Helvetica', '', 10);
            $pdf->MultiCell(170, 5, $p2, 0, 'J');
            $pdf->Ln(3.5);
        }

        // 6. Paragraf Penutup
        $defaultClosing = 'Demikian keterangan ini kami buat, untuk dipergunakan sebagaimana mestinya.';
        $closing = ! empty($extra['closing_template'])
            ? $this->parseTemplate($extra['closing_template'], $letter)
            : ($letterType?->closing_template ? $this->parseTemplate($letterType->closing_template, $letter) : $defaultClosing);

        $pdf->MultiCell(170, 5, $closing, 0, 'J');
    }

    /**
     * Render specialized format for Surat Undangan Rapat Akademik.
     */
    protected function renderBodyUndanganRapatAkademik(FPDF $pdf, LetterRequest $letter, ?LetterType $letterType): void
    {
        $extra = $letter->extra_data ?? [];

        $agenda = ! empty($extra['agenda']) ? $extra['agenda'] : 'Rapat Akademik';
        $dayDate = ! empty($extra['meeting_day_date']) ? $extra['meeting_day_date'] : 'Selasa, 8 September 2026';
        $time = ! empty($extra['meeting_time']) ? $extra['meeting_time'] : '09.00 WIB';
        $meetingType = $extra['meeting_type'] ?? 'offline'; // 'offline' or 'online'
        $location = ! empty($extra['meeting_location']) ? $extra['meeting_location'] : 'Ruang 12 Fakultas Ilmu Komputer';
        $meetLink = ! empty($extra['meeting_link']) ? $extra['meeting_link'] : 'https://meet.google.com/abc-defg-hij';

        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(15, 23, 42);

        // 1. Salutation
        $pdf->Cell(170, 5.5, 'Dengan hormat,', 0, 1, 'L');
        $pdf->Ln(2);

        // 2. Opening Paragraph
        if (! empty($letterType?->opening_template)) {
            $opening = $this->parseTemplate($letterType->opening_template, $letter);
        } else {
            $opening = "Bersama ini kami mengundang kepada Bapak/Ibu Dosen Fakultas Ilmu Komputer Universitas Subang, dalam rangka {$agenda} yang akan diselenggarakan pada:";
        }

        $pdf->MultiCell(170, 5.5, $opening, 0, 'J');
        $pdf->Ln(3.5);

        // 3. Meeting Details (Indented with aligned colons)
        $indent = 16;
        $wLabel = 36;
        $wColon = 4;
        $wVal = 114;
        $lineH = 5.5;

        // Hari / Tanggal
        $pdf->SetX(20 + $indent);
        $pdf->Cell($wLabel, $lineH, 'Hari / Tanggal', 0, 0, 'L');
        $pdf->Cell($wColon, $lineH, ':', 0, 0, 'C');
        $pdf->Cell($wVal, $lineH, $dayDate, 0, 1, 'L');

        // Waktu
        $pdf->SetX(20 + $indent);
        $pdf->Cell($wLabel, $lineH, 'Waktu', 0, 0, 'L');
        $pdf->Cell($wColon, $lineH, ':', 0, 0, 'C');
        $pdf->Cell($wVal, $lineH, $time, 0, 1, 'L');

        // Tempat
        $pdf->SetX(20 + $indent);
        $pdf->Cell($wLabel, $lineH, 'Tempat', 0, 0, 'L');
        $pdf->Cell($wColon, $lineH, ':', 0, 0, 'C');
        if ($meetingType === 'online') {
            $platform = ! empty($extra['meeting_platform']) ? $extra['meeting_platform'] : 'Google Meet';
            $pdf->Cell($wVal, $lineH, "Online ({$platform})", 0, 1, 'L');

            // Link Meet
            $pdf->SetX(20 + $indent);
            $pdf->Cell($wLabel, $lineH, 'Link Meet', 0, 0, 'L');
            $pdf->Cell($wColon, $lineH, ':', 0, 0, 'C');
            $pdf->SetTextColor(37, 99, 235);
            $pdf->Cell($wVal, $lineH, $meetLink, 0, 1, 'L');
            $pdf->SetTextColor(15, 23, 42);
        } else {
            $pdf->Cell($wVal, $lineH, $location, 0, 1, 'L');
        }

        $pdf->Ln(3.5);

        // 4. Closing Paragraph
        if (! empty($letterType?->closing_template)) {
            $closing = $this->parseTemplate($letterType->closing_template, $letter);
        } else {
            $closing = 'Demikian undangan kami sampaikan, atas perhatiannya diucapkan terimakasih.';
        }

        $pdf->MultiCell(170, 5.5, $closing, 0, 'L');
    }

    /**
     * Render Attachment Page (Lampiran Halaman 2: Daftar Mahasiswa Program Magang MBKM atau Jadwal Ujian UTS/UAS).
     */
    protected function renderAttachmentPageIfNeeded(FPDF $pdf, LetterRequest $letter): void
    {
        if ($letter->letterType?->code === 'pengantar_magang') {
            $this->renderAttachmentPageMagang($pdf, $letter);

            return;
        }

        if ($letter->letterType?->code === 'izin_mengikuti_ujian') {
            $this->renderAttachmentPageJadwalUjian($pdf, $letter);

            return;
        }
    }

    /**
     * Render Attachment Page for Magang MBKM (Halaman 2).
     */
    protected function renderAttachmentPageMagang(FPDF $pdf, LetterRequest $letter): void
    {
        $extra = $letter->extra_data ?? [];

        // Add Page 2 for Lampiran
        $pdf->AddPage();

        // Render Kop Surat Resmi on Lampiran (Page 2)
        $this->renderHeader($pdf);

        $agency = ! empty($extra['target_agency'])
            ? $extra['target_agency']
            : (! empty($extra['target_recipient'])
                ? preg_replace('/^(Kepala\s+|Pimpinan\s+)/i', '', $extra['target_recipient'])
                : 'Kantor Urusan Agama (KUA) Kecamatan Ciater Kabupaten Subang');
        $agencyUpper = mb_strtoupper($agency, 'UTF-8');

        // Attachment Title (positioned below Kop Surat double-line)
        $pdf->SetY(53);
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(170, 5.5, 'DAFTAR MAHASISWA PROGRAM MAGANG MBKM', 0, 1, 'C');
        $pdf->MultiCell(170, 5.5, $agencyUpper, 0, 'C');
        $pdf->Ln(5);

        // Build Student List (Applicant as No. 1, plus additional group members)
        $students = [];
        $students[] = [
            'npm' => $letter->npm ?? '-',
            'name' => mb_strtoupper($letter->student_name ?? '-', 'UTF-8'),
        ];

        if (! empty($extra['group_members']) && is_array($extra['group_members'])) {
            foreach ($extra['group_members'] as $m) {
                if (! empty($m['name'])) {
                    $students[] = [
                        'npm' => ! empty($m['npm']) ? trim($m['npm']) : '-',
                        'name' => mb_strtoupper(trim($m['name']), 'UTF-8'),
                    ];
                }
            }
        }

        // Table Dimensions: Total width 170mm (NO: 16mm, NPM: 64mm, NAMA: 90mm)
        $wNo = 16;
        $wNpm = 64;
        $wName = 90;

        // Table Header
        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->Cell($wNo, 7, 'NO', 1, 0, 'C');
        $pdf->Cell($wNpm, 7, 'NPM', 1, 0, 'C');
        $pdf->Cell($wName, 7, 'NAMA', 1, 1, 'C');

        // Table Rows
        $pdf->SetFont('Helvetica', '', 9.5);
        foreach ($students as $idx => $s) {
            $noStr = ($idx + 1).'.';
            $npmStr = $s['npm'];
            $nameStr = $s['name'];

            $nameLines = ($pdf->GetStringWidth($nameStr) > ($wName - 4)) ? 2 : 1;
            $rowH = $nameLines === 1 ? 6.5 : 10;
            $lineH = $nameLines === 1 ? 6.5 : 5;

            $x = $pdf->GetX();
            $y = $pdf->GetY();

            $pdf->Cell($wNo, $rowH, $noStr, 1, 0, 'C');
            $pdf->Cell($wNpm, $rowH, $npmStr, 1, 0, 'C');

            if ($nameLines > 1) {
                $pdf->SetXY($x + $wNo + $wNpm, $y);
                $pdf->MultiCell($wName, $lineH, '  '.$nameStr, 1, 'L');
            } else {
                $pdf->Cell($wName, $rowH, '  '.$nameStr, 1, 1, 'L');
            }
        }

        $pdf->Ln(4);

        // Signature on Lampiran (Page 2)
        $this->renderSignatureBlock($pdf, $letter);
    }

    /**
     * Render Attachment Page for Jadwal Ujian UTS / UAS (Halaman 2).
     */
    protected function renderAttachmentPageJadwalUjian(FPDF $pdf, LetterRequest $letter): void
    {
        $extra = $letter->extra_data ?? [];

        // Add Page 2 for Lampiran
        $pdf->AddPage();

        // Render Kop Surat Resmi on Lampiran (Page 2)
        $this->renderHeader($pdf);

        $examTypeUpper = strtoupper($extra['exam_type'] ?? 'UAS');
        if (! empty($extra['lampiran_exam_title'])) {
            $rawJudul = trim($extra['lampiran_exam_title']);
            $ujianJudul = stripos($rawJudul, 'JADWAL') === 0 ? strtoupper($rawJudul) : 'JADWAL '.strtoupper($rawJudul);
        } else {
            $ujianJudul = ($examTypeUpper === 'UTS') ? 'JADWAL UJIAN TENGAH SEMESTER' : 'JADWAL UJIAN AKHIR SEMESTER';
        }
        $semTipeUpper = strtoupper($extra['semester_type'] ?? ($letter->semester % 2 === 1 ? 'GANJIL' : 'GENAP'));
        $thnAkademik = ! empty($extra['academic_year']) ? trim($extra['academic_year']) : ($letter->academic_year ?? '2026/2027');

        // Attachment Title (positioned below Kop Surat double-line)
        $pdf->SetY(53);
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(170, 5, $ujianJudul, 0, 1, 'C');
        $pdf->Cell(170, 5, "SEMESTER {$semTipeUpper} TAHUN AKADEMIK {$thnAkademik}", 0, 1, 'C');
        $pdf->Ln(6);

        // Build Exam Schedule Table
        // Total width: 170mm (NO: 12, NAMA MK: 56, KELAS: 18, SKS: 14, TANGGAL: 30, WAKTU: 22, RUANG: 18)
        $wNo = 12;
        $wMk = 56;
        $wKelas = 18;
        $wSks = 14;
        $wTgl = 30;
        $wWaktu = 22;
        $wRuang = 18;

        // Table Header
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->Cell($wNo, 6.5, 'NO', 1, 0, 'C');
        $pdf->Cell($wMk, 6.5, 'NAMA MK', 1, 0, 'C');
        $pdf->Cell($wKelas, 6.5, 'KELAS', 1, 0, 'C');
        $pdf->Cell($wSks, 6.5, 'SKS', 1, 0, 'C');
        $pdf->Cell($wTgl, 6.5, 'TANGGAL', 1, 0, 'C');
        $pdf->Cell($wWaktu, 6.5, 'WAKTU', 1, 0, 'C');
        $pdf->Cell($wRuang, 6.5, 'RUANG', 1, 1, 'C');

        // Table Data Rows
        $schedules = $extra['exam_schedules'] ?? [];
        if (empty($schedules)) {
            $schedules = [
                [
                    'course_name' => 'Audit Sistem Informasi',
                    'class_name' => '4A',
                    'sks' => 3,
                    'date' => '21-03-2025',
                    'time' => '08.00',
                    'room' => '8',
                ],
            ];
        }

        $pdf->SetFont('Helvetica', '', 8.5);
        foreach ($schedules as $idx => $sch) {
            $noStr = ($idx + 1).'.';
            $mkStr = $sch['course_name'] ?? ($sch['name'] ?? '-');
            $kelasStr = $sch['class_name'] ?? ($sch['kelas'] ?? '-');
            $sksStr = (string) ($sch['sks'] ?? '3');
            $tglStr = $sch['date'] ?? ($sch['tanggal'] ?? '-');
            $waktuStr = $sch['time'] ?? ($sch['waktu'] ?? '-');
            $ruangStr = (string) ($sch['room'] ?? ($sch['ruang'] ?? '-'));

            $nameLines = ($pdf->GetStringWidth($mkStr) > ($wMk - 4)) ? 2 : 1;
            $rowH = $nameLines === 1 ? 6 : 9;
            $lineH = $nameLines === 1 ? 6 : 4.5;

            $x = $pdf->GetX();
            $y = $pdf->GetY();

            $pdf->Cell($wNo, $rowH, $noStr, 1, 0, 'C');

            if ($nameLines > 1) {
                $pdf->SetXY($x + $wNo, $y);
                $pdf->MultiCell($wMk, $lineH, $mkStr, 1, 'C');
            } else {
                $pdf->Cell($wMk, $rowH, $mkStr, 1, 0, 'C');
            }

            $pdf->SetXY($x + $wNo + $wMk, $y);
            $pdf->Cell($wKelas, $rowH, $kelasStr, 1, 0, 'C');
            $pdf->Cell($wSks, $rowH, $sksStr, 1, 0, 'C');
            $pdf->Cell($wTgl, $rowH, $tglStr, 1, 0, 'C');
            $pdf->Cell($wWaktu, $rowH, $waktuStr, 1, 0, 'C');
            $pdf->Cell($wRuang, $rowH, $ruangStr, 1, 1, 'C');
        }

        $pdf->Ln(4);

        // Signature on Lampiran (Page 2)
        $this->renderSignatureBlock($pdf, $letter);
    }

    /**
     * Render specialized format for Surat Keterangan Mengikuti Seminar Usulan Penelitian / Sidang Tugas Akhir (Tabel Peserta).
     */
    protected function renderBodySeminarUsulanPenelitian(FPDF $pdf, LetterRequest $letter, ?LetterType $letterType): void
    {
        $extra = $letter->extra_data ?? [];
        $isSidangTa = ($letterType?->code === 'izin_sidang_ta');

        // 1. Opening Narrative
        if (! empty($letterType?->opening_template)) {
            $opening = $this->parseTemplate($letterType->opening_template, $letter);
        } else {
            $activityDefault = $isSidangTa ? 'Sidang Tugas Akhir' : 'Seminar Usulan Penelitian';
            $opening = "Berdasarkan jadwal {$activityDefault} yang dilaksanakan secara offline (Tatap muka) Mahasiswa bertepatan dengan kegiatan {$activityDefault} yang sedang berjalan di Fakultas Ilmu Komputer, maka dengan ini kami menerangkan bahwa:";
        }

        $pdf->SetFont('Helvetica', '', 9.5);
        $pdf->SetTextColor(15, 23, 42);
        $this->renderMarkdownFormattedText($pdf, $opening, 4.6, 9.5);
        $pdf->Ln(2.5);

        // 2. Build Student Participant List (Main applicant is No 1, plus optional additional participants)
        $defaultActivity = ! empty($extra['activity_name'])
            ? $extra['activity_name']
            : ($isSidangTa ? 'Sidang Tugas Akhir' : 'Seminar Usulan Penelitian');
        $defaultDay = ! empty($extra['event_day']) ? $extra['event_day'] : 'Kamis';

        $participants = [];
        $participants[] = [
            'name' => mb_strtoupper($letter->student_name ?? '-', 'UTF-8'),
            'npm' => $letter->npm ?? '-',
            'activity' => $defaultActivity,
            'day' => $defaultDay,
        ];

        if (! empty($extra['group_members']) && is_array($extra['group_members'])) {
            foreach ($extra['group_members'] as $m) {
                if (! empty($m['name'])) {
                    $participants[] = [
                        'name' => mb_strtoupper(trim($m['name']), 'UTF-8'),
                        'npm' => ! empty($m['npm']) ? trim($m['npm']) : '-',
                        'activity' => ! empty($m['activity']) ? trim($m['activity']) : $defaultActivity,
                        'day' => ! empty($m['day']) ? trim($m['day']) : $defaultDay,
                    ];
                }
            }
        }

        // 3. Render Table
        // Total printable width: 170mm (12 + 68 + 35 + 35 + 20)
        $wNo = 12;
        $wName = 68;
        $wNpm = 35;
        $wKegiatan = 35;
        $wHari = 20;

        // Header Row
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->Cell($wNo, 6, 'NO', 1, 0, 'C');
        $pdf->Cell($wName, 6, 'NAMA', 1, 0, 'C');
        $pdf->Cell($wNpm, 6, 'NPM', 1, 0, 'C');
        $pdf->Cell($wKegiatan, 6, 'KEGIATAN', 1, 0, 'C');
        $pdf->Cell($wHari, 6, 'HARI', 1, 1, 'C');

        // Data Rows
        $pdf->SetFont('Helvetica', '', 8.5);
        foreach ($participants as $index => $p) {
            $noStr = (string) ($index + 1);
            $nameStr = $p['name'];
            $npmStr = $p['npm'];
            $kegiatanStr = $p['activity'];
            $hariStr = $p['day'];

            // Determine if wrapped onto 2 lines
            $kegiatanLines = (strlen($kegiatanStr) > 18 || str_contains($kegiatanStr, "\n") || $pdf->GetStringWidth($kegiatanStr) > ($wKegiatan - 4)) ? 2 : 1;
            $nameLines = ($pdf->GetStringWidth($nameStr) > ($wName - 4)) ? 2 : 1;
            $lines = max($kegiatanLines, $nameLines, 1);
            $rowH = $lines === 1 ? 6.0 : 9.2;
            $lineH = $lines === 1 ? 6.0 : 4.6;

            $x = $pdf->GetX();
            $y = $pdf->GetY();

            $pdf->Cell($wNo, $rowH, $noStr, 1, 0, 'C');

            if ($nameLines > 1) {
                $pdf->SetXY($x + $wNo, $y);
                $pdf->MultiCell($wName, $lineH, $nameStr, 1, 'C');
            } else {
                $pdf->Cell($wName, $rowH, $nameStr, 1, 0, 'C');
            }

            $pdf->SetXY($x + $wNo + $wName, $y);
            $pdf->Cell($wNpm, $rowH, $npmStr, 1, 0, 'C');

            $pdf->SetXY($x + $wNo + $wName + $wNpm, $y);
            if ($kegiatanLines > 1) {
                $pdf->MultiCell($wKegiatan, $lineH, $kegiatanStr, 1, 'C');
            } else {
                $pdf->Cell($wKegiatan, $rowH, $kegiatanStr, 1, 0, 'C');
            }

            $pdf->SetXY($x + $wNo + $wName + $wNpm + $wKegiatan, $y);
            $pdf->Cell($wHari, $rowH, $hariStr, 1, 1, 'C');
        }

        $pdf->Ln(3.5);

        // 4. Content / Narrative
        if (! empty($letterType?->content_template)) {
            $content = $this->parseTemplate($letterType->content_template, $letter);
        } else {
            $actPhrase = $isSidangTa ? 'Mengikuti Sidang Tugas Akhir' : 'Mengikuti Seminar Usulan Penelitian';
            $content = "Sehubungan dengan hal itu kami mohon kiranya Bapak/Ibu dapat memberikan izin kepada mahasiswa yang bersangkutan untuk **{$actPhrase}**.";
        }
        $this->renderMarkdownFormattedText($pdf, $content, 4.6, 9.5);

        $pdf->Ln(2.5);

        // 5. Closing Narrative
        if (! empty($letterType?->closing_template)) {
            $closing = $this->parseTemplate($letterType->closing_template, $letter);
        } else {
            $closing = 'Demikian surat ini dibuat, atas bantuan dan kerjasamanya kami ucapkan terima kasih.';
        }
        $pdf->SetFont('Helvetica', '', 9.5);
        $pdf->MultiCell(170, 4.6, $closing, 0, 'J');
    }

    /**
     * Render specialized format for Surat Tugas Mengajar (Jadwal Mengajar Dosen).
     */
    protected function renderBodySuratTugasMengajar(FPDF $pdf, LetterRequest $letter, ?LetterType $letterType): void
    {
        $extra = $letter->extra_data ?? [];

        // 1. Opening Narrative
        if (! empty($letterType?->opening_template)) {
            $opening = $this->parseTemplate($letterType->opening_template, $letter);
        } else {
            $title = strtolower($letter->signer_title ?? '');
            $name = strtolower($letter->signer_name ?? '');

            if (str_contains($title, 'wakil') || str_contains($name, 'bambang')) {
                $role = 'Wakil Dekan Fakultas Ilmu Komputer Universitas Subang';
            } elseif (str_contains($title, 'dekan') || str_contains($name, 'tepi')) {
                $role = 'Dekan Fakultas Ilmu Komputer Universitas Subang';
            } else {
                $prodi = $letter->study_program ?: 'Sistem Informasi';
                $role = "Ketua Program Studi {$prodi} Fakultas Ilmu Komputer Universitas Subang";
            }
            $opening = "Yang bertanda tangan di bawah ini {$role} dengan ini menugaskan :";
        }

        $pdf->SetFont('Helvetica', '', 9.5);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->MultiCell(170, 4.8, $opening, 0, 'L');
        $pdf->Ln(4);

        // 2. Lecturer Info
        $dosenName = mb_strtoupper($letter->student_name ?? ($extra['lecturer_name'] ?? '-'), 'UTF-8');
        $statusPengajar = $extra['lecturer_status'] ?? 'Dosen Tetap';
        $nidn = $extra['nidn'] ?? ($letter->npm ?? '-');
        $prodi = $letter->study_program ?: 'Sistem Informasi';
        $semesterTipe = $extra['semester_type'] ?? ($letter->semester % 2 === 1 ? 'Ganjil' : 'Genap');
        $tahunAkademik = $letter->academic_year ?? '2026/2027';

        $lecturerFields = [
            ['label' => 'N A M A', 'val' => $dosenName, 'bold' => true],
            ['label' => 'STATUS TENAGA PENGAJAR', 'val' => $statusPengajar, 'bold' => false],
            ['label' => 'NIDN', 'val' => $nidn, 'bold' => false],
            ['label' => 'PROGRAM STUDI', 'val' => $prodi, 'bold' => false],
            ['label' => 'SEMESTER', 'val' => $semesterTipe, 'bold' => false],
            ['label' => 'TAHUN AKADEMIK', 'val' => $tahunAkademik, 'bold' => false],
        ];

        $indent = 4;
        $labelW = 54;
        $colonW = 4;
        $valW = 170 - $indent - $labelW - $colonW;

        foreach ($lecturerFields as $field) {
            $pdf->SetX(20 + $indent);
            $pdf->SetFont('Helvetica', '', 9.5);
            $pdf->Cell($labelW, 4.8, $field['label'], 0, 0, 'L');
            $pdf->Cell($colonW, 4.8, ':', 0, 0, 'C');

            if ($field['bold']) {
                $pdf->SetFont('Helvetica', 'B', 9.5);
            } else {
                $pdf->SetFont('Helvetica', '', 9.5);
            }
            $pdf->Cell($valW, 4.8, $field['val'], 0, 1, 'L');
        }

        $pdf->Ln(4.5);

        // 3. Teaching Schedule Table
        $schedules = $extra['teaching_schedules'] ?? [];
        if (empty($schedules) && ! empty($extra['course_name'])) {
            $schedules = [
                [
                    'course_name' => $extra['course_name'],
                    'code' => $extra['course_code'] ?? 'D1A12004',
                    'sks' => $extra['sks'] ?? 2,
                    'day' => $extra['day'] ?? 'Kamis',
                    'time' => $extra['time'] ?? '18.30-20.00',
                    'room' => $extra['room'] ?? 'Lab II',
                ],
            ];
        }

        // Col widths: 8 + 48 + 20 + 12 + 18 + 20 + 24 + 20 = 170mm
        $wNo = 8;
        $wMatkul = 48;
        $wKode = 20;
        $wSks = 12;
        $wKelas = 18;
        $wHari = 20;
        $wJam = 24;
        $wRuang = 20;

        // Header Row
        $pdf->SetFont('Helvetica', 'B', 8.5);
        $pdf->Cell($wNo, 6.5, 'No.', 1, 0, 'C');
        $pdf->Cell($wMatkul, 6.5, 'Mata Kuliah', 1, 0, 'C');
        $pdf->Cell($wKode, 6.5, 'Kode', 1, 0, 'C');
        $pdf->Cell($wSks, 6.5, 'SKS', 1, 0, 'C');
        $pdf->Cell($wKelas, 6.5, 'Kelas', 1, 0, 'C');
        $pdf->Cell($wHari, 6.5, 'H a r i', 1, 0, 'C');
        $pdf->Cell($wJam, 6.5, 'J a m', 1, 0, 'C');
        $pdf->Cell($wRuang, 6.5, 'RUANG', 1, 1, 'C');

        // Data Rows
        $pdf->SetFont('Helvetica', '', 8.5);
        foreach ($schedules as $index => $sch) {
            $noStr = ($index + 1).'.';
            $matkulStr = $sch['course_name'] ?? ($sch['name'] ?? '-');
            $kodeStr = $sch['code'] ?? ($sch['course_code'] ?? '-');
            $sksStr = (string) ($sch['sks'] ?? '2');
            $kelasStr = $sch['class_name'] ?? ($sch['class'] ?? ($sch['kelas'] ?? '-'));
            $hariStr = $sch['day'] ?? ($sch['hari'] ?? '-');
            $jamStr = $sch['time'] ?? ($sch['jam'] ?? '-');
            $ruangStr = $sch['room'] ?? ($sch['ruang'] ?? '-');

            $this->renderTableRow($pdf, [
                ['width' => $wNo, 'text' => $noStr, 'align' => 'C'],
                ['width' => $wMatkul, 'text' => ' '.$matkulStr, 'align' => 'L'],
                ['width' => $wKode, 'text' => $kodeStr, 'align' => 'C'],
                ['width' => $wSks, 'text' => $sksStr, 'align' => 'C'],
                ['width' => $wKelas, 'text' => $kelasStr, 'align' => 'C'],
                ['width' => $wHari, 'text' => $hariStr, 'align' => 'C'],
                ['width' => $wJam, 'text' => $jamStr, 'align' => 'C'],
                ['width' => $wRuang, 'text' => $ruangStr, 'align' => 'C'],
            ], 4.4, 6.0);
        }

        $pdf->Ln(4.5);

        // 4. Closing Narrative
        $closing = ! empty($letterType?->content_template)
            ? $this->parseTemplate($letterType->content_template, $letter)
            : 'Demikian Surat Tugas ini agar dilaksanakan dengan berpedoman pada Kurikulum dan Silabus Jurusan serta Satuan Acara Perkuliahan (SAP) yang dibuat oleh masing-masing Dosen.';

        $pdf->SetFont('Helvetica', '', 9.5);
        $pdf->MultiCell(170, 4.8, $closing, 0, 'J');
    }

    /**
     * Render specialized format for Surat Pernyataan Kesanggupan Mengajar.
     */
    protected function renderBodyKesanggupanMengajar(FPDF $pdf, LetterRequest $letter, ?LetterType $letterType): void
    {
        $extra = $letter->extra_data ?? [];

        $dosenName = $extra['lecturer_name'] ?? ($letter->student_name ?? '-');
        $nipNidn = ! empty($extra['nip']) ? ($extra['nip'].' / '.($extra['nidn'] ?? '-')) : (! empty($extra['nidn']) ? $extra['nidn'] : ($letter->npm ?? '-'));
        $jabatanAkademik = $extra['academic_rank'] ?? ($extra['functional_position'] ?? ($extra['jabatan_akademik'] ?? 'Tenaga Pengajar'));
        $alamat = $extra['address'] ?? ($letter->address ?? 'Subang');
        $email = $extra['email'] ?? ($extra['lecturer_email'] ?? 'dosen@unsub.ac.id');
        $keahlian = $extra['expertise'] ?? ($extra['keahlian'] ?? '-');

        $semTipe = $extra['semester_type'] ?? ($letter->semester % 2 === 1 ? 'Ganjil' : 'Genap');
        $thnAkademik = $letter->academic_year ?? '2026/2027';

        // 1. Opening Narrative
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(170, 5, 'Saya yang bertandatangan di bawah ini :', 0, 1, 'L');
        $pdf->Ln(2);

        // 2. Data Dosen Rows
        $labelW = 42;
        $colonW = 5;
        $valW = 118;
        $indent = 8;
        $rowH = 5.2;

        $fields = [
            ['label' => 'Nama', 'val' => $dosenName],
            ['label' => 'NIP/NIDN', 'val' => $nipNidn],
            ['label' => 'Jabatan Akadmik', 'val' => $jabatanAkademik],
            ['label' => 'Alamat', 'val' => $alamat],
            ['label' => 'Email', 'val' => $email],
            ['label' => 'Keahlian', 'val' => $keahlian],
        ];

        foreach ($fields as $f) {
            $pdf->SetX(20 + $indent);
            $pdf->SetFont('Helvetica', '', 9.5);
            $pdf->Cell($labelW, $rowH, $f['label'], 0, 0, 'L');
            $pdf->Cell($colonW, $rowH, ':', 0, 0, 'C');
            $pdf->Cell($valW, $rowH, $f['val'], 0, 1, 'L');
        }

        $pdf->Ln(3.5);

        // 3. Middle Statement Paragraph
        $middleText = "Menyatakan BERSEDIA DAN SANGGUP menjadi dosen pengampu pada Semester {$semTipe} Tahun Akademik {$thnAkademik}, dengan MATA KULIAH sebagaimana tersebut dalam tabel di bawah ini.";
        $this->renderMarkdownFormattedText($pdf, $middleText, 5.0, 10);
        $pdf->Ln(2.5);

        // 4. Tabel Mata Kuliah Kesanggupan Mengajar
        // Kolom: No (10), Kode MK (28), Nama Mata Kuliah (52), Jenis Pengajaran (36), Kelas (16), SKS (14), Total SKS (14) = 170mm
        $wNo = 10;
        $wKode = 28;
        $wNama = 52;
        $wJenis = 36;
        $wKelas = 16;
        $wSks = 14;
        $wTotalSks = 14;

        // Header Row
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->Cell($wNo, 6.5, 'No.', 1, 0, 'C');
        $pdf->Cell($wKode, 6.5, 'Kode MK', 1, 0, 'C');
        $pdf->Cell($wNama, 6.5, 'Nama Mata Kuliah', 1, 0, 'C');
        $pdf->Cell($wJenis, 6.5, 'Jenis Pengajaran', 1, 0, 'C');
        $pdf->Cell($wKelas, 6.5, 'Kelas', 1, 0, 'C');
        $pdf->Cell($wSks, 6.5, 'SKS', 1, 0, 'C');
        $pdf->Cell($wTotalSks, 6.5, 'Total SKS', 1, 1, 'C');

        // Rows Data
        $courses = $extra['teaching_courses'] ?? ($extra['teaching_schedules'] ?? []);
        if (empty($courses)) {
            $courses = [
                [
                    'code' => 'D1A12004',
                    'name' => 'Desain WEB',
                    'teaching_type' => '',
                    'class_name' => '3',
                    'sks' => 2,
                    'total_sks' => 6,
                ],
            ];
        }

        $totalSksAll = 0;
        $pdf->SetFont('Helvetica', '', 8.5);

        foreach ($courses as $idx => $c) {
            $noStr = (string) ($idx + 1);
            $kodeStr = $c['code'] ?? ($c['kode'] ?? '-');
            $namaStr = $c['name'] ?? ($c['course_name'] ?? '-');
            $jenisStr = $c['teaching_type'] ?? ($c['jenis_pengajaran'] ?? '');
            $kelasStr = (string) ($c['class_name'] ?? ($c['kelas'] ?? ''));
            $sksNum = (int) ($c['sks'] ?? 0);
            $totalSksItem = isset($c['total_sks']) ? (int) $c['total_sks'] : ($sksNum * (is_numeric($kelasStr) ? (int) $kelasStr : 1));
            $totalSksAll += $totalSksItem;

            $this->renderTableRow($pdf, [
                ['width' => $wNo, 'text' => $noStr, 'align' => 'C'],
                ['width' => $wKode, 'text' => $kodeStr, 'align' => 'C'],
                ['width' => $wNama, 'text' => ' '.$namaStr, 'align' => 'L'],
                ['width' => $wJenis, 'text' => $jenisStr, 'align' => 'C'],
                ['width' => $wKelas, 'text' => $kelasStr, 'align' => 'C'],
                ['width' => $wSks, 'text' => (string) $sksNum, 'align' => 'C'],
                ['width' => $wTotalSks, 'text' => (string) $totalSksItem, 'align' => 'C'],
            ], 4.2, 5.8);
        }

        // Tampilkan baris kosong tambahan jika kurang dari 5 baris agar mirip format cetak aslinya
        $count = count($courses);
        if ($count < 5) {
            for ($i = $count; $i < 5; $i++) {
                $this->renderTableRow($pdf, [
                    ['width' => $wNo, 'text' => '', 'align' => 'C'],
                    ['width' => $wKode, 'text' => '', 'align' => 'C'],
                    ['width' => $wNama, 'text' => '', 'align' => 'L'],
                    ['width' => $wJenis, 'text' => '', 'align' => 'C'],
                    ['width' => $wKelas, 'text' => '', 'align' => 'C'],
                    ['width' => $wSks, 'text' => '', 'align' => 'C'],
                    ['width' => $wTotalSks, 'text' => '', 'align' => 'C'],
                ], 4.2, 5.8);
            }
        }

        // Footer Row Total Jumlah SKS
        $displayTotal = ! empty($extra['total_sks_sum']) ? $extra['total_sks_sum'] : ($totalSksAll > 0 ? (string) $totalSksAll : '');
        $pdf->SetFont('Helvetica', 'B', 9);
        $wJumlahLabel = $wNo + $wKode + $wNama + $wJenis + $wKelas + $wSks; // 156mm
        $pdf->Cell($wJumlahLabel, 6.2, 'Jumlah', 1, 0, 'C');
        $pdf->Cell($wTotalSks, 6.2, (string) $displayTotal, 1, 1, 'C');

        $pdf->Ln(4.5);

        // 5. Closing Narrative
        $closing = ! empty($letterType?->closing_template)
            ? $this->parseTemplate($letterType->closing_template, $letter)
            : 'Demikian pernyataan ini saya sampaikan, untuk dapat dipergunakan sebagaimana mestinya.';

        $pdf->SetFont('Helvetica', '', 10);
        $pdf->MultiCell(170, 5, $closing, 0, 'J');
    }

    /**
     * Render specialized format for Fakta Integritas Dosen Pengampu Mata Kuliah.
     */
    protected function renderBodyPaktaIntegritas(FPDF $pdf, LetterRequest $letter, ?LetterType $letterType): void
    {
        $extra = $letter->extra_data ?? [];

        // 1. Opening text (Mendukung custom opening template dari Studio atau default)
        $customOpening = ! empty($extra['opening_template']) ? $extra['opening_template'] : ($letterType?->opening_template ?? null);
        $opening = ! empty($customOpening)
            ? $this->parseTemplate($customOpening, $letter)
            : 'Saya yang bertanda tangan di bawah ini:';

        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->MultiCell(170, 5, $opening, 0, 'L');
        $pdf->Ln(2);

        // 2. Data Dosen
        $lecturerName = ! empty($extra['lecturer_name']) ? $extra['lecturer_name'] : ($letter->student_name ?: 'BAGUS ALI AKBAR, S.SI., M.Kom');
        $coursesTaught = ! empty($extra['courses_taught']) ? $extra['courses_taught'] : ($extra['course_name'] ?? '');

        // Fallback jika belum diisi atau dari format teaching_courses
        if (empty($coursesTaught) && ! empty($extra['teaching_courses']) && is_array($extra['teaching_courses'])) {
            $cNames = array_filter(array_map(fn ($c) => $c['name'] ?? '', $extra['teaching_courses']));
            if (! empty($cNames)) {
                $coursesTaught = implode(', ', $cNames);
            }
        }

        // Tampilkan Nama
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->Cell(45, 5, 'Nama', 0, 0, 'L');
        $pdf->Cell(4, 5, ':', 0, 0, 'C');
        $pdf->Cell(121, 5, $lecturerName, 0, 1, 'L');

        // Tampilkan Mata Kuliah yang diampu
        $pdf->Cell(45, 5, 'Mata Kuliah yang diampu', 0, 0, 'L');
        $pdf->Cell(4, 5, ':', 0, 0, 'C');
        if (! empty($coursesTaught)) {
            $pdf->MultiCell(121, 5, $coursesTaught, 0, 'L');
        } else {
            $pdf->Cell(121, 5, '', 0, 1, 'L');
        }

        $pdf->Ln(4);

        // 3. Kalimat Pernyataan (Mendukung custom content template dari Studio atau default)
        $customContent = ! empty($extra['content_template']) ? $extra['content_template'] : ($letterType?->content_template ?? null);
        $statementText = ! empty($customContent)
            ? $this->parseTemplate($customContent, $letter)
            : 'Menyatakan bahwa saya akan melaksanakan ketentuan sebagai berikut:';

        $pdf->SetFont('Helvetica', '', 10);
        $pdf->MultiCell(170, 5, $statementText, 0, 'L');
        $pdf->Ln(1.5);

        // 4. Butir-butir Komitmen Fakta Integritas (Dinamis dari input form atau fallback ke 5 butir standar)
        $defaultItems = [
            'Bersedia hadir dan melaksanakan perkuliahan tatap muka pada kelas yang telah dijadwalkan oleh Prodi 14 kali pertemuan secara online di LMS (Learning Management Sistem) Universitas Subang Komputer maupun offline di lingkungan Fakultas Ilmu Komputer, bilamana dalam pelaksanaan perkuliahan tidak melaksanakan kewajiban mengajar dalam jangka waktu 3 kali berturut-turut tanpa konfirmasi kepada Kaprodi, maka Kaprodi berhak mengambil alih matakuliah dan mengganti Dosen Pengampu Mata Kuliah.',
            'Bersedia membuat perangkat pembelajaran baik berupa RPS dan materi pembelajaran.',
            'Bersedia memberikan pelayanan terkait dengan perkuliahan melalui media komunikasi online maupun offline dengan mahasiswa yang mengontrak mata kuliah yang diampu (dapat merespon pertanyaan mahasiswa terkait dengan perkuliahan).',
            'Bersedia mengisi Berita Acara Perkuliahan pada setiap pertemuan melalui SIAKAD UNSUB.',
            'Bersedia mengisi nilai hasil Ujian Tengah Semester (UTS) dan Ujian Akhir Semester (UAS) pada SIAKAD maksimal dua minggu setelah pelaksanaan Ujian. Bilamana hingga batas waktu yang telah ditentukan belum melakukan pengumpulan nilai maka Kaprodi berhak mengambil alih dalam pemberian nilai mahasiswa.',
        ];

        $rawPoints = ! empty($extra['integrity_points']) && is_array($extra['integrity_points'])
            ? array_values(array_filter(array_map('trim', $extra['integrity_points'])))
            : $defaultItems;

        if (empty($rawPoints)) {
            $rawPoints = $defaultItems;
        }

        $numWidth = 6;
        $textWidth = 170 - $numWidth;

        foreach ($rawPoints as $index => $desc) {
            $num = ($index + 1).'.';
            $pdf->SetFont('Helvetica', '', 9.8);
            $pdf->Cell($numWidth, 4.8, $num, 0, 0, 'L');
            $pdf->MultiCell($textWidth, 4.8, $desc, 0, 'J');
            $pdf->Ln(1.5);
        }

        $pdf->Ln(2);

        // 5. Paragraf Penutup & Sanksi (Mendukung custom closing template dari Studio atau default)
        $defaultClosing = 'Demikian pernyataan ini saya buat, apabila di kemudian hari saya tidak memenuhi ketentuan di atas, saya bersedia menerima sanksi baik berupa teguran lisan, teguran tertulis, serta tidak diberi kesempatan mengampu mata kuliah pada semester berikutnya.';
        $customClosing = ! empty($extra['closing_template']) ? $extra['closing_template'] : ($letterType?->closing_template ?? null);
        $closing = ! empty($customClosing)
            ? $this->parseTemplate($customClosing, $letter)
            : $defaultClosing;

        $pdf->MultiCell(170, 4.8, $closing, 0, 'J');
    }

    /**
     * Render specialized format for Surat Pengajuan Kebutuhan Dana Fakultas.
     */
    protected function renderBodyPengajuanDana(FPDF $pdf, LetterRequest $letter, ?LetterType $letterType): void
    {
        $extra = $letter->extra_data ?? [];

        // 1. Narasi Pembuka
        $monthPeriod = ! empty($extra['period_month_year']) ? $extra['period_month_year'] : 'November 2026';
        $defaultOpening = "Bersama ini kami sampaikan dengan hormat kebutuhan dana bulan {$monthPeriod}\nsebesar :";
        $opening = ! empty($extra['opening_template']) ? $extra['opening_template'] : $defaultOpening;

        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->MultiCell(170, 5, $opening, 0, 'L');
        $pdf->Ln(3);

        // 2. Data Angka Keuangan
        // Nilai Default sesuai contoh surat pengguna
        $pagu = ! empty($extra['pagu_amount']) ? $extra['pagu_amount'] : '165.200.000';
        $pencairan1Label = ! empty($extra['disbursement_1_label']) ? $extra['disbursement_1_label'] : 'Pencairan bulan September 2026';
        $pencairan1Val = ! empty($extra['disbursement_1_amount']) ? $extra['disbursement_1_amount'] : '6.950.000';
        $pencairan2Label = ! empty($extra['disbursement_2_label']) ? $extra['disbursement_2_label'] : 'Pencairan bulan Oktober 2026';
        $pencairan2Val = ! empty($extra['disbursement_2_amount']) ? $extra['disbursement_2_amount'] : '13.203.500';
        $sisaPaguVal = ! empty($extra['remaining_pagu_amount']) ? $extra['remaining_pagu_amount'] : '145.046.500';

        $pengajuanLabel = ! empty($extra['submitted_label']) ? $extra['submitted_label'] : 'Pengajuan November 2026';
        $pengajuanVal = ! empty($extra['submitted_amount']) ? $extra['submitted_amount'] : '26.101.509';
        $saldoLaluLabel = ! empty($extra['last_balance_label']) ? $extra['last_balance_label'] : 'Saldo Penggunaan bulan lalu';
        $saldoLaluVal = ! empty($extra['last_balance_amount']) ? $extra['last_balance_amount'] : '1.439.509';
        $finalAmount = ! empty($extra['final_net_amount']) ? $extra['final_net_amount'] : '24.662.000';

        $terbilangText = ! empty($extra['terbilang'])
            ? $extra['terbilang']
            : 'Dua Puluh Empat Juta Enam Ratus Enam Puluh Dua Ribu Rupiah';

        // Layout perhitungan bertingkat
        // Indentasi kolom kiri: 35mm dari margin kiri (20 + 35 = 55)
        $leftMargin = 40;
        $labelW1 = 70;
        $currW = 10;
        $numW = 40;

        // Baris Pagu
        $pdf->SetX($leftMargin);
        $pdf->Cell($labelW1, 5, 'Pagu', 0, 0, 'L');
        $pdf->Cell(20, 5, '', 0, 0, 'L');
        $pdf->Cell($currW, 5, 'Rp', 0, 0, 'R');
        $pdf->Cell($numW, 5, $pagu, 0, 1, 'R');

        // Baris Pencairan 1
        $pdf->SetX($leftMargin);
        $pdf->Cell($labelW1, 5, $pencairan1Label, 0, 0, 'L');
        $pdf->Cell(20, 5, '', 0, 0, 'L');
        $pdf->Cell($currW, 5, 'Rp', 0, 0, 'R');
        $pdf->Cell($numW, 5, $pencairan1Val, 0, 1, 'R');

        // Baris Pencairan 2 (dengan underline)
        $pdf->SetX($leftMargin);
        $pdf->Cell($labelW1, 5, $pencairan2Label, 0, 0, 'L');
        $pdf->Cell(20, 5, '', 0, 0, 'L');
        $pdf->Cell($currW, 5, 'Rp', 0, 0, 'R');
        $pdf->Cell($numW, 5, $pencairan2Val, 0, 1, 'R');

        // Garis pemisah Jumlah 1
        $lineX1 = $leftMargin + $labelW1 + 20;
        $lineX2 = $lineX1 + $currW + $numW;
        $pdf->SetLineWidth(0.3);
        $pdf->Line($lineX1, $pdf->GetY(), $lineX2, $pdf->GetY());

        // Baris Jumlah 1 (Sisa Pagu)
        $pdf->SetX($leftMargin);
        $pdf->Cell($labelW1, 5.5, 'Jumlah', 0, 0, 'C');
        $pdf->Cell(20, 5.5, '', 0, 0, 'L');
        $pdf->Cell($currW, 5.5, 'Rp', 0, 0, 'R');
        $pdf->Cell($numW, 5.5, $sisaPaguVal, 0, 1, 'R');

        // Baris Pengajuan Bulan Ini
        $pdf->SetX($leftMargin);
        $pdf->Cell($labelW1, 5, $pengajuanLabel, 0, 0, 'L');
        $pdf->Cell(8, 5, 'Rp', 0, 0, 'R');
        $pdf->Cell(28, 5, $pengajuanVal, 0, 1, 'R');

        // Baris Saldo Penggunaan Bulan Lalu (dengan underline)
        $pdf->SetX($leftMargin);
        $pdf->Cell($labelW1, 5, $saldoLaluLabel, 0, 0, 'L');
        $pdf->Cell(8, 5, 'Rp', 0, 0, 'R');
        $pdf->Cell(28, 5, $saldoLaluVal, 0, 1, 'R');

        // Garis pemisah Jumlah 2
        $subLineX1 = $leftMargin + $labelW1;
        $subLineX2 = $subLineX1 + 8 + 28;
        $pdf->Line($subLineX1, $pdf->GetY(), $subLineX2, $pdf->GetY());

        // Baris Jumlah 2 (Final Net)
        $pdf->SetX($leftMargin);
        $pdf->Cell($labelW1, 5.5, 'Jumlah', 0, 0, 'C');
        $pdf->Cell(8, 5.5, 'Rp', 0, 0, 'R');
        $pdf->Cell(28, 5.5, $finalAmount, 0, 1, 'R');

        $pdf->Ln(6);

        // 3. Terbilang
        $pdf->SetX(20);
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->Cell(26, 5, 'Terbilang :', 0, 0, 'L');
        $pdf->Cell(144, 5, '('.trim($terbilangText).')', 0, 1, 'L');

        // 4. Narasi Penutup (Opsional)
        if (! empty($extra['closing_template'])) {
            $pdf->Ln(4);
            $pdf->SetX(20);
            $pdf->MultiCell(170, 5, $extra['closing_template'], 0, 'L');
        }
    }

    /**
     * Render specialized format for Surat Pengajuan Pengadaan Sarana dan Prasarana.
     */
    protected function renderBodyPengadaanSarpras(FPDF $pdf, LetterRequest $letter, ?LetterType $letterType): void
    {
        $extra = $letter->extra_data ?? [];

        // 1. Salam Pembuka
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(170, 5, 'Dengan hormat,', 0, 1, 'L');
        $pdf->Ln(2);

        // 2. Paragraf Pembuka / Permohonan dengan Bold Highlight
        $unitKerja = ! empty($extra['unit_kerja']) ? $extra['unit_kerja'] : 'BAAK FAKULTAS ILMU KOMPUTER';
        $academicYear = ! empty($extra['academic_year']) ? $extra['academic_year'] : ($letter->academic_year ?? '2026/2027');

        // Ambil daftar item dari Form Rincian
        $items = $extra['sarpras_items'] ?? [];
        $validItems = [];
        if (is_array($items)) {
            foreach ($items as $it) {
                $itText = is_array($it) ? ($it['name'] ?? ($it['text'] ?? '')) : (string) $it;
                if (trim($itText) !== '') {
                    $validItems[] = trim($itText);
                }
            }
        }

        // Teks pembuka: prioritas dari template kustom jika diisi user, jika tidak gunakan narasi baku resmi
        if (! empty($extra['opening_template'])) {
            $this->renderMarkdownFormattedText($pdf, $extra['opening_template'], 5.2, 10);
            $pdf->Ln(2);
        } elseif (! empty($letterType?->opening_template)) {
            $narrative = $this->parseTemplate($letterType->opening_template, $letter);
            $this->renderMarkdownFormattedText($pdf, $narrative, 5.2, 10);
            $pdf->Ln(2);
        } else {
            // Narasi pembuka baku resmi:
            // "Sehubungan dengan meningkatnya aktivitas dan kebutuhan perangkat kerja di [UNIT KERJA] di Tahun akademik [TA], bersama ini kami mengajukan permohonan pengadaan sarana dan prasarana dengan spesifikasi terlampir guna menunjang pekerjaan harian."
            $pdf->Write(5.2, 'Sehubungan dengan meningkatnya aktivitas dan kebutuhan perangkat kerja di ');
            $pdf->SetFont('Helvetica', 'B', 10);
            $pdf->Write(5.2, $unitKerja);
            $pdf->SetFont('Helvetica', '', 10);
            $pdf->Write(5.2, ' di Tahun akademik '.$academicYear.', bersama ini kami mengajukan ');
            $pdf->SetFont('Helvetica', 'B', 10);
            $pdf->Write(5.2, 'Permohonan Pengadaan Sarana dan Prasarana');
            $pdf->SetFont('Helvetica', '', 10);
            $pdf->Write(5.2, ' dengan spesifikasi terlampir guna menunjang pekerjaan harian.');
            $pdf->Ln(7.5);
        }

        // 3. Rincian Item Sarana & Prasarana
        if (! empty($validItems)) {
            $pdf->SetFont('Helvetica', '', 10);
            $pdf->Cell(170, 5, 'Adapun rincian sarana dan prasarana yang diajukan yaitu :', 0, 1, 'L');
            $pdf->Ln(2);

            $wNo = 6;
            $wText = 164;
            foreach ($validItems as $idx => $itName) {
                $pdf->SetX(20);
                $pdf->SetFont('Helvetica', '', 10);
                $pdf->Cell($wNo, 5.2, ($idx + 1).'.', 0, 0, 'L');
                $pdf->MultiCell($wText, 5.2, $itName, 0, 'L');
                $pdf->Ln(2);
            }
            $pdf->Ln(2);
        }

        // 4. Sebagai bahan pertimbangan :
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->Cell(170, 5, 'Sebagai bahan pertimbangan :', 0, 1, 'L');
        $pdf->Ln(2);

        // Poin-poin pertimbangan
        $considerations = $extra['considerations'] ?? [];
        if (empty($considerations)) {
            $considerations = [
                'Perangkat di bagian kerja sudah tidak dapat digunakan secara optimal.',
                'Meningkatnya beban kerja dan kompleksitas operasional layanan akademik.',
                'Perangkat yang ada saat ini sudah berusia lebih dari 10 tahun.',
            ];
        }

        $wNo = 6;
        $wText = 164;
        foreach ($considerations as $idx => $point) {
            $pointText = is_array($point) ? ($point['text'] ?? '') : (string) $point;
            if (trim($pointText) === '') {
                continue;
            }

            $pdf->SetX(20);
            $pdf->SetFont('Helvetica', '', 10);
            $pdf->Cell($wNo, 5.2, ($idx + 1).'.', 0, 0, 'L');
            $pdf->MultiCell($wText, 5.2, trim($pointText), 0, 'J');
            $pdf->Ln(2.5);
        }

        $pdf->Ln(2);

        // 5. Paragraf Harapan
        $hopeText = ! empty($extra['content_template'])
            ? $extra['content_template']
            : (! empty($extra['hope_text'])
                ? $extra['hope_text']
                : 'Kami berharap permohonan ini dapat dipenuhi guna memenuhi kelancaran operasional harian dan pelayanan akademik bagi mahasiswa.');
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->MultiCell(170, 5.2, $hopeText, 0, 'J');
        $pdf->Ln(3.5);

        // 6. Paragraf Penutup
        if (! empty($extra['closing_template'])) {
            $closing = $extra['closing_template'];
        } elseif (! empty($letterType?->closing_template)) {
            $closing = $this->parseTemplate($letterType->closing_template, $letter);
        } else {
            $closing = 'Demikian surat permohonan ini kami sampaikan. Atas perhatian dan kerjasamanya, kami ucapkan terima kasih.';
        }
        $pdf->MultiCell(170, 5.2, $closing, 0, 'J');
    }

    /**
     * Render specialized format for Surat Perbaikan Sarana dan Prasarana.
     */
    protected function renderBodyPerbaikanSarpras(FPDF $pdf, LetterRequest $letter, ?LetterType $letterType): void
    {
        $extra = $letter->extra_data ?? [];

        // 1. Salam Pembuka
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(170, 5, 'Dengan Hormat,', 0, 1, 'L');
        $pdf->Ln(2);

        // 2. Paragraf Pembuka Permohonan
        if (! empty($extra['opening_template'])) {
            $this->renderMarkdownFormattedText($pdf, $extra['opening_template'], 5.5, 10);
            $pdf->Ln(2);
        } elseif (! empty($letterType?->opening_template)) {
            $opening = $this->parseTemplate($letterType->opening_template, $letter);
            $this->renderMarkdownFormattedText($pdf, $opening, 5.5, 10);
            $pdf->Ln(2);
        } else {
            $opening = 'Bersama ini kami sampaikan surat permohonan pergantian Sarana dan Prasarana Fakultas Ilmu Komputer Universitas Subang yang memerlukan pergantian.';
            $pdf->MultiCell(170, 5.5, $opening, 0, 'L');
            $pdf->Ln(2);
        }

        // 3. Kalimat Pengantar Item
        $itemIntro = ! empty($extra['content_template'])
            ? $extra['content_template']
            : 'Adapun sarana dan prasarana yang kami mohonkan yaitu :';
        $pdf->MultiCell(170, 5, $itemIntro, 0, 'L');
        $pdf->Ln(2.5);

        // 4. Daftar Item Sarana dan Prasarana
        $items = $extra['sarpras_items'] ?? [];
        if (empty($items)) {
            $items = [
                'Kursi Pelayanan BAAK Fakultas Ilmu Komputer Sebanyak 10',
                'Sofa 1 Set Ruang Dosen',
            ];
        }

        $wNo = 6;
        $wText = 164;
        foreach ($items as $idx => $item) {
            $itemText = is_array($item) ? ($item['name'] ?? ($item['text'] ?? '')) : (string) $item;
            if (trim($itemText) === '') {
                continue;
            }

            $pdf->SetX(20);
            $pdf->SetFont('Helvetica', '', 10);
            $pdf->Cell($wNo, 5.5, ($idx + 1).'.', 0, 0, 'L');
            $pdf->MultiCell($wText, 5.5, trim($itemText), 0, 'L');
            $pdf->Ln(2);
        }

        $pdf->Ln(2.5);

        // 5. Paragraf Penutup
        if (! empty($extra['closing_template'])) {
            $closing = $extra['closing_template'];
        } elseif (! empty($letterType?->closing_template)) {
            $closing = $this->parseTemplate($letterType->closing_template, $letter);
        } else {
            $closing = 'Demikian surat permohonan ini kami sampaikan, atas perhatiannya kami ucapkan terimakasih.';
        }
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->MultiCell(170, 5.5, $closing, 0, 'J');
    }

    /**
     * Render specialized format for Surat Pemberitahuan UTS/UAS ke Dosen.
     */
    protected function renderBodyPemberitahuanUjianDosen(FPDF $pdf, LetterRequest $letter, ?LetterType $letterType): void
    {
        $extra = $letter->extra_data ?? [];

        // 1. Salam Pembuka
        $pdf->SetFont('Helvetica', '', 9.5);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(170, 4.5, 'Dengan Hormat,', 0, 1, 'L');
        $pdf->Ln(2);

        // 2. Paragraf Pembuka Permohonan
        $examType = $extra['exam_type'] ?? 'Ujian Tengah Semester';
        $semesterType = $extra['semester_type'] ?? 'Ganjil';
        $academicYear = $extra['academic_year'] ?? '2026/2027';
        $examPeriod = $extra['exam_period'] ?? '9 November 2026 s/d 14 November 2026';

        if (! empty($extra['opening_template'])) {
            $opening = $this->parseTemplate($extra['opening_template'], $letter);
            $this->renderMarkdownFormattedText($pdf, $opening, 4.6, 9.5);
            $pdf->Ln(2);
        } elseif (! empty($letterType?->opening_template)) {
            $opening = $this->parseTemplate($letterType->opening_template, $letter);
            $this->renderMarkdownFormattedText($pdf, $opening, 4.6, 9.5);
        } else {
            // "Diberitahukan sesuai dengan kalender Akademik Fakultas Ilmu Komputer, Bahwa pelaksanaan Ujian Tengah Semester Ganjil TA. 2026/2027 akan dilaksanakan secara Daring dan atau Luring mulai tanggal 9 November 2026 s/d 14 November 2026 dengan ketentuan sebagai berikut :"
            $pdf->Write(4.6, 'Diberitahukan sesuai dengan kalender Akademik Fakultas Ilmu Komputer, Bahwa pelaksanaan ');
            $pdf->SetFont('Helvetica', 'B', 9.5);
            $pdf->Write(4.6, "{$examType} {$semesterType} TA. {$academicYear}");
            $pdf->SetFont('Helvetica', '', 9.5);
            $pdf->Write(4.6, ' akan dilaksanakan secara ');
            $pdf->SetFont('Helvetica', 'I', 9.5);
            $pdf->Write(4.6, 'Daring dan atau Luring');
            $pdf->SetFont('Helvetica', '', 9.5);
            $pdf->Write(4.6, ' mulai tanggal ');
            $pdf->SetFont('Helvetica', 'B', 9.5);
            $pdf->Write(4.6, $examPeriod);
            $pdf->SetFont('Helvetica', 'I', 9.5);
            $pdf->Write(4.6, ' dengan ketentuan sebagai berikut :');
            $pdf->Ln(6.5);
        }

        // 3. Poin-Poin Ketentuan
        $defaultProvisions = [
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
        ];

        $points = $extra['provision_points'] ?? $defaultProvisions;

        $wNo = 6;
        $wText = 164;
        foreach ($points as $idx => $p) {
            $pText = is_array($p) ? ($p['text'] ?? '') : (string) $p;
            if (trim($pText) === '') {
                continue;
            }

            $pdf->SetX(20);
            $pdf->SetFont('Helvetica', '', 9.2);
            $pdf->Cell($wNo, 4.4, ($idx + 1).'.', 0, 0, 'L');
            $pdf->MultiCell($wText, 4.4, trim($pText), 0, 'J');
            $pdf->Ln(1.8);
        }

        $pdf->Ln(2);

        // 4. Paragraf Penutup
        if (! empty($extra['closing_template'])) {
            $closing = $this->parseTemplate($extra['closing_template'], $letter);
        } elseif (! empty($letterType?->closing_template)) {
            $closing = $this->parseTemplate($letterType->closing_template, $letter);
        } else {
            $closing = 'Demikian agar maklum, atas perhatiannya kami ucapkan terimakasih.';
        }
        $pdf->SetFont('Helvetica', '', 9.5);
        $pdf->MultiCell(170, 4.8, $closing, 0, 'J');
    }

    /**
     * Render specialized format for Surat Pengumuman UTS/UAS ke Mahasiswa.
     */
    protected function renderBodyPengumumanUjianMahasiswa(FPDF $pdf, LetterRequest $letter, ?LetterType $letterType): void
    {
        $extra = $letter->extra_data ?? [];

        // Parameter Ujian
        $examType = $extra['exam_type'] ?? 'Ujian Tengah Semester';
        $semesterType = $extra['semester_type'] ?? 'Ganjil';
        $academicYear = $extra['academic_year'] ?? '2026/2027';
        $examPeriod = $extra['exam_period'] ?? '9 s/d 14 November 2026';

        // 1. Paragraf Pembuka Pengumuman
        if (! empty($extra['opening_template'])) {
            $opening = $this->parseTemplate($extra['opening_template'], $letter);
            $this->renderMarkdownFormattedText($pdf, $opening, 5.0, 10);
            $pdf->Ln(2);
        } elseif (! empty($letterType?->opening_template)) {
            $opening = $this->parseTemplate($letterType->opening_template, $letter);
            $this->renderMarkdownFormattedText($pdf, $opening, 5.0, 10);
        } else {
            // "Diberitahukan sesuai dengan surat edaran dari Wakil Rektor I Bidang Akademik dan kemahasiswaan, Bahwa pelaksanaan Ujian Tengah Semester Ganjil TA. 2026/2027 akan dilaksanakan secara Daring dan atau Luring mulai tanggal 9 s/d 14 November 2026 dengan ketentuan sebagai berikut :"
            $sourceReference = ! empty($extra['source_reference'])
                ? $extra['source_reference']
                : 'surat edaran dari Wakil Rektor I Bidang Akademik dan kemahasiswaan';

            $pdf->SetFont('Helvetica', '', 10);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Write(5.0, 'Diberitahukan sesuai dengan '.$sourceReference.', Bahwa pelaksanaan ');
            $pdf->SetFont('Helvetica', 'B', 10);
            $pdf->Write(5.0, "{$examType} {$semesterType} TA. {$academicYear}");
            $pdf->SetFont('Helvetica', '', 10);
            $pdf->Write(5.0, ' akan dilaksanakan secara ');
            $pdf->SetFont('Helvetica', 'I', 10);
            $pdf->Write(5.0, 'Daring');
            $pdf->SetFont('Helvetica', '', 10);
            $pdf->Write(5.0, ' dan atau ');
            $pdf->SetFont('Helvetica', 'I', 10);
            $pdf->Write(5.0, 'Luring');
            $pdf->SetFont('Helvetica', '', 10);
            $pdf->Write(5.0, ' mulai tanggal ');
            $pdf->SetFont('Helvetica', 'B', 10);
            $pdf->Write(5.0, $examPeriod);
            $pdf->SetFont('Helvetica', '', 10);
            $pdf->Write(5.0, ' dengan ketentuan sebagai berikut :');
            $pdf->Ln(7.5);
        }

        // 2. Poin-Poin Ketentuan Pengumuman
        $defaultProvisions = [
            'Perkuliahan dilaksanakan sampai dengan tanggal 7 November 2026.',
            'Yang diujikan adalah mata kuliah yang telah melaksanakan Perkuliahan Daring dan atau Luring selama 7 (tujuh) kali sebelum UTS.',
            'Mahasiswa yang belum melunasi uang kuliah atau kurang dari 75%, serta bagi mahasiswa angkatan 2026, Angakatan 2023 dan P2K yang belum melunasi sampai dengan bulan November 2026 tidak diperkenankan mengikuti ujian.',
            'Mahasiswa Menggunakan Pakaian Putih Hitam dan Jas Almamater, jika UTS diadakan secara Luring.',
            'Hasil ujian maupun tugas/komponen Nilai Akhir lainnya, akan secepatnya diumumkan oleh Dosen kepada mahasiswa di SIAKAD maksimal 2 ( dua ) minggu setelah ujian.',
            'Tanggal 16 November 2026 kuliah seperti biasa',
        ];

        $points = $extra['provision_points'] ?? $defaultProvisions;

        $wNo = 6;
        $wText = 164;
        foreach ($points as $idx => $p) {
            $pText = is_array($p) ? ($p['text'] ?? '') : (string) $p;
            if (trim($pText) === '') {
                continue;
            }

            $pdf->SetX(20);
            $pdf->SetFont('Helvetica', '', 9.8);
            $pdf->Cell($wNo, 4.8, ($idx + 1).'.', 0, 0, 'L');
            $pdf->MultiCell($wText, 4.8, trim($pText), 0, 'J');
            $pdf->Ln(2.2);
        }

        $pdf->Ln(3);

        // 3. Paragraf Penutup
        if (! empty($extra['closing_template'])) {
            $closing = $this->parseTemplate($extra['closing_template'], $letter);
        } elseif (! empty($letterType?->closing_template)) {
            $closing = $this->parseTemplate($letterType->closing_template, $letter);
        } else {
            $closing = 'Demikian agar maklum, atas perhatiannya kami ucapkan terimakasih.';
        }
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->MultiCell(170, 5.0, $closing, 0, 'J');
    }

    /**
     * Render specialized format for Surat Pengantar Perbaikan Data Mahasiswa PDDIKTI.
     */
    protected function renderBodyPerbaikanDataPddikti(FPDF $pdf, LetterRequest $letter, ?LetterType $letterType): void
    {
        $extra = $letter->extra_data ?? [];

        // 1. Salam Pembuka
        $pdf->SetFont('Helvetica', '', 9.5);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(170, 5, 'Dengan Hormat,', 0, 1, 'L');
        $pdf->Ln(2);

        // 2. Paragraf Pembuka
        if (! empty($extra['opening_template'])) {
            $opening = $this->parseTemplate($extra['opening_template'], $letter);
        } elseif (! empty($letterType?->opening_template)) {
            $opening = $this->parseTemplate($letterType->opening_template, $letter);
        } else {
            $opening = 'Sehubungan dengan kesalahan pada data mahasiswa di bawah ini, dengan ini kami mengajukan perubahan data mahasiswa sesuai dengan tabel berikut ini:';
        }
        $pdf->MultiCell(170, 5.0, $opening, 0, 'J');
        $pdf->Ln(3.5);

        // 3. Tabel Perbaikan Data Mahasiswa
        $wNo = 10;
        $wNpm = 25;
        $wNama = 45;
        $wAwal = 45;
        $wUsulan = 45; // total = 170

        // Header Tabel
        $pdf->SetFont('Helvetica', 'B', 9.5);
        $this->renderTableRow($pdf, [
            ['width' => $wNo, 'text' => 'No', 'align' => 'C'],
            ['width' => $wNpm, 'text' => 'NPM', 'align' => 'C'],
            ['width' => $wNama, 'text' => "Nama\nMahasiswa", 'align' => 'C'],
            ['width' => $wAwal, 'text' => 'Data Awal', 'align' => 'C'],
            ['width' => $wUsulan, 'text' => 'Usulan Perbaikan', 'align' => 'C'],
        ], 4.5, 9.0);

        // Data Rows
        $items = $extra['correction_items'] ?? [];
        if (empty($items)) {
            $items = [
                [
                    'npm' => $letter->npm ?: 'D1A210020',
                    'name' => $letter->student_name ?: 'FANNY APRILIANTI',
                    'initial_data' => 'Kesalahan Nama : Fany Aprilianti',
                    'proposed_correction' => 'FANNY APRILIANTI',
                ],
            ];
        }

        $pdf->SetFont('Helvetica', '', 9);
        foreach ($items as $idx => $item) {
            $noStr = (string) ($idx + 1);
            $npmStr = (string) ($item['npm'] ?? '-');
            $namaStr = (string) ($item['name'] ?? '-');
            $awalStr = (string) ($item['initial_data'] ?? '-');
            $usulanStr = (string) ($item['proposed_correction'] ?? '-');

            $this->renderTableRow($pdf, [
                ['width' => $wNo, 'text' => $noStr, 'align' => 'C'],
                ['width' => $wNpm, 'text' => $npmStr, 'align' => 'C'],
                ['width' => $wNama, 'text' => $namaStr, 'align' => 'C'],
                ['width' => $wAwal, 'text' => $awalStr, 'align' => 'C'],
                ['width' => $wUsulan, 'text' => $usulanStr, 'align' => 'C'],
            ], 4.6, 12.0);
        }

        $pdf->Ln(4.5);

        // 4. Paragraf Penutup & Fakta
        $evidenceText = ! empty($extra['content_template'])
            ? $this->parseTemplate($extra['content_template'], $letter)
            : (! empty($extra['evidence_text'])
                ? $extra['evidence_text']
                : 'Data yang diubah sesuai dengan fakta dan dibuktikan dengan dokumen terlampir.');
        $pdf->SetFont('Helvetica', '', 9.5);
        $pdf->MultiCell(170, 4.8, $evidenceText, 0, 'J');
        $pdf->Ln(1);

        if (! empty($extra['closing_template'])) {
            $closing = $this->parseTemplate($extra['closing_template'], $letter);
        } elseif (! empty($letterType?->closing_template)) {
            $closing = $this->parseTemplate($letterType->closing_template, $letter);
        } else {
            $closing = 'Demikian surat ini kami buat untuk dapat ditindaklanjuti. Atas perhatiannya kami ucapkan terimakasih.';
        }
        $pdf->MultiCell(170, 4.8, $closing, 0, 'J');
    }

    /**
     * Render specialized format for Surat Pengantar Pembukaan Periode.
     */
    protected function renderBodyPembukaanPeriode(FPDF $pdf, LetterRequest $letter, ?LetterType $letterType): void
    {
        $extra = $letter->extra_data ?? [];

        // 1. Salam Pembuka
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(170, 5, 'Dengan Hormat,', 0, 1, 'L');
        $pdf->Ln(2.5);

        // 2. Paragraf Isi
        $purpose = ! empty($extra['purpose_description'])
            ? $extra['purpose_description']
            : 'Sehubungan dengan kebutuhan perbaikan data PIN mahasiswa yang akan lulus, dengan ini kami mengajukan permohonan Pembukaan Periode Tipe 2 melalui Feeder PDDIKTI untuk memperbaiki data pada Program Studi Sistem Informasi (57201). Adapun data yang kami ajukan sebagaimana data terlampir.';

        $pdf->MultiCell(170, 5.2, $purpose, 0, 'J');
        $pdf->Ln(2);

        // 3. Paragraf Penutup
        if (! empty($letterType?->closing_template)) {
            $closing = $this->parseTemplate($letterType->closing_template, $letter);
        } else {
            $closing = 'Demikian surat ini kami buat untuk dapat ditindaklanjuti. Atas perhatiannya kami ucapkan terimakasih.';
        }
        $pdf->MultiCell(170, 5.2, $closing, 0, 'J');
    }

    /**
     * Render specialized format for Surat Pengajuan Penandatanganan Ijazah.
     */
    protected function renderBodyPengajuanTtdIjazah(FPDF $pdf, LetterRequest $letter, ?LetterType $letterType): void
    {
        $extra = $letter->extra_data ?? [];

        // 1. Salam Pembuka
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(170, 5, 'Dengan Hormat,', 0, 1, 'L');
        $pdf->Ln(3.0);

        // 2. Paragraf Pembuka (dengan indentasi awal)
        $opening = ! empty($extra['opening_template'])
            ? $this->parseTemplate($extra['opening_template'], $letter)
            : (! empty($letterType?->opening_template)
                ? $this->parseTemplate($letterType->opening_template, $letter)
                : (! empty($extra['opening_text'])
                    ? $extra['opening_text']
                    : 'Bersama surat ini kami sampaikan bahwa Fakultas Ilmu Komputer Universitas Subang telah melaksanakan sidang komprehensif pada:'));

        // Teks dengan indentasi 10mm untuk paragraf pertama
        $indent = '          ';
        $pdf->MultiCell(170, 5.2, $indent.$opening, 0, 'J');
        $pdf->Ln(2.5);

        // 3. Tabel Rekap Tanggal Sidang & Jumlah Mahasiswa
        // Total printable width = 170mm (atau 150mm di tengah: X=30: No=12, Tgl=58, Jumlah=80)
        $wNo = 14;
        $wTgl = 58;
        $wJml = 98; // 14 + 58 + 98 = 170mm

        // Header Tabel
        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->SetX(20);
        $this->renderTableRow($pdf, [
            ['width' => $wNo, 'text' => 'No', 'align' => 'C'],
            ['width' => $wTgl, 'text' => 'Tanggal Sidang', 'align' => 'C'],
            ['width' => $wJml, 'text' => 'Jumlah', 'align' => 'C'],
        ], 5.0, 8.0);

        // Data Rows
        $sessions = $extra['session_summary_table'] ?? [];
        if (empty($sessions)) {
            $sessions = [
                [
                    'session_date' => '27 Agustus 2026',
                    'count_text' => '32 (Tiga Puluh Dua ) mahasiswa',
                ],
            ];
        }

        $pdf->SetFont('Helvetica', '', 9.5);
        foreach ($sessions as $idx => $item) {
            $noStr = (string) ($idx + 1);
            $tglStr = (string) ($item['session_date'] ?? '-');
            $jmlStr = (string) ($item['count_text'] ?? '-');

            $pdf->SetX(20);
            $this->renderTableRow($pdf, [
                ['width' => $wNo, 'text' => $noStr, 'align' => 'C'],
                ['width' => $wTgl, 'text' => $tglStr, 'align' => 'L'],
                ['width' => $wJml, 'text' => $jmlStr, 'align' => 'L'],
            ], 4.8, 8.0);
        }

        // Total Row
        $totalText = ! empty($extra['total_count_text']) ? $extra['total_count_text'] : '32 (Tiga Puluh Dua ) mahasiswa';
        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->SetX(20);
        $this->renderTableRow($pdf, [
            ['width' => $wNo + $wTgl, 'text' => 'Total', 'align' => 'C'],
            ['width' => $wJml, 'text' => $totalText, 'align' => 'L'],
        ], 5.0, 8.0);

        $pdf->Ln(3.5);

        // 4. Paragraf Pengajuan
        $bodyText = ! empty($extra['content_template'])
            ? $this->parseTemplate($extra['content_template'], $letter)
            : (! empty($letterType?->content_template)
                ? $this->parseTemplate($letterType->content_template, $letter)
                : (! empty($extra['body_text'])
                    ? $extra['body_text']
                    : 'bersamaan dengan ini kami mengajukan penandatanganan ijazah untuk mahasiswa tersebut diatas, data suplemen pengajuan terlampir.'));

        $pdf->SetFont('Helvetica', '', 10);
        $pdf->MultiCell(170, 5.2, $indent.$bodyText, 0, 'J');
        $pdf->Ln(2.0);

        // 5. Paragraf Penutup
        $closing = ! empty($extra['closing_template'])
            ? $this->parseTemplate($extra['closing_template'], $letter)
            : (! empty($letterType?->closing_template)
                ? $this->parseTemplate($letterType->closing_template, $letter)
                : 'Demikian surat pengajuan ini dibuat untuk dapat dipergunakan sebagaimana mestinya.');
        $pdf->MultiCell(170, 5.2, $closing, 0, 'J');
    }

    /**
     * Render specialized format for Surat Pengajuan Blanko Ijazah dan Blanko Transkrip.
     */
    protected function renderBodyPengajuanBlankoIjazahTranskrip(FPDF $pdf, LetterRequest $letter, ?LetterType $letterType): void
    {
        $extra = $letter->extra_data ?? [];

        // 1. Salam Pembuka
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(170, 5, 'Dengan hormat,', 0, 1, 'L');
        $pdf->Ln(3.0);

        // 2. Paragraf Pembuka (dengan indentasi awal)
        $opening = ! empty($extra['opening_template'])
            ? $this->parseTemplate($extra['opening_template'], $letter)
            : (! empty($letterType?->opening_template)
                ? $this->parseTemplate($letterType->opening_template, $letter)
                : (! empty($extra['opening_text'])
                    ? $extra['opening_text']
                    : 'Bersama surat ini kami sampaikan bahwa Fakultas Ilmu Komputer Universitas Subang telah melaksanakan sidang komprehensif pada:'));

        $indent = '          ';
        $pdf->MultiCell(170, 5.2, $indent.$opening, 0, 'J');
        $pdf->Ln(2.5);

        // 3. Tabel Rekap Tanggal Sidang & Jumlah Mahasiswa
        $wNo = 14;
        $wTgl = 58;
        $wJml = 98; // 14 + 58 + 98 = 170mm

        // Header Tabel
        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->SetX(20);
        $this->renderTableRow($pdf, [
            ['width' => $wNo, 'text' => 'No', 'align' => 'C'],
            ['width' => $wTgl, 'text' => 'Tanggal Sidang', 'align' => 'C'],
            ['width' => $wJml, 'text' => 'Jumlah', 'align' => 'C'],
        ], 5.0, 8.0);

        // Data Rows
        $sessions = $extra['session_summary_table'] ?? [];
        if (empty($sessions)) {
            $sessions = [
                [
                    'session_date' => '26 Juni 2026',
                    'count_text' => '12 ( Dua belas ) mahasiswa',
                ],
            ];
        }

        $pdf->SetFont('Helvetica', '', 9.5);
        foreach ($sessions as $idx => $item) {
            $noStr = (string) ($idx + 1);
            $tglStr = (string) ($item['session_date'] ?? '-');
            $jmlStr = (string) ($item['count_text'] ?? '-');

            $pdf->SetX(20);
            $this->renderTableRow($pdf, [
                ['width' => $wNo, 'text' => $noStr, 'align' => 'C'],
                ['width' => $wTgl, 'text' => $tglStr, 'align' => 'C'],
                ['width' => $wJml, 'text' => $jmlStr, 'align' => 'C'],
            ], 4.8, 8.0);
        }

        // Tampilkan Total jika lebih dari 1 baris, atau opsional
        if (! empty($extra['show_total_row']) || count($sessions) > 1) {
            $totalText = ! empty($extra['total_count_text']) ? $extra['total_count_text'] : '12 ( Dua belas ) mahasiswa';
            $pdf->SetFont('Helvetica', 'B', 10);
            $pdf->SetX(20);
            $this->renderTableRow($pdf, [
                ['width' => $wNo + $wTgl, 'text' => 'Total', 'align' => 'C'],
                ['width' => $wJml, 'text' => $totalText, 'align' => 'C'],
            ], 5.0, 8.0);
        }

        $pdf->Ln(4.0);

        // 4. Paragraf Pengajuan Permohonan Blanko
        $bodyText = ! empty($extra['content_template'])
            ? $this->parseTemplate($extra['content_template'], $letter)
            : (! empty($letterType?->content_template)
                ? $this->parseTemplate($letterType->content_template, $letter)
                : (! empty($extra['body_text'])
                    ? $extra['body_text']
                    : 'Sehubungan dengan hal tersebut, kami mengajukan permohonan blanko ijazah dan blanko transkrip pendamping ijazah untuk mahasiswa sebagaimana data terlampir.'));

        $pdf->SetFont('Helvetica', '', 10);
        $pdf->MultiCell(170, 5.2, $indent.$bodyText, 0, 'J');
        $pdf->Ln(3.0);

        // 5. Paragraf Penutup
        $closing = ! empty($extra['closing_template'])
            ? $this->parseTemplate($extra['closing_template'], $letter)
            : (! empty($letterType?->closing_template)
                ? $this->parseTemplate($letterType->closing_template, $letter)
                : 'Demikian surat pengajuan ini kami sampaikan untuk dapat dipergunakan sebagaimana mestinya. Atas perhatian dan kerja samanya kami ucapkan terima kasih.');
        $pdf->MultiCell(170, 5.2, $indent.$closing, 0, 'J');
    }

    /**
     * Render specialized format for Surat Tugas Pembimbing MBKM (Tabel Mahasiswa MBKM).
     */
    protected function renderBodySuratTugasPembimbingMbkm(FPDF $pdf, LetterRequest $letter, ?LetterType $letterType): void
    {
        $extra = $letter->extra_data ?? [];

        // 1. Opening Narrative
        if (! empty($letterType?->opening_template)) {
            $opening = $this->parseTemplate($letterType->opening_template, $letter);
        } else {
            $title = strtolower($letter->signer_title ?? '');
            $name = strtolower($letter->signer_name ?? '');

            if (str_contains($title, 'wakil') || str_contains($name, 'bambang')) {
                $role = 'Wakil Dekan Fakultas Ilmu Komputer Universitas Subang';
            } elseif (str_contains($title, 'dekan') || str_contains($name, 'tepi')) {
                $role = 'Dekan Fakultas Ilmu Komputer Universitas Subang';
            } else {
                $prodi = $letter->study_program ?: 'Sistem Informasi';
                $role = "Ketua Program Studi {$prodi} Fakultas Ilmu Komputer Universitas Subang";
            }
            $opening = "Yang bertanda tangan di bawah ini {$role} dengan ini menugaskan :";
        }

        $pdf->SetFont('Helvetica', '', 9.5);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->MultiCell(170, 4.8, $opening, 0, 'L');
        $pdf->Ln(4);

        // 2. Lecturer Info
        $dosenName = mb_strtoupper($letter->student_name ?? ($extra['lecturer_name'] ?? '-'), 'UTF-8');
        $statusPengajar = $extra['lecturer_status'] ?? 'Dosen Tetap';
        $nidn = $extra['nidn'] ?? ($letter->npm ?? '-');
        $prodi = $letter->study_program ?: 'Sistem Informasi';
        $semesterTipe = $extra['semester_type'] ?? ($letter->semester % 2 === 1 ? 'Ganjil' : 'Genap');
        $tahunAkademik = $letter->academic_year ?? '2026/2027';

        $lecturerFields = [
            ['label' => 'N A M A', 'val' => $dosenName, 'bold' => true],
            ['label' => 'STATUS TENAGA PENGAJAR', 'val' => $statusPengajar, 'bold' => false],
            ['label' => 'NIDN', 'val' => $nidn, 'bold' => false],
            ['label' => 'PROGRAM STUDI', 'val' => $prodi, 'bold' => false],
            ['label' => 'SEMESTER', 'val' => $semesterTipe, 'bold' => false],
            ['label' => 'TAHUN AKADEMIK', 'val' => $tahunAkademik, 'bold' => false],
        ];

        $indent = 4;
        $labelW = 54;
        $colonW = 4;
        $valW = 170 - $indent - $labelW - $colonW;

        foreach ($lecturerFields as $field) {
            $pdf->SetX(20 + $indent);
            $pdf->SetFont('Helvetica', '', 9.5);
            $pdf->Cell($labelW, 4.8, $field['label'], 0, 0, 'L');
            $pdf->Cell($colonW, 4.8, ':', 0, 0, 'C');

            if ($field['bold']) {
                $pdf->SetFont('Helvetica', 'B', 9.5);
            } else {
                $pdf->SetFont('Helvetica', '', 9.5);
            }
            $pdf->Cell($valW, 4.8, $field['val'], 0, 1, 'L');
        }

        $pdf->Ln(4.5);

        // 3. MBKM Students Table
        $students = $extra['mbkm_students'] ?? [];
        if (empty($students)) {
            $students = [
                [
                    'name' => 'LIA KARTIKA',
                    'npm' => 'D1A230409',
                    'mitra_project' => 'Dicoding (Studi Independen)',
                    'role' => 'Pembimbing I',
                ],
            ];
        }

        // Column widths: 10 + 52 + 26 + 54 + 28 = 170mm
        $wNo = 10;
        $wName = 52;
        $wNpm = 26;
        $wMitra = 54;
        $wPembimbing = 28;

        // Header Row
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->Cell($wNo, 6.5, 'NO', 1, 0, 'C');
        $pdf->Cell($wName, 6.5, 'NAMA', 1, 0, 'C');
        $pdf->Cell($wNpm, 6.5, 'NPM', 1, 0, 'C');
        $pdf->Cell($wMitra, 6.5, 'MITRA/PROJECT', 1, 0, 'C');
        $pdf->Cell($wPembimbing, 6.5, 'PEMBIMBING', 1, 1, 'C');

        // Data Rows
        $pdf->SetFont('Helvetica', '', 8.5);
        foreach ($students as $index => $s) {
            $noStr = ($index + 1).'.';
            $nameStr = mb_strtoupper($s['name'] ?? '-', 'UTF-8');
            $npmStr = $s['npm'] ?? '-';
            $mitraStr = $s['mitra_project'] ?? ($s['mitra'] ?? '-');
            $pembimbingStr = $s['role'] ?? ($s['pembimbing'] ?? 'Pembimbing I');

            $this->renderTableRow($pdf, [
                ['width' => $wNo, 'text' => $noStr, 'align' => 'C'],
                ['width' => $wName, 'text' => ' '.$nameStr, 'align' => 'L'],
                ['width' => $wNpm, 'text' => $npmStr, 'align' => 'C'],
                ['width' => $wMitra, 'text' => $mitraStr, 'align' => 'C'],
                ['width' => $wPembimbing, 'text' => $pembimbingStr, 'align' => 'C'],
            ], 4.4, 6.2);
        }

        $pdf->Ln(4.5);

        // 4. Closing Narrative
        $closing = ! empty($letterType?->content_template)
            ? $this->parseTemplate($letterType->content_template, $letter)
            : 'Demikian Surat Tugas ini kami sampaikan atas perhatiannya kami ucapkan terimakasih.';

        $pdf->SetFont('Helvetica', '', 9.5);
        $pdf->MultiCell(170, 4.8, $closing, 0, 'J');
    }

    /**
     * Render specialized format for Surat Tugas Pembimbing Tugas Akhir (Tabel Mahasiswa TA / Skripsi).
     */
    protected function renderBodySuratTugasPembimbingTa(FPDF $pdf, LetterRequest $letter, ?LetterType $letterType): void
    {
        $extra = $letter->extra_data ?? [];

        // 1. Opening Narrative
        if (! empty($letterType?->opening_template)) {
            $opening = $this->parseTemplate($letterType->opening_template, $letter);
        } else {
            $title = strtolower($letter->signer_title ?? '');
            $name = strtolower($letter->signer_name ?? '');

            if (str_contains($title, 'wakil') || str_contains($name, 'bambang')) {
                $role = 'Wakil Dekan Fakultas Ilmu Komputer Universitas Subang';
            } elseif (str_contains($title, 'dekan') || str_contains($name, 'tepi')) {
                $role = 'Dekan Fakultas Ilmu Komputer Universitas Subang';
            } else {
                $prodi = $letter->study_program ?: 'Sistem Informasi';
                $role = "Ketua Program Studi {$prodi} Fakultas Ilmu Komputer Universitas Subang";
            }
            $opening = "Yang bertanda tangan di bawah ini {$role} dengan ini menugaskan :";
        }

        $pdf->SetFont('Helvetica', '', 9.5);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->MultiCell(170, 4.8, $opening, 0, 'L');
        $pdf->Ln(4);

        // 2. Lecturer Info
        $dosenName = mb_strtoupper($letter->student_name ?? ($extra['lecturer_name'] ?? '-'), 'UTF-8');
        $statusPengajar = $extra['lecturer_status'] ?? 'Dosen Tetap';
        $nidn = $extra['nidn'] ?? ($letter->npm ?? '-');
        $prodi = $letter->study_program ?: 'Sistem Informasi';
        $semesterTipe = $extra['semester_type'] ?? ($letter->semester % 2 === 1 ? 'Ganjil' : 'Genap');
        $tahunAkademik = $letter->academic_year ?? '2026/2027';

        $lecturerFields = [
            ['label' => 'N A M A', 'val' => $dosenName, 'bold' => true],
            ['label' => 'STATUS TENAGA PENGAJAR', 'val' => $statusPengajar, 'bold' => false],
            ['label' => 'NIDN', 'val' => $nidn, 'bold' => false],
            ['label' => 'PROGRAM STUDI', 'val' => $prodi, 'bold' => false],
            ['label' => 'SEMESTER', 'val' => $semesterTipe, 'bold' => false],
            ['label' => 'TAHUN AKADEMIK', 'val' => $tahunAkademik, 'bold' => false],
        ];

        $indent = 4;
        $labelW = 54;
        $colonW = 4;
        $valW = 170 - $indent - $labelW - $colonW;

        foreach ($lecturerFields as $field) {
            $pdf->SetX(20 + $indent);
            $pdf->SetFont('Helvetica', '', 9.5);
            $pdf->Cell($labelW, 4.8, $field['label'], 0, 0, 'L');
            $pdf->Cell($colonW, 4.8, ':', 0, 0, 'C');

            if ($field['bold']) {
                $pdf->SetFont('Helvetica', 'B', 9.5);
            } else {
                $pdf->SetFont('Helvetica', '', 9.5);
            }
            $pdf->Cell($valW, 4.8, $field['val'], 0, 1, 'L');
        }

        $pdf->Ln(4.5);

        // 3. TA Students Table (No | Nama | NPM | Pembimbing)
        $students = $extra['ta_students'] ?? [];
        if (empty($students)) {
            $students = [
                [
                    'name' => 'Tiara Maharani Ar',
                    'npm' => 'D1A200403',
                    'role' => 'Pembimbing I',
                ],
            ];
        }

        // Total width: 10 + 78 + 42 + 40 = 170mm
        $wNo = 10;
        $wName = 78;
        $wNpm = 42;
        $wPembimbing = 40;

        // Header Row
        $pdf->SetFont('Helvetica', 'B', 9.5);
        $pdf->Cell($wNo, 6.5, 'No', 1, 0, 'C');
        $pdf->Cell($wName, 6.5, 'Nama', 1, 0, 'C');
        $pdf->Cell($wNpm, 6.5, 'NPM', 1, 0, 'C');
        $pdf->Cell($wPembimbing, 6.5, 'Pembimbing', 1, 1, 'C');

        // Data Rows
        $pdf->SetFont('Helvetica', '', 9);
        foreach ($students as $index => $s) {
            $noStr = ($index + 1).'.';
            $nameStr = $s['name'] ?? '-';
            $npmStr = $s['npm'] ?? '-';
            $pembimbingStr = $s['role'] ?? ($s['pembimbing'] ?? 'Pembimbing I');

            $this->renderTableRow($pdf, [
                ['width' => $wNo, 'text' => $noStr, 'align' => 'C'],
                ['width' => $wName, 'text' => ' '.$nameStr, 'align' => 'L'],
                ['width' => $wNpm, 'text' => $npmStr, 'align' => 'C'],
                ['width' => $wPembimbing, 'text' => $pembimbingStr, 'align' => 'C'],
            ], 4.6, 6.2);
        }

        $pdf->Ln(4.5);

        // 4. Closing Narrative
        $closing = ! empty($letterType?->content_template)
            ? $this->parseTemplate($letterType->content_template, $letter)
            : 'Demikian Surat Tugas ini kami sampaikan atas perhatiannya kami ucapkan terimakasih.';

        $pdf->SetFont('Helvetica', '', 9.5);
        $pdf->MultiCell(170, 4.8, $closing, 0, 'J');
    }

    /**
     * Calculate the number of lines a string will occupy when printed in a cell of given width.
     */
    protected function calculateNumLines(FPDF $pdf, float $width, string $text): int
    {
        $text = trim($text);
        if ($text === '') {
            return 1;
        }

        $paragraphs = explode("\n", str_replace("\r", '', $text));
        $totalLines = 0;
        $maxInnerWidth = max(1.0, $width - 2.5);

        foreach ($paragraphs as $para) {
            $words = preg_split('/\s+/', trim($para));
            $currentLine = '';
            $linesInPara = 1;

            foreach ($words as $word) {
                if ($word === '') {
                    continue;
                }
                $testLine = $currentLine === '' ? $word : $currentLine.' '.$word;
                if ($pdf->GetStringWidth($testLine) > $maxInnerWidth && $currentLine !== '') {
                    $linesInPara++;
                    $currentLine = $word;
                } else {
                    $currentLine = $testLine;
                }
            }
            $totalLines += $linesInPara;
        }

        return max(1, $totalLines);
    }

    /**
     * Render a table row where each cell has a solid outer rectangle of matching height,
     * and text is vertically centered and correctly wrapped without partial borders.
     *
     * @param  array<int, array{width: float, text: string, align?: string}>  $cells
     */
    protected function renderTableRow(FPDF $pdf, array $cells, float $lineHeight = 4.4, float $minRowHeight = 6.0): void
    {
        $startX = $pdf->GetX();
        $startY = $pdf->GetY();

        // 1. Calculate the max number of lines needed across all cells in this row
        $maxLines = 1;
        foreach ($cells as $cell) {
            $lines = $this->calculateNumLines($pdf, $cell['width'], $cell['text']);
            if ($lines > $maxLines) {
                $maxLines = $lines;
            }
        }

        $rowH = max($minRowHeight, ($maxLines * $lineHeight) + 1.6);

        // 2. Render each cell with full height rectangle and vertically centered text
        $currentX = $startX;
        foreach ($cells as $cell) {
            $w = $cell['width'];
            $text = $cell['text'];
            $align = $cell['align'] ?? 'C';

            // Draw solid outer border of full cell height
            $pdf->Rect($currentX, $startY, $w, $rowH);

            // Calculate text height and vertical centering offset
            $numLines = $this->calculateNumLines($pdf, $w, $text);
            $textBlockHeight = $numLines * $lineHeight;
            $offsetY = max(0.5, ($rowH - $textBlockHeight) / 2);

            // Position for text printing inside cell
            $pdf->SetXY($currentX, $startY + $offsetY);
            $pdf->MultiCell($w, $lineHeight, $text, 0, $align);

            $currentX += $w;
        }

        // 3. Move cursor to the start of the next row
        $pdf->SetXY($startX, $startY + $rowH);
    }

    /**
     * Helper to render markdown-style bold text (**text**) across multiple paragraphs.
     */
    protected function renderMarkdownFormattedText(FPDF $pdf, string $text, float $lineHeight = 4.6, float $fontSize = 9.5): void
    {
        $paragraphs = preg_split('/\r\n\r\n|\n\n/', $text);
        foreach ($paragraphs as $pIndex => $paragraph) {
            $paragraph = trim($paragraph);
            if ($paragraph === '') {
                continue;
            }

            if (str_contains($paragraph, '**')) {
                $parts = explode('**', $paragraph);
                foreach ($parts as $idx => $part) {
                    if ($part === '') {
                        continue;
                    }
                    $isBold = ($idx % 2 === 1);
                    $pdf->SetFont('Helvetica', $isBold ? 'B' : '', $fontSize);
                    $pdf->Write($lineHeight, $part);
                }
                $pdf->Ln($lineHeight + 1.8);
            } else {
                $pdf->SetFont('Helvetica', '', $fontSize);
                $pdf->MultiCell(170, $lineHeight, $paragraph, 0, 'J');
                $pdf->Ln(2);
            }
        }
    }

    /**
     * Render specialized layout for Surat Rekomendasi Pinjaman BJB.
     */
    protected function renderBodyRekomendasiPinjamanBjb(FPDF $pdf, LetterRequest $letter, ?LetterType $letterType): void
    {
        $extra = $letter->extra_data ?? [];

        $dekanName = ! empty($extra['dekan_name']) ? $extra['dekan_name'] : 'Dr. Tepi Peirisal, S.Sos, M.Si';
        $dekanNik = ! empty($extra['dekan_nik']) ? $extra['dekan_nik'] : '0410420142';
        $dekanPosition = ! empty($extra['dekan_position']) ? $extra['dekan_position'] : 'Dekan Fakultas Ilmu Komputer';

        $employeeName = ! empty($extra['employee_name']) ? $extra['employee_name'] : ($letter->student_name ?: '-');
        $employeeNik = ! empty($extra['employee_nik']) ? $extra['employee_nik'] : ($letter->npm ?: '-');
        $employeePosition = ! empty($extra['employee_position']) ? $extra['employee_position'] : 'Staf Fakultas Ilmu Komputer Universitas Subang';
        $loanPurpose = ! empty($extra['loan_purpose']) ? $extra['loan_purpose'] : 'untuk tujuan lainnya';
        $bankName = ! empty($extra['bank_name']) ? $extra['bank_name'] : 'Bank BJB';

        $pdf->SetTextColor(0, 0, 0);

        // Bagian 1: Yang bertandatangan dibawah ini :
        $opening = ! empty($extra['opening_template'])
            ? $this->parseTemplate($extra['opening_template'], $letter)
            : (! empty($letterType?->opening_template)
                ? $this->parseTemplate($letterType->opening_template, $letter)
                : 'Yang bertandatangan dibawah ini :');

        $pdf->SetFont('Helvetica', '', 10.5);
        $pdf->Cell(170, 6, $opening, 0, 1, 'L');
        $pdf->Ln(1);

        $pdf->SetX(20);
        $pdf->Cell(30, 5.5, 'Nama', 0, 0, 'L');
        $pdf->Cell(5, 5.5, ':', 0, 0, 'L');
        $pdf->Cell(135, 5.5, $dekanName, 0, 1, 'L');

        $pdf->SetX(20);
        $pdf->Cell(30, 5.5, 'NIK', 0, 0, 'L');
        $pdf->Cell(5, 5.5, ':', 0, 0, 'L');
        $pdf->Cell(135, 5.5, $dekanNik, 0, 1, 'L');

        $pdf->SetX(20);
        $pdf->Cell(30, 5.5, 'Jabatan', 0, 0, 'L');
        $pdf->Cell(5, 5.5, ':', 0, 0, 'L');
        $pdf->Cell(135, 5.5, $dekanPosition, 0, 1, 'L');

        $pdf->Ln(4);

        // Bagian 2: Dengan ini merekomendasikan kepada :
        $pdf->Cell(170, 6, 'Dengan ini merekomendasikan kepada :', 0, 1, 'L');
        $pdf->Ln(1);

        $pdf->SetX(20);
        $pdf->Cell(30, 5.5, 'Nama', 0, 0, 'L');
        $pdf->Cell(5, 5.5, ':', 0, 0, 'L');
        $pdf->Cell(135, 5.5, $employeeName, 0, 1, 'L');

        $pdf->SetX(20);
        $pdf->Cell(30, 5.5, 'NIK', 0, 0, 'L');
        $pdf->Cell(5, 5.5, ':', 0, 0, 'L');
        $pdf->Cell(135, 5.5, $employeeNik, 0, 1, 'L');

        $pdf->SetX(20);
        $pdf->Cell(30, 5.5, 'Jabatan', 0, 0, 'L');
        $pdf->Cell(5, 5.5, ':', 0, 0, 'L');
        $pdf->Cell(135, 5.5, $employeePosition, 0, 1, 'L');

        $pdf->Ln(6);

        // Bagian 3: Paragraf Isi Rekomendasi Pinjaman & Potong Gaji
        // Normalisasi kata loanPurpose jika user sudah memasukkan awalan 'untuk' atau belum
        $cleanPurpose = trim($loanPurpose);
        if (! str_starts_with(strtolower($cleanPurpose), 'untuk')) {
            $cleanPurpose = 'untuk '.$cleanPurpose;
        }

        $customContent = ! empty($extra['content_template']) ? $extra['content_template'] : null;

        if ($customContent) {
            $parsedContent = $this->parseTemplate($customContent, $letter);
            $parsedContent = str_replace(
                ['{bank_name}', '{loan_purpose}'],
                [$bankName, $cleanPurpose],
                $parsedContent
            );
            $paragraphs = preg_split("/\r\n|\n|\r/", $parsedContent);
            foreach ($paragraphs as $para) {
                $para = trim($para);
                if ($para !== '') {
                    $pdf->MultiCell(170, 6, $para, 0, 'J');
                    $pdf->Ln(3);
                }
            }
        } else {
            $p1 = "Adapun sebagai staf Fakultas Ilmu Komputer Universitas Subang, dan oleh karenanya dapat dipertimbangkan untuk diberikan kredit dari {$bankName} {$cleanPurpose}.";
            $pdf->MultiCell(170, 6, $p1, 0, 'J');
            $pdf->Ln(3);

            $p2 = "Untuk kelancaran pengambilan kredit tersebut, maka kami mendukung/tidak berkeberatan untuk dipotong langsung dari gaji yang bersangkutan oleh {$bankName}.";
            $pdf->MultiCell(170, 6, $p2, 0, 'J');
            $pdf->Ln(3);
        }

        $p3 = ! empty($extra['closing_template'])
            ? $this->parseTemplate($extra['closing_template'], $letter)
            : (! empty($letterType?->closing_template)
                ? $this->parseTemplate($letterType->closing_template, $letter)
                : 'Demikian surat rekomendasi ini di buat, untuk dipergunakan sebagaimana mestinya.');
        $pdf->MultiCell(170, 6, $p3, 0, 'J');
    }

    /**
     * Render specialized layout for Surat Rekomendasi Beasiswa.
     */
    protected function renderBodyRekomendasiBeasiswa(FPDF $pdf, LetterRequest $letter, ?LetterType $letterType): void
    {
        $extra = $letter->extra_data ?? [];

        // Penandatangan default Kaprodi / signer yang dipilih
        $signerName = $letter->signer_name ?: 'BAGUS ALI AKBAR, S.SI.,M.Kom';
        $signerTitle = $letter->signer_title ?: 'Ketua Program Studi Sistem Informasi';
        $signerFaculty = ! empty($extra['pejabat_fakultas']) ? $extra['pejabat_fakultas'] : 'Ilmu Komputer Universitas Subang';

        $studentName = $letter->student_name ?: 'Muhammad Arif Amrullah';
        $npm = $letter->npm ?: 'D1A240040';
        $scholarshipName = ! empty($extra['scholarship_name']) ? $extra['scholarship_name'] : 'Beasiswa Pendidikan Pemimpin Indonesia';
        $scholarshipProvider = ! empty($extra['scholarship_provider']) ? $extra['scholarship_provider'] : 'LEADERS.ID';

        $pdf->SetTextColor(0, 0, 0);

        // Bagian 1: Yang bertanda tangan di bawah ini:
        $pdf->SetFont('Helvetica', '', 10.5);
        $pdf->Cell(170, 6, 'Yang bertanda tangan di bawah ini:', 0, 1, 'L');
        $pdf->Ln(2);

        $pdf->SetX(20);
        $pdf->Cell(30, 6, 'Nama', 0, 0, 'L');
        $pdf->Cell(5, 6, ':', 0, 0, 'L');
        $pdf->Cell(135, 6, $signerName, 0, 1, 'L');

        $pdf->SetX(20);
        $pdf->Cell(30, 6, 'Jabatan', 0, 0, 'L');
        $pdf->Cell(5, 6, ':', 0, 0, 'L');
        $pdf->Cell(135, 6, $signerTitle, 0, 1, 'L');

        $pdf->SetX(20);
        $pdf->Cell(30, 6, 'Fakultas', 0, 0, 'L');
        $pdf->Cell(5, 6, ':', 0, 0, 'L');
        $pdf->Cell(135, 6, $signerFaculty, 0, 1, 'L');

        $pdf->Ln(6);

        // Bagian 2: Paragraf Rekomendasi Beasiswa
        $p1 = "Merekomendasikan kepada mahasiswa atas nama {$studentName} dengan nomor pokok mahasiswa {$npm} untuk mendapatkan {$scholarshipName} oleh {$scholarshipProvider}.";
        $pdf->MultiCell(170, 6, $p1, 0, 'J');
        $pdf->Ln(4);

        // Bagian 3: Paragraf Penutup
        $p2 = 'Demikian surat rekomendasi ini dibuat untuk dipergunakan sebagaimana mestinya. Atas perhatiannya, kami ucapkan terima kasih';
        $pdf->MultiCell(170, 6, $p2, 0, 'J');
    }

    /**
     * Render specialized layout for Surat Rekomendasi Kegiatan Pembelajaran di Luar Program Studi.
     */
    protected function renderBodyRekomendasiLuarProdi(FPDF $pdf, LetterRequest $letter, ?LetterType $letterType): void
    {
        $extra = $letter->extra_data ?? [];

        // Pejabat Penandatangan (Default Wakil Dekan I / Penandatangan Terpilih)
        $signerName = $letter->signer_name ?: 'Bambang Tjahjo Utomo, MT';
        $signerTitle = $letter->signer_title ?: 'Wakil Dekan I';
        $signerProdi = ! empty($extra['pejabat_prodi']) ? $extra['pejabat_prodi'] : ($letter->study_program ?: 'Sistem Informasi');
        $signerFaculty = ! empty($extra['pejabat_fakultas']) ? $extra['pejabat_fakultas'] : 'Ilmu Komputer Universitas Subang';

        // Identitas Mahasiswa
        $studentName = $letter->student_name ?: 'Asep Sophian Rahayu';
        $npm = $letter->npm ?: 'D1A.19.0037';
        $studyProgram = $letter->study_program ?: 'Sistem Informasi';
        $studentFaculty = ! empty($extra['fakultas_mahasiswa']) ? $extra['fakultas_mahasiswa'] : 'Ilmu Komputer Universitas Subang';

        // Format Semester & Tahun Akademik
        $semesterWords = [
            1 => 'Satu', 2 => 'Dua', 3 => 'Tiga', 4 => 'Empat',
            5 => 'Lima', 6 => 'Enam', 7 => 'Tujuh', 8 => 'Delapan',
            9 => 'Sembilan', 10 => 'Sepuluh', 11 => 'Sebelas', 12 => 'Dua Belas',
        ];
        $romanSemesters = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
        ];
        $semNum = (int) ($letter->semester ?: 6);
        $semRoman = $romanSemesters[$semNum] ?? (string) $semNum;
        $semWord = $semesterWords[$semNum] ?? '';
        $semesterText = "{$semRoman} ({$semWord})";
        $academicYear = $letter->academic_year ?: '2021/2022';

        // Informasi Program & Periode
        $programName = ! empty($extra['program_name']) ? $extra['program_name'] : 'Program Coding Camp 2025 powered by DBS Foundation';
        $activityPeriod = ! empty($extra['activity_period']) ? $extra['activity_period'] : 'periode Februari - Juli 2025';
        $cleanPeriod = trim($activityPeriod);
        if (! str_starts_with(strtolower($cleanPeriod), 'periode') && ! str_starts_with(strtolower($cleanPeriod), 'pada')) {
            $cleanPeriod = 'periode '.$cleanPeriod;
        }

        $pdf->SetTextColor(0, 0, 0);

        // 1. Yang bertanda tangan di bawah ini:
        $opening = ! empty($extra['opening_template'])
            ? $this->parseTemplate($extra['opening_template'], $letter)
            : ($letterType?->opening_template ? $this->parseTemplate($letterType->opening_template, $letter) : 'Yang bertanda tangan di bawah ini:');
        
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->Cell(170, 5, $opening, 0, 1, 'L');
        $pdf->Ln(1);

        $pdf->SetX(20);
        $pdf->Cell(32, 5, 'Nama', 0, 0, 'L');
        $pdf->Cell(5, 5, ':', 0, 0, 'L');
        $pdf->Cell(133, 5, $signerName, 0, 1, 'L');

        $pdf->SetX(20);
        $pdf->Cell(32, 5, 'Jabatan', 0, 0, 'L');
        $pdf->Cell(5, 5, ':', 0, 0, 'L');
        $pdf->Cell(133, 5, $signerTitle, 0, 1, 'L');

        $pdf->SetX(20);
        $pdf->Cell(32, 5, 'Program Studi', 0, 0, 'L');
        $pdf->Cell(5, 5, ':', 0, 0, 'L');
        $pdf->Cell(133, 5, $signerProdi, 0, 1, 'L');

        $pdf->SetX(20);
        $pdf->Cell(32, 5, 'Fakultas', 0, 0, 'L');
        $pdf->Cell(5, 5, ':', 0, 0, 'L');
        $pdf->Cell(133, 5, $signerFaculty, 0, 1, 'L');

        $pdf->Ln(3.5);

        // 2. Dengan ini memberikan rekomendasi kepada:
        $pdf->Cell(170, 5, 'Dengan ini memberikan rekomendasi kepada:', 0, 1, 'L');
        $pdf->Ln(1);

        $pdf->SetX(20);
        $pdf->Cell(32, 5, 'Nama', 0, 0, 'L');
        $pdf->Cell(5, 5, ':', 0, 0, 'L');
        $pdf->Cell(133, 5, $studentName, 0, 1, 'L');

        $pdf->SetX(20);
        $pdf->Cell(32, 5, 'NPM', 0, 0, 'L');
        $pdf->Cell(5, 5, ':', 0, 0, 'L');
        $pdf->Cell(133, 5, $npm, 0, 1, 'L');

        $pdf->SetX(20);
        $pdf->Cell(32, 5, 'Program Studi', 0, 0, 'L');
        $pdf->Cell(5, 5, ':', 0, 0, 'L');
        $pdf->Cell(133, 5, $studyProgram, 0, 1, 'L');

        $pdf->SetX(20);
        $pdf->Cell(32, 5, 'Fakultas', 0, 0, 'L');
        $pdf->Cell(5, 5, ':', 0, 0, 'L');
        $pdf->Cell(133, 5, $studentFaculty, 0, 1, 'L');

        $pdf->SetX(20);
        $pdf->Cell(32, 5, 'Semester', 0, 0, 'L');
        $pdf->Cell(5, 5, ':', 0, 0, 'L');
        $pdf->Cell(133, 5, $semesterText, 0, 1, 'L');

        $pdf->SetX(20);
        $pdf->Cell(32, 5, 'Tahun Akademik', 0, 0, 'L');
        $pdf->Cell(5, 5, ':', 0, 0, 'L');
        $pdf->Cell(133, 5, $academicYear, 0, 1, 'L');

        $pdf->Ln(3.5);

        // 3. Paragraf Narasi Rekomendasi Partisipasi & Butir Dukungan
        $customContent = ! empty($extra['content_template'])
            ? $extra['content_template']
            : ($letterType?->content_template ?: '');

        // If custom content is provided and doesn't contain raw table placeholders, render it dynamically
        if (! empty($customContent) && ! str_contains($customContent, '{student_name}') && ! str_contains($customContent, 'NPM :')) {
            $parsedContent = $this->parseTemplate($customContent, $letter);
            $parsedContent = str_replace(
                ['{program_name}', '{activity_period}'],
                [$programName, $cleanPeriod],
                $parsedContent
            );

            $paragraphs = preg_split('/\r\n\r\n|\n\n/', trim($parsedContent));
            foreach ($paragraphs as $idx => $para) {
                $lines = explode("\n", trim($para));
                foreach ($lines as $line) {
                    $trimmedLine = trim($line);
                    if (empty($trimmedLine)) {
                        continue;
                    }

                    // Check if line starts with numbered list item (e.g. "1. ", "2. ", "3. ")
                    if (preg_match('/^(\d+)\.\s*(.+)$/', $trimmedLine, $matches)) {
                        $pdf->SetX(24);
                        $pdf->Cell(6, 4.8, $matches[1].'.', 0, 0, 'L');
                        $pdf->MultiCell(160, 4.8, $matches[2], 0, 'J');
                        $pdf->Ln(1);
                    } else {
                        $pdf->MultiCell(170, 4.8, $trimmedLine, 0, 'J');
                        $pdf->Ln(1.5);
                    }
                }
                $pdf->Ln(1);
            }
        } else {
            // Default Standard Fallback
            $p1 = "Untuk menjadi peserta di {$programName} pada {$cleanPeriod} dengan pertimbangan bahwa mahasiswa tersebut sanggup mengikuti {$programName} secara penuh dan bertanggung jawab hingga akhir program selesai.";
            $pdf->MultiCell(170, 4.8, $p1, 0, 'J');
            $pdf->Ln(2);

            $p2 = 'Sebagai bentuk dukungan, kami selaku pihak perwakilan kampus menyatakan kesediaan untuk hal berikut:';
            $pdf->MultiCell(170, 4.8, $p2, 0, 'J');
            $pdf->Ln(1.5);

            $pdf->SetX(24);
            $pdf->Cell(6, 4.8, '1.', 0, 0, 'L');
            $pdf->MultiCell(160, 4.8, "Memberikan dukungan dan bertanggung jawab atas mahasiswa selama periode pelaksanaan {$programName}.", 0, 'J');
            $pdf->Ln(1);

            $pdf->SetX(24);
            $pdf->Cell(6, 4.8, '2.', 0, 0, 'L');
            $pdf->MultiCell(160, 4.8, "Memberikan pengakuan keikutsertaan siswa/mahasiswa di {$programName} melalui konversi SKS atau hal lainnya yang dapat diakui dan menunjang kegiatan akademik di kampus.", 0, 'J');
            $pdf->Ln(1);

            $pdf->SetX(24);
            $pdf->Cell(6, 4.8, '3.', 0, 0, 'L');
            $pdf->MultiCell(160, 4.8, "Mampu menjadi jembatan untuk berkoordinasi antara Tim {$programName} dan mahasiswa apabila terjadi masalah/kendala di kemudian hari.", 0, 'J');
            $pdf->Ln(2);
        }

        // 5. Paragraf Penutup
        $closing = ! empty($extra['closing_template'])
            ? $this->parseTemplate($extra['closing_template'], $letter)
            : ($letterType?->closing_template ? $this->parseTemplate($letterType->closing_template, $letter) : 'Demikian surat rekomendasi ini kami sampaikan untuk dipergunakan sebagaimana mestinya.');
        
        $pdf->MultiCell(170, 4.8, $closing, 0, 'J');
    }

    /**
     * Render numbered key-value row with indented alignment and optional bold value.
     */
    protected function renderNumberedFieldRow(FPDF $pdf, string $label, string $value, bool $boldValue = false, float $indent = 3, float $labelWidth = 52): void
    {
        $pdf->SetX(20 + $indent);
        $pdf->SetFont('Helvetica', '', 9.5);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell($labelWidth, 4.8, $label, 0, 0, 'L');
        $pdf->Cell(4, 4.8, ':', 0, 0, 'C');

        if ($boldValue) {
            $pdf->SetFont('Helvetica', 'B', 9.5);
        } else {
            $pdf->SetFont('Helvetica', '', 9.5);
        }

        $valueWidth = 170 - $indent - $labelWidth - 4;
        $val = ($value !== null && $value !== '') ? $value : '-';

        if ($pdf->GetStringWidth($val) > $valueWidth) {
            $pdf->MultiCell($valueWidth, 4.8, $val, 0, 'L');
        } else {
            $pdf->Cell($valueWidth, 4.8, $val, 0, 1, 'L');
        }
    }

    /**
     * Render clean tabular key-value row with aligned colons.
     */
    protected function renderFieldRow(FPDF $pdf, string $label, string $value): void
    {
        $pdf->SetFont('Helvetica', '', 9.5);
        $pdf->Cell(45, 5, $label, 0, 0, 'L');
        $pdf->Cell(4, 5, ':', 0, 0, 'C');

        // Check text length for address or long titles
        if (strlen($value) > 65) {
            $pdf->MultiCell(121, 5, $value, 0, 'L');
        } else {
            $pdf->Cell(121, 5, $value, 0, 1, 'L');
        }
    }

    /**
     * Render Date & Kaprodi Signature Block.
     */
    protected function renderSignatureBlock(FPDF $pdf, LetterRequest $letter): void
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $now = now();
        $dateStr = 'Subang,   '.$now->day.' '.($months[$now->month] ?? '').' '.$now->year;

        // Khusus Surat Pernyataan Kesanggupan Mengajar (Tanda tangan Dosen ybs + Materai 10000)
        if ($letter->letterType?->code === 'kesanggupan_mengajar') {
            $extra = $letter->extra_data ?? [];
            $lecturerName = ! empty($extra['lecturer_name']) ? $extra['lecturer_name'] : ($letter->student_name ?: 'Dosen Pengampu');
            $nidn = ! empty($extra['nidn']) ? $extra['nidn'] : ($letter->npm ?: '-');

            $currentY = $pdf->GetY();
            $sigY = max($currentY + 6, 175);
            $this->lastSignatureY = $sigY;

            $pdf->SetY($sigY);
            $pdf->SetX(115);
            $pdf->SetFont('Helvetica', '', 10);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(75, 5, $dateStr, 0, 1, 'L');

            $pdf->SetX(115);
            $pdf->Cell(75, 5, 'Dosen ybs,', 0, 1, 'L');

            // Kotak Materai 10000 di sebelah kiri sebelum nama/TTE
            $materaiY = $sigY + 11;
            $pdf->SetY($materaiY);
            $pdf->SetX(115);
            $pdf->SetLineWidth(0.4);
            $pdf->Rect(115, $materaiY, 26, 16);
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->SetXY(115, $materaiY + 3);
            $pdf->Cell(26, 4, 'Materai', 0, 1, 'C');
            $pdf->SetX(115);
            $pdf->Cell(26, 4, '10000', 0, 1, 'C');

            // Nama & NIDN Dosen
            $nameY = $materaiY + 20;
            $pdf->SetY($nameY);
            $pdf->SetX(115);
            $pdf->SetFont('Helvetica', 'U', 9.5);
            $pdf->Cell(75, 4.5, $lecturerName, 0, 1, 'L');

            $pdf->SetX(115);
            $pdf->SetFont('Helvetica', '', 9.5);
            $pdf->Cell(75, 4.5, 'NIDN. '.$nidn, 0, 1, 'L');

            return;
        }

        // Khusus Fakta Integritas Dosen Pengampu (Subang, September 2026 \n Yang Membuat Pernyataan \n\n ...................................)
        if ($letter->letterType?->code === 'pakta_integritas') {
            $extra = $letter->extra_data ?? [];
            $lecturerName = ! empty($extra['lecturer_name']) ? $extra['lecturer_name'] : ($letter->student_name ?: 'BAGUS ALI AKBAR, S.SI., M.Kom');

            $currentY = $pdf->GetY();
            $sigY = max($currentY + 12, 195);
            $this->lastSignatureY = $sigY;

            $pdf->SetY($sigY);
            $pdf->SetX(115);
            $pdf->SetFont('Helvetica', '', 10);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(75, 5, $dateStr, 0, 1, 'L');

            $pdf->SetX(115);
            $pdf->Cell(75, 5, 'Yang Membuat Pernyataan', 0, 1, 'L');

            // Garis titik-titik untuk tempat tanda tangan dosen (atau teks nama dosen jika sudah TTE)
            $nameY = $sigY + 28;
            $pdf->SetY($nameY);
            $pdf->SetX(115);
            $pdf->SetFont('Helvetica', '', 9.5);
            $pdf->Cell(75, 4.5, '.......................................................', 0, 1, 'L');

            // Cetak nama dosen di bawah titik-titik / TTE
            $pdf->SetX(115);
            $pdf->SetFont('Helvetica', 'B', 9);
            $pdf->Cell(75, 4.5, $lecturerName, 0, 1, 'L');

            return;
        }

        // Khusus Surat Pengajuan Kebutuhan Dana (Dual Signature: Dekan di kiri "Mengetahui Dekan", Bendahara di kanan "Subang, 2 November 2026 \n Bendahara")
        if ($letter->letterType?->code === 'pengajuan_dana') {
            $extra = $letter->extra_data ?? [];
            $dekanName = ! empty($extra['dekan_name']) ? $extra['dekan_name'] : 'Dr. Tepi Peirisal,S.Sos, M.Si';
            $treasurerName = ! empty($extra['treasurer_name']) ? $extra['treasurer_name'] : 'Fina Widya Ramdhani, S.AN';

            $currentY = $pdf->GetY();
            $sigY = max($currentY + 12, 195);
            $this->lastSignatureY = $sigY;

            // Kiri: Mengetahui Dekan
            $pdf->SetY($sigY + 5);
            $pdf->SetX(25);
            $pdf->SetFont('Helvetica', '', 10);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(70, 5, 'Mengetahui', 0, 1, 'C');
            $pdf->SetX(25);
            $pdf->Cell(70, 5, 'Dekan', 0, 1, 'C');

            // Kanan: Subang, Tanggal \n Bendahara
            $letterDate = ! empty($extra['letter_date_text']) ? $extra['letter_date_text'] : $dateStr;
            $pdf->SetY($sigY);
            $pdf->SetX(115);
            $pdf->Cell(75, 5, $letterDate, 0, 1, 'C');
            $pdf->SetX(115);
            $pdf->Cell(75, 5, 'Bendahara', 0, 1, 'C');

            // Ruang tanda tangan / TTE
            $nameY = $sigY + 32;

            // Nama Dekan (Kiri)
            $pdf->SetY($nameY);
            $pdf->SetX(25);
            $pdf->SetFont('Helvetica', '', 10);
            $pdf->Cell(70, 5, $dekanName, 0, 0, 'C');

            // Nama Bendahara (Kanan)
            $pdf->SetX(115);
            $pdf->Cell(75, 5, $treasurerName, 0, 1, 'C');

            return;
        }

        $currentY = $pdf->GetY();
        $sigY = max($currentY + 8, 150);
        $this->lastSignatureY = $sigY;

        $pdf->SetY($sigY);
        $pdf->SetX(115);
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->Cell(75, 4.5, $dateStr, 0, 1, 'L');

        $pdf->SetX(115);
        $pdf->Cell(75, 4.2, 'UNIVERSITAS SUBANG', 0, 1, 'L');

        $pdf->SetX(115);
        $pdf->Cell(75, 4.2, 'Fakultas Ilmu Komputer', 0, 1, 'L');

        $pdf->SetX(115);
        $pdf->Cell(75, 4.2, $letter->signer_title ?: 'Ka. Program Studi', 0, 1, 'L');

        // Reserve space for signature / TTE QR code (standard gap +1 ketuk between role and name)
        $nameY = $sigY + 43;
        $pdf->SetY($nameY);
        $pdf->SetX(115);
        $pdf->SetFont('Helvetica', 'BU', 10);
        $pdf->Cell(75, 5, $letter->signer_name, 0, 1, 'L');
    }

    /**
     * Approve and electronically seal (TTE) the letter with QR Code.
     */
    public function approveAndSeal(LetterRequest $letter): void
    {
        if (! $letter->document) {
            $this->generatePdf($letter);
            $letter->refresh();
        }

        // Finalize & stamp TTE on the linked document
        $this->pdfSignerService->signDocument($letter->document);

        $letter->update([
            'status' => 'signed',
            'signed_at' => now(),
        ]);
    }

    /**
     * Render live preview PDF as raw binary string without saving to disk.
     */
    public function renderPreviewPdf(LetterRequest $letter, bool $isWatermarked = true): string
    {
        $noLetterNumberTypes = ['kesanggupan_mengajar', 'pakta_integritas'];
        $isNoNumber = in_array($letter->letterType?->code, $noLetterNumberTypes);

        if ($isNoNumber) {
            $letter->letter_number = null;
        } elseif (empty($letter->letter_number)) {
            $letter->letter_number = '001/'.($letter->letterType->letter_code ?? 'PD.1.2/FIK-US').'/'.Carbon::now()->format('m/Y');
        }

        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->SetMargins(20, 15, 20);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();

        // 1. KOP SURAT RESMI FASILKOM UNIVERSITAS SUBANG
        $this->renderHeader($pdf);

        $contentStartY = $pdf->GetY();

        if ($isWatermarked) {
            $pdf->SetFont('Helvetica', 'B', 32);
            $pdf->SetTextColor(242, 245, 250);
            $pdf->SetXY(20, 135);
            $pdf->Cell(170, 18, 'PRATINJAU LANGSUNG (LIVE)', 0, 0, 'C');
            $pdf->SetXY(20, $contentStartY);
            $pdf->SetTextColor(15, 23, 42);
        }

        // 2. JUDUL SURAT & NOMOR
        $this->renderTitle($pdf, $letter);

        // 3. KATA PEMBUKA & DATA MAHASISWA
        $this->renderBody($pdf, $letter);

        // 4. BLOK TANDA TANGAN KAPRODI
        $this->renderSignatureBlock($pdf, $letter);
        $this->pageOneSignatureY = $this->lastSignatureY;

        // 5. HALAMAN 2: LAMPIRAN (Jika ada)
        $this->renderAttachmentPageIfNeeded($pdf, $letter);

        if ($isWatermarked && $pdf->PageNo() > 1) {
            $pdf->SetFont('Helvetica', 'B', 32);
            $pdf->SetTextColor(242, 245, 250);
            $pdf->SetXY(20, 140);
            $pdf->Cell(170, 18, 'PRATINJAU LANGSUNG (LIVE)', 0, 0, 'C');
        }

        return $pdf->Output('S');
    }
}
