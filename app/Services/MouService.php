<?php

namespace App\Services;

use App\Models\LetterRequest;
use FPDF;

class MouService
{
    public int $mouSignaturePage = 6;

    public float $mouSignatureY = 140;

    /**
     * Convert an integer number to Indonesian words (e.g. 5 -> lima).
     */
    public function numberToWords(int $number): string
    {
        $words = [
            0 => 'nol',
            1 => 'satu',
            2 => 'dua',
            3 => 'tiga',
            4 => 'empat',
            5 => 'lima',
            6 => 'enam',
            7 => 'tujuh',
            8 => 'delapan',
            9 => 'sembilan',
            10 => 'sepuluh',
            11 => 'sebelas',
            12 => 'dua belas',
            13 => 'tiga belas',
            14 => 'empat belas',
            15 => 'lima belas',
            16 => 'enam belas',
            17 => 'tujuh belas',
            18 => 'delapan belas',
            19 => 'sembilan belas',
            20 => 'dua puluh',
            30 => 'tiga puluh',
            40 => 'empat puluh',
            50 => 'lima puluh',
            60 => 'enam puluh',
            70 => 'tujuh puluh',
            80 => 'delapan puluh',
            90 => 'sembilan puluh',
            100 => 'seratus',
        ];

        if (isset($words[$number])) {
            return $words[$number];
        }

        if ($number < 100) {
            $tens = (int) ($number / 10) * 10;
            $units = $number % 10;

            return $words[$tens].' '.$words[$units];
        }

        if ($number < 200) {
            return 'seratus '.$this->numberToWords($number - 100);
        }

        if ($number < 1000) {
            $hundreds = (int) ($number / 100);
            $rest = $number % 100;

            return $words[$hundreds].' ratus'.($rest ? ' '.$this->numberToWords($rest) : '');
        }

        if ($number < 2000) {
            return 'seribu '.$this->numberToWords($number - 1000);
        }

        if ($number < 10000) {
            $thousands = (int) ($number / 1000);
            $rest = $number % 1000;

            return $this->numberToWords($thousands).' ribu'.($rest ? ' '.$this->numberToWords($rest) : '');
        }

        return (string) $number;
    }

    /**
     * Get Indonesian day name in words.
     */
    public function getDayNameWords(string $dayName): string
    {
        $map = [
            'Sunday' => 'Minggu',
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            'Ahad' => 'Minggu',
            'Minggu' => 'Minggu',
            'Senin' => 'Senin',
            'Selasa' => 'Selasa',
            'Rabu' => 'Rabu',
            'Kamis' => 'Kamis',
            'Jumat' => 'Jumat',
            'Sabtu' => 'Sabtu',
        ];

        return $map[$dayName] ?? $dayName;
    }

    /**
     * Get Indonesian month name.
     */
    public function getMonthName(int $month): string
    {
        $map = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        return $map[$month] ?? '';
    }

    /**
     * Render the official 2-column PARAF box at bottom-right of a page.
     */
    public function renderParafTable(FPDF $pdf, float $x = 133, float $y = 260): void
    {
        $origX = $pdf->GetX();
        $origY = $pdf->GetY();

        $pdf->SetXY($x, $y);
        $pdf->SetFont('Times', 'B', 8);

        // Header PARAF (Lebar total 52 mm, masing-masing kolom 26 mm)
        $pdf->Cell(52, 4.5, 'PARAF', 1, 1, 'C');
        $pdf->SetX($x);

        // Sub-headers dengan font ukuran 6.5 agar teks rapi di dalam kotak 26 mm
        $pdf->SetFont('Times', 'B', 6.5);
        $pdf->Cell(26, 4.5, 'PIHAK PERTAMA', 1, 0, 'C');
        $pdf->Cell(26, 4.5, 'PIHAK KEDUA', 1, 1, 'C');
        $pdf->SetX($x);

        // Empty row for paraf/initials
        $pdf->Cell(26, 11, '', 1, 0, 'C');
        $pdf->Cell(26, 11, '', 1, 1, 'C');

        $pdf->SetXY($origX, $origY);
    }

    /**
     * Render the 6-page official MOU document.
     */
    public function renderMouPdf(LetterRequest $letter, bool $isWatermarked = false): string
    {
        $extra = $letter->extra_data ?? [];

        // Partner data (Pihak 1)
        $partnerName = ! empty($extra['partner_name']) ? $extra['partner_name'] : '(Institusi/Perusahaan)';
        $partnerAddress = ! empty($extra['partner_address']) ? $extra['partner_address'] : '(Alamat Institusi/Perusahaan)';
        $partnerBusinessField = ! empty($extra['partner_business_field']) ? $extra['partner_business_field'] : '(Penjelasan Bidang)';
        $partnerOfficialName = ! empty($extra['partner_official_name']) ? $extra['partner_official_name'] : '(NAMA PEJABAT PIHAK KE 1)';
        $partnerOfficialPosition = ! empty($extra['partner_official_position']) ? $extra['partner_official_position'] : '(Jabatan di Institusi/Perusahaan)';
        $partnerPhone = ! empty($extra['partner_phone']) ? $extra['partner_phone'] : '(Telepon)';
        $partnerEmail = ! empty($extra['partner_email']) ? $extra['partner_email'] : '(e-mail)';

        // Faculty data (Pihak 2)
        $facultyOfficialName = ! empty($extra['faculty_official_name']) ? $extra['faculty_official_name'] : ($letter->signer_name ?: 'Dr. Tepi Peirisal, S.Sos, M.Si');
        $facultyOfficialPosition = ! empty($extra['faculty_official_position']) ? $extra['faculty_official_position'] : ($letter->signer_title ?: 'Dekan');
        $facultyNik = ! empty($extra['faculty_nik']) ? $extra['faculty_nik'] : '0410420142';

        // Title and Numbers
        $mouTitle = ! empty($extra['mou_title']) ? mb_strtoupper($extra['mou_title'], 'UTF-8') : 'PENYELENGGARAAN PROGRAM MAGANG BERSERTIFIKAT - MBKM';
        $partnerMouNumber = ! empty($extra['partner_mou_number']) ? $extra['partner_mou_number'] : '-';

        // Penomoran otomatis MoU Fasilkom jika belum diset atau kosong
        if (! empty($extra['faculty_mou_number']) && trim($extra['faculty_mou_number']) !== '') {
            $facultyMouNumber = $extra['faculty_mou_number'];
        } elseif (! empty($letter->letter_number)) {
            $facultyMouNumber = $letter->letter_number;
        } else {
            // Placeholder nomor otomatis sebelum surat diterbitkan resmi
            $signingDate = ! empty($extra['signing_date']) ? $extra['signing_date'] : now()->format('Y-m-d');
            $monthNum = (int) date('n', strtotime($signingDate));
            $year = date('Y', strtotime($signingDate));
            $romans = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
            $romanMonth = $romans[$monthNum] ?? 'XI';
            $facultyMouNumber = ".../MOU/FIK-US/{$romanMonth}/{$year}";
        }

        // Dates and Locations
        $signingCity = ! empty($extra['signing_city']) ? $extra['signing_city'] : 'Subang';
        $dayWords = ! empty($extra['signing_day_words']) ? $extra['signing_day_words'] : '(Hari dengan bentuk terbilang)';
        $dateWords = ! empty($extra['signing_date_words']) ? $extra['signing_date_words'] : '(Tanggal dengan bentuk terbilang)';
        $monthWords = ! empty($extra['signing_month_words']) ? $extra['signing_month_words'] : '(Bulan dengan bentuk terbilang)';
        $yearWords = ! empty($extra['signing_year_words']) ? $extra['signing_year_words'] : '(Tahun dengan bentuk terbilang)';
        $dateFormatted = ! empty($extra['signing_date_formatted']) ? $extra['signing_date_formatted'] : '(dd-mm-yyyy)';

        // Durations
        $programDurationMonths = ! empty($extra['program_duration_months']) ? (int) $extra['program_duration_months'] : 5;
        $agreementDurationYears = ! empty($extra['agreement_duration_years']) ? (int) $extra['agreement_duration_years'] : 2;
        $noticeDays = ! empty($extra['notice_days']) ? (int) $extra['notice_days'] : 30;
        $obligationMonths = ! empty($extra['obligation_months']) ? (int) $extra['obligation_months'] : 3;
        $confidentialityYears = ! empty($extra['confidentiality_duration_years']) ? (int) $extra['confidentiality_duration_years'] : 5;

        // FPDF Initial Setup: Portrait A4, margins 25mm left/right, 20mm top/bottom
        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->SetMargins(25, 20, 25);
        $pdf->SetAutoPageBreak(false);

        $lineH = 4.8;
        $wBody = 160;

        // ==========================================
        // HALAMAN 1: JUDUL, PARA PIHAK & KONSIDERANS
        // ==========================================
        $pdf->AddPage('P');

        if ($isWatermarked) {
            $pdf->SetFont('Helvetica', 'B', 30);
            $pdf->SetTextColor(242, 245, 250);
            $pdf->SetXY(25, 140);
            $pdf->Cell($wBody, 18, 'PRATINJAU LANGSUNG (LIVE)', 0, 0, 'C');
            $pdf->SetTextColor(0, 0, 0);
        }

        // Logo Universitas Subang
        $logoPath = public_path('images/logos/universitas-subang.png');
        if (file_exists($logoPath)) {
            $pdf->Image($logoPath, 92.5, 16, 25);
            $pdf->SetY(44);
        } else {
            $pdf->SetY(25);
        }

        // Header Judul
        $pdf->SetFont('Times', 'B', 11);
        $pdf->Cell($wBody, 5, 'NOTA KESEPAHAMAN', 0, 1, 'C');
        $pdf->Cell($wBody, 5, 'ANTARA', 0, 1, 'C');
        $pdf->Cell($wBody, 5, mb_strtoupper($partnerName, 'UTF-8'), 0, 1, 'C');
        $pdf->Cell($wBody, 5, 'DENGAN', 0, 1, 'C');
        $pdf->Cell($wBody, 5, 'FAKULTAS ILMU KOMPUTER UNIVERSITAS SUBANG', 0, 1, 'C');
        $pdf->Cell($wBody, 5, 'TENTANG', 0, 1, 'C');
        $pdf->MultiCell($wBody, 5, $mouTitle, 0, 'C');
        $pdf->Ln(3);

        // Nomor MOU
        $pdf->SetFont('Times', '', 10);
        $numStartX = 65;
        $pdf->SetX($numStartX);
        $pdf->Cell(20, 4.5, 'Nomor', 0, 0, 'L');
        $pdf->Cell(5, 4.5, ':', 0, 0, 'C');
        $pdf->Cell(70, 4.5, $partnerMouNumber, 0, 1, 'L');

        $pdf->SetX($numStartX);
        $pdf->Cell(20, 4.5, 'Nomor', 0, 0, 'L');
        $pdf->Cell(5, 4.5, ':', 0, 0, 'C');
        $pdf->Cell(70, 4.5, $facultyMouNumber, 0, 1, 'L');
        $pdf->Ln(4);

        // Paragraf Pembuka Tanggal
        $pdf->SetFont('Times', '', 10);
        $introText = "Pada hari ini, {$dayWords} tanggal {$dateWords} bulan {$monthWords} tahun {$yearWords} ({$dateFormatted}) bertempat di {$signingCity}, kami yang bertandatangan di bawah ini :";
        $pdf->MultiCell($wBody, $lineH, $introText, 0, 'J');
        $pdf->Ln(2);

        // Pihak Kesatu
        $yI = $pdf->GetY();
        $pdf->SetFont('Times', 'B', 10);
        $pdf->SetXY(25, $yI);
        $pdf->Cell(8, $lineH, 'I.', 0, 0, 'L');
        $pdf->Cell(55, $lineH, mb_strtoupper($partnerOfficialName, 'UTF-8'), 0, 0, 'L');
        $pdf->Cell(4, $lineH, ':', 0, 0, 'C');

        $pdf->SetFont('Times', '', 10);
        $textPihak1 = "{$partnerOfficialPosition} yang berkedudukan di {$partnerAddress}, selanjutnya disebut PIHAK KESATU.";
        $pdf->SetXY(92, $yI);
        $pdf->MultiCell(93, $lineH, $textPihak1, 0, 'J');
        $pdf->Ln(2);

        // Pihak Kedua
        $yII = $pdf->GetY();
        $pdf->SetFont('Times', 'B', 10);
        $pdf->SetXY(25, $yII);
        $pdf->Cell(8, $lineH, 'II.', 0, 0, 'L');
        $pdf->Cell(55, $lineH, mb_strtoupper($facultyOfficialName, 'UTF-8'), 0, 0, 'L');
        $pdf->Cell(4, $lineH, ':', 0, 0, 'C');

        $pdf->SetFont('Times', '', 10);
        $textPihak2 = "{$facultyOfficialPosition} Fakultas Ilmu Komputer Universitas Subang, berkedudukan di Jalan R.A Kartini No. 3 Subang dalam hal ini bertindak untuk dan atas nama Fakultas Ilmu Komputer Universitas Subang, untuk selanjutnya disebut PIHAK KEDUA;";
        $pdf->SetXY(92, $yII);
        $pdf->MultiCell(93, $lineH, $textPihak2, 0, 'J');
        $pdf->Ln(3);

        // Paragraf Menerangkan
        $pdf->SetFont('Times', '', 10);
        $pdf->MultiCell($wBody, $lineH, 'Selanjutnya PIHAK PERTAMA dan PIHAK KEDUA, secara sendiri-sendiri disebut sebagai PIHAK dan secara bersama-sama disebut PARA PIHAK terlebih dahulu menerangkan hal-hal sebagai berikut:', 0, 'J');
        $pdf->Ln(2);

        // Poin 1
        $this->renderNumberedListItem($pdf, '1.', "Bahwa PIHAK PERTAMA adalah {$partnerName} yang bergerak dalam bidang {$partnerBusinessField};", $lineH, $wBody);

        // Poin 2
        $this->renderNumberedListItem($pdf, '2.', 'Bahwa PIHAK KEDUA adalah Fakultas yang merupakan bagian dari Perguruan Tinggi Universitas Subang yang mempunyai tugas dan fungsi dalam penyelenggaraan Pendidikan, Penelitian, dan Pengabdian kepada Masyarakat;', $lineH, $wBody);

        // Poin 3 (Awal)
        $this->renderNumberedListItem($pdf, '3.', 'Bahwa berdasarkan Nota Kesepakatan Bersama antara Pemerintah Daerah Kabupaten Subang dengan Universitas Subang Nomor : Hm.03/KB/III/2020, dan', $lineH, $wBody);

        // Paraf Box Halaman 1
        $this->renderParafTable($pdf);

        // ==========================================
        // HALAMAN 2: LANJUTAN KONSIDERANS, PASAL 1 & 2
        // ==========================================
        $pdf->AddPage('P');

        if ($isWatermarked) {
            $pdf->SetFont('Helvetica', 'B', 30);
            $pdf->SetTextColor(242, 245, 250);
            $pdf->SetXY(25, 140);
            $pdf->Cell($wBody, 18, 'PRATINJAU LANGSUNG (LIVE)', 0, 0, 'C');
            $pdf->SetTextColor(0, 0, 0);
        }

        $pdf->SetY(25);
        $pdf->SetFont('Times', '', 10);

        // Lanjutan Poin 3
        $this->renderNumberedListItem($pdf, '', 'Nomor : 126/US/III/2020 tanggal 12 Maret 2020 tentang Kerjasama di bidang Pendidikan, Penelitian dan Pengabdian pada Masyarakat, dan;', $lineH, $wBody);

        // Poin 4
        $this->renderNumberedListItem($pdf, '4.', 'Bahwa PARA PIHAK berencana untuk mengadakan Perjanjian kerja sama secara kelembagaan tanpa mengganggu tugas pokok masing-masing PIHAK, untuk melaksanakan program magang bersertifikat – MBKM.', $lineH, $wBody);
        $pdf->Ln(2);

        // Paragraf Kesepakatan
        $pdf->MultiCell($wBody, $lineH, 'Berdasarkan hal-hal tersebut di atas, maka PARA PIHAK sepakat untuk membuat Perjanjian Kerja Sama tentang pelaksanaan program magang bersertifikat - MBKM (selanjutnya disebut "Perjanjian Kerja sama") dengan syarat dan ketentuan sebagai berikut:', 0, 'J');
        $pdf->Ln(4);

        // PASAL 1: DEFINISI
        $this->renderArticleHeader($pdf, 'PASAL 1', 'DEFINISI', $wBody);

        $pasal1 = ! empty($extra['pasal_1_definitions']) && is_array($extra['pasal_1_definitions'])
            ? array_values(array_filter($extra['pasal_1_definitions'], fn ($v) => ! is_null($v) && trim((string) $v) !== ''))
            : [];

        if (empty($pasal1)) {
            $pasal1 = [
                'Merdeka Belajar Kampus Merdeka yang biasa disingkat MBKM adalah Program yang dicanangkan oleh Menteri Pendidikan dan Kebudayaan yang bertujuan mendorong mahasiswa untuk menguasai berbagai keilmuan untuk bekal memasuki dunia kerja.',
                'Pendidikan adalah usaha sadar dan terencana untuk mewujudkan suasana belajar dan proses pembelajaran agar peserta didik secara aktif mengembangkan potensi dirinya untuk memiliki kekuatan spiritual keagamaan, pengendalian diri, kepribadian, kecerdasan akhlak mulia serta keterampilan yang diperlukan dirinya, masyarakat, bangsa dan negara;',
                'Penelitian adalah kegiatan yang dilakukan menurut kaidah dan metode ilmiah secara sistematis untuk memperoleh informasi data dan keterangan yang berkaitan dengan pemahaman dan/atau pengujian suatu cabang pengetahuan dan teknologi',
                'Kompetensi adalah kemampuan kerja setiap individu yang mencakup aspek pengetahuan, keterampilan, dan sikap kerja yang sesuai dengan standardisasi yang diharapkan',
            ];
        }

        foreach ($pasal1 as $idx => $defText) {
            $this->renderNumberedListItem($pdf, ($idx + 1).'.', (string) $defText, $lineH, $wBody);
        }
        $pdf->Ln(2);

        // PASAL 2: MAKSUD DAN TUJUAN
        $this->renderArticleHeader($pdf, 'PASAL 2', 'MAKSUD DAN TUJUAN', $wBody);

        $pasal2 = ! empty($extra['pasal_2_intent']) && is_array($extra['pasal_2_intent'])
            ? array_values(array_filter($extra['pasal_2_intent'], fn ($v) => ! is_null($v) && trim((string) $v) !== ''))
            : [];

        if (empty($pasal2)) {
            $pasal2 = [
                'Maksud Perjanjian Kerja Sama ini adalah untuk mendukung langkah PARA PIHAK dalam melakukan kerja sama di bidang Pendidikan, Penelitian, Pengabdian Kepada Masyarakat dan bidang Lainnya dengan mendayagunakan potensi, keahlian dan sumber daya yang dimiliki PARA PIHAK.',
                'Perjanjian Kerja Sama ini bertujuan untuk mengadakan kerja sama awal dalam program magang bersertifikat dengan memanfaatkan sumber daya yang dimiliki PARA PIHAK demi kemajuan bersama',
            ];
        }

        foreach ($pasal2 as $idx => $intentText) {
            $this->renderNumberedListItem($pdf, '('.($idx + 1).')', (string) $intentText, $lineH, $wBody, 9);
        }
        $pdf->Ln(2);

        // PASAL 3: RUANG LINGKUP
        $this->renderArticleHeader($pdf, 'PASAL 3', 'RUANG LINGKUP', $wBody);

        // Paraf Box Halaman 2
        $this->renderParafTable($pdf);

        // ==========================================
        // HALAMAN 3: PASAL 3, PASAL 4, PASAL 5
        // ==========================================
        $pdf->AddPage('P');

        if ($isWatermarked) {
            $pdf->SetFont('Helvetica', 'B', 30);
            $pdf->SetTextColor(242, 245, 250);
            $pdf->SetXY(25, 140);
            $pdf->Cell($wBody, 18, 'PRATINJAU LANGSUNG (LIVE)', 0, 0, 'C');
            $pdf->SetTextColor(0, 0, 0);
        }

        $pdf->SetY(25);
        $pdf->SetFont('Times', '', 10);

        // Lanjutan Pasal 3
        $pdf->MultiCell($wBody, $lineH, 'Ruang lingkup Perjanjian Kerja Sama ini adalah :', 0, 'J');

        $pasal3 = ! empty($extra['pasal_3_scope']) && is_array($extra['pasal_3_scope'])
            ? $extra['pasal_3_scope']
            : [
                'Penyelenggaraan Program Magang Bersertifikat;',
                'Peningkatan dan Pengembangan Kompetensi Sumber Daya Manusia; dan',
                'Kegiatan lain yang disepakati oleh PARA PIHAK.',
            ];

        foreach ($pasal3 as $idx => $scopeText) {
            $this->renderNumberedListItem($pdf, '('.($idx + 1).')', $scopeText, $lineH, $wBody, 9);
        }
        $pdf->Ln(3);

        // PASAL 4: PELAKSANAAN
        $this->renderArticleHeader($pdf, 'PASAL 4', 'PELAKSANAAN', $wBody);

        $pasal4 = ! empty($extra['pasal_4_implementation']) && is_array($extra['pasal_4_implementation'])
            ? $extra['pasal_4_implementation']
            : [
                'PIHAK PERTAMA menginformasikan kepada PIHAK KEDUA terkait peserta yang diterima Program Magang;',
                'Calon Peserta Program yang telah lulus seleksi akan mengikuti kegiatan Program sesuai ketentuan yang telah ditetapkan oleh PIHAK PERTAMA',
                'PARA PIHAK bekerja sama untuk melaksanakan Program Magang, bagi Calon Peserta yang merupakan Mahasiswa dari PIHAK KEDUA.',
            ];

        foreach ($pasal4 as $idx => $implText) {
            $this->renderNumberedListItem($pdf, '('.($idx + 1).')', $implText, $lineH, $wBody, 9);
        }
        $pdf->Ln(3);

        // PASAL 5: HAK DAN KEWAJIBAN PARA PIHAK
        $this->renderArticleHeader($pdf, 'PASAL 5', 'HAK DAN KEWAJIBAN PARA PIHAK', $wBody);

        // (1) Hak PIHAK PERTAMA
        $pdf->SetFont('Times', '', 10);
        $pdf->SetX(25);
        $pdf->Cell(9, $lineH, '(1)', 0, 0, 'L');
        $pdf->Cell(60, $lineH, 'Hak PIHAK PERTAMA:', 0, 1, 'L');

        $p5RightsP1 = ! empty($extra['pasal_5_rights_p1']) && is_array($extra['pasal_5_rights_p1'])
            ? $extra['pasal_5_rights_p1']
            : [
                'Menentukan jumlah Calon Peserta Program;',
                'Mengatur penempatan Peserta Program;',
                'Mendapatkan Peserta Program yang berasal dari Mahasiswa PIHAK KEDUA;',
                'Menetapkan peraturan yang harus ditaati oleh peserta selama Program Magang Bersertifikat berlangsung',
            ];
        foreach ($p5RightsP1 as $idx => $item) {
            $this->renderSubListItem($pdf, chr(97 + $idx).'.', $item, $lineH, $wBody);
        }
        $pdf->Ln(1);

        // (2) Kewajiban PIHAK PERTAMA
        $pdf->SetX(25);
        $pdf->Cell(9, $lineH, '(2)', 0, 0, 'L');
        $pdf->Cell(60, $lineH, 'Kewajiban PIHAK PERTAMA:', 0, 1, 'L');

        $p5ObligationsP1 = ! empty($extra['pasal_5_obligations_p1']) && is_array($extra['pasal_5_obligations_p1'])
            ? $extra['pasal_5_obligations_p1']
            : [
                'Melakukan wawancara dalam pelaksanaan seleksi di lingkungan PIHAK PERTAMA (apabila diperlukan);',
                'Menyediakan fasilitas beserta sarana dan prasana pelaksanaan Program (terbatas pada lokasi dimana pelaksanaan Program tersebut dilaksanakan);',
                'Menyiapkan mentor /pembimbing lapangan yang bertugas mendampingi dan menilai kinerja mahasiswa selama magang bersama dosen pembimbing PIHAK KEDUA;',
                'Memberikan hak dan jaminan sesuai peraturan perundangan.',
            ];
        foreach ($p5ObligationsP1 as $idx => $item) {
            $this->renderSubListItem($pdf, chr(97 + $idx).'.', $item, $lineH, $wBody);
        }
        $pdf->Ln(1);

        // (3) Hak PIHAK KEDUA
        $pdf->SetX(25);
        $pdf->Cell(9, $lineH, '(3)', 0, 0, 'L');
        $pdf->Cell(60, $lineH, 'Hak PIHAK KEDUA:', 0, 1, 'L');

        $p5RightsP2 = ! empty($extra['pasal_5_rights_p2']) && is_array($extra['pasal_5_rights_p2'])
            ? $extra['pasal_5_rights_p2']
            : [
                'Menerima Sertifikat Magang dan nilai untuk Mahasiswa PIHAK KEDUA yang mengikuti Program Magang.',
            ];
        foreach ($p5RightsP2 as $idx => $item) {
            $this->renderSubListItem($pdf, chr(97 + $idx).'.', $item, $lineH, $wBody);
        }
        $pdf->Ln(1);

        // (4) Kewajiban PIHAK KEDUA
        $pdf->SetX(25);
        $pdf->Cell(9, $lineH, '(4)', 0, 0, 'L');
        $pdf->Cell(60, $lineH, 'Kewajiban PIHAK KEDUA:', 0, 1, 'L');

        $p5ObligationsP2 = ! empty($extra['pasal_5_obligations_p2']) && is_array($extra['pasal_5_obligations_p2'])
            ? $extra['pasal_5_obligations_p2']
            : [
                'Melakukan seleksi Calon Peserta Program;',
                'Menyiapkan Dosen Pembimbing untuk Mahasiswa Magang;',
            ];
        foreach ($p5ObligationsP2 as $idx => $item) {
            $this->renderSubListItem($pdf, chr(97 + $idx).'.', $item, $lineH, $wBody);
        }
        $pdf->Ln(3);

        // PASAL 6: JANGKA WAKTU DAN BERAKHIRNYA PROGRAM
        $this->renderArticleHeader($pdf, 'PASAL 6', 'JANGKA WAKTU DAN BERAKHIRNYA PROGRAM', $wBody);

        // Paraf Box Halaman 3
        $this->renderParafTable($pdf);

        // ==========================================
        // HALAMAN 4: PASAL 6, PASAL 7, PASAL 8
        // ==========================================
        $pdf->AddPage('P');

        if ($isWatermarked) {
            $pdf->SetFont('Helvetica', 'B', 30);
            $pdf->SetTextColor(242, 245, 250);
            $pdf->SetXY(25, 140);
            $pdf->Cell($wBody, 18, 'PRATINJAU LANGSUNG (LIVE)', 0, 0, 'C');
            $pdf->SetTextColor(0, 0, 0);
        }

        $pdf->SetY(25);
        $pdf->SetFont('Times', '', 10);

        // Lanjutan Pasal 6
        if (! empty($extra['pasal_6_terms']) && is_array($extra['pasal_6_terms'])) {
            $progMonthsWords = $this->numberToWords($programDurationMonths);
            foreach ($extra['pasal_6_terms'] as $idx => $term) {
                $termText = (string) $term;
                // Jika ini ayat pertama, pastikan angka durasi sinkron dengan parameter angka program_duration_months jika teks masih mengikuti pola standar
                if ($idx === 0 && preg_match('/Jangka waktu penyelenggaraan Program adalah \d+/i', $termText)) {
                    $termText = preg_replace(
                        '/Jangka waktu penyelenggaraan Program adalah \d+ \([^)]+\) bulan/i',
                        "Jangka waktu penyelenggaraan Program adalah {$programDurationMonths} ({$progMonthsWords}) bulan",
                        $termText
                    );
                }
                $this->renderNumberedListItem($pdf, '('.($idx + 1).')', $termText, $lineH, $wBody, 9);
            }
        } else {
            $progMonthsWords = $this->numberToWords($programDurationMonths);
            $this->renderNumberedListItem($pdf, '(1)', "Jangka waktu penyelenggaraan Program adalah {$programDurationMonths} ({$progMonthsWords}) bulan terhitung mulai Calon Peserta Program telah mengikuti Program pertama kali dilaksanakan di lingkungan PIHAK PERTAMA.", $lineH, $wBody, 9);
            $this->renderNumberedListItem($pdf, '(2)', 'Jangka waktu penyelenggaraan Program sebagaimana dimaksud pada ayat (1) dapat diperpanjang berdasarkan evaluasi dan kesepakatan PARA PIHAK.', $lineH, $wBody, 9);
            $this->renderNumberedListItem($pdf, '(3)', 'Peserta Program dinyatakan sudah mengakhiri Program apabila PIHAK PERTAMA menyatakan Peserta Program telah mengikuti seluruh rangkaian Program dan dinyatakan berkompeten atau tidak berkompeten.', $lineH, $wBody, 9);
        }
        $pdf->Ln(4);

        // PASAL 7: JANGKA WAKTU DAN PENGAKHIRAN PERJANJIAN
        $this->renderArticleHeader($pdf, 'PASAL 7', 'JANGKA WAKTU DAN PENGAKHIRAN PERJANJIAN', $wBody);

        $agreeYearsWords = $this->numberToWords($agreementDurationYears);
        $pasal7_1 = ! empty($extra['pasal_7_term1'])
            ? (string) $extra['pasal_7_term1']
            : "Perjanjian ini berlaku selama {$agreementDurationYears} ({$agreeYearsWords}) tahun terhitung sejak tanggal ditandatanganinya perjanjian ini dan dapat diubah, diperpanjang atau diakhiri berdasarkan kesepakatan tertulis dari PARA PIHAK.";
        
        // Pastikan sinkron dengan input agreement_duration_years jika masih teks default
        if (preg_match('/Perjanjian ini berlaku selama \d+/i', $pasal7_1)) {
            $pasal7_1 = preg_replace(
                '/Perjanjian ini berlaku selama \d+ \([^)]+\) tahun/i',
                "Perjanjian ini berlaku selama {$agreementDurationYears} ({$agreeYearsWords}) tahun",
                $pasal7_1
            );
        }
        $this->renderNumberedListItem($pdf, '(1)', $pasal7_1, $lineH, $wBody, 9);

        // (2) Pengakhiran
        $pdf->SetX(25);
        $pdf->Cell(9, $lineH, '(2)', 0, 0, 'L');
        $pdf->Cell(140, $lineH, 'Perjanjian Kerja Sama ini dapat diakhiri yang disebabkan oleh:', 0, 1, 'L');

        $p7Term2List = ! empty($extra['pasal_7_termination_reasons']) && is_array($extra['pasal_7_termination_reasons'])
            ? $extra['pasal_7_termination_reasons']
            : [
                'Berakhirnya jangka waktu Perjanjian;',
                'Kesepakatan PARA PIHAK; dan/atau',
                'Terdapat ketentuan perundangan-undangan dan/atau kebijakan yang tidak memungkinkan dilaksanakannya kerja sama menurut Perjanjian ini, tanpa terikat dengan ketentuan waktu sebagaimana dimaksud pada ayat (1).',
            ];
        foreach ($p7Term2List as $idx => $item) {
            $this->renderSubListItem($pdf, chr(97 + $idx).'.', $item, $lineH, $wBody);
        }
        $pdf->Ln(1);

        $noticeWords = $this->numberToWords($noticeDays);
        $pasal7_3 = ! empty($extra['pasal_7_term3'])
            ? (string) $extra['pasal_7_term3']
            : "Apabila salah satu PIHAK berkeinginan untuk mengakhiri Perjanjian ini sebelum jangka waktu sebagaimana dimaksud pada ayat (1), maka PIHAK yang memiliki keinginan tersebut memberitahu selambat-lambatnya {$noticeDays} ({$noticeWords}) hari kalender sebelum Perjanjian Kerja Sama ini diakhiri.";
        if (preg_match('/memberitahu selambat-lambatnya \d+/i', $pasal7_3)) {
            $pasal7_3 = preg_replace(
                '/memberitahu selambat-lambatnya \d+ \([^)]+\) hari/i',
                "memberitahu selambat-lambatnya {$noticeDays} ({$noticeWords}) hari",
                $pasal7_3
            );
        }
        $this->renderNumberedListItem($pdf, '(3)', $pasal7_3, $lineH, $wBody, 9);

        $oblgWords = $this->numberToWords($obligationMonths);
        $pasal7_4 = ! empty($extra['pasal_7_term4'])
            ? (string) $extra['pasal_7_term4']
            : "Apabila pada saat Perjanjian ini berakhir atau diputuskan terdapat kewajiban yang belum dapat diselesaikan, maka ketentuan dalam Perjanjian ini tetap berlaku sampai diselesaikannya kewajiban tersebut, paling lambat {$obligationMonths} ({$oblgWords}) bulan sejak Perjanjian ini berakhir.";
        if (preg_match('/paling lambat \d+/i', $pasal7_4)) {
            $pasal7_4 = preg_replace(
                '/paling lambat \d+ \([^)]+\) bulan/i',
                "paling lambat {$obligationMonths} ({$oblgWords}) bulan",
                $pasal7_4
            );
        }
        $this->renderNumberedListItem($pdf, '(4)', $pasal7_4, $lineH, $wBody, 9);
        $pdf->Ln(4);

        // PASAL 8: KERAHASIAAN
        $this->renderArticleHeader($pdf, 'PASAL 8', 'KERAHASIAAN', $wBody);

        $pasal8_1 = ! empty($extra['pasal_8_term1'])
            ? $extra['pasal_8_term1']
            : 'PARA PIHAK sepakat bahwa informasi tertentu diklasifikasikan sebagai rahasia (“Informasi Rahasia”).';
        $this->renderNumberedListItem($pdf, '(1)', $pasal8_1, $lineH, $wBody, 9);

        $pasal8_2_intro = ! empty($extra['pasal_8_term2_intro'])
            ? $extra['pasal_8_term2_intro']
            : 'PARA PIHAK akan secara ketat menjaga kerahasiaan dan tidak akan digunakan untuk tujuan lain selain pelaksanaan Perjanjian Kerjasama, atau diberikan kepada PIHAK lain, lembaga atau perusahaan lain segala Informasi Rahasia kecuali dengan persetujuan tertulis dari masing-masing PIHAK. Ketentuan ini tidak berlaku terhadap:';
        $this->renderNumberedListItem($pdf, '(2)', $pasal8_2_intro, $lineH, $wBody, 9);

        $p8Exceptions = ! empty($extra['pasal_8_exceptions']) && is_array($extra['pasal_8_exceptions'])
            ? $extra['pasal_8_exceptions']
            : [
                'Pemberitahuan kepada direktur, petugas atau karyawan dari Pihak yang secara langsung terlibat dengan Informasi Rahasia tersebut berdasarkan Perjanjian Kerja sama ini.',
                'Informasi yang merupakan informasi publik.',
                'Informasi yang tersedia pada PARA PIHAK yang pada dasarnya dikategorikan nonrahasia sebelum Perjanjian Kerjasama ini berlaku.',
                'Informasi yang diperbolehkan atau diizinkan untuk diungkapkan berdasarkan peraturan perundang-undangan, Peraturan Pemerintah, Surat Keputusan, Undang-Undang, atau peraturan yang berlaku.',
            ];

        // Exception a & b pada Halaman 4
        if (isset($p8Exceptions[0])) {
            $this->renderSubListItem($pdf, 'a.', $p8Exceptions[0], $lineH, $wBody);
        }
        if (isset($p8Exceptions[1])) {
            $this->renderSubListItem($pdf, 'b.', $p8Exceptions[1], $lineH, $wBody);
        }

        // Paraf Box Halaman 4
        $this->renderParafTable($pdf);

        // ==========================================
        // HALAMAN 5: PASAL 8 (Lanjutan) & PASAL 9
        // ==========================================
        $pdf->AddPage('P');

        if ($isWatermarked) {
            $pdf->SetFont('Helvetica', 'B', 30);
            $pdf->SetTextColor(242, 245, 250);
            $pdf->SetXY(25, 140);
            $pdf->Cell($wBody, 18, 'PRATINJAU LANGSUNG (LIVE)', 0, 0, 'C');
            $pdf->SetTextColor(0, 0, 0);
        }

        $pdf->SetY(25);
        $pdf->SetFont('Times', '', 10);

        // Lanjutan Exception c & d pada Halaman 5
        if (isset($p8Exceptions[2])) {
            $this->renderSubListItem($pdf, 'c.', $p8Exceptions[2], $lineH, $wBody);
        }
        if (isset($p8Exceptions[3])) {
            $this->renderSubListItem($pdf, 'd.', $p8Exceptions[3], $lineH, $wBody);
        }
        // Jika ada exception tambahan lainnya
        for ($i = 4; $i < count($p8Exceptions); $i++) {
            $this->renderSubListItem($pdf, chr(97 + $i).'.', $p8Exceptions[$i], $lineH, $wBody);
        }
        $pdf->Ln(1);

        $confWords = $this->numberToWords($confidentialityYears);
        $pasal8_3 = ! empty($extra['pasal_8_term3'])
            ? (string) $extra['pasal_8_term3']
            : "Kewajiban PARA PIHAK terhadap Kerahasiaan akan bertahan sampai dengan {$confidentialityYears} ({$confWords}) tahun setelah berakhirnya Perjanjian Kerja Sama ini.";
        if (preg_match('/bertahan sampai dengan \d+/i', $pasal8_3)) {
            $pasal8_3 = preg_replace(
                '/bertahan sampai dengan \d+ \([^)]+\) tahun/i',
                "bertahan sampai dengan {$confidentialityYears} ({$confWords}) tahun",
                $pasal8_3
            );
        }
        $this->renderNumberedListItem($pdf, '(3)', $pasal8_3, $lineH, $wBody, 9);
        $pdf->Ln(4);

        // PASAL 9: KORESPONDENSI
        $this->renderArticleHeader($pdf, 'PASAL 9', 'KORESPONDENSI', $wBody);

        $this->renderNumberedListItem($pdf, '1.', 'Untuk kepentingan surat-menyurat dan persetujuan dalam pelaksanaan Nota Kesepakatan ini, PARA PIHAK menunjuk wakil masing-masing sebagai berikut :', $lineH, $wBody);
        $pdf->Ln(1.5);

        // Kontak Pihak 1 (Mitra)
        $pdf->SetFont('Times', 'B', 10);
        $pdf->SetX(34);
        $pdf->Cell(100, $lineH, mb_strtoupper($partnerName, 'UTF-8'), 0, 1, 'L');

        $pdf->SetFont('Times', '', 10);
        $this->renderContactRow($pdf, 34, 'Tujuan', $partnerOfficialPosition);
        $this->renderContactRow($pdf, 34, 'Alamat', $partnerAddress);
        $this->renderContactRow($pdf, 34, 'Faksimili', '-');
        $this->renderContactRow($pdf, 34, 'Telepon', $partnerPhone);
        $this->renderContactRow($pdf, 34, 'Email', $partnerEmail);
        $pdf->Ln(2);

        // Kontak Pihak 2 (Fasilkom UNSUB)
        $pdf->SetFont('Times', 'B', 10);
        $pdf->SetX(34);
        $pdf->Cell(100, $lineH, 'FAKULTAS ILMU KOMPUTER UNIVERSITAS SUBANG', 0, 1, 'L');

        $pdf->SetFont('Times', '', 10);
        $this->renderContactRow($pdf, 34, 'Tujuan', "{$facultyOfficialPosition} Fakultas Ilmu Komputer Universitas Subang");
        $this->renderContactRow($pdf, 34, 'Alamat', 'Jalan RA Kartini No. 3 Subang');
        $this->renderContactRow($pdf, 34, 'Faksimili', '-');
        $this->renderContactRow($pdf, 34, 'Telepon', '+62 821-2022-589');
        $this->renderContactRow($pdf, 34, 'Email', 'Fasikom@unsub.ac.id');
        $pdf->Ln(3);

        // Poin 2 & 3
        $pasal9_2 = ! empty($extra['pasal_9_term2'])
            ? $extra['pasal_9_term2']
            : 'Dalam hal terjadi perubahan alamat dari alamat sebagaimana dimaksud pada ayat (1) atau alamat terakhir yang tercatat pada PARA PIHAK, maka perubahan harus diberitahukan secara tertulis kepada Pihak lain, paling lambat 5 (lima) hari kerja sebelum perubahan alamat dimaksud berlaku efektif';
        $this->renderNumberedListItem($pdf, '2.', $pasal9_2, $lineH, $wBody);

        $pasal9_3 = ! empty($extra['pasal_9_term3'])
            ? $extra['pasal_9_term3']
            : 'Dalam hal perubahan alamat sebagaimana dimaksud pada ayat (2) tidak diberitahukan, maka surat-menyurat atau pemberitahuan dengan pengiriman yang ditujukan ke alamat di atas atau alamat terakhir yang diketahui/tercatat pada PARA PIHAK sebagaimana dimaksud pada ayat (1), dianggap telah diberikan sebagaimana mestinya.';

        // Paraf Box Halaman 5
        $this->renderParafTable($pdf);

        // ==========================================
        // HALAMAN 6: PASAL 9 (Sambungan), PASAL 10, PASAL 11 & TANDA TANGAN
        // ==========================================
        $pdf->AddPage('P');

        if ($isWatermarked) {
            $pdf->SetFont('Helvetica', 'B', 30);
            $pdf->SetTextColor(242, 245, 250);
            $pdf->SetXY(25, 140);
            $pdf->Cell($wBody, 18, 'PRATINJAU LANGSUNG (LIVE)', 0, 0, 'C');
            $pdf->SetTextColor(0, 0, 0);
        }

        $pdf->SetY(25);
        $pdf->SetFont('Times', '', 10);

        // Poin 3 Pasal 9
        $this->renderNumberedListItem($pdf, '3.', $pasal9_3, $lineH, $wBody);
        $pdf->Ln(4);

        // PASAL 10: PENYELESAIAN PERSELISIHAN
        $this->renderArticleHeader($pdf, 'PASAL 10', 'PENYELESAIAN PERSELISIHAN', $wBody);

        $pasal10 = ! empty($extra['pasal_10_text'])
            ? $extra['pasal_10_text']
            : 'Apabila timbul perselisihan yang terjadi dalam dan atau sebagai akibat dari dilaksanakannya Perjanjian ini maka PARA PIHAK sepakat untuk menyelesaikannya secara musyawarah.';
        $pdf->MultiCell($wBody, $lineH, $pasal10, 0, 'J');
        $pdf->Ln(4);

        // PASAL 11: LAIN-LAIN
        $this->renderArticleHeader($pdf, 'PASAL 11', 'LAIN-LAIN', $wBody);

        $pasal11_1 = ! empty($extra['pasal_11_text1'])
            ? $extra['pasal_11_text1']
            : 'Hal-hal yang belum diatur dalam perjanjian ini akan diatur kemudian secara tertulis dalam addendum yang merupakan bagian tidak terpisahkan dari Perjanjian ini;';
        $pasal11_2 = ! empty($extra['pasal_11_text2'])
            ? $extra['pasal_11_text2']
            : 'Perjanjian ini dibuat tanpa paksaan dari siapapun, rangkap dua dan bermaterai cukup serta masing-masing mempunyai kekuatan hukum yang sama.';

        $this->renderNumberedListItem($pdf, '(1)', $pasal11_1, $lineH, $wBody, 9);
        $this->renderNumberedListItem($pdf, '(2)', $pasal11_2, $lineH, $wBody, 9);
        $pdf->Ln(4);

        // Kalimat Penutup
        $closingText = 'Demikian Perjanjian Kerja Sama ini dibuat dan ditandatangani pada hari, tanggal, bulan dan tahun sebagaimana tersebut pada awal Perjanjian, yang dibuat dalam rangkap 2 (dua) asli bermaterai cukup, memiliki kekuatan hukum yang sama dan PARA PIHAK memegang masing-masing 1 (satu) rangkap asli.';
        $pdf->MultiCell($wBody, $lineH, $closingText, 0, 'J');
        $pdf->Ln(6);

        // ==========================================
        // BLOK TANDA TANGAN BERDAMPINGAN
        // ==========================================
        $sigStartY = $pdf->GetY();
        $this->mouSignaturePage = $pdf->PageNo();
        $this->mouSignatureY = $sigStartY;

        // Kiri: PIHAK KEDUA (Fasilkom UNSUB)
        $pdf->SetFont('Times', 'B', 10);
        $pdf->SetXY(25, $sigStartY);
        $pdf->Cell(75, 5, 'PIHAK KEDUA', 0, 1, 'C');
        $pdf->SetX(25);
        $pdf->SetFont('Times', '', 9.5);
        $pdf->Cell(75, 4.5, 'Fakultas Ilmu Komputer Universitas Subang', 0, 1, 'C');

        // Kanan: PIHAK PERTAMA (Mitra)
        $pdf->SetFont('Times', 'B', 10);
        $pdf->SetXY(110, $sigStartY);
        $pdf->Cell(75, 5, 'PIHAK PERTAMA', 0, 1, 'C');
        $pdf->SetX(110);
        $pdf->SetFont('Times', '', 9.5);
        $pdf->Cell(75, 4.5, $partnerName, 0, 1, 'C');

        // Ruang Tanda Tangan / Materai
        $stampY = $sigStartY + 12;

        // Materai placeholder di Pihak Pertama (Kanan)
        $pdf->SetXY(110, $stampY + 2);
        $pdf->SetFont('Times', 'I', 8);
        $pdf->SetTextColor(140, 140, 140);
        $pdf->Cell(75, 4, '[ Materai 10.000 ]', 0, 1, 'C');
        $pdf->SetTextColor(0, 0, 0);

        // Nama & Jabatan Pejabat
        $nameY = $sigStartY + 42;

        // Nama Pihak Kedua
        $pdf->SetXY(25, $nameY);
        $pdf->SetFont('Times', 'BU', 10);
        $pdf->Cell(75, 5, $facultyOfficialName, 0, 1, 'C');

        // Nama Pihak Pertama
        $pdf->SetXY(110, $nameY);
        $pdf->SetFont('Times', 'BU', 10);
        $pdf->Cell(75, 5, $partnerOfficialName, 0, 1, 'C');

        // Paraf Box Halaman 6
        $this->renderParafTable($pdf);

        return $pdf->Output('S');
    }

    /**
     * Helper to render centered Article Header (e.g. PASAL 1 / DEFINISI).
     */
    protected function renderArticleHeader(FPDF $pdf, string $pasal, string $title, float $wBody): void
    {
        $pdf->SetFont('Times', 'B', 10);
        $pdf->Cell($wBody, 4.5, $pasal, 0, 1, 'C');
        $pdf->Cell($wBody, 4.5, $title, 0, 1, 'C');
        $pdf->Ln(2);
    }

    /**
     * Helper to render numbered list items with indentation.
     */
    protected function renderNumberedListItem(FPDF $pdf, string $num, ?string $text, float $lineH, float $wBody, float $wNum = 7): void
    {
        $text = (string) ($text ?? '');
        $startX = 25;
        $pdf->SetX($startX);
        $pdf->SetFont('Times', '', 10);

        if ($num !== '') {
            $pdf->Cell($wNum, $lineH, $num, 0, 0, 'L');
        } else {
            $pdf->Cell($wNum, $lineH, '', 0, 0, 'L');
        }

        $wText = $wBody - $wNum;
        $pdf->MultiCell($wText, $lineH, $text, 0, 'J');
        $pdf->Ln(1);
    }

    /**
     * Helper to render sub-list items (e.g. a., b., c.) with sub-indentation.
     */
    protected function renderSubListItem(FPDF $pdf, string $char, ?string $text, float $lineH, float $wBody): void
    {
        $text = (string) ($text ?? '');
        $startX = 34;
        $pdf->SetX($startX);
        $pdf->SetFont('Times', '', 10);

        $pdf->Cell(6, $lineH, $char, 0, 0, 'L');
        $wText = $wBody - 15;
        $pdf->MultiCell($wText, $lineH, $text, 0, 'J');
        $pdf->Ln(0.5);
    }

    /**
     * Helper to render contact table rows in Pasal 9.
     */
    protected function renderContactRow(FPDF $pdf, float $x, string $label, ?string $value): void
    {
        $value = (string) ($value ?? '');
        $pdf->SetX($x);
        $pdf->Cell(20, 4.5, $label, 0, 0, 'L');
        $pdf->Cell(5, 4.5, ':', 0, 0, 'C');
        $pdf->MultiCell(125, 4.5, $value, 0, 'L');
    }
}
