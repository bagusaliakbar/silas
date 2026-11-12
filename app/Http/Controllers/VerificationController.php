<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\QrCodeService;
use App\Services\VerificationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VerificationController extends Controller
{
    public function __construct(
        protected VerificationService $verificationService,
        protected QrCodeService $qrCodeService
    ) {}

    /**
     * Public page to search / upload file for verification.
     */
    public function index(): Response
    {
        return Inertia::render('Verification/Index');
    }

    /**
     * Public verification page accessed by QR Code or direct token link.
     */
    public function show(string $token, Request $request): Response
    {
        $result = $this->verificationService->verifyByToken($token);

        if ($result['found'] && isset($result['document'])) {
            $doc = $result['document'];
            AuditLog::record(
                $doc,
                'verified',
                'Dokumen diverifikasi secara publik oleh '.($request->ip() ?: 'anonim'),
                [
                    'via' => 'qr_or_token',
                    'ip' => $request->ip(),
                ]
            );
        }

        $qrDataUri = $this->qrCodeService->generateDataUri(
            route('documents.verify', ['token' => $token]),
            180
        );

        return Inertia::render('Verification/Show', [
            'verification' => $result,
            'token' => $token,
            'qrDataUri' => $qrDataUri,
        ]);
    }

    /**
     * Public verification by uploading PDF file.
     */
    public function verifyFile(Request $request): Response
    {
        $request->validate([
            'pdf_file' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $result = $this->verificationService->verifyUploadedFile($request->file('pdf_file'));

        return Inertia::render('Verification/Index', [
            'verificationResult' => $result,
        ]);
    }
}
