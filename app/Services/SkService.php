<?php

namespace App\Services;

use App\Models\LetterRequest;
use Carbon\Carbon;
use FPDF;

class SkService
{
    public float $skSignaturePage = 1;

    public float $skSignatureY = 175;

    public function __construct(
        protected PdfSignerService $pdfSignerService
    ) {}

    /**
     * Render Header Kop Surat Resmi Fasilkom Universitas Subang.
     */
    public function renderHeader(FPDF $pdf): void
    {
        $kopPath = public_path('images/logos/kop-surat-fasilkom.png');
        if (file_exists($kopPath)) {
            // Lebar printable area A4 = 170mm (margin kiri 20mm, kanan 20mm: 210 - 40 = 170mm)
            // Rasio gambar cropped 993 x 207 = 4.7971 => Tinggi = 170 / 4.7971 = 35.44mm
            $pdf->Image($kopPath, 20, 11, 170, 35.44);
            $pdf->SetY(52);

            return;
        }

        $logoPath = public_path('images/logos/universitas-subang.png');
        if (file_exists($logoPath)) {
            $pdf->Image($logoPath, 20, 10, 26, 26);
        }

        $pdf->SetXY(48, 10);
        $pdf->SetFont('Times', 'B', 11);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(142, 4.5, 'YAYASAN KUTAWARINGIN SUBANG', 0, 1, 'C');

        $pdf->SetX(48);
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

        // Double Line Kop Surat
        $y = 44.5;
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.85);
        $pdf->Line(20, $y, 190, $y);

        $pdf->SetLineWidth(0.25);
        $pdf->Line(20, $y + 1.1, 190, $y + 1.1);

        $pdf->SetY(52);
    }

    /**
     * Render Header Lampiran Halaman SK (Lampiran: Surat Keputusan Dekan...).
     */
    public function renderAttachmentHeader(FPDF $pdf, LetterRequest $letter): void
    {
        $extra = $letter->extra_data ?? [];
        $skNumber = $letter->letter_number ?: '/D/FIK-US/IX/2025';
        $skDate = ! empty($extra['decision_date_text']) ? $extra['decision_date_text'] : '01 September 2025';
        $skTitle = ! empty($extra['about_title']) ? $extra['about_title'] : 'Penyelenggaraan dan Penetapan Dosen Pembimbing BKP MBKM Fakultas Ilmu Komputer Universitas Subang Semester Ganjil Tahun Akademik 2025/2026';

        $pdf->SetFont('Times', '', 9.5);
        $pdf->SetTextColor(0, 0, 0);

        $wLabel = 20;
        $wColon = 4;
        $wVal = 246; // total 270 on landscape (margins 15 + 15 = 297 - 27)

        $isDosenMengajar = ($letter->letterType?->code === 'sk_dekan_dosen_mengajar');
        $isPembimbingTa = ($letter->letterType?->code === 'sk_dekan_pembimbing_ta');
        $isDosenWali = ($letter->letterType?->code === 'sk_dekan_dosen_wali');
        $isYudisium = ($letter->letterType?->code === 'sk_dekan_yudisium');
        $isSeminarTa = ($letter->letterType?->code === 'sk_dekan_penguji_sidang_ta');
        $isSidangTa = ($letter->letterType?->code === 'sk_dekan_pengujian_sidang_skripsi');
        $isPengunduranDiri = ($letter->letterType?->code === 'sk_dekan_pengunduran_diri');
        $prodi = ! empty($extra['study_program']) ? $extra['study_program'] : 'Program Studi Sistem Informasi S1';

        $pdf->SetX(15);
        $pdf->Cell($wLabel, 4.2, ($isDosenWali || $isYudisium || $isSeminarTa || $isSidangTa ? 'LAMPIRAN' : 'Lampiran'), 0, 0, 'L');
        $pdf->Cell($wColon, 4.2, ':', 0, 0, 'C');
        if ($isDosenMengajar) {
            $semType = ! empty($extra['semester_type']) ? $extra['semester_type'] : 'Ganjil';
            $ta = ! empty($letter->academic_year) ? $letter->academic_year : '2024/2025';
            $pdf->Cell($wVal, 4.2, "Surat Keputusan Dekan Dosen Pengajar Semester {$semType} Tahun Akademik {$ta}", 0, 1, 'L');
            $pdf->SetX(15 + $wLabel + $wColon);
            $pdf->Cell($wVal, 4.2, $prodi, 0, 1, 'L');
        } elseif ($isYudisium) {
            $pdf->Cell($wVal, 4.2, 'SURAT KEPUTUSAN DEKAN FAKULTAS ILMU KOMPUTER', 0, 1, 'L');
            $pdf->SetX(15 + $wLabel + $wColon);
            $pdf->Cell($wVal, 4.2, 'UNIVERSITAS SUBANG', 0, 1, 'L');
        } elseif ($isPembimbingTa || $isDosenWali || $isPengunduranDiri) {
            $pdf->Cell($wVal, 4.2, 'KEPUTUSAN DEKAN FAKULTAS ILMU KOMPUTER UNIVERSITAS SUBANG', 0, 1, 'L');
        } elseif ($isSeminarTa || $isSidangTa) {
            $pdf->Cell($wVal, 4.2, 'SURAT KEPUTUSAN DEKAN FAKULTAS ILMU KOMPUTER UNIVERSITAS SUBANG', 0, 1, 'L');
        } else {
            $pdf->Cell($wVal, 4.2, 'Surat Keputusan Dekan Fakultas Ilmu Komputer Universitas Subang', 0, 1, 'L');
        }

        $pdf->SetX(15);
        $pdf->Cell($wLabel, 4.2, 'Nomor', 0, 0, 'L');
        $pdf->Cell($wColon, 4.2, ':', 0, 0, 'C');
        $pdf->Cell($wVal, 4.2, $skNumber, 0, 1, 'L');

        if (! $isPembimbingTa && ! $isDosenWali && ! $isYudisium && ! $isSeminarTa && ! $isSidangTa && ! $isPengunduranDiri) {
            $pdf->SetX(15);
            $pdf->Cell($wLabel, 4.2, 'Tanggal', 0, 0, 'L');
            $pdf->Cell($wColon, 4.2, ':', 0, 0, 'C');
            $pdf->Cell($wVal, 4.2, $skDate, 0, 1, 'L');
        }

        if (! $isDosenMengajar) {
            $pdf->SetX(15);
            $pdf->Cell($wLabel, 4.2, 'Tentang', 0, 0, 'L');
            $pdf->Cell($wColon, 4.2, ':', 0, 0, 'C');

            $lines = explode("\n", str_replace("\r", '', $skTitle));
            foreach ($lines as $idx => $line) {
                if ($idx > 0) {
                    $pdf->SetX(15 + $wLabel + $wColon);
                }
                $pdf->Cell($wVal, 4.2, trim($line), 0, 1, 'L');
            }
        }

        if ($isPengunduranDiri) {
            $pdf->SetX(15);
            $pdf->Cell(267, 0.5, '', 'T', 1, 'L');
        } else {
            $pdf->Ln(4);
        }
    }

    /**
     * Helper to render list of bullet points with a label (Menimbang, Mengingat, Memperhatikan).
     *
     * @param  array<int, string>  $points
     */
    protected function renderConsiderationSection(FPDF $pdf, string $label, array $points, string $pointsFormat = 'bullets'): void
    {
        $wLabel = 32;
        $wColon = 4;
        $wBullet = 5;
        $wText = 129; // 32 + 4 + 5 + 129 = 170mm

        $pdf->SetFont('Times', '', 10);
        $pdf->SetTextColor(0, 0, 0);

        $isLetterNumbered = ($pointsFormat === 'numbers');

        foreach ($points as $idx => $point) {
            $pointText = trim(is_array($point) ? ($point['text'] ?? '') : (string) $point);
            if ($pointText === '') {
                continue;
            }

            // Check if page break is needed
            if ($pdf->GetY() > 255) {
                $pdf->AddPage();
                $pdf->SetY(20);
            }

            $currentY = $pdf->GetY();
            $pdf->SetX(20);

            if ($idx === 0) {
                $pdf->Cell($wLabel, 4.8, $label, 0, 0, 'L');
                $pdf->Cell($wColon, 4.8, ':', 0, 0, 'C');
            } else {
                $pdf->Cell($wLabel + $wColon, 4.8, '', 0, 0, 'L');
            }

            if ($pointsFormat === 'letters') {
                $letterPrefix = chr(97 + $idx).'.';
                $pdf->Cell($wBullet, 4.8, $letterPrefix, 0, 0, 'L');
            } elseif ($pointsFormat === 'numbers') {
                $numPrefix = ($idx + 1).'.';
                $pdf->Cell($wBullet, 4.8, $numPrefix, 0, 0, 'L');
            } else {
                $pdf->Cell($wBullet, 4.8, chr(149), 0, 0, 'C'); // Bullet dot
            }

            $pdf->MultiCell($wText, 4.8, $pointText, 0, 'J');
            $pdf->Ln(1.2);
        }

        $pdf->Ln(2);
    }

    /**
     * Helper to render diktum points (Pertama, Kedua, Ketiga, ...).
     *
     * @param  array<int, array{label: string, text: string}>  $dictums
     */
    protected function renderDictumSection(FPDF $pdf, array $dictums): void
    {
        $wLabel = 32;
        $wColon = 4;
        $wText = 134; // 32 + 4 + 134 = 170mm

        $pdf->SetFont('Times', '', 10);
        $pdf->SetTextColor(0, 0, 0);

        foreach ($dictums as $d) {
            $dLabel = trim($d['label'] ?? '');
            $dText = trim($d['text'] ?? '');
            if ($dText === '') {
                continue;
            }

            // Check if page break is needed
            if ($pdf->GetY() > 255) {
                $pdf->AddPage();
                $pdf->SetY(20);
            }

            $pdf->SetX(20);
            $pdf->Cell($wLabel, 5.0, $dLabel, 0, 0, 'L');
            $pdf->Cell($wColon, 5.0, ':', 0, 0, 'C');
            $pdf->MultiCell($wText, 5.0, $dText, 0, 'J');
            $pdf->Ln(2.5);
        }
    }

    /**
     * Render SK Dekan signature block.
     */
    protected function renderSkSignatureBlock(FPDF $pdf, LetterRequest $letter): void
    {
        $extra = $letter->extra_data ?? [];
        $city = ! empty($extra['decision_city']) ? $extra['decision_city'] : 'Subang';
        $dateText = ! empty($extra['decision_date_text']) ? $extra['decision_date_text'] : Carbon::now()->translatedFormat('d F Y');
        $signerName = ! empty($letter->signer_name) ? $letter->signer_name : (! empty($extra['dekan_name']) ? $extra['dekan_name'] : 'Dr. TEPI PEIRISAL, M. Si.');
        $signerTitle = ! empty($letter->signer_title) ? $letter->signer_title : 'Dekan';

        if ($pdf->GetY() > 220) {
            $pdf->AddPage();
            $pdf->SetY(25);
        }

        $currentY = $pdf->GetY();
        // Beri jarak fleksibel yang proporsional setelah diktum terakhir (antara 8mm s.d. 12mm)
        // Jika halaman masih sangat kosong, beri jarak 12mm agar pas & tidak terlalu jauh
        $sigY = $currentY + 10;
        $this->skSignaturePage = $pdf->PageNo();
        $this->skSignatureY = $sigY;

        $pdf->SetY($sigY);
        $pdf->SetX(110);
        $pdf->SetFont('Times', '', 10);
        $pdf->Cell(25, 4.5, 'Ditetapkan di', 0, 0, 'L');
        $pdf->Cell(4, 4.5, ':', 0, 0, 'C');
        $pdf->Cell(50, 4.5, $city, 0, 1, 'L');

        $pdf->SetX(110);
        $pdf->Cell(25, 4.5, 'Pada Tanggal', 0, 0, 'L');
        $pdf->Cell(4, 4.5, ':', 0, 0, 'C');
        $pdf->Cell(50, 4.5, $dateText, 0, 1, 'L');

        $pdf->Ln(2);
        $pdf->SetX(110);
        $pdf->SetFont('Times', '', 10);
        $pdf->Cell(75, 4.5, 'UNIVERSITAS SUBANG', 0, 1, 'C');
        $pdf->SetX(110);
        $pdf->Cell(75, 4.5, 'FAKULTAS ILMU KOMPUTER', 0, 1, 'C');
        $pdf->SetX(110);
        $pdf->Cell(75, 4.5, $signerTitle.',', 0, 1, 'C');

        // Signature gap
        $pdf->Ln(22);

        $pdf->SetX(110);
        $pdf->SetFont('Times', 'B', 10);
        $pdf->Cell(75, 5, $signerName, 0, 1, 'C');

        $signerNik = ! empty($extra['dekan_nik']) ? $extra['dekan_nik'] : (! empty($letter->user?->nik) ? $letter->user->nik : '');
        if ($signerNik !== '') {
            $pdf->SetX(110);
            $pdf->SetFont('Times', '', 10);
            $pdf->Cell(75, 4.5, $signerNik, 0, 1, 'C');
        }

        // Tembusan Block
        $tembusanList = $extra['tembusan_list'] ?? [
            'Yth. Yayasan Kutawaringin Subang',
            'Yth. Wakil Rektor II Bidang Akademik Kemahasiswaan Universitas Subang',
        ];

        if (! empty($tembusanList)) {
            $tembusanY = $signerNik !== '' ? $sigY + 32 : $sigY + 28;
            $pdf->SetY($tembusanY);
            $pdf->SetX(20);
            $pdf->SetFont('Times', '', 9.5);
            $pdf->Cell(170, 4.5, 'Tembusan :', 0, 1, 'L');

            $isOrderedNumbered = ! empty($extra['tembusan_is_numbered']) || ($letter->letterType?->code === 'sk_dekan_gkm_ukm');

            foreach ($tembusanList as $tIdx => $t) {
                $tText = trim(is_array($t) ? ($t['text'] ?? '') : (string) $t);
                if ($tText === '') {
                    continue;
                }
                $pdf->SetX(20);
                if ($isOrderedNumbered) {
                    $pdf->Cell(5, 4.2, ($tIdx + 1).'.', 0, 0, 'R');
                    $pdf->Cell(2, 4.2, '', 0, 0);
                    $pdf->Cell(100, 4.2, $tText, 0, 1, 'L');
                } else {
                    $pdf->Cell(4, 4.2, chr(149), 0, 0, 'C');
                    $pdf->Cell(85, 4.2, $tText, 0, 1, 'L');
                }
            }
        }
    }

    /**
     * Render Landscape SK Attachment Signature Block.
     */
    protected function renderAttachmentSignatureBlock(FPDF $pdf, LetterRequest $letter): void
    {
        $extra = $letter->extra_data ?? [];
        $city = ! empty($extra['decision_city']) ? $extra['decision_city'] : 'Subang';
        $dateText = ! empty($extra['decision_date_text']) ? $extra['decision_date_text'] : Carbon::now()->translatedFormat('d F Y');
        $signerName = ! empty($letter->signer_name) ? $letter->signer_name : (! empty($extra['dekan_name']) ? $extra['dekan_name'] : 'Dr. TEPI PEIRISAL, M. Si.');
        $signerTitle = ! empty($letter->signer_title) ? $letter->signer_title : 'Dekan';

        if ($pdf->GetY() > 140) {
            $pdf->AddPage('L');
            $pdf->SetY(20);
        }

        $pdf->Ln(8);
        $sigY = $pdf->GetY();

        $pdf->SetY($sigY);
        $pdf->SetX(200);
        $pdf->SetFont('Times', '', 9.5);
        $pdf->Cell(80, 4.5, $city.', '.$dateText, 0, 1, 'C');
        $pdf->SetX(200);
        $pdf->Cell(80, 4.5, 'UNIVERSITAS SUBANG', 0, 1, 'C');
        $pdf->SetX(200);
        $pdf->Cell(80, 4.5, 'FAKULTAS ILMU KOMPUTER', 0, 1, 'C');
        $pdf->SetX(200);
        $pdf->Cell(80, 4.5, $signerTitle.',', 0, 1, 'C');

        $pdf->Ln(20);

        $pdf->SetX(200);
        $pdf->SetFont('Times', 'B', 9.5);
        $pdf->Cell(80, 5, $signerName, 0, 1, 'C');
    }

    /**
     * Calculate lines for multi-cell text in tables.
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
     * Render solid table row with vertical centering.
     *
     * @param  array<int, array{width: float, text: string, align?: string}>  $cells
     */
    protected function renderTableRow(FPDF $pdf, array $cells, float $lineHeight = 4.2, float $minRowHeight = 6.0): void
    {
        $startX = $pdf->GetX();
        $startY = $pdf->GetY();

        $maxLines = 1;
        foreach ($cells as $cell) {
            $lines = $this->calculateNumLines($pdf, $cell['width'], $cell['text']);
            if ($lines > $maxLines) {
                $maxLines = $lines;
            }
        }

        $rowH = max($minRowHeight, ($maxLines * $lineHeight) + 1.8);

        $currentX = $startX;
        foreach ($cells as $cell) {
            $w = $cell['width'];
            $text = $cell['text'];
            $align = $cell['align'] ?? 'C';

            $pdf->Rect($currentX, $startY, $w, $rowH);

            $numLines = $this->calculateNumLines($pdf, $w, $text);
            $textBlockHeight = $numLines * $lineHeight;
            $offsetY = max(0.5, ($rowH - $textBlockHeight) / 2);

            $pdf->SetXY($currentX, $startY + $offsetY);
            $pdf->MultiCell($w, $lineHeight, $text, 0, $align);

            $currentX += $w;
        }

        $pdf->SetXY($startX, $startY + $rowH);
    }

    /**
     * Generate complete SK Document PDF.
     */
    public function renderSkPdf(LetterRequest $letter, bool $isWatermarked = true): string
    {
        $extra = $letter->extra_data ?? [];
        $skNumber = $letter->letter_number ?: '.../D/FIK-US/IX/2025';

        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->SetMargins(20, 15, 20);
        $pdf->SetAutoPageBreak(false);

        // ==========================================
        // HALAMAN 1: JUDUL & KONSIDERANS
        // ==========================================
        $pdf->AddPage('P');
        $this->renderHeader($pdf);

        // Watermark if requested
        if ($isWatermarked) {
            $pdf->SetFont('Helvetica', 'B', 32);
            $pdf->SetTextColor(242, 245, 250);
            $pdf->SetXY(20, 135);
            $pdf->Cell(170, 18, 'PRATINJAU LANGSUNG (LIVE)', 0, 0, 'C');
            $pdf->SetXY(20, 48);
            $pdf->SetTextColor(0, 0, 0);
        }

        // Judul Keputusan Dekan
        $pdf->SetFont('Times', 'B', 11);
        $pdf->Cell(170, 4.8, 'KEPUTUSAN DEKAN', 0, 1, 'C');
        $pdf->Cell(170, 4.8, 'FAKULTAS ILMU KOMPUTER UNIVERSITAS SUBANG', 0, 1, 'C');

        $pdf->SetFont('Times', '', 10);
        $pdf->Cell(170, 4.5, 'NOMOR : '.$skNumber, 0, 1, 'C');
        $pdf->Ln(3.5);

        $pdf->SetFont('Times', 'B', 10);
        $pdf->Cell(170, 4.5, 'TENTANG', 0, 1, 'C');
        $pdf->Ln(2);

        $code = $letter->letterType?->code ?? '';
        $isDosenMengajar = ($code === 'sk_dekan_dosen_mengajar');
        $isPembimbingTa = ($code === 'sk_dekan_pembimbing_ta');
        $isDosenWali = ($code === 'sk_dekan_dosen_wali');
        $isYudisium = ($code === 'sk_dekan_yudisium');
        $isSeminarTa = ($code === 'sk_dekan_penguji_sidang_ta');
        $isSidangTa = ($code === 'sk_dekan_pengujian_sidang_skripsi');
        $isPengunduranDiri = ($code === 'sk_dekan_pengunduran_diri');
        $isGkmUkm = ($code === 'sk_dekan_gkm_ukm');

        if (! empty($extra['about_title'])) {
            $aboutTitle = mb_strtoupper($extra['about_title'], 'UTF-8');
        } elseif ($isGkmUkm) {
            $aboutTitle = "PENUNJUKAN DAN PENGANGKATAN KETUA\nGUGUS KENDALI MUTU (GKM) PADA FAKULTAS DAN UNIT KENDALI MUTU\n(UKM) PADA PROGRAM STUDI SISTEM INFORMASI UNIVERSITAS SUBANG";
        } else {
            $aboutTitle = "PENYELENGGARAAN DAN PENETAPAN DOSEN PEMBIMBING BKP MBKM\nSEMESTER GANJIL\nFAKULTAS ILMU KOMPUTER UNIVERSITAS SUBANG\nTAHUN AKADEMIK 2025/2026";
        }

        $pdf->SetFont('Times', 'B', 10);
        $pdf->MultiCell(170, 4.6, $aboutTitle, 0, 'C');
        $pdf->Ln(4);

        $pdf->SetFont('Times', 'B', 10);
        $pdf->Cell(170, 4.5, 'DEKAN FAKULTAS ILMU KOMPUTER UNIVERSITAS SUBANG', 0, 1, 'C');
        $pdf->Ln(4.5);

        // 1. Menimbang
        if (! empty($extra['menimbang'])) {
            $menimbangPoints = $extra['menimbang'];
        } elseif ($isGkmUkm) {
            $menimbangPoints = [
                'Bahwa dalam rangka mencapai Standar Nasional Pendidikan Tinggi diperlukan sistem penjaminan mutu pendidikan tinggi di Fakultas Ilmu Komputer Universitas Subang;',
                'Bahwa Fakultas Ilmu Komputer bertugas menetapkan, melaksanakan, mengevaluasi, mengendalikan, dan meningkatkan Standar Dikti secara berkelanjutan perlu sistem penjaminan mutu internal;',
                'Bahwa dalam upaya mengimplementasikan penjaminan mutu internal di Fakultas Ilmu Komputer Universitas Subang ditetapkan tugas pokok dan fungsi serta jabatan pelaksanaannya;',
                'Bahwa untuk keperluan yang dimaksud pada nomor 1, 2 dan 3 diatas maka perlu ditetapkan dengan Surat Keputusan Dekan Fakultas Ilmu Komputer Universitas Subang.',
            ];
        } else {
            $menimbangPoints = [
                'Bahwa dalam rangka kebijakan merdeka belajar yang tertuang dalam Permendikbud No. 3 Tahun 2020 memberikan hak kepada mahasiswa untuk 3 semester belajar di luar program studinya;',
                'Bahwa sehubungan dengan butir a, mahasiswa diberi kesempatan untuk mempraktekan ilmunya sesuai dengan program Bentuk Kegiatan Pembelajaran Merdeka Belajar Kampus Merdeka (BKP MBKM) yang dipilih setiap mahasiswa;',
                'Bahwa sebagai sarana untuk memberikan kesempatan kepada mahasiswa sebagaimana disebutkan dalam butir a dan b tentang program Merdeka Belajar Kampus Merdeka perlu diselenggarakan Bentuk Kegiatan Pembelajaran Merdeka Belajar Kampus Merdeka Tahun Akademik 2022/2023 dengan bimbingan Dosen Pembimbing;',
                'Penyelenggaraan Bentuk Kegiatan Pembelajaran Merdeka Belajar Kampus Merdeka dan penetapan dosen pembimbing yang dimaksud perlu ditetapkan dengan Keputusan Dekan;',
            ];
        }

        // 2. Mengingat
        if (! empty($extra['mengingat'])) {
            $mengingatPoints = $extra['mengingat'];
        } elseif ($isGkmUkm) {
            $mengingatPoints = [
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
            ];
        } else {
            $mengingatPoints = [
                'Undang-undang Republik Indonesia Nomor 20 tahun 2003 tentang Sistem Pendidikan Nasional;',
                'Undang-undang Republik Indonesia Nomor 12 tahun 2012 tentang Sistem Pendidikan Tinggi;',
                'Peraturan Presiden Nomor 8 Tahun 2012 tentang kerangka Kualifikasi Nasional Indonesia;',
                'Peraturan pemerintah No.17 Tahun 2010 Tentang Penyelenggaraan dan Pengelolaan Pendidikan;',
                'Peraturan Menteri Pendidikan dan Kebudayaan Nomor 3 tahun 2020 tentang Standar Nasional Pendidikan Tinggi;',
                'Surat Keputusan Menteri Pendidikan Nasional Republik Indonesia Nomor : 33/D/O/2005 tentang Penggabungan Sekolah Tinggi Ilmu Administrasi (STIA) Kutawaringin di Subang dan Sekolah Tinggi Teknologi (STT) Kutawaringin di Subang menjadi Universitas Subang serta Penambahan Program Studi Baru yang diselenggarakan oleh Yayasan Kutawaringin Subang di Subang;',
                'Surat Keputusan Yayasan Kutawaringin Nomor : 18/Y/XII/2007 tanggal 28 Oktober 2007 tentang Penetapan Statuta Universitas Subang;',
                'Permendikbud No. 3 Tahun 2020 tentang Standar Nasional Pendidikan Tinggi.',
            ];
        }

        // 3. Memperhatikan
        if (isset($extra['memperhatikan'])) {
            $memperhatikanPoints = $extra['memperhatikan'];
        } elseif ($isGkmUkm) {
            $memperhatikanPoints = [
                'Berita Acara rapat Fakultas Ilmu Komputer pada tanggal 13 November 2023 tentang lembaga penjaminan mutu pendidikan.',
            ];
        } else {
            $memperhatikanPoints = [
                'Kurikulum Program Studi Sistem Informasi Universitas Subang',
                'Kalender Akademik Fakultas Ilmu Komputer Universitas Subang',
                'Pedoman Akademik Proses Belajar Mengajar di Fakultas Ilmu Komputer Universitas Subang.',
                'Rapat Akademik tentang Bentuk Kegiatan Pembelajaran Merdeka Belajar Kampus Merdeka (BKP MBKM)',
            ];
        }

        $menimbangFormat = $isGkmUkm ? 'numbers' : (($isDosenMengajar || $isPembimbingTa || $isDosenWali || $isYudisium || $isSeminarTa || $isSidangTa || $isPengunduranDiri) ? 'letters' : 'bullets');
        $mengingatFormat = ($isDosenMengajar || $isPembimbingTa || $isDosenWali || $isYudisium || $isSeminarTa || $isSidangTa || $isPengunduranDiri || $isGkmUkm) ? 'numbers' : 'bullets';
        $memperhatikanFormat = ($isDosenWali || $isGkmUkm) ? 'numbers' : 'bullets';

        $this->renderConsiderationSection($pdf, 'Menimbang', $menimbangPoints, $menimbangFormat);
        $this->renderConsiderationSection($pdf, 'Mengingat', $mengingatPoints, $mengingatFormat);
        if (! empty($memperhatikanPoints)) {
            $this->renderConsiderationSection($pdf, 'Memperhatikan', $memperhatikanPoints, $memperhatikanFormat);
        }

        // ==========================================
        // DIKTUM MEMUTUSKAN
        // ==========================================
        if ($pdf->GetY() > 220) {
            $pdf->AddPage('P');
            $pdf->SetY(25);
        } else {
            $pdf->Ln(2);
        }

        $pdf->SetFont('Times', 'B', 10);
        $memutuskanLabel = $isGkmUkm ? 'MEMUTUSKAN :' : 'MEMUTUSKAN:';
        $pdf->Cell(170, 5, $memutuskanLabel, 0, 1, 'C');
        $pdf->Ln(2);

        $menetapkanText = ! empty($extra['menetapkan_text']) ? $extra['menetapkan_text'] : null;

        $pdf->SetFont('Times', '', 10);
        $pdf->SetX(20);
        $pdf->Cell(32, 5, 'Menetapkan', 0, 0, 'L');
        $pdf->Cell(4, 5, ':', 0, 0, 'C');

        if ($menetapkanText) {
            $pdf->MultiCell(134, 5, $menetapkanText, 0, 'L');
            $pdf->Ln(1.5);
        } else {
            $pdf->Cell(134, 5, '', 0, 1, 'L');
            $pdf->Ln(1);
        }

        if (! empty($extra['dictums'])) {
            $dictums = $extra['dictums'];
        } elseif ($isGkmUkm) {
            $gkmChairman = ! empty($extra['gkm_chairman_name']) ? $extra['gkm_chairman_name'] : 'MAYA DESTRIANI, S.Kom., M.Kom';
            $ukmChairman = ! empty($extra['ukm_chairman_name']) ? $extra['ukm_chairman_name'] : 'CACA ARIF HERDIAN, S.Kom., M.Kom';
            $dictums = [
                [
                    'label' => '1.',
                    'text' => "Menunjuk dan mengangkat Saudara {$gkmChairman} sebagai Ketua Gugus Kendali Mutu (GKM) pada Fakultas dan Saudara {$ukmChairman} sebagai Ketua Unit Kedali Mutu (UKM) pada Program studi Sistem Informasi Universitas Subang.",
                ],
                [
                    'label' => '2.',
                    'text' => "Tugas dan wewenang Ketua Gugus Kendali Mutu di lingkungan Fakultas adalah sebagai berikut :\n1. Menyiapkan bahan kebijakan sistem penjaminan mutu kepada Dekan;\n2. Mengkoordinasikan penyusunan dokumen-dokumen yang diperlukan dalam rangka pelaksanaan peningkatan mutu akademik dan non akademik;\n3. Memantau dan mengevaluasi pelaksanaan mutu akademik dan non akademik.\n4. Melakukan kajian terhadap upaya peningkatan mutu baik akademik maupun non akademik.\n5. Mempersiapkan Akreditasi, Evaluasi dan Implementasinya.",
                ],
                [
                    'label' => '3.',
                    'text' => "Tugas dan wewenang Katua Unit Kendali Mutu (UKM) di lingkup Fakultas Ilmu Komputer sebagai berikut :\n1. Menyiapkan bahan untuk Ketua GKM dalam menetapkan sistem penjamin mutu internal program studi.\n2. Pelaksanaan dan mengembangkan sistem Unit Kendali Mutu.\n3. Mengkoordinasikan penyusunan dokumen-dokumen yang diperlukan dalam rangka pelaksanaan peningkatan mutu akademik dan non akademik.\n4. Memantau dan mengevaluasi pelaksanaan mutu akademik dan non akademik.\n5. Melakukan kajian terhadap pelaksanaan Unit Kendali Mutu baik akademik maupun non akademik.\n6. Mempersiapkan akreditasi, evaluasi dan implementasinya.",
                ],
                [
                    'label' => '4.',
                    'text' => 'Dalam rangka persiapan dan pelaksanaan akreditasi Fakultas Ilmu Komputer Universitas Subang maka Gugus Kendali Mutu dan Unit Kendali Mutu membantu memberikan bahan dan dokumen mutu yang diperlukan oleh lembaga penjaminan mutu pendidikan.',
                ],
                [
                    'label' => '5.',
                    'text' => 'Segala pembiayaan yang timbul sebagai akibat dan pelaksanaan keputusan ini bersumber pada anggaran pendapatan dan belanja Fakultas untuk Gugus Kendali Mutu (GKM) dan Unit Kendali Mutu (UKM) dan Anggaran Pendapatan dan Belanja Fakultas Ilmu Komputer Universitas Subang untuk Lembaga Penjaminan Mutu Pendidikan (LPMP).',
                ],
                [
                    'label' => '6.',
                    'text' => 'Keputusan ini mulai berlaku sejak tanggal ditetapkan dengan ketentuan apabila dikemudian hari ternyata ada kekeliruan kesalahan dalam penetapannya akan diadakan perubahan dan perbaikan sebagaimana mestinya.',
                ],
            ];
        } else {
            $dictums = [
                [
                    'label' => 'Pertama',
                    'text' => 'Menyelenggarakan Program Bentuk Kegiatan Pembelajaran Merdeka Belajar Kampus Merdeka (BKP MBKM) Semester Ganjil Tahun Akademik 2025/2026 dengan menunjuk nama-nama dosen pembimbing sebagaimana tercantum pada lampiran Surat Keputusan',
                ],
                [
                    'label' => 'Kedua',
                    'text' => 'Yang berhak mengikuti Program Bentuk Kegiatan Pembelajaran Merdeka Belajar Kampus Merdeka (BKP MBKM) adalah mahasiswa Fakultas Ilmu Komputer yang telah menempuh 80 sks pada Tahun Akademik 2025/2026.',
                ],
                [
                    'label' => 'Ketiga',
                    'text' => 'Untuk mengikuti Program Bentuk Kegiatan Pembelajaran Merdeka Belajar Kampus Merdeka (BKP MBKM) yang dimaksud pada butir kedua, mahasiswa yang bersangkutan telah terdaftar sebagai mahasiswa Fakultas Ilmu Komputer Universitas Subang Semester Ganjil Tahun Akademik 2025/2026.',
                ],
                [
                    'label' => 'Keempat',
                    'text' => 'Prosedur penyelenggaraan mahasiswa yang akan mengikuti Program Bentuk Kegiatan Pembelajaran Merdeka Belajar Kampus Merdeka (BKP MBKM) sesuai dengan pedoman akademik proses belajar mengajar di Fakultas Ilmu Komputer Universitas Subang.',
                ],
                [
                    'label' => 'Kelima',
                    'text' => 'Segala pembiayaan sebagai akibat dari pelaksanaan Surat Keputusan ini dibebankan kepada biaya kuliah/ praktikum Fakultas Ilmu Komputer Universitas Subang Tahun Akademik 2025/2026.',
                ],
                [
                    'label' => 'Keenam',
                    'text' => 'Keputusan ini mulai berlaku sejak tanggal ditetapkan dan apabila terdapat kekeliruan dalam keputusan ini akan diperbaiki sebagaimana mestinya.',
                ],
            ];
        }
        $this->renderDictumSection($pdf, $dictums);

        // Blok Tanda Tangan Dekan pada halaman akhir naskah SK
        $this->renderSkSignatureBlock($pdf, $letter);

        // SK Pengangkatan GKM dan UKM adalah SK Mandiri Tanpa Lampiran (Semua ketetapan ada pada naskah SK)
        if ($isGkmUkm) {
            return $pdf->Output('S');
        }

        // ==========================================
        // HALAMAN LAMPIRAN (LANDSCAPE)
        // ==========================================
        $pdf->AddPage('L');
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetY(15);

        // Header Lampiran
        $this->renderAttachmentHeader($pdf, $letter);

        if ($isDosenMengajar) {
            $this->renderTeachingScheduleAttachment($pdf, $letter);
        } elseif ($isPembimbingTa) {
            $this->renderTaAttachment($pdf, $letter);
        } elseif ($isDosenWali) {
            $this->renderDosenWaliAttachment($pdf, $letter);
        } elseif ($isYudisium) {
            $this->renderYudisiumAttachment($pdf, $letter);
        } elseif ($isSeminarTa) {
            $this->renderSeminarTaAttachment($pdf, $letter);
        } elseif ($isSidangTa) {
            $this->renderSidangTaAttachment($pdf, $letter);
        } elseif ($isPengunduranDiri) {
            $this->renderPengunduranDiriAttachment($pdf, $letter);
        } else {
            $this->renderMbkmAttachment($pdf, $letter);
        }

        // Tanda tangan Dekan di Lampiran
        $this->renderAttachmentSignatureBlock($pdf, $letter);

        return $pdf->Output('S');
    }

    /**
     * Render Lampiran SK Dosen Mengajar (No, Nama Dosen, Mata Kuliah, Jumlah SKS, Semester, Kelas).
     */
    protected function renderTeachingScheduleAttachment(FPDF $pdf, LetterRequest $letter): void
    {
        $extra = $letter->extra_data ?? [];
        $prodi = ! empty($extra['study_program']) ? $extra['study_program'] : 'Program Studi Sistem Informasi S1';

        $pdf->Ln(2);

        // Kolom Tabel Landscape (Total printable width = 297 - 30 = 267mm)
        $wNo = 14;
        $wDosen = 70;
        $wMatkul = 105;
        $wSks = 26;
        $wSem = 26;
        $wKelas = 26; // 14 + 70 + 105 + 26 + 26 + 26 = 267mm

        // Table Header
        $pdf->SetFont('Times', 'B', 10);
        $pdf->SetX(15);
        $this->renderTableRow($pdf, [
            ['width' => $wNo, 'text' => 'No.', 'align' => 'C'],
            ['width' => $wDosen, 'text' => 'Nama Dosen', 'align' => 'C'],
            ['width' => $wMatkul, 'text' => 'Mata Kuliah', 'align' => 'C'],
            ['width' => $wSks, 'text' => "Jumlah\nSKS", 'align' => 'C'],
            ['width' => $wSem, 'text' => 'Semester', 'align' => 'C'],
            ['width' => $wKelas, 'text' => 'Kelas', 'align' => 'C'],
        ], 4.5, 9.0);

        $lecturers = $extra['teaching_lecturers'] ?? [];
        if (empty($lecturers)) {
            $lecturers = [
                [
                    'name' => 'Bambang Tjahjo Utomo, MT',
                    'courses' => [
                        ['name' => 'APSI II', 'sks' => '3', 'semester' => '5', 'class' => 'A'],
                        ['name' => 'APSI II', 'sks' => '3', 'semester' => '5', 'class' => 'B'],
                        ['name' => 'APSI II', 'sks' => '3', 'semester' => '5', 'class' => 'C'],
                        ['name' => 'Bisnis Online', 'sks' => '3', 'semester' => '7', 'class' => 'A'],
                        ['name' => 'Bisnis Online', 'sks' => '3', 'semester' => '7', 'class' => 'B'],
                        ['name' => 'Digital Marketing', 'sks' => '3', 'semester' => '7', 'class' => 'A'],
                        ['name' => 'Digital Marketing', 'sks' => '3', 'semester' => '7', 'class' => 'B'],
                        ['name' => 'Praktek Manajemen lanjut', 'sks' => '2', 'semester' => '7', 'class' => 'A KP'],
                        ['name' => 'Praktek Analisis lanjut', 'sks' => '4', 'semester' => '7', 'class' => 'A KP'],
                    ],
                ],
            ];
        }

        $pdf->SetFont('Times', '', 9.5);
        $rowNumber = 1;

        foreach ($lecturers as $lIdx => $lecturer) {
            $dosenName = $lecturer['name'] ?? '-';
            $courses = $lecturer['courses'] ?? [];
            if (empty($courses)) {
                $courses = [['name' => '-', 'sks' => '-', 'semester' => '-', 'class' => '-']];
            }

            $courseCount = count($courses);
            $lineH = 5.2;
            $blockTotalH = $courseCount * $lineH;

            // Check if page overflow
            if ($pdf->GetY() + min($blockTotalH, 30) > 165) {
                $pdf->AddPage('L');
                $pdf->SetY(20);
                $pdf->SetX(15);
                $pdf->SetFont('Times', 'B', 10);
                $this->renderTableRow($pdf, [
                    ['width' => $wNo, 'text' => 'No.', 'align' => 'C'],
                    ['width' => $wDosen, 'text' => 'Nama Dosen', 'align' => 'C'],
                    ['width' => $wMatkul, 'text' => 'Mata Kuliah', 'align' => 'C'],
                    ['width' => $wSks, 'text' => "Jumlah\nSKS", 'align' => 'C'],
                    ['width' => $wSem, 'text' => 'Semester', 'align' => 'C'],
                    ['width' => $wKelas, 'text' => 'Kelas', 'align' => 'C'],
                ], 4.5, 9.0);
                $pdf->SetFont('Times', '', 9.5);
            }

            $startY = $pdf->GetY();
            $startX = 15;

            // Draw outer border for No & Nama Dosen across all course rows of this lecturer
            $pdf->Rect($startX, $startY, $wNo, $blockTotalH);
            $pdf->Rect($startX + $wNo, $startY, $wDosen, $blockTotalH);

            // Print No
            $pdf->SetXY($startX, $startY + 1.5);
            $pdf->Cell($wNo, $lineH, (string) $rowNumber, 0, 0, 'C');

            // Print Nama Dosen
            $pdf->SetXY($startX + $wNo + 2, $startY + 1.5);
            $pdf->MultiCell($wDosen - 4, 4.6, $dosenName, 0, 'L');

            // Print Each Course Row
            $currentCourseY = $startY;
            foreach ($courses as $cIdx => $c) {
                $cName = $c['name'] ?? '-';
                $cSks = (string) ($c['sks'] ?? '-');
                $cSem = (string) ($c['semester'] ?? '-');
                $cClass = (string) ($c['class'] ?? '-');

                $courseX = $startX + $wNo + $wDosen;
                $pdf->SetXY($courseX, $currentCourseY);

                $pdf->Rect($courseX, $currentCourseY, $wMatkul, $lineH);
                $pdf->Rect($courseX + $wMatkul, $currentCourseY, $wSks, $lineH);
                $pdf->Rect($courseX + $wMatkul + $wSks, $currentCourseY, $wSem, $lineH);
                $pdf->Rect($courseX + $wMatkul + $wSks + $wSem, $currentCourseY, $wKelas, $lineH);

                $pdf->Cell($wMatkul, $lineH, '  '.$cName, 0, 0, 'L');
                $pdf->Cell($wSks, $lineH, $cSks, 0, 0, 'C');
                $pdf->Cell($wSem, $lineH, $cSem, 0, 0, 'C');
                $pdf->Cell($wKelas, $lineH, $cClass, 0, 1, 'C');

                $currentCourseY += $lineH;
            }

            $pdf->SetY($startY + $blockTotalH);
            $rowNumber++;
        }
    }

    /**
     * Render Lampiran Matriks MBKM.
     */
    protected function renderMbkmAttachment(FPDF $pdf, LetterRequest $letter): void
    {
        $extra = $letter->extra_data ?? [];
        $subtitle = ! empty($extra['attachment_subtitle'])
            ? $extra['attachment_subtitle']
            : "Daftar Nama Dosen Pembimbing Program Bentuk Kegiatan Pembelajaran\nMerdeka Belajar Kampus Merdeka (BKP MBKM) Semester Ganjil\nFakultas Ilmu Komputer Universitas Subang\nTahun Akademik 2025/2026";

        $pdf->SetFont('Times', 'B', 10);
        $pdf->MultiCell(267, 4.5, $subtitle, 0, 'C');
        $pdf->Ln(4);

        $mbkmRows = $extra['mbkm_matrix'] ?? [
            [
                'npm' => 'D1A210020',
                'name' => 'FANNY APRILIANTI',
                'type' => 'Studi Independen',
                'company' => 'Dicoding Indonesia',
                'position' => 'Pengembang Front-End Web dan Back-End',
                'advisor_1' => 'Bagus Ali Akbar, S.Si., M.Kom',
                'advisor_2' => 'Achmad Syafaat, S.Kom., MT',
            ],
            [
                'npm' => 'D1A210025',
                'name' => 'LUNA AMELLA WARDHANI',
                'type' => 'Magang Mandiri',
                'company' => 'PT. Len Industri (Persero)',
                'position' => 'UI/UX & Mobile Developer',
                'advisor_1' => 'Ir. Syarif Hidayat, MT',
                'advisor_2' => 'Bagus Ali Akbar, S.Si., M.Kom',
            ],
        ];

        // Tabel Matriks MBKM
        $wNo = 10;
        $wNpm = 24;
        $wNama = 48;
        $wJenis = 28;
        $wMitra = 45;
        $wPosisi = 48;
        $wPemb1 = 32;
        $wPemb2 = 32;

        // Table Header
        $pdf->SetFont('Times', 'B', 9);
        $pdf->SetX(15);
        $this->renderTableRow($pdf, [
            ['width' => $wNo, 'text' => 'NO', 'align' => 'C'],
            ['width' => $wNpm, 'text' => 'NPM', 'align' => 'C'],
            ['width' => $wNama, 'text' => 'NAMA', 'align' => 'C'],
            ['width' => $wJenis, 'text' => 'JENIS', 'align' => 'C'],
            ['width' => $wMitra, 'text' => 'MITRA/PERUSAHAAN', 'align' => 'C'],
            ['width' => $wPosisi, 'text' => "POSISI/LEARNING\nPATH/NAMA APLIKASI", 'align' => 'C'],
            ['width' => $wPemb1, 'text' => 'PEMBIMBING 1', 'align' => 'C'],
            ['width' => $wPemb2, 'text' => 'PEMBIMBING 2', 'align' => 'C'],
        ], 4.2, 8.5);

        // Table Rows
        $pdf->SetFont('Times', '', 8.5);
        foreach ($mbkmRows as $idx => $row) {
            $noStr = (string) ($idx + 1);
            $npmStr = (string) ($row['npm'] ?? '-');
            $namaStr = (string) ($row['name'] ?? '-');
            $jenisStr = (string) ($row['type'] ?? '-');
            $mitraStr = (string) ($row['company'] ?? '-');
            $posisiStr = (string) ($row['position'] ?? '-');
            $pemb1Str = (string) ($row['advisor_1'] ?? '-');
            $pemb2Str = (string) ($row['advisor_2'] ?? '-');

            if ($pdf->GetY() > 165) {
                $pdf->AddPage('L');
                $pdf->SetY(20);
                $pdf->SetX(15);
                $pdf->SetFont('Times', 'B', 9);
                $this->renderTableRow($pdf, [
                    ['width' => $wNo, 'text' => 'NO', 'align' => 'C'],
                    ['width' => $wNpm, 'text' => 'NPM', 'align' => 'C'],
                    ['width' => $wNama, 'text' => 'NAMA', 'align' => 'C'],
                    ['width' => $wJenis, 'text' => 'JENIS', 'align' => 'C'],
                    ['width' => $wMitra, 'text' => 'MITRA/PERUSAHAAN', 'align' => 'C'],
                    ['width' => $wPosisi, 'text' => "POSISI/LEARNING\nPATH/NAMA APLIKASI", 'align' => 'C'],
                    ['width' => $wPemb1, 'text' => 'PEMBIMBING 1', 'align' => 'C'],
                    ['width' => $wPemb2, 'text' => 'PEMBIMBING 2', 'align' => 'C'],
                ], 4.2, 8.5);
                $pdf->SetFont('Times', '', 8.5);
            }

            $pdf->SetX(15);
            $this->renderTableRow($pdf, [
                ['width' => $wNo, 'text' => $noStr, 'align' => 'C'],
                ['width' => $wNpm, 'text' => $npmStr, 'align' => 'C'],
                ['width' => $wNama, 'text' => $namaStr, 'align' => 'L'],
                ['width' => $wJenis, 'text' => $jenisStr, 'align' => 'C'],
                ['width' => $wMitra, 'text' => $mitraStr, 'align' => 'L'],
                ['width' => $wPosisi, 'text' => $posisiStr, 'align' => 'L'],
                ['width' => $wPemb1, 'text' => $pemb1Str, 'align' => 'L'],
                ['width' => $wPemb2, 'text' => $pemb2Str, 'align' => 'L'],
            ], 4.2, 8.0);
        }
    }

    /**
     * Render Lampiran Matriks SK Dosen Pembimbing Tugas Akhir (NO, NPM, NAMA, JUDUL TUGAS AKHIR, PEMBIMBING I, PEMBIMBING II).
     */
    protected function renderTaAttachment(FPDF $pdf, LetterRequest $letter): void
    {
        $extra = $letter->extra_data ?? [];

        $pdf->Ln(2);

        // Printable width landscape: 297 - 30 = 267mm
        $wNo = 14;
        $wNpm = 28;
        $wNama = 52;
        $wJudul = 93;
        $wPemb1 = 40;
        $wPemb2 = 40; // 14 + 28 + 52 + 93 + 40 + 40 = 267mm

        // Header Tabel
        $pdf->SetFont('Times', 'B', 9.5);
        $pdf->SetX(15);
        $this->renderTableRow($pdf, [
            ['width' => $wNo, 'text' => 'NO', 'align' => 'C'],
            ['width' => $wNpm, 'text' => 'NPM', 'align' => 'C'],
            ['width' => $wNama, 'text' => 'NAMA', 'align' => 'C'],
            ['width' => $wJudul, 'text' => 'JUDUL TUGAS AKHIR', 'align' => 'C'],
            ['width' => $wPemb1, 'text' => 'PEMBIMBING I', 'align' => 'C'],
            ['width' => $wPemb2, 'text' => 'PEMBIMBING II', 'align' => 'C'],
        ], 4.5, 9.0);

        $taRows = $extra['ta_matrix'] ?? [];
        if (empty($taRows)) {
            $taRows = [
                [
                    'npm' => 'D1A210001',
                    'name' => 'RIZKI RAMADHAN',
                    'title' => 'Sistem Informasi Geografis Pemetaan UMKM Berbasis Web di Kabupaten Subang',
                    'advisor_1' => 'Dr. TEPI PEIRISAL, M.Si',
                    'advisor_2' => 'Bagus Ali Akbar, S.Si., M.Kom',
                ],
            ];
        }

        $pdf->SetFont('Times', '', 9.0);
        foreach ($taRows as $idx => $row) {
            $noStr = (string) ($idx + 1);
            $npmStr = (string) ($row['npm'] ?? '-');
            $namaStr = (string) ($row['name'] ?? '-');
            $judulStr = (string) ($row['title'] ?? '-');
            $pemb1Str = (string) ($row['advisor_1'] ?? '-');
            $pemb2Str = (string) ($row['advisor_2'] ?? '-');

            if ($pdf->GetY() > 165) {
                $pdf->AddPage('L');
                $pdf->SetY(20);
                $pdf->SetX(15);
                $pdf->SetFont('Times', 'B', 9.5);
                $this->renderTableRow($pdf, [
                    ['width' => $wNo, 'text' => 'NO', 'align' => 'C'],
                    ['width' => $wNpm, 'text' => 'NPM', 'align' => 'C'],
                    ['width' => $wNama, 'text' => 'NAMA', 'align' => 'C'],
                    ['width' => $wJudul, 'text' => 'JUDUL TUGAS AKHIR', 'align' => 'C'],
                    ['width' => $wPemb1, 'text' => 'PEMBIMBING I', 'align' => 'C'],
                    ['width' => $wPemb2, 'text' => 'PEMBIMBING II', 'align' => 'C'],
                ], 4.5, 9.0);
                $pdf->SetFont('Times', '', 9.0);
            }

            $pdf->SetX(15);
            $this->renderTableRow($pdf, [
                ['width' => $wNo, 'text' => $noStr, 'align' => 'C'],
                ['width' => $wNpm, 'text' => $npmStr, 'align' => 'C'],
                ['width' => $wNama, 'text' => $namaStr, 'align' => 'L'],
                ['width' => $wJudul, 'text' => $judulStr, 'align' => 'L'],
                ['width' => $wPemb1, 'text' => $pemb1Str, 'align' => 'L'],
                ['width' => $wPemb2, 'text' => $pemb2Str, 'align' => 'L'],
            ], 4.5, 8.5);
        }
    }

    /**
     * Render Lampiran Matriks SK Dosen Wali Akademik (NO, NPM, NAMA, DOSEN WALI).
     */
    protected function renderDosenWaliAttachment(FPDF $pdf, LetterRequest $letter): void
    {
        $extra = $letter->extra_data ?? [];

        $pdf->Ln(2);

        // Printable width landscape: 297 - 30 = 267mm
        $wNo = 16;
        $wNpm = 35;
        $wNama = 108;
        $wDosenWali = 108; // 16 + 35 + 108 + 108 = 267mm

        // Header Tabel
        $pdf->SetFont('Times', 'B', 9.5);
        $pdf->SetX(15);
        $this->renderTableRow($pdf, [
            ['width' => $wNo, 'text' => 'NO', 'align' => 'C'],
            ['width' => $wNpm, 'text' => 'NPM', 'align' => 'C'],
            ['width' => $wNama, 'text' => 'NAMA', 'align' => 'C'],
            ['width' => $wDosenWali, 'text' => 'DOSEN WALI', 'align' => 'C'],
        ], 4.5, 9.0);

        $waliRows = $extra['wali_matrix'] ?? [];
        if (empty($waliRows)) {
            $waliRows = [
                [
                    'npm' => 'D1A210001',
                    'name' => 'RIZKI RAMADHAN',
                    'advisor' => 'Bagus Ali Akbar, S.Si., M.Kom',
                ],
            ];
        }

        $pdf->SetFont('Times', '', 9.0);
        foreach ($waliRows as $idx => $row) {
            $noStr = (string) ($idx + 1);
            $npmStr = (string) ($row['npm'] ?? '-');
            $namaStr = (string) ($row['name'] ?? '-');
            $advisorStr = (string) ($row['advisor'] ?? '-');

            if ($pdf->GetY() > 165) {
                $pdf->AddPage('L');
                $pdf->SetY(20);
                $pdf->SetX(15);
                $pdf->SetFont('Times', 'B', 9.5);
                $this->renderTableRow($pdf, [
                    ['width' => $wNo, 'text' => 'NO', 'align' => 'C'],
                    ['width' => $wNpm, 'text' => 'NPM', 'align' => 'C'],
                    ['width' => $wNama, 'text' => 'NAMA', 'align' => 'C'],
                    ['width' => $wDosenWali, 'text' => 'DOSEN WALI', 'align' => 'C'],
                ], 4.5, 9.0);
                $pdf->SetFont('Times', '', 9.0);
            }

            $pdf->SetX(15);
            $this->renderTableRow($pdf, [
                ['width' => $wNo, 'text' => $noStr, 'align' => 'C'],
                ['width' => $wNpm, 'text' => $npmStr, 'align' => 'C'],
                ['width' => $wNama, 'text' => $namaStr, 'align' => 'L'],
                ['width' => $wDosenWali, 'text' => $advisorStr, 'align' => 'L'],
            ], 4.5, 8.5);
        }
    }

    /**
     * Render Lampiran Matriks SK Kelulusan / Yudisium (NO, NPM, NAMA, JUDUL SKRIPSI, IPK, PREDIKAT YUDISIUM).
     */
    protected function renderYudisiumAttachment(FPDF $pdf, LetterRequest $letter): void
    {
        $extra = $letter->extra_data ?? [];
        $prodi = ! empty($extra['study_program']) ? mb_strtoupper($extra['study_program'], 'UTF-8') : 'PROGRAM STUDI SISTEM INFORMASI';
        $semType = ! empty($extra['semester_type']) ? mb_strtoupper($extra['semester_type'], 'UTF-8') : 'GENAP';
        if (str_starts_with($semType, 'SEMESTER ')) {
            $semType = substr($semType, 9);
        }
        $ta = ! empty($letter->academic_year) ? $letter->academic_year : '2025/2026';

        $subtitle = ! empty($extra['attachment_subtitle'])
            ? $extra['attachment_subtitle']
            : "DAFTAR PESERTA YUDISIUM {$prodi}\n{$semType} TAHUN AKADEMIK {$ta}\nFAKULTAS ILMU KOMPUTER\nUNIVERSITAS SUBANG";

        $pdf->Ln(2);

        $pdf->SetFont('Times', 'B', 10);
        $pdf->MultiCell(267, 4.5, $subtitle, 0, 'C');
        $pdf->Ln(4);

        // Printable width landscape: 297 - 30 = 267mm
        $wNo = 14;
        $wNpm = 28;
        $wNama = 52;
        $wJudul = 105;
        $wIpk = 26;
        $wPredikat = 42; // 14 + 28 + 52 + 105 + 26 + 42 = 267mm

        // Header Tabel
        $pdf->SetFont('Times', 'B', 9.5);
        $pdf->SetX(15);
        $this->renderTableRow($pdf, [
            ['width' => $wNo, 'text' => 'NO', 'align' => 'C'],
            ['width' => $wNpm, 'text' => 'NPM', 'align' => 'C'],
            ['width' => $wNama, 'text' => 'NAMA', 'align' => 'C'],
            ['width' => $wJudul, 'text' => 'JUDUL SKRIPSI', 'align' => 'C'],
            ['width' => $wIpk, 'text' => 'IPK', 'align' => 'C'],
            ['width' => $wPredikat, 'text' => "PREDIKAT\nYUDISIUM", 'align' => 'C'],
        ], 4.5, 9.0);

        $yudisiumRows = $extra['yudisium_matrix'] ?? [];
        if (empty($yudisiumRows)) {
            $yudisiumRows = [
                [
                    'npm' => 'D1A210001',
                    'name' => 'RIZKI RAMADHAN',
                    'thesis_title' => 'Sistem Informasi Geografis Pemetaan UMKM Berbasis Web di Kabupaten Subang',
                    'ipk' => '3.85',
                    'predicate' => 'Dengan Pujian (Cum Laude)',
                ],
            ];
        }

        $pdf->SetFont('Times', '', 9.0);
        foreach ($yudisiumRows as $idx => $row) {
            $noStr = (string) ($idx + 1);
            $npmStr = (string) ($row['npm'] ?? '-');
            $namaStr = (string) ($row['name'] ?? '-');
            $judulStr = (string) ($row['thesis_title'] ?? ($row['title'] ?? '-'));
            $ipkStr = (string) ($row['ipk'] ?? '-');
            $predikatStr = (string) ($row['predicate'] ?? '-');

            if ($pdf->GetY() > 165) {
                $pdf->AddPage('L');
                $pdf->SetY(20);
                $pdf->SetX(15);
                $pdf->SetFont('Times', 'B', 9.5);
                $this->renderTableRow($pdf, [
                    ['width' => $wNo, 'text' => 'NO', 'align' => 'C'],
                    ['width' => $wNpm, 'text' => 'NPM', 'align' => 'C'],
                    ['width' => $wNama, 'text' => 'NAMA', 'align' => 'C'],
                    ['width' => $wJudul, 'text' => 'JUDUL SKRIPSI', 'align' => 'C'],
                    ['width' => $wIpk, 'text' => 'IPK', 'align' => 'C'],
                    ['width' => $wPredikat, 'text' => "PREDIKAT\nYUDISIUM", 'align' => 'C'],
                ], 4.5, 9.0);
                $pdf->SetFont('Times', '', 9.0);
            }

            $pdf->SetX(15);
            $this->renderTableRow($pdf, [
                ['width' => $wNo, 'text' => $noStr, 'align' => 'C'],
                ['width' => $wNpm, 'text' => $npmStr, 'align' => 'C'],
                ['width' => $wNama, 'text' => $namaStr, 'align' => 'L'],
                ['width' => $wJudul, 'text' => $judulStr, 'align' => 'L'],
                ['width' => $wIpk, 'text' => $ipkStr, 'align' => 'C'],
                ['width' => $wPredikat, 'text' => $predikatStr, 'align' => 'C'],
            ], 4.5, 8.5);
        }
    }

    /**
     * Render Lampiran Matriks SK Pelaksanaan Pengujian Seminar Tugas Akhir.
     * Header atas tabel: Hari / Tanggal & RUANGAN
     * Kolom: NO, WAKTU, NPM, NAMA MAHASISWA, JUDUL SKRIPSI, PEMBIMBING, PENGUJI
     */
    protected function renderSeminarTaAttachment(FPDF $pdf, LetterRequest $letter): void
    {
        $extra = $letter->extra_data ?? [];
        $semType = ! empty($extra['semester_type']) ? mb_strtoupper($extra['semester_type'], 'UTF-8') : 'SEMESTER GANJIL';
        $ta = ! empty($letter->academic_year) ? $letter->academic_year : '2026/2027';
        $wave = ! empty($extra['wave_info']) ? mb_strtoupper($extra['wave_info'], 'UTF-8') : 'GELOMBANG I';
        $examDate = ! empty($extra['exam_schedule_date']) ? $extra['exam_schedule_date'] : 'Kamis, 15 Oktober 2026';
        $room = ! empty($extra['exam_room']) ? $extra['exam_room'] : 'RUANG 8';

        $pdf->Ln(2);

        $pdf->SetFont('Times', 'B', 12);
        $pdf->Cell(267, 5, 'JADWAL SEMINAR SKRIPSI', 0, 1, 'C');
        $pdf->SetFont('Times', 'B', 9.5);
        $pdf->Cell(267, 4.5, "{$semType} {$wave} TAHUN AKADEMIK {$ta}", 0, 1, 'C');
        $pdf->Ln(3.5);

        // Printable width landscape: 297 - 30 = 267mm
        $wNo = 10;
        $wWaktu = 24;
        $wNpm = 24;
        $wNama = 44;
        $wJudul = 65;
        $wPemb = 50;
        $wPeng = 50; // 10 + 24 + 24 + 44 + 65 + 50 + 50 = 267mm

        // Subheader Baris Atas: Hari/Tanggal & Ruangan
        $pdf->SetFont('Times', 'B', 9.0);
        $pdf->SetX(15);
        $pdf->Cell($wNo + $wWaktu + $wNpm + $wNama + $wJudul + $wPemb, 5, '  Hari / Tanggal : '.$examDate, 1, 0, 'L');
        $pdf->Cell($wPeng, 5, 'RUANGAN: '.$room.'  ', 1, 1, 'R');

        // Header Kolom Tabel
        $pdf->SetX(15);
        $this->renderTableRow($pdf, [
            ['width' => $wNo, 'text' => 'NO', 'align' => 'C'],
            ['width' => $wWaktu, 'text' => 'WAKTU', 'align' => 'C'],
            ['width' => $wNpm, 'text' => 'NPM', 'align' => 'C'],
            ['width' => $wNama, 'text' => 'NAMA MAHASISWA', 'align' => 'C'],
            ['width' => $wJudul, 'text' => 'JUDUL SKRIPSI', 'align' => 'C'],
            ['width' => $wPemb, 'text' => 'PEMBIMBING', 'align' => 'C'],
            ['width' => $wPeng, 'text' => 'PENGUJI', 'align' => 'C'],
        ], 4.5, 9.0);

        $seminarRows = $extra['seminar_matrix'] ?? [];
        if (empty($seminarRows)) {
            $seminarRows = [
                [
                    'is_header_row' => true,
                    'time' => '07.30 – 08.00',
                    'note' => 'Persiapan dan Pembukaan Seminar',
                ],
                [
                    'is_header_row' => false,
                    'time' => '08.00 – 08.45',
                    'npm' => 'D1A230046',
                    'name' => 'SYIFA MUTAALIA',
                    'thesis_title' => 'PERANCANGAN AI AGENT BERBASIS LARGE LANGUAGE MODEL UNTUK MENINGKATKAN EFISIENSI LAYANAN INFORMASI KEPEMILUAN PADA KPU KABUPATEN SUBANG',
                    'advisor_1' => 'Sofwandi Noor, MT',
                    'advisor_2' => 'Bambang Tjahjo Utomo, MT',
                    'examiner_1' => 'Bagus Ali Akbar, S.Si., M.Kom',
                    'examiner_2' => 'Tazkia Salsabila Ardan, M.Kom',
                ],
            ];
        }

        $pdf->SetFont('Times', '', 8.5);
        $rowNum = 1;

        foreach ($seminarRows as $row) {
            $isOpening = ! empty($row['is_header_row']);

            if ($pdf->GetY() > 165) {
                $pdf->AddPage('L');
                $pdf->SetY(20);
                $pdf->SetX(15);
                $pdf->SetFont('Times', 'B', 9.0);
                $pdf->Cell($wNo + $wWaktu + $wNpm + $wNama + $wJudul + $wPemb, 5, '  Hari / Tanggal : '.$examDate, 1, 0, 'L');
                $pdf->Cell($wPeng, 5, 'RUANGAN: '.$room.'  ', 1, 1, 'R');
                $pdf->SetX(15);
                $this->renderTableRow($pdf, [
                    ['width' => $wNo, 'text' => 'NO', 'align' => 'C'],
                    ['width' => $wWaktu, 'text' => 'WAKTU', 'align' => 'C'],
                    ['width' => $wNpm, 'text' => 'NPM', 'align' => 'C'],
                    ['width' => $wNama, 'text' => 'NAMA MAHASISWA', 'align' => 'C'],
                    ['width' => $wJudul, 'text' => 'JUDUL SKRIPSI', 'align' => 'C'],
                    ['width' => $wPemb, 'text' => 'PEMBIMBING', 'align' => 'C'],
                    ['width' => $wPeng, 'text' => 'PENGUJI', 'align' => 'C'],
                ], 4.5, 9.0);
                $pdf->SetFont('Times', '', 8.5);
            }

            $pdf->SetX(15);

            if ($isOpening) {
                $timeStr = (string) ($row['time'] ?? '07.30 – 08.00');
                $noteStr = (string) ($row['note'] ?? 'Persiapan dan Pembukaan Seminar');

                $this->renderTableRow($pdf, [
                    ['width' => $wNo, 'text' => '#', 'align' => 'C'],
                    ['width' => $wWaktu, 'text' => $timeStr, 'align' => 'C'],
                    ['width' => $wNpm + $wNama + $wJudul + $wPemb + $wPeng, 'text' => '  '.$noteStr, 'align' => 'L'],
                ], 4.2, 8.5);
            } else {
                $noStr = (string) $rowNum;
                $timeStr = (string) ($row['time'] ?? '-');
                $npmStr = (string) ($row['npm'] ?? '-');
                $namaStr = (string) ($row['name'] ?? '-');
                $judulStr = (string) ($row['thesis_title'] ?? ($row['title'] ?? '-'));

                // Format Pembimbing (1. ... 2. ...)
                $pemb1 = trim((string) ($row['advisor_1'] ?? ''));
                $pemb2 = trim((string) ($row['advisor_2'] ?? ''));
                $pembArr = [];
                if ($pemb1 !== '') {
                    $pembArr[] = '1. '.$pemb1;
                }
                if ($pemb2 !== '') {
                    $pembArr[] = '2. '.$pemb2;
                }
                $pembStr = implode("\n", $pembArr);

                // Format Penguji (1. ... 2. ...)
                $peng1 = trim((string) ($row['examiner_1'] ?? ''));
                $peng2 = trim((string) ($row['examiner_2'] ?? ''));
                $pengArr = [];
                if ($peng1 !== '') {
                    $pengArr[] = '1. '.$peng1;
                }
                if ($peng2 !== '') {
                    $pengArr[] = '2. '.$peng2;
                }
                $pengStr = implode("\n", $pengArr);

                $this->renderTableRow($pdf, [
                    ['width' => $wNo, 'text' => $noStr, 'align' => 'C'],
                    ['width' => $wWaktu, 'text' => $timeStr, 'align' => 'C'],
                    ['width' => $wNpm, 'text' => $npmStr, 'align' => 'C'],
                    ['width' => $wNama, 'text' => $namaStr, 'align' => 'C'],
                    ['width' => $wJudul, 'text' => $judulStr, 'align' => 'L'],
                    ['width' => $wPemb, 'text' => $pembStr, 'align' => 'L'],
                    ['width' => $wPeng, 'text' => $pengStr, 'align' => 'L'],
                ], 4.2, 8.2);

                $rowNum++;
            }
        }
    }

    /**
     * Render Lampiran Matriks SK Pelaksanaan Pengujian Sidang Tugas Akhir.
     * Header atas tabel: Hari / Tanggal & RUANGAN
     * Kolom: NO, WAKTU, NPM, NAMA MAHASISWA, JUDUL SKRIPSI, PEMBIMBING, PENGUJI
     */
    protected function renderSidangTaAttachment(FPDF $pdf, LetterRequest $letter): void
    {
        $extra = $letter->extra_data ?? [];
        $semType = ! empty($extra['semester_type']) ? mb_strtoupper($extra['semester_type'], 'UTF-8') : 'SEMESTER GANJIL';
        $ta = ! empty($letter->academic_year) ? $letter->academic_year : '2026/2027';
        $wave = ! empty($extra['wave_info']) ? mb_strtoupper($extra['wave_info'], 'UTF-8') : 'GELOMBANG 1';
        $examDate = ! empty($extra['exam_schedule_date']) ? $extra['exam_schedule_date'] : 'Kamis, 15 Oktober 2026';
        $room = ! empty($extra['exam_room']) ? $extra['exam_room'] : 'RUANG 8';

        $pdf->Ln(2);

        $pdf->SetFont('Times', 'B', 12);
        $pdf->Cell(267, 5, 'JADWAL SIDANG SKRIPSI', 0, 1, 'C');
        $pdf->SetFont('Times', 'B', 9.5);
        $pdf->Cell(267, 4.5, "{$semType} {$wave} PADA TAHUN AKADEMIK {$ta}", 0, 1, 'C');
        $pdf->Ln(3.5);

        // Printable width landscape: 297 - 30 = 267mm
        $wNo = 10;
        $wWaktu = 24;
        $wNpm = 24;
        $wNama = 44;
        $wJudul = 65;
        $wPemb = 50;
        $wPeng = 50; // 10 + 24 + 24 + 44 + 65 + 50 + 50 = 267mm

        // Subheader Baris Atas: Hari/Tanggal & Ruangan
        $pdf->SetFont('Times', 'B', 9.0);
        $pdf->SetX(15);
        $pdf->Cell($wNo + $wWaktu + $wNpm + $wNama + $wJudul + $wPemb, 5, '  Hari / Tanggal : '.$examDate, 1, 0, 'L');
        $pdf->Cell($wPeng, 5, 'RUANGAN: '.$room.'  ', 1, 1, 'R');

        // Header Kolom Tabel
        $pdf->SetX(15);
        $this->renderTableRow($pdf, [
            ['width' => $wNo, 'text' => 'NO', 'align' => 'C'],
            ['width' => $wWaktu, 'text' => 'WAKTU', 'align' => 'C'],
            ['width' => $wNpm, 'text' => 'NPM', 'align' => 'C'],
            ['width' => $wNama, 'text' => 'NAMA MAHASISWA', 'align' => 'C'],
            ['width' => $wJudul, 'text' => 'JUDUL SKRIPSI', 'align' => 'C'],
            ['width' => $wPemb, 'text' => 'PEMBIMBING', 'align' => 'C'],
            ['width' => $wPeng, 'text' => 'PENGUJI', 'align' => 'C'],
        ], 4.5, 9.0);

        $sidangRows = $extra['sidang_matrix'] ?? ($extra['seminar_matrix'] ?? []);
        if (empty($sidangRows)) {
            $sidangRows = [
                [
                    'is_header_row' => true,
                    'time' => '07.30 – 08.00',
                    'note' => 'Persiapan dan Pembukaan Sidang',
                ],
                [
                    'is_header_row' => false,
                    'time' => '08.00 – 09.00',
                    'npm' => 'D1A230046',
                    'name' => 'SYIFA MUTAALIA',
                    'thesis_title' => 'PERANCANGAN AI AGENT BERBASIS LARGE LANGUAGE MODEL UNTUK MENINGKATKAN EFISIENSI LAYANAN INFORMASI KEPEMILUAN PADA KPU KABUPATEN SUBANG',
                    'advisor_1' => 'Sofwandi Noor, MT',
                    'advisor_2' => 'Bambang Tjahjo Utomo, MT',
                    'examiner_1' => 'Bagus Ali Akbar, S.Si., M.Kom',
                    'examiner_2' => 'Tazkia Salsabila Ardan, M.Kom',
                ],
            ];
        }

        $pdf->SetFont('Times', '', 8.5);
        $rowNum = 1;

        foreach ($sidangRows as $row) {
            $isOpening = ! empty($row['is_header_row']);

            if ($pdf->GetY() > 165) {
                $pdf->AddPage('L');
                $pdf->SetY(20);
                $pdf->SetX(15);
                $pdf->SetFont('Times', 'B', 9.0);
                $pdf->Cell($wNo + $wWaktu + $wNpm + $wNama + $wJudul + $wPemb, 5, '  Hari / Tanggal : '.$examDate, 1, 0, 'L');
                $pdf->Cell($wPeng, 5, 'RUANGAN: '.$room.'  ', 1, 1, 'R');
                $pdf->SetX(15);
                $this->renderTableRow($pdf, [
                    ['width' => $wNo, 'text' => 'NO', 'align' => 'C'],
                    ['width' => $wWaktu, 'text' => 'WAKTU', 'align' => 'C'],
                    ['width' => $wNpm, 'text' => 'NPM', 'align' => 'C'],
                    ['width' => $wNama, 'text' => 'NAMA MAHASISWA', 'align' => 'C'],
                    ['width' => $wJudul, 'text' => 'JUDUL SKRIPSI', 'align' => 'C'],
                    ['width' => $wPemb, 'text' => 'PEMBIMBING', 'align' => 'C'],
                    ['width' => $wPeng, 'text' => 'PENGUJI', 'align' => 'C'],
                ], 4.5, 9.0);
                $pdf->SetFont('Times', '', 8.5);
            }

            $pdf->SetX(15);

            if ($isOpening) {
                $timeStr = (string) ($row['time'] ?? '07.30 – 08.00');
                $noteStr = (string) ($row['note'] ?? 'Persiapan dan Pembukaan Sidang');

                $this->renderTableRow($pdf, [
                    ['width' => $wNo, 'text' => '#', 'align' => 'C'],
                    ['width' => $wWaktu, 'text' => $timeStr, 'align' => 'C'],
                    ['width' => $wNpm + $wNama + $wJudul + $wPemb + $wPeng, 'text' => '  '.$noteStr, 'align' => 'L'],
                ], 4.2, 8.5);
            } else {
                $noStr = (string) $rowNum;
                $timeStr = (string) ($row['time'] ?? '-');
                $npmStr = (string) ($row['npm'] ?? '-');
                $namaStr = (string) ($row['name'] ?? '-');
                $judulStr = (string) ($row['thesis_title'] ?? ($row['title'] ?? '-'));

                // Format Pembimbing (1. ... 2. ...)
                $pemb1 = trim((string) ($row['advisor_1'] ?? ''));
                $pemb2 = trim((string) ($row['advisor_2'] ?? ''));
                $pembArr = [];
                if ($pemb1 !== '') {
                    $pembArr[] = '1. '.$pemb1;
                }
                if ($pemb2 !== '') {
                    $pembArr[] = '2. '.$pemb2;
                }
                $pembStr = implode("\n", $pembArr);

                // Format Penguji (1. ... 2. ...)
                $peng1 = trim((string) ($row['examiner_1'] ?? ''));
                $peng2 = trim((string) ($row['examiner_2'] ?? ''));
                $pengArr = [];
                if ($peng1 !== '') {
                    $pengArr[] = '1. '.$peng1;
                }
                if ($peng2 !== '') {
                    $pengArr[] = '2. '.$peng2;
                }
                $pengStr = implode("\n", $pengArr);

                $this->renderTableRow($pdf, [
                    ['width' => $wNo, 'text' => $noStr, 'align' => 'C'],
                    ['width' => $wWaktu, 'text' => $timeStr, 'align' => 'C'],
                    ['width' => $wNpm, 'text' => $npmStr, 'align' => 'C'],
                    ['width' => $wNama, 'text' => $namaStr, 'align' => 'C'],
                    ['width' => $wJudul, 'text' => $judulStr, 'align' => 'L'],
                    ['width' => $wPemb, 'text' => $pembStr, 'align' => 'L'],
                    ['width' => $wPeng, 'text' => $pengStr, 'align' => 'L'],
                ], 4.2, 8.2);

                $rowNum++;
            }
        }
    }

    /**
     * Render Lampiran Matriks SK Pengunduran Diri Mahasiswa.
     * Judul Lampiran: DAFTAR MAHASISWA YANG DINYATAKAN MENGUNDURKAN DIRI
     * PROGRAM SARJANA PRODI SISTEM INFORMASI
     * FAKULTAS ILMU KOMPUTER UNIVERSITAS SUBANG
     * SEMESTER GANJIL TA. 2026/2027
     * Kolom: No, NIM, NAMA, KETERANGAN, IPK
     */
    protected function renderPengunduranDiriAttachment(FPDF $pdf, LetterRequest $letter): void
    {
        $extra = $letter->extra_data ?? [];
        $prodi = ! empty($extra['study_program']) ? mb_strtoupper($extra['study_program'], 'UTF-8') : 'PRODI SISTEM INFORMASI';
        if (! str_starts_with($prodi, 'PROGRAM SARJANA')) {
            $prodiText = 'PROGRAM SARJANA '.$prodi;
        } else {
            $prodiText = $prodi;
        }
        $semType = ! empty($extra['semester_type']) ? mb_strtoupper($extra['semester_type'], 'UTF-8') : 'SEMESTER GANJIL';
        $ta = ! empty($letter->academic_year) ? $letter->academic_year : '2026/2027';

        $pdf->Ln(2);

        $pdf->SetFont('Times', 'B', 12);
        $pdf->Cell(267, 5, 'DAFTAR MAHASISWA YANG DINYATAKAN MENGUNDURKAN DIRI', 0, 1, 'C');
        $pdf->SetFont('Times', 'B', 11);
        $pdf->Cell(267, 5, $prodiText, 0, 1, 'C');
        $pdf->Cell(267, 5, 'FAKULTAS ILMU KOMPUTER UNIVERSITAS SUBANG', 0, 1, 'C');
        $pdf->SetFont('Times', 'B', 10);
        $pdf->Cell(267, 5, "{$semType} TA. {$ta}", 0, 1, 'C');
        $pdf->Ln(4);

        // Printable width landscape: 297 - 30 = 267mm
        // Kolom: No (15), NIM (40), NAMA (82), KETERANGAN (105), IPK (25) = 267mm
        $wNo = 15;
        $wNim = 40;
        $wNama = 82;
        $wKet = 105;
        $wIpk = 25;

        // Header Kolom Tabel
        $pdf->SetFont('Times', 'B', 10);
        $pdf->SetX(15);
        $this->renderTableRow($pdf, [
            ['width' => $wNo, 'text' => 'No', 'align' => 'C'],
            ['width' => $wNim, 'text' => 'NIM', 'align' => 'C'],
            ['width' => $wNama, 'text' => 'NAMA', 'align' => 'C'],
            ['width' => $wKet, 'text' => 'KETERANGAN', 'align' => 'C'],
            ['width' => $wIpk, 'text' => 'IPK', 'align' => 'C'],
        ], 5.0, 8.0);

        $resignationRows = $extra['resignation_matrix'] ?? [];
        if (empty($resignationRows)) {
            $resignationRows = [
                [
                    'npm' => 'D1A190012',
                    'name' => 'ADITYA BUDHI RAHMAT',
                    'reason' => 'Mengajukan Pengunduran Diri',
                    'ipk' => '2.10',
                ],
                [
                    'npm' => 'D1A190025',
                    'name' => 'ALIF HIDAYAT',
                    'reason' => 'Mengajukan Pengunduran Diri',
                    'ipk' => '1.04',
                ],
            ];
        }

        $pdf->SetFont('Times', '', 9.5);
        $rowNum = 1;

        foreach ($resignationRows as $row) {
            if ($pdf->GetY() > 165) {
                $pdf->AddPage('L');
                $pdf->SetY(20);
                $pdf->SetX(15);
                $pdf->SetFont('Times', 'B', 10);
                $this->renderTableRow($pdf, [
                    ['width' => $wNo, 'text' => 'No', 'align' => 'C'],
                    ['width' => $wNim, 'text' => 'NIM', 'align' => 'C'],
                    ['width' => $wNama, 'text' => 'NAMA', 'align' => 'C'],
                    ['width' => $wKet, 'text' => 'KETERANGAN', 'align' => 'C'],
                    ['width' => $wIpk, 'text' => 'IPK', 'align' => 'C'],
                ], 5.0, 8.0);
                $pdf->SetFont('Times', '', 9.5);
            }

            $noStr = (string) $rowNum;
            $nimStr = (string) ($row['npm'] ?? ($row['nim'] ?? '-'));
            $namaStr = (string) ($row['name'] ?? '-');
            $ketStr = (string) ($row['reason'] ?? ($row['keterangan'] ?? 'Mengajukan Pengunduran Diri'));
            $ipkStr = (string) ($row['ipk'] ?? '-');

            $pdf->SetX(15);
            $this->renderTableRow($pdf, [
                ['width' => $wNo, 'text' => $noStr, 'align' => 'C'],
                ['width' => $wNim, 'text' => $nimStr, 'align' => 'C'],
                ['width' => $wNama, 'text' => $namaStr, 'align' => 'L'],
                ['width' => $wKet, 'text' => $ketStr, 'align' => 'L'],
                ['width' => $wIpk, 'text' => $ipkStr, 'align' => 'C'],
            ], 4.5, 7.5);

            $rowNum++;
        }
    }
}
