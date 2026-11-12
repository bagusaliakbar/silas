<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'notifications' => function () use ($request) {
                $user = $request->user();
                if (! $user) {
                    return [
                        'list' => [],
                        'unread_count' => 0,
                    ];
                }

                return [
                    'list' => $user->notifications()->take(10)->get()->map(function ($n) {
                        return [
                            'id' => $n->id,
                            'title' => $n->data['title'] ?? 'Pemberitahuan Sistem',
                            'message' => $n->data['message'] ?? '',
                            'url' => $n->data['url'] ?? null,
                            'category' => $n->data['category'] ?? 'info',
                            'read_at' => $n->read_at,
                            'time_ago' => $n->created_at?->diffForHumans() ?? 'Baru saja',
                        ];
                    }),
                    'unread_count' => $user->unreadNotifications()->count(),
                ];
            },
        ];
    }
}
