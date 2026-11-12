<?php

namespace App\Http\Controllers;

use App\Models\DigitalCertificate;
use App\Models\Document;
use App\Models\LetterRequest;
use App\Models\SigningOfficial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the admin/pejabat executive dashboard.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->isMahasiswa()) {
            return redirect()->route('mahasiswa.dashboard');
        }

        $letterQuery = LetterRequest::with(['letterType', 'document']);

        $letterStats = [
            'total' => (clone $letterQuery)->count(),
            'pending' => (clone $letterQuery)->where('status', 'submitted')->count(),
            'signed' => (clone $letterQuery)->where('status', 'signed')->count(),
            'rejected' => (clone $letterQuery)->where('status', 'rejected')->count(),
        ];

        $documentQuery = Document::query();

        $documentStats = [
            'total' => (clone $documentQuery)->count(),
            'draft' => (clone $documentQuery)->where('status', 'draft')->count(),
            'pending' => (clone $documentQuery)->where('status', 'draft')->count(),
            'signed' => (clone $documentQuery)->where('status', 'signed')->count(),
            'completed' => (clone $documentQuery)->where('status', 'signed')->count(),
        ];

        $user = $request->user();
        $userDefaultCert = $user?->digitalCertificates()->where('is_default', true)->first();
        $fallbackCert = DigitalCertificate::where('is_default', true)->first() ?? DigitalCertificate::first();

        $userActiveCerts = $user?->digitalCertificates()->where('is_active', true)->count() ?? 0;
        $totalActiveCerts = DigitalCertificate::where('is_active', true)->count();

        $certificateStats = [
            'total' => DigitalCertificate::count(),
            'active' => $userActiveCerts ?: $totalActiveCerts,
            'user_active' => $userActiveCerts,
            'faculty_active' => $totalActiveCerts,
            'default' => $userDefaultCert ?? $fallbackCert,
        ];

        $pendingLetters = (clone $letterQuery)
            ->where('status', 'submitted')
            ->latest()
            ->take(6)
            ->get();

        $recentLetters = (clone $letterQuery)
            ->latest()
            ->take(6)
            ->get();

        $recentDocuments = Document::with('user')
            ->latest()
            ->take(5)
            ->get();

        $activeSigners = SigningOfficial::where('is_active', true)
            ->orderBy('order')
            ->get();

        return Inertia::render('Dashboard', [
            'user' => $user,
            'letterStats' => $letterStats,
            'documentStats' => $documentStats,
            'certificateStats' => $certificateStats,
            'pendingLetters' => $pendingLetters,
            'recentLetters' => $recentLetters,
            'recentDocuments' => $recentDocuments,
            'activeSigners' => $activeSigners,
        ]);
    }
}
