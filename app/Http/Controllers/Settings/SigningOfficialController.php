<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\SigningOfficial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SigningOfficialController extends Controller
{
    /**
     * Authorize access for admin / staff only.
     */
    protected function authorizeAdmin(Request $request): void
    {
        if ($request->user()?->isMahasiswa()) {
            abort(403, 'Akses ditolak. Fitur pengaturan pejabat penandatangan hanya dapat diakses oleh administrator / staf.');
        }
    }

    /**
     * Display a listing of signing officials.
     */
    public function index(Request $request): Response
    {
        $this->authorizeAdmin($request);

        $search = $request->query('search');

        $query = SigningOfficial::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('role_title', 'like', "%{$search}%")
                    ->orWhere('signature_title', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $signers = $query->ordered()->get();

        $stats = [
            'total' => SigningOfficial::count(),
            'active' => SigningOfficial::where('is_active', true)->count(),
            'inactive' => SigningOfficial::where('is_active', false)->count(),
        ];

        return Inertia::render('Settings/Signers/Index', [
            'signers' => $signers,
            'stats' => $stats,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Store a newly created signing official in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'role_title' => ['required', 'string', 'max:255'],
            'signature_title' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', 'alpha_dash', 'unique:signing_officials,code'],
            'nip' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'signature_image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'description' => ['nullable', 'string'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('signature_image')) {
            $path = $request->file('signature_image')->store('signatures/officials', 'public');
            $validated['signature_image_path'] = $path;
        }

        $code = ! empty($validated['code'])
            ? Str::slug($validated['code'], '_')
            : Str::slug($validated['role_title'], '_');

        // Ensure unique code
        $baseCode = $code;
        $counter = 1;
        while (SigningOfficial::where('code', $code)->exists()) {
            $code = "{$baseCode}_{$counter}";
            $counter++;
        }

        $validated['code'] = $code;
        $validated['display_name'] = ! empty($validated['display_name']) ? $validated['display_name'] : $validated['name'];
        $validated['order'] = $validated['order'] ?? (SigningOfficial::max('order') + 1);
        $validated['is_active'] = $request->boolean('is_active', true);

        SigningOfficial::create($validated);

        return back()->with('success', 'Pejabat penandatangan "'.$validated['display_name'].'" berhasil ditambahkan.');
    }

    /**
     * Update the specified signing official in storage.
     */
    public function update(Request $request, SigningOfficial $signer): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'role_title' => ['required', 'string', 'max:255'],
            'signature_title' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('signing_officials', 'code')->ignore($signer->id)],
            'nip' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'signature_image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'remove_signature' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('remove_signature') && $signer->signature_image_path) {
            if (Storage::disk('public')->exists($signer->signature_image_path)) {
                Storage::disk('public')->delete($signer->signature_image_path);
            }
            $validated['signature_image_path'] = null;
        }

        if ($request->hasFile('signature_image')) {
            if ($signer->signature_image_path && Storage::disk('public')->exists($signer->signature_image_path)) {
                Storage::disk('public')->delete($signer->signature_image_path);
            }
            $validated['signature_image_path'] = $request->file('signature_image')->store('signatures/officials', 'public');
        }

        $validated['display_name'] = ! empty($validated['display_name']) ? $validated['display_name'] : $validated['name'];
        $validated['is_active'] = $request->boolean('is_active', true);

        $signer->update($validated);

        return back()->with('success', 'Data pejabat "'.$signer->display_name.'" berhasil diperbarui.');
    }

    /**
     * Remove the specified signing official from storage.
     */
    public function destroy(Request $request, SigningOfficial $signer): RedirectResponse
    {
        $this->authorizeAdmin($request);

        if (SigningOfficial::count() <= 1) {
            return back()->with('error', 'Tidak dapat menghapus pejabat terakhir. Sistem membutuhkan minimal 1 pejabat penandatangan.');
        }

        $name = $signer->display_name;
        $signer->delete();

        return back()->with('success', 'Pejabat penandatangan "'.$name.'" berhasil dihapus.');
    }

    /**
     * Toggle active status of the specified signing official.
     */
    public function toggle(Request $request, SigningOfficial $signer): RedirectResponse
    {
        $this->authorizeAdmin($request);

        if ($signer->is_active && SigningOfficial::where('is_active', true)->count() <= 1) {
            return back()->with('error', 'Tidak dapat menonaktifkan pejabat terakhir yang aktif.');
        }

        $signer->update([
            'is_active' => ! $signer->is_active,
        ]);

        $status = $signer->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', 'Status pejabat "'.$signer->display_name.'" berhasil '.$status.'.');
    }
}
