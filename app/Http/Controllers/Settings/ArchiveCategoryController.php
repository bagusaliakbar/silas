<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\ArchiveCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ArchiveCategoryController extends Controller
{
    public function index(): Response
    {
        $categories = ArchiveCategory::withCount('archives')->orderBy('name')->get();

        return Inertia::render('Settings/ArchiveCategories/Index', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:archive_categories,code'],
            'letter_direction' => ['required', 'in:masuk,keluar,both'],
            'description' => ['nullable', 'string', 'max:500'],
            'color' => ['required', 'string', 'max:30'],
            'is_active' => ['boolean'],
        ]);

        ArchiveCategory::create($validated);

        return back()->with('success', 'Kategori arsip baru berhasil ditambahkan.');
    }

    public function update(ArchiveCategory $archiveCategory, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:archive_categories,code,' . $archiveCategory->id],
            'letter_direction' => ['required', 'in:masuk,keluar,both'],
            'description' => ['nullable', 'string', 'max:500'],
            'color' => ['required', 'string', 'max:30'],
            'is_active' => ['boolean'],
        ]);

        $archiveCategory->update($validated);

        return back()->with('success', 'Kategori arsip berhasil diperbarui.');
    }

    public function destroy(ArchiveCategory $archiveCategory): RedirectResponse
    {
        if ($archiveCategory->archives()->count() > 0) {
            return back()->with('error', 'Kategori ini tidak dapat dihapus karena masih memiliki berkas arsip terkait.');
        }

        $archiveCategory->delete();

        return back()->with('success', 'Kategori arsip berhasil dihapus.');
    }
}

