<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $quotes = [
            ['Find a role. Sit the interview.', 'Hirely'],
            ['A shortlist, not a vibe.', 'Hirely'],
            ['The last word stays with people.', 'Hirely'],
            ['Jobs and interviews, in one place.', 'Hirely'],
        ];
        $quote = $quotes[array_rand($quotes)];

        return [
            ...parent::share($request),
            'name' => config('app.name', 'Hirely'),
            'quote' => ['message' => $quote[0], 'author' => $quote[1]],
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'avatar' => $request->user()->avatar ?? null,
                    'role' => $request->user()->role ?? 'job_seeker',
                    'email_verified_at' => $request->user()->email_verified_at,
                    'created_at' => $request->user()->created_at,
                    'updated_at' => $request->user()->updated_at,
                ] : null,
            ],
            'notifications' => $this->notifications($request),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * @return array{unread: int, recent: array<int, array<string, mixed>>}
     */
    protected function notifications(Request $request): array
    {
        $user = $request->user();

        if (! $user) {
            return ['unread' => 0, 'recent' => []];
        }

        return [
            'unread' => $user->unreadNotifications()->count(),
            'recent' => $user->notifications()
                ->latest()
                ->take(8)
                ->get()
                ->map(fn ($notification) => [
                    'id' => $notification->id,
                    'title' => $notification->data['title'] ?? 'Notification',
                    'body' => $notification->data['body'] ?? '',
                    'url' => $notification->data['url'] ?? '/dashboard',
                    'type' => $notification->data['type'] ?? 'general',
                    'read' => $notification->read_at !== null,
                    'created_at' => $notification->created_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
        ];
    }
}
