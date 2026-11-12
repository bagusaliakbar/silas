<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateCertificateRequest;
use App\Http\Requests\ImportCertificateRequest;
use App\Models\DigitalCertificate;
use App\Notifications\SystemActivityNotification;
use App\Services\CertificateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class CertificateController extends Controller
{
    public function __construct(
        protected CertificateService $certificateService
    ) {}

    /**
     * Display listing of user certificates.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $certificates = $user->digitalCertificates()->latest()->get();

        return Inertia::render('Certificates/Index', [
            'certificates' => $certificates,
        ]);
    }

    /**
     * Generate a new self-signed X.509 PKI certificate.
     */
    public function store(GenerateCertificateRequest $request): RedirectResponse
    {
        $user = $request->user();

        try {
            $this->certificateService->generateSelfSignedCertificate($user, $request->validated());

            $user->notify(new SystemActivityNotification(
                title: 'Sertifikat Digital Diterbitkan',
                message: 'Sertifikat Digital X.509 baru Anda berhasil diterbitkan untuk TTE.',
                url: route('certificates.index'),
                category: 'certificate_created',
                icon: 'shield-check'
            ));

            return back()->with('success', 'Sertifikat Digital X.509 berhasil diterbitkan.');
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Gagal membuat sertifikat: '.$e->getMessage());
        }
    }

    /**
     * Import an existing PKCS#12 (.p12 / .pfx) certificate bundle.
     */
    public function import(ImportCertificateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $file = $request->file('p12_file');

        try {
            $content = file_get_contents($file->getRealPath());
            $this->certificateService->importPkcs12(
                $user,
                $content,
                $request->validated('passphrase'),
                $request->validated('name')
            );

            $user->notify(new SystemActivityNotification(
                title: 'Sertifikat Digital Diimpor',
                message: 'Sertifikat PKCS#12 (.p12/.pfx) berhasil diimpor ke akun Anda.',
                url: route('certificates.index'),
                category: 'certificate_created',
                icon: 'shield-check'
            ));

            return back()->with('success', 'Sertifikat PKCS#12 berhasil diimpor.');
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Gagal mengimpor sertifikat: '.$e->getMessage());
        }
    }

    /**
     * Set a certificate as default for signing.
     */
    public function setDefault(DigitalCertificate $certificate, Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($certificate->user_id !== $user->id) {
            abort(403);
        }

        $user->digitalCertificates()->update(['is_default' => false]);
        $certificate->update(['is_default' => true]);

        return back()->with('success', 'Sertifikat default berhasil diperbarui.');
    }

    /**
     * Download public certificate file (.crt).
     */
    public function download(DigitalCertificate $certificate, Request $request): BinaryFileResponse
    {
        $user = $request->user();
        if ($certificate->user_id !== $user->id) {
            abort(403);
        }

        $type = $request->query('type', 'crt');
        $path = $type === 'p12' && $certificate->p12_path
            ? $certificate->p12_path
            : $certificate->certificate_path;

        $absolutePath = Storage::disk('local')->path($path);

        if (! file_exists($absolutePath)) {
            abort(404, 'File sertifikat tidak ditemukan.');
        }

        $extension = $type === 'p12' ? 'p12' : 'crt';
        $downloadName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $certificate->name).'.'.$extension;

        return response()->download($absolutePath, $downloadName);
    }

    /**
     * Remove certificate.
     */
    public function destroy(DigitalCertificate $certificate, Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($certificate->user_id !== $user->id) {
            abort(403);
        }

        if ($certificate->certificate_path && Storage::disk('local')->exists($certificate->certificate_path)) {
            Storage::disk('local')->delete($certificate->certificate_path);
        }
        if ($certificate->private_key_path && Storage::disk('local')->exists($certificate->private_key_path)) {
            Storage::disk('local')->delete($certificate->private_key_path);
        }
        if ($certificate->p12_path && Storage::disk('local')->exists($certificate->p12_path)) {
            Storage::disk('local')->delete($certificate->p12_path);
        }

        $certificate->delete();

        return back()->with('success', 'Sertifikat digital berhasil dihapus.');
    }
}
