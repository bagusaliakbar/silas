<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CourseController extends Controller
{
    /**
     * Authorize access for admin / staff only.
     */
    protected function authorizeAdmin(Request $request): void
    {
        if ($request->user()?->isMahasiswa()) {
            abort(403, 'Akses ditolak. Fitur kelola data mata kuliah hanya dapat diakses oleh administrator / staf.');
        }
    }

    /**
     * Display a listing of courses.
     */
    public function index(Request $request): Response
    {
        $this->authorizeAdmin($request);

        $search = $request->query('search');
        $status = $request->query('status'); // all, active, inactive
        $studyProgram = $request->query('study_program');

        $query = Course::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        if ($studyProgram && $studyProgram !== 'all') {
            $query->where('study_program', $studyProgram);
        }

        $courses = $query->orderBy('code')->paginate(15)->withQueryString();

        $stats = [
            'total' => Course::count(),
            'active' => Course::where('is_active', true)->count(),
            'inactive' => Course::where('is_active', false)->count(),
            'total_sks' => (int) Course::where('is_active', true)->sum('sks'),
        ];

        return Inertia::render('Settings/Courses/Index', [
            'courses' => $courses,
            'filters' => [
                'search' => $search ?? '',
                'status' => $status ?? 'all',
                'study_program' => $studyProgram ?? 'all',
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * Store a newly created course.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:courses,code'],
            'name' => ['required', 'string', 'max:255'],
            'sks' => ['required', 'integer', 'min:1', 'max:12'],
            'semester' => ['nullable', 'integer', 'min:1', 'max:14'],
            'study_program' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['is_active'] = $request->boolean('is_active', true);

        Course::create($validated);

        return redirect()->route('settings.courses.index')
            ->with('success', "Mata Kuliah {$validated['name']} ({$validated['code']}) berhasil ditambahkan.");
    }

    /**
     * Update the specified course.
     */
    public function update(Request $request, Course $course): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:courses,code,'.$course->id],
            'name' => ['required', 'string', 'max:255'],
            'sks' => ['required', 'integer', 'min:1', 'max:12'],
            'semester' => ['nullable', 'integer', 'min:1', 'max:14'],
            'study_program' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['is_active'] = $request->boolean('is_active', true);

        $course->update($validated);

        return redirect()->route('settings.courses.index')
            ->with('success', "Data Mata Kuliah {$course->name} berhasil diperbarui.");
    }

    /**
     * Toggle course active status.
     */
    public function toggle(Request $request, Course $course): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $course->update(['is_active' => ! $course->is_active]);

        $statusStr = $course->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Status Mata Kuliah {$course->name} berhasil {$statusStr}.");
    }

    /**
     * Remove the specified course.
     */
    public function destroy(Request $request, Course $course): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $name = $course->name;
        $course->delete();

        return redirect()->route('settings.courses.index')
            ->with('success', "Mata Kuliah {$name} berhasil dihapus.");
    }

    /**
     * Search API endpoint for letter creation form and live studio pickers.
     */
    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $query = Course::active();

        if ($q !== '') {
            $query->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%");
            });
        }

        $results = $query->orderBy('name')->limit(30)->get([
            'id',
            'code',
            'name',
            'sks',
            'semester',
            'study_program',
        ]);

        return response()->json($results);
    }

    /**
     * Download Excel (.xlsx) template for importing courses.
     */
    public function downloadTemplate(Request $request): StreamedResponse
    {
        $this->authorizeAdmin($request);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Mata Kuliah');

        // Headers
        $headers = [
            'A1' => 'kode_mk',
            'B1' => 'nama_mk',
            'C1' => 'sks',
            'D1' => 'semester',
            'E1' => 'program_studi',
            'F1' => 'keterangan',
        ];

        foreach ($headers as $cell => $val) {
            $sheet->setCellValue($cell, $val);
        }

        // Header Styling (Biru Fasilkom dengan teks putih tebal)
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
                'name' => 'Calibri',
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E40AF'], // Tailwind blue-800
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1'],
                ],
            ],
        ];
        $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(26);

        // Sample Data Rows
        $samples = [
            ['SI-101', 'Pengantar Teknologi Informasi', 2, 1, 'Sistem Informasi', 'Mata kuliah dasar keilmuan TI'],
            ['SI-102', 'Algoritma dan Pemrograman', 3, 1, 'Sistem Informasi', 'Wajib & Praktikum'],
            ['SI-201', 'Struktur Data', 3, 2, 'Sistem Informasi', 'Prasyarat SI-102'],
            ['SI-202', 'Basis Data I', 3, 2, 'Sistem Informasi', 'SQL & Desain Relasional'],
            ['SI-301', 'Rekayasa Perangkat Lunak', 3, 3, 'Sistem Informasi', 'Metodologi SDLC'],
        ];

        $rowIdx = 2;
        foreach ($samples as $row) {
            $sheet->setCellValueExplicit('A' . $rowIdx, $row[0], DataType::TYPE_STRING);
            $sheet->setCellValue('B' . $rowIdx, $row[1]);
            $sheet->setCellValue('C' . $rowIdx, $row[2]);
            $sheet->setCellValue('D' . $rowIdx, $row[3]);
            $sheet->setCellValue('E' . $rowIdx, $row[4]);
            $sheet->setCellValue('F' . $rowIdx, $row[5]);

            $sheet->getStyle('A' . $rowIdx . ':F' . $rowIdx)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E2E8F0');
            $sheet->getStyle('A' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('C' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $rowIdx++;
        }

        // Auto-size columns
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'template_import_mata_kuliah.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Export all or filtered courses to Excel (.xlsx).
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorizeAdmin($request);

        $search = $request->query('search');
        $status = $request->query('status');
        $studyProgram = $request->query('study_program');

        $query = Course::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        if ($studyProgram && $studyProgram !== 'all') {
            $query->where('study_program', $studyProgram);
        }

        $courses = $query->orderBy('code')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Mata Kuliah');

        // Header titles
        $headers = [
            'A1' => 'NO',
            'B1' => 'KODE MK',
            'C1' => 'NAMA MATA KULIAH',
            'D1' => 'SKS',
            'E1' => 'SEMESTER',
            'F1' => 'PROGRAM STUDI',
            'G1' => 'KETERANGAN',
            'H1' => 'STATUS',
        ];

        foreach ($headers as $cell => $val) {
            $sheet->setCellValue($cell, $val);
        }

        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
                'name' => 'Calibri',
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0F766E'], // Tailwind teal-700
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1'],
                ],
            ],
        ];
        $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(26);

        $rowIdx = 2;
        foreach ($courses as $idx => $c) {
            $sheet->setCellValue('A' . $rowIdx, $idx + 1);
            $sheet->setCellValueExplicit('B' . $rowIdx, $c->code, DataType::TYPE_STRING);
            $sheet->setCellValue('C' . $rowIdx, $c->name);
            $sheet->setCellValue('D' . $rowIdx, $c->sks);
            $sheet->setCellValue('E' . $rowIdx, $c->semester ? 'Semester ' . $c->semester : '-');
            $sheet->setCellValue('F' . $rowIdx, $c->study_program ?? 'Sistem Informasi');
            $sheet->setCellValue('G' . $rowIdx, $c->description ?? '-');
            $sheet->setCellValue('H' . $rowIdx, $c->is_active ? 'Aktif' : 'Nonaktif');

            // Borders & alignments
            $sheet->getStyle('A' . $rowIdx . ':H' . $rowIdx)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E2E8F0');
            $sheet->getStyle('A' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('E' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('H' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Conditional text color for status
            if ($c->is_active) {
                $sheet->getStyle('H' . $rowIdx)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('059669'))->setBold(true);
            } else {
                $sheet->getStyle('H' . $rowIdx)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('64748B'));
            }

            $rowIdx++;
        }

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'data_mata_kuliah_' . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Import courses from Excel (.xlsx/.xls) or CSV file.
     */
    public function import(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'], // max 10MB
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();

        try {
            $spreadsheet = IOFactory::load($path);
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal membaca berkas Excel: ' . $e->getMessage());
        }

        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        if (empty($rows)) {
            return back()->with('error', 'Berkas Excel kosong.');
        }

        // Header row (baris 1)
        $headerRow = array_shift($rows);
        $headerMap = [];

        foreach ($headerRow as $colLetter => $colName) {
            if (! $colName) continue;
            $clean = strtolower(trim((string) $colName));
            $clean = str_replace([' ', '-', '_'], '', $clean);
            if (in_array($clean, ['kodemk', 'kode', 'code', 'kodematakuliah'])) {
                $headerMap['code'] = $colLetter;
            } elseif (in_array($clean, ['namamk', 'nama', 'name', 'namamatakuliah', 'matakuliah'])) {
                $headerMap['name'] = $colLetter;
            } elseif (in_array($clean, ['sks', 'bobotsks', 'credits'])) {
                $headerMap['sks'] = $colLetter;
            } elseif (in_array($clean, ['semester', 'sem'])) {
                $headerMap['semester'] = $colLetter;
            } elseif (in_array($clean, ['programstudi', 'prodi', 'jurusan', 'studyprogram'])) {
                $headerMap['study_program'] = $colLetter;
            } elseif (in_array($clean, ['keterangan', 'deskripsi', 'description', 'notes'])) {
                $headerMap['description'] = $colLetter;
            }
        }

        if (! isset($headerMap['code']) || ! isset($headerMap['name'])) {
            return back()->with('error', 'Format header Excel tidak sesuai. Wajib memiliki kolom "kode_mk" dan "nama_mk".');
        }

        $importedCount = 0;
        $updatedCount = 0;
        $rowNumber = 1;
        $errors = [];

        foreach ($rows as $row) {
            $rowNumber++;

            // Skip empty rows
            if (empty(array_filter($row, fn ($val) => trim((string) $val) !== ''))) {
                continue;
            }

            $code = isset($headerMap['code']) && isset($row[$headerMap['code']]) ? strtoupper(trim((string) $row[$headerMap['code']])) : '';
            $name = isset($headerMap['name']) && isset($row[$headerMap['name']]) ? trim((string) $row[$headerMap['name']]) : '';
            $sks = isset($headerMap['sks']) && isset($row[$headerMap['sks']]) ? (int) trim((string) $row[$headerMap['sks']]) : 2;
            
            $rawSemester = isset($headerMap['semester']) && isset($row[$headerMap['semester']]) ? trim((string) $row[$headerMap['semester']]) : '';
            $semester = $rawSemester !== '' ? (int) preg_replace('/[^0-9]/', '', $rawSemester) : null;
            if ($semester === 0) $semester = null;

            $studyProgram = isset($headerMap['study_program']) && isset($row[$headerMap['study_program']]) && trim((string) $row[$headerMap['study_program']]) !== '' 
                ? trim((string) $row[$headerMap['study_program']]) 
                : 'Sistem Informasi';

            $description = isset($headerMap['description']) && isset($row[$headerMap['description']]) && trim((string) $row[$headerMap['description']]) !== '' 
                ? trim((string) $row[$headerMap['description']]) 
                : null;

            if ($code === '' || $name === '') {
                $errors[] = "Baris {$rowNumber}: Kode MK atau Nama MK kosong, baris dilewati.";
                continue;
            }

            if ($sks < 1 || $sks > 12) {
                $sks = 2;
            }

            $existing = Course::where('code', $code)->first();
            if ($existing) {
                $existing->update([
                    'name' => $name,
                    'sks' => $sks,
                    'semester' => $semester ?? $existing->semester,
                    'study_program' => $studyProgram,
                    'description' => $description ?? $existing->description,
                    'is_active' => true,
                ]);
                $updatedCount++;
            } else {
                Course::create([
                    'code' => $code,
                    'name' => $name,
                    'sks' => $sks,
                    'semester' => $semester,
                    'study_program' => $studyProgram,
                    'description' => $description,
                    'is_active' => true,
                ]);
                $importedCount++;
            }
        }

        $msg = "Proses import Excel selesai: {$importedCount} mata kuliah baru ditambahkan, {$updatedCount} mata kuliah diperbarui.";
        if (! empty($errors)) {
            $msg .= ' (' . count($errors) . ' baris dilewati karena data tidak lengkap)';
        }

        return redirect()->route('settings.courses.index')->with('success', $msg);
    }
}

