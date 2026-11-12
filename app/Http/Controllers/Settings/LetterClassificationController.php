<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\LetterClassification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LetterClassificationController extends Controller
{
    /**
     * Authorize access for admin / staff only.
     */
    protected function authorizeAdmin(Request $request): void
    {
        if ($request->user()?->isMahasiswa()) {
            abort(403, 'Akses ditolak. Fitur klasifikasi surat hanya dapat diakses oleh administrator / staf.');
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $this->authorizeAdmin($request);

        $search = $request->query('search');
        $category = $request->query('category');

        $query = LetterClassification::with(['letterTypes:id,letter_code,name,code,is_active'])
            ->withCount('letterTypes');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($category && $category !== 'all') {
            $query->where('category', $category);
        }

        $classifications = $query->ordered()->get();

        $categories = LetterClassification::whereNotNull('category')
            ->distinct()
            ->pluck('category');

        $stats = [
            'total' => LetterClassification::count(),
            'active' => LetterClassification::where('is_active', true)->count(),
            'used' => LetterClassification::whereHas('letterTypes')->count(),
        ];

        return Inertia::render('Settings/Classifications/Index', [
            'classifications' => $classifications,
            'categories' => $categories,
            'stats' => $stats,
            'filters' => [
                'search' => $search,
                'category' => $category ?: 'all',
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:letter_classifications,code'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['order'] = $validated['order'] ?? ((LetterClassification::max('order') ?: 0) + 1);
        $validated['is_active'] = $request->boolean('is_active', true);

        LetterClassification::create($validated);

        return back()->with('success', 'Kode klasifikasi "'.$validated['code'].'" berhasil ditambahkan.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LetterClassification $classification): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('letter_classifications', 'code')->ignore($classification->id)],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $classification->update($validated);

        return back()->with('success', 'Data klasifikasi surat "'.$classification->code.'" berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, LetterClassification $classification): RedirectResponse
    {
        $this->authorizeAdmin($request);

        if ($classification->letterTypes()->exists()) {
            return back()->with('error', 'Tidak dapat menghapus kode klasifikasi yang sedang digunakan oleh template surat.');
        }

        $code = $classification->code;
        $classification->delete();

        return back()->with('success', 'Kode klasifikasi "'.$code.'" berhasil dihapus.');
    }

    /**
     * Toggle active status.
     */
    public function toggle(Request $request, LetterClassification $classification): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $classification->update([
            'is_active' => ! $classification->is_active,
        ]);

        $status = $classification->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', 'Status klasifikasi "'.$classification->code.'" berhasil '.$status.'.');
    }
}
