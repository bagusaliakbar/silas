<?php

namespace App\Http\Controllers;

use App\Models\ArchiveCategory;
use App\Models\IncomingLetter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class IncomingLetterController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        if ($user->isMahasiswa()) {
            abort(403, 'Akses terbatas untuk Administrator dan Tenaga Kependidikan Fakultas.');
        }

        $query = IncomingLetter::query()->with(['creator', 'archive']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('sender', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category') && $request->input('category') !== 'all') {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        $letters = $query->latest('received_date')->latest('id')->paginate(10)->withQueryString();

        $stats = [
            'total' => IncomingLetter::count(),
            'this_month' => IncomingLetter::whereMonth('received_date', now()->month)
                ->whereYear('received_date', now()->year)
                ->count(),
            'with_file' => IncomingLetter::where(function ($q) {
                $q->whereNotNull('drive_url')->orWhereNotNull('file_path');
            })->count(),
        ];

        $categories = ArchiveCategory::where('is_active', true)
            ->whereIn('letter_direction', ['masuk', 'both'])
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'color']);

        return Inertia::render('IncomingLetters/Index', [
            'letters' => $letters,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category', 'status']),
            'stats' => $stats,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user->isMahasiswa()) {
            abort(403, 'Akses ditolak.');
        }

        $validated = $request->validate([
            'reference_number' => ['nullable', 'string', 'max:150'],
            'sender' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'letter_date' => ['required', 'date'],
            'received_date' => ['required', 'date'],
            'category' => ['required', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'in:received,disposed,archived'],
            'description' => ['nullable', 'string', 'max:2000'],
            'drive_url' => ['nullable', 'url', 'max:1000'],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'], // Max 10MB
        ]);

        $filePath = null;
        $fileName = null;
        $fileSize = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $fileSize = $file->getSize();
            $filePath = $file->store('incoming_letters', 'public');
        }

        IncomingLetter::create([
            'reference_number' => $validated['reference_number'] ?? null,
            'sender' => $validated['sender'],
            'subject' => $validated['subject'],
            'letter_date' => $validated['letter_date'],
            'received_date' => $validated['received_date'],
            'category' => $validated['category'],
            'status' => $validated['status'] ?? 'received',
            'description' => $validated['description'] ?? null,
            'drive_url' => $validated['drive_url'] ?? null,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_size' => $fileSize,
            'created_by' => $user->id,
        ]);

        return redirect()->route('incoming-letters.index')
            ->with('success', 'Surat masuk berhasil dicatat dan diarsipkan ke dalam sistem.');
    }

    public function update(Request $request, IncomingLetter $incomingLetter): RedirectResponse
    {
        $user = $request->user();
        if ($user->isMahasiswa()) {
            abort(403, 'Akses ditolak.');
        }

        $validated = $request->validate([
            'reference_number' => ['nullable', 'string', 'max:150'],
            'sender' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'letter_date' => ['required', 'date'],
            'received_date' => ['required', 'date'],
            'category' => ['required', 'string', 'max:100'],
            'status' => ['required', 'string', 'in:received,disposed,archived'],
            'description' => ['nullable', 'string', 'max:2000'],
            'drive_url' => ['nullable', 'url', 'max:1000'],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        if ($request->hasFile('file')) {
            if ($incomingLetter->file_path && Storage::disk('public')->exists($incomingLetter->file_path)) {
                Storage::disk('public')->delete($incomingLetter->file_path);
            }

            $file = $request->file('file');
            $incomingLetter->file_name = $file->getClientOriginalName();
            $incomingLetter->file_size = $file->getSize();
            $incomingLetter->file_path = $file->store('incoming_letters', 'public');
        }

        $incomingLetter->update([
            'reference_number' => $validated['reference_number'] ?? null,
            'sender' => $validated['sender'],
            'subject' => $validated['subject'],
            'letter_date' => $validated['letter_date'],
            'received_date' => $validated['received_date'],
            'category' => $validated['category'],
            'status' => $validated['status'],
            'description' => $validated['description'] ?? null,
            'drive_url' => $validated['drive_url'] ?? null,
        ]);

        return redirect()->route('incoming-letters.index')
            ->with('success', 'Data surat masuk berhasil diperbarui.');
    }

    public function download(IncomingLetter $incomingLetter): BinaryFileResponse
    {
        if (! $incomingLetter->file_path || ! Storage::disk('public')->exists($incomingLetter->file_path)) {
            abort(404, 'Berkas fisik surat masuk tidak ditemukan.');
        }

        $fullPath = Storage::disk('public')->path($incomingLetter->file_path);

        return response()->download($fullPath, $incomingLetter->file_name ?: 'surat-masuk.pdf');
    }

    public function destroy(IncomingLetter $incomingLetter, Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user->isMahasiswa()) {
            abort(403, 'Akses ditolak.');
        }

        if ($incomingLetter->file_path && Storage::disk('public')->exists($incomingLetter->file_path)) {
            Storage::disk('public')->delete($incomingLetter->file_path);
        }

        $subject = $incomingLetter->subject;
        $incomingLetter->delete();

        return redirect()->route('incoming-letters.index')
            ->with('success', "Arsip surat masuk '{$subject}' berhasil dihapus.");
    }
}
