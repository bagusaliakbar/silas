<?php

use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentSigningController;
use App\Http\Controllers\IncomingLetterController;
use App\Http\Controllers\LetterController;
use App\Http\Controllers\LetterTemplateController;
use App\Http\Controllers\LiveLetterStudioController;
use App\Http\Controllers\MahasiswaController;
use App\Http\Controllers\MouStudioController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Settings\ArchiveCategoryController;
use App\Http\Controllers\Settings\CourseController;
use App\Http\Controllers\Settings\LecturerController;
use App\Http\Controllers\Settings\LetterClassificationController;
use App\Http\Controllers\Settings\SigningOfficialController;
use App\Http\Controllers\SignatureFieldController;
use App\Http\Controllers\SkStudioController;
use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;

// Public Verification Portal
Route::get('/verify', [VerificationController::class, 'index'])->name('verify.index');
Route::post('/verify/file', [VerificationController::class, 'verifyFile'])->name('verify.file');
Route::get('/verify/{token}', [VerificationController::class, 'show'])->name('documents.verify');

Route::get('/', function () {
    if (auth()->check()) {
        return auth()->user()->isMahasiswa()
            ? redirect()->route('mahasiswa.dashboard')
            : redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Mahasiswa Dedicated Portal
    Route::prefix('mahasiswa')->name('mahasiswa.')->group(function () {
        Route::get('/dashboard', [MahasiswaController::class, 'dashboard'])->name('dashboard');
        Route::get('/pengajuan', [MahasiswaController::class, 'create'])->name('letters.create');
        Route::post('/pengajuan', [MahasiswaController::class, 'store'])->name('letters.store');
        Route::get('/pengajuan/{letter:uuid}/edit', [MahasiswaController::class, 'edit'])->name('letters.edit');
        Route::put('/pengajuan/{letter:uuid}', [MahasiswaController::class, 'update'])->name('letters.update');
        Route::delete('/pengajuan/{letter:uuid}', [MahasiswaController::class, 'destroy'])->name('letters.destroy');
        Route::get('/riwayat', [MahasiswaController::class, 'index'])->name('letters.index');
        Route::get('/riwayat/{letter:uuid}', [MahasiswaController::class, 'show'])->name('letters.show');
        Route::get('/riwayat/{letter:uuid}/download', [MahasiswaController::class, 'download'])->name('letters.download');
    });

    // Surat Masuk (Arsip Surat Eksternal / Masuk ke Fakultas)
    Route::get('/incoming-letters', [IncomingLetterController::class, 'index'])->name('incoming-letters.index');
    Route::post('/incoming-letters', [IncomingLetterController::class, 'store'])->name('incoming-letters.store');
    Route::put('/incoming-letters/{incomingLetter}', [IncomingLetterController::class, 'update'])->name('incoming-letters.update');
    Route::delete('/incoming-letters/{incomingLetter}', [IncomingLetterController::class, 'destroy'])->name('incoming-letters.destroy');
    Route::get('/incoming-letters/{incomingLetter}/download', [IncomingLetterController::class, 'download'])->name('incoming-letters.download');

    // Pusat Arsip (Surat Masuk, Surat Keluar & Google Form / Google Drive)
    Route::get('/archives', [ArchiveController::class, 'index'])->name('archives.index');
    Route::post('/archives', [ArchiveController::class, 'store'])->name('archives.store');
    Route::post('/archives/outgoing/{letter}', [ArchiveController::class, 'archiveOutgoingLetter'])->name('archives.outgoing');
    Route::post('/archives/incoming/{incomingLetter}', [ArchiveController::class, 'archiveIncomingLetter'])->name('archives.incoming');
    Route::put('/archives/{archive}', [ArchiveController::class, 'update'])->name('archives.update');
    Route::delete('/archives/{archive}', [ArchiveController::class, 'destroy'])->name('archives.destroy');
    Route::get('/archives/{archive}/download', [ArchiveController::class, 'download'])->name('archives.download');

    // Academic Letter Requests (Layanan Persuratan Mahasiswa Fasilkom)
    Route::get('/letters', [LetterController::class, 'index'])->name('letters.index');
    Route::get('/letters/create', [LetterController::class, 'create'])->name('letters.create');

    // Live Letter Studio (Pembuatan Surat Interaktif & Live Preview Khusus Admin)
    Route::get('/letters/live-studio', [LiveLetterStudioController::class, 'index'])->name('letters.live-studio');
    Route::post('/letters/live-studio/preview', [LiveLetterStudioController::class, 'preview'])->name('letters.live-studio.preview');
    Route::post('/letters/live-studio/store', [LiveLetterStudioController::class, 'store'])->name('letters.live-studio.store');

    // Studio SK (Pembuatan Surat Keputusan Dekan Interaktif & Live Preview Khusus Admin)
    Route::get('/letters/sk-studio', [SkStudioController::class, 'index'])->name('letters.sk-studio');
    Route::post('/letters/sk-studio/preview', [SkStudioController::class, 'preview'])->name('letters.sk-studio.preview');
    Route::post('/letters/sk-studio/store', [SkStudioController::class, 'store'])->name('letters.sk-studio.store');

    // Studio MoU (Pembuatan Nota Kesepahaman / MoU Interaktif & Live Preview Khusus Admin)
    Route::get('/letters/mou-studio', [MouStudioController::class, 'index'])->name('letters.mou-studio');
    Route::post('/letters/mou-studio/preview', [MouStudioController::class, 'preview'])->name('letters.mou-studio.preview');
    Route::post('/letters/mou-studio/store', [MouStudioController::class, 'store'])->name('letters.mou-studio.store');

    Route::post('/letters', [LetterController::class, 'store'])->name('letters.store');
    Route::get('/letters/{letter:uuid}', [LetterController::class, 'show'])->name('letters.show');
    Route::patch('/letters/{letter:uuid}/signer', [LetterController::class, 'updateSigner'])->name('letters.update-signer');
    Route::patch('/letters/{letter:uuid}/toggle-download', [LetterController::class, 'toggleDownload'])->name('letters.toggle-download');
    Route::post('/letters/{letter:uuid}/upload-manual-signed', [LetterController::class, 'uploadManualSigned'])->name('letters.upload-manual-signed');
    Route::post('/letters/{letter:uuid}/sign', [LetterController::class, 'sign'])->name('letters.sign');
    Route::post('/letters/{letter:uuid}/reject', [LetterController::class, 'reject'])->name('letters.reject');
    Route::delete('/letters/{letter:uuid}', [LetterController::class, 'destroy'])->name('letters.destroy');
    Route::get('/letters/{letter:uuid}/download', [LetterController::class, 'download'])->name('letters.download');

    // Documents
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::get('/documents/create', [DocumentController::class, 'create'])->name('documents.create');
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{document}/studio', [DocumentController::class, 'studio'])->name('documents.studio');
    Route::get('/documents/{document}/stream', [DocumentController::class, 'streamPdf'])->name('documents.stream');
    Route::get('/documents/{document}/preview-pdf', [DocumentController::class, 'previewPdf'])->name('documents.preview-pdf');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

    // Signature Fields & Visual Signing
    Route::post('/documents/{document}/fields', [SignatureFieldController::class, 'store'])->name('documents.fields.store');
    Route::patch('/documents/{document}/fields/{field}', [SignatureFieldController::class, 'update'])->name('documents.fields.update');
    Route::post('/documents/{document}/fields/{field}/sign', [SignatureFieldController::class, 'sign'])->name('documents.fields.sign');
    Route::delete('/documents/{document}/fields/{field}', [SignatureFieldController::class, 'destroy'])->name('documents.fields.destroy');

    // Finalize Signing & PKI / PSrE Driver
    Route::post('/documents/{document}/process-sign', [DocumentSigningController::class, 'process'])->name('documents.process-sign');

    // Digital Certificates (PKI)
    Route::get('/certificates', [CertificateController::class, 'index'])->name('certificates.index');
    Route::post('/certificates', [CertificateController::class, 'store'])->name('certificates.store');
    Route::post('/certificates/import', [CertificateController::class, 'import'])->name('certificates.import');
    Route::post('/certificates/{certificate}/default', [CertificateController::class, 'setDefault'])->name('certificates.default');
    Route::get('/certificates/{certificate}/download', [CertificateController::class, 'download'])->name('certificates.download');
    Route::delete('/certificates/{certificate}', [CertificateController::class, 'destroy'])->name('certificates.destroy');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Letter Templates Management (Admin & Pejabat)
    Route::get('/letter-templates', [LetterTemplateController::class, 'index'])->name('letter-templates.index');
    Route::get('/letter-templates/create', [LetterTemplateController::class, 'create'])->name('letter-templates.create');
    Route::post('/letter-templates', [LetterTemplateController::class, 'store'])->name('letter-templates.store');
    Route::post('/letter-templates/preview-new', [LetterTemplateController::class, 'previewNew'])->name('letter-templates.preview-new');
    Route::get('/letter-templates/{letterType}/edit', [LetterTemplateController::class, 'edit'])->name('letter-templates.edit');
    Route::put('/letter-templates/{letterType}', [LetterTemplateController::class, 'update'])->name('letter-templates.update');
    Route::delete('/letter-templates/{letterType}', [LetterTemplateController::class, 'destroy'])->name('letter-templates.destroy');
    Route::match(['GET', 'POST'], '/letter-templates/{letterType}/preview', [LetterTemplateController::class, 'preview'])->name('letter-templates.preview');

    // Settings: Pejabat Penandatangan & Klasifikasi Surat (Admin & Pejabat)
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/signers', [SigningOfficialController::class, 'index'])->name('signers.index');
        Route::post('/signers', [SigningOfficialController::class, 'store'])->name('signers.store');
        Route::match(['PUT', 'POST'], '/signers/{signer}', [SigningOfficialController::class, 'update'])->name('signers.update');
        Route::patch('/signers/{signer}/toggle', [SigningOfficialController::class, 'toggle'])->name('signers.toggle');
        Route::delete('/signers/{signer}', [SigningOfficialController::class, 'destroy'])->name('signers.destroy');

        Route::get('/classifications', [LetterClassificationController::class, 'index'])->name('classifications.index');
        Route::post('/classifications', [LetterClassificationController::class, 'store'])->name('classifications.store');
        Route::put('/classifications/{classification}', [LetterClassificationController::class, 'update'])->name('classifications.update');
        Route::patch('/classifications/{classification}/toggle', [LetterClassificationController::class, 'toggle'])->name('classifications.toggle');
        Route::delete('/classifications/{classification}', [LetterClassificationController::class, 'destroy'])->name('classifications.destroy');

        // Master Data Dosen
        Route::get('/lecturers/search', [LecturerController::class, 'search'])->name('lecturers.search');
        Route::get('/lecturers', [LecturerController::class, 'index'])->name('lecturers.index');
        Route::post('/lecturers', [LecturerController::class, 'store'])->name('lecturers.store');
        Route::put('/lecturers/{lecturer}', [LecturerController::class, 'update'])->name('lecturers.update');
        Route::patch('/lecturers/{lecturer}/toggle', [LecturerController::class, 'toggle'])->name('lecturers.toggle');
        Route::delete('/lecturers/{lecturer}', [LecturerController::class, 'destroy'])->name('lecturers.destroy');

        // Master Data Mata Kuliah
        Route::get('/courses/search', [CourseController::class, 'search'])->name('courses.search');
        Route::get('/courses/template', [CourseController::class, 'downloadTemplate'])->name('courses.template');
        Route::get('/courses/export', [CourseController::class, 'export'])->name('courses.export');
        Route::post('/courses/import', [CourseController::class, 'import'])->name('courses.import');
        Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
        Route::post('/courses', [CourseController::class, 'store'])->name('courses.store');
        Route::put('/courses/{course}', [CourseController::class, 'update'])->name('courses.update');
        Route::patch('/courses/{course}/toggle', [CourseController::class, 'toggle'])->name('courses.toggle');
        Route::delete('/courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');

        // Master Kategori Arsip
        Route::get('/archive-categories', [ArchiveCategoryController::class, 'index'])->name('archive-categories.index');
        Route::post('/archive-categories', [ArchiveCategoryController::class, 'store'])->name('archive-categories.store');
        Route::put('/archive-categories/{archiveCategory}', [ArchiveCategoryController::class, 'update'])->name('archive-categories.update');
        Route::delete('/archive-categories/{archiveCategory}', [ArchiveCategoryController::class, 'destroy'])->name('archive-categories.destroy');
    });

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
});

require __DIR__.'/auth.php';
