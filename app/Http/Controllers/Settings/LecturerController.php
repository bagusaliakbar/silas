<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Lecturer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LecturerController extends Controller
{
    /**
     * Authorize access for admin / staff only.
     */
    protected function authorizeAdmin(Request $request): void
    {
        if ($request->user()?->isMahasiswa()) {
            abort(403, 'Akses ditolak. Fitur kelola data dosen hanya dapat diakses oleh administrator / staf.');
        }
    }

    /**
     * Display a listing of lecturers.
     */
    public function index(Request $request): Response
    {
        $this->authorizeAdmin($request);

        $search = $request->query('search');
        $status = $request->query('status'); // all, active, inactive
        $functionalPosition = $request->query('functional_position');

        $query = Lecturer::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nidn', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%")
                    ->orWhere('functional_position', 'like', "%{$search}%")
                    ->orWhere('study_program', 'like', "%{$search}%");
            });
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        if ($functionalPosition && $functionalPosition !== 'all') {
            $query->where('functional_position', $functionalPosition);
        }

        $lecturers = $query->orderBy('name')->paginate(15)->withQueryString();

        $stats = [
            'total' => Lecturer::count(),
            'active' => Lecturer::where('is_active', true)->count(),
            'inactive' => Lecturer::where('is_active', false)->count(),
        ];

        return Inertia::render('Settings/Lecturers/Index', [
            'lecturers' => $lecturers,
            'stats' => $stats,
            'filters' => [
                'search' => $search,
                'status' => $status ?? 'all',
                'functional_position' => $functionalPosition ?? 'all',
            ],
            'functionalPositions' => [
                'Tenaga Pengajar',
                'Asisten Ahli',
                'Lektor',
                'Lektor Kepala',
                'Guru Besar',
            ],
            'studyPrograms' => [
                'Sistem Informasi',
                'Teknik Informatika',
                'Ilmu Komputer',
            ],
        ]);
    }

    /**
     * API search for letter form autocomplete / dropdown.
     */
    public function search(Request $request): JsonResponse
    {
        $search = $request->query('query', '');

        $lecturers = Lecturer::query()
            ->active()
            ->when($search, function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nidn', 'like', "%{$search}%")
                    ->orWhere('functional_position', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'nidn', 'nip', 'functional_position', 'academic_rank', 'study_program']);

        return response()->json($lecturers);
    }

    /**
     * Store a newly created lecturer in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nidn' => ['nullable', 'string', 'max:50'],
            'nip' => ['nullable', 'string', 'max:50'],
            'functional_position' => ['nullable', 'string', 'max:100'],
            'academic_rank' => ['nullable', 'string', 'max:100'],
            'study_program' => ['nullable', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]);

        Lecturer::create([
            'name' => $validated['name'],
            'nidn' => $validated['nidn'] ?? null,
            'nip' => $validated['nip'] ?? null,
            'functional_position' => $validated['functional_position'] ?? null,
            'academic_rank' => $validated['academic_rank'] ?? null,
            'study_program' => $validated['study_program'] ?? null,
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Data dosen berhasil ditambahkan.');
    }

    /**
     * Update the specified lecturer in storage.
     */
    public function update(Request $request, Lecturer $lecturer): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nidn' => ['nullable', 'string', 'max:50'],
            'nip' => ['nullable', 'string', 'max:50'],
            'functional_position' => ['nullable', 'string', 'max:100'],
            'academic_rank' => ['nullable', 'string', 'max:100'],
            'study_program' => ['nullable', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]);

        $lecturer->update([
            'name' => $validated['name'],
            'nidn' => $validated['nidn'] ?? null,
            'nip' => $validated['nip'] ?? null,
            'functional_position' => $validated['functional_position'] ?? null,
            'academic_rank' => $validated['academic_rank'] ?? null,
            'study_program' => $validated['study_program'] ?? null,
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Data dosen berhasil diperbarui.');
    }

    /**
     * Toggle active status.
     */
    public function toggle(Request $request, Lecturer $lecturer): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $lecturer->update([
            'is_active' => ! $lecturer->is_active,
        ]);

        $statusText = $lecturer->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Status dosen berhasil {$statusText}.");
    }

    /**
     * Remove the specified lecturer from storage.
     */
    public function destroy(Request $request, Lecturer $lecturer): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $lecturer->delete();

        return back()->with('success', 'Data dosen berhasil dihapus.');
    }
}
