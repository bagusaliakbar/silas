<?php

namespace App\Http\Controllers;

use App\Models\Archive;
use App\Models\ArchiveCategory;
use App\Models\IncomingLetter;
use App\Models\LetterRequest;
use App\Services\ArchiveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ArchiveController extends Controller
{
    /**
     * Display a listing of archives with filters & statistics.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        if ($user->isMahasiswa()) {
            abort(403, 'Akses terbatas untuk Administrator dan Staf Fakultas.');
        }

        $query = Archive::query()->with(['category', 'creator', 'incomingLetter', 'letterRequest']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%")
                    ->orWhere('sender', 'like', "%{$search}%")
                    ->orWhere('recipient', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type') && $request->input('type') !== 'all') {
            $query->where('letter_type', $request->input('type'));
        }

        if ($request->filled('category_id') && $request->input('category_id') !== 'all') {
            $query->where('archive_category_id', $request->input('category_id'));
        }

        $archives = $query->latest('letter_date')->latest('id')->paginate(12)->withQueryString();

        $categories = ArchiveCategory::where('is_active', true)->orderBy('name')->get();

        // Letters available for archiving
        $incomingLetters = IncomingLetter::latest()->limit(50)->get(['id', 'reference_number', 'subject', 'sender', 'letter_date', 'drive_url', 'file_path']);
        $outgoingLetters = LetterRequest::with('letterType')->latest()->limit(50)->get(['id', 'letter_number', 'student_name', 'purpose', 'created_at', 'manual_drive_link']);

        $stats = [
            'total' => Archive::count(),
            'masuk' => Archive::where('letter_type', 'masuk')->count(),
            'keluar' => Archive::where('letter_type', 'keluar')->count(),
            'gform' => Archive::whereNotNull('google_form_url')->count(),
        ];

        return Inertia::render('Archives/Index', [
            'archives' => $archives,
            'categories' => $categories,
            'incomingLetters' => $incomingLetters,
            'outgoingLetters' => $outgoingLetters,
            'filters' => $request->only(['search', 'type', 'category_id']),
            'stats' => $stats,
        ]);
    }

    /**
     * Store a newly created archive.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'letter_date' => ['required', 'date'],
            'letter_type' => ['required', 'in:masuk,keluar,lainnya'],
            'archive_category_id' => ['nullable', 'exists:archive_categories,id'],
            'sender' => ['nullable', 'string', 'max:255'],
            'recipient' => ['nullable', 'string', 'max:255'],
            'google_form_url' => ['nullable', 'url', 'max:1000'],
            'drive_url' => ['nullable', 'url', 'max:1000'],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,zip', 'max:20480'], // max 20MB
            'description' => ['nullable', 'string'],
            'incoming_letter_id' => ['nullable', 'exists:incoming_letters,id'],
            'letter_request_id' => ['nullable', 'exists:letter_requests,id'],
        ]);

        $filePath = null;
        $fileName = null;
        $fileSize = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $fileSize = $file->getSize();
            $extension = $file->getClientOriginalExtension();
            $filePath = $file->storeAs('archives', 'arsip-'.time().'-'.uniqid().'.'.$extension, 'local');
        }

        Archive::create([
            'title' => $validated['title'],
            'reference_number' => $validated['reference_number'] ?? null,
            'letter_date' => $validated['letter_date'],
            'letter_type' => $validated['letter_type'],
            'archive_category_id' => $validated['archive_category_id'] ?? null,
            'sender' => $validated['sender'] ?? null,
            'recipient' => $validated['recipient'] ?? null,
            'google_form_url' => $validated['google_form_url'] ?? null,
            'drive_url' => $validated['drive_url'] ?? null,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_size' => $fileSize,
            'description' => $validated['description'] ?? null,
            'incoming_letter_id' => $validated['incoming_letter_id'] ?? null,
            'letter_request_id' => $validated['letter_request_id'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Dokumen berhasil disimpan ke pusat arsip.');
    }

    /**
     * Update existing archive.
     */
    public function update(Archive $archive, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'letter_date' => ['required', 'date'],
            'letter_type' => ['required', 'in:masuk,keluar,lainnya'],
            'archive_category_id' => ['nullable', 'exists:archive_categories,id'],
            'sender' => ['nullable', 'string', 'max:255'],
            'recipient' => ['nullable', 'string', 'max:255'],
            'google_form_url' => ['nullable', 'url', 'max:1000'],
            'drive_url' => ['nullable', 'url', 'max:1000'],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,zip', 'max:20480'],
            'description' => ['nullable', 'string'],
        ]);

        $filePath = $archive->file_path;
        $fileName = $archive->file_name;
        $fileSize = $archive->file_size;

        if ($request->hasFile('file')) {
            if ($filePath && Storage::disk('local')->exists($filePath)) {
                Storage::disk('local')->delete($filePath);
            }
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $fileSize = $file->getSize();
            $extension = $file->getClientOriginalExtension();
            $filePath = $file->storeAs('archives', 'arsip-'.time().'-'.uniqid().'.'.$extension, 'local');
        }

        $archive->update([
            'title' => $validated['title'],
            'reference_number' => $validated['reference_number'] ?? null,
            'letter_date' => $validated['letter_date'],
            'letter_type' => $validated['letter_type'],
            'archive_category_id' => $validated['archive_category_id'] ?? null,
            'sender' => $validated['sender'] ?? null,
            'recipient' => $validated['recipient'] ?? null,
            'google_form_url' => $validated['google_form_url'] ?? null,
            'drive_url' => $validated['drive_url'] ?? null,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_size' => $fileSize,
            'description' => $validated['description'] ?? null,
        ]);

        return back()->with('success', 'Data arsip berhasil diperbarui.');
    }

    /**
     * Remove the archive.
     */
    public function destroy(Archive $archive): RedirectResponse
    {
        if ($archive->file_path && str_starts_with($archive->file_path, 'archives/')) {
            if (Storage::disk('local')->exists($archive->file_path)) {
                Storage::disk('local')->delete($archive->file_path);
            }
        }

        $archive->delete();

        return back()->with('success', 'Berkas arsip berhasil dihapus.');
    }

    /**
     * Download archive direct file.
     */
    public function download(Archive $archive): BinaryFileResponse|RedirectResponse
    {
        $disk = null;
        if ($archive->file_path) {
            if (Storage::disk('local')->exists($archive->file_path)) {
                $disk = 'local';
            } elseif (Storage::disk('public')->exists($archive->file_path)) {
                $disk = 'public';
            }
        }

        if (! $disk) {
            if ($archive->drive_url) {
                return redirect()->away($archive->drive_url);
            }
            if ($archive->google_form_url) {
                return redirect()->away($archive->google_form_url);
            }

            return back()->with('error', 'Berkas fisik arsip tidak ditemukan.');
        }

        return response()->download(
            Storage::disk($disk)->path($archive->file_path),
            $archive->file_name ?: "arsip-{$archive->uuid}.pdf"
        );
    }

    /**
     * Archive an outgoing letter request directly.
     */
    public function archiveOutgoingLetter(LetterRequest $letter, Request $request, ArchiveService $archiveService): RedirectResponse
    {
        $archive = $archiveService->archiveLetterRequest($letter, $request->user());
        if (! $archive) {
            return back()->with('info', 'Surat keluar ini belum berstatus resmi/selesai ditandatangani.');
        }

        return back()->with('success', "Surat keluar [{$letter->letter_number}] berhasil disimpan ke Pusat Arsip.");
    }

    /**
     * Archive an incoming letter directly.
     */
    public function archiveIncomingLetter(IncomingLetter $incomingLetter, Request $request, ArchiveService $archiveService): RedirectResponse
    {
        $archiveService->archiveIncomingLetter($incomingLetter, $request->user());

        $incomingLetter->update(['status' => 'archived']);

        return back()->with('success', "Surat masuk [{$incomingLetter->reference_number}] berhasil disimpan ke Pusat Arsip.");
    }
}
