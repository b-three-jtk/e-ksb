<?php

namespace App\Http\Middleware;

use App\Services\NotifikasiService;
use Inertia\Middleware;
use Illuminate\Http\Request;

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
        $user = $request->user();
        $permissions = [];

        if ($user) {
            $userPermissions = $user->getPermissionsViaRoles();
            foreach ($userPermissions as $perm) {
                $permissions[$perm->name] = true;
            }
        }

        $notifications = [];
        $unreadCount = 0;
        $pendingPopup = [];

        if ($user && $user->member) {
            $service = app(NotifikasiService::class);
            $notifications = $service->getNotificationDropdown($user->member->id);
            $unreadCount = $service->getUnreadCount($user->member->id);
            $pendingPopup = $service->getPendingPopupNotifications($user->member->id);
        }

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user,
                'role' => $user ? $user->getRoleNames()->first() : null,
                'can' => $permissions,
            ],
            'guest' => $request->user() === null,
            'notification_dropdown' => $notifications,
            'unread_notification_count' => $unreadCount,
            'pending_notification_popups' => $pendingPopup,
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error' => fn() => $request->session()->get('error'),
                'member_credentials' => fn() => $request->session()->get('member_credentials'),
                'struk' => fn() => $request->session()->get('struk'),
                'receipt' => fn () => $request->session()->get('receipt'),
                'pdf_url' => fn() => $request->session()->get('pdf_url'),
            ],
            'csrf_token' => csrf_token(),
        ]);
    }
}
