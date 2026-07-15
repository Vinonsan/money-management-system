<?php
namespace Components\Layouts;

/**
 * Admin sidebar route map — parent items with optional children.
 *
 * Tabs:
 *   1. Dashboard (leaf)
 *   2. Location Management → Location, Ward
 *   3. Users → All Users, Add User
 *   4. Profile (leaf)
 *
 * activeNav: "dashboard" | "locations.location" | "users.list"
 */
final class SidebarRouter
{
    /**
     * @return array<string, array{
     *   label: string,
     *   href?: string|null,
     *   icon: string,
     *   children?: array<string, array{label: string, href: string}>
     * }>
     */
    public static function routes(): array
    {
        return [
            'dashboard' => [
                'label' => 'Dashboard',
                'href'  => BASE_URL . '/admin',
                'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z"/>',
            ],
            'payments' => [
                'label' => 'Payments',
                'href'  => null,
                'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                'children' => [
                    'update'  => ['label' => 'Update',     'href' => BASE_URL . '/admin/payments/update'],
                    'members' => ['label' => 'Members',    'href' => BASE_URL . '/admin/payments/members'],
                ],
            ],
            'locations' => [
                'label' => 'Location Management',
                'href'  => null,
                'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>',
                'children' => [
                    'location' => ['label' => 'Location', 'href' => BASE_URL . '/admin/locations'],
                    'ward'     => ['label' => 'Ward',     'href' => BASE_URL . '/admin/wards'],
                ],
            ],
            'users' => [
                'label' => 'Users',
                'href'  => null,
                'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>',
                'children' => [
                    'list' => ['label' => 'All Users', 'href' => BASE_URL . '/admin/users'],
                ],
            ],
            'profile' => [
                'label' => 'Profile',
                'href'  => BASE_URL . '/admin/profile',
                'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>',
            ],
            'system_config' => [
                'label' => 'System Config',
                'href'  => null,
                'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
                'children' => [
                    'settings' => ['label' => 'Settings', 'href' => BASE_URL . '/admin/system-config'],
                    'messages' => ['label' => 'Message Config', 'href' => BASE_URL . '/admin/system-config/messages'],
                ],
            ],

            // ─── Super Admin (only shown for super_admin role) ─────────
            'super_admin' => [
                'label' => 'Super Admin',
                'href'  => null,
                'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                'role'  => 'super_admin',
                'children' => [
                    'dashboard' => ['label' => 'Dashboard', 'href' => BASE_URL . '/super-admin'],
                    'admins'    => ['label' => 'Manage Admins', 'href' => BASE_URL . '/super-admin/admins'],
                    'sms'       => ['label' => 'SMS Management', 'href' => BASE_URL . '/super-admin/sms'],
                ],
            ],
        ];
    }

    public static function hasChildren(array $item): bool
    {
        return !empty($item['children']) && is_array($item['children']);
    }

    public static function href(string $key): string
    {
        $routes = self::routes();

        if (str_contains($key, '.')) {
            [$parent, $child] = explode('.', $key, 2);
            return $routes[$parent]['children'][$child]['href'] ?? BASE_URL . '/admin';
        }

        $item = $routes[$key] ?? null;
        if (!$item) {
            return BASE_URL . '/admin';
        }

        if (self::hasChildren($item)) {
            $first = reset($item['children']);
            return $first['href'] ?? BASE_URL . '/admin';
        }

        return $item['href'] ?? BASE_URL . '/admin';
    }

    public static function parentKey(string $activeNav): ?string
    {
        if ($activeNav === '') {
            return null;
        }
        if (str_contains($activeNav, '.')) {
            return explode('.', $activeNav, 2)[0];
        }
        $routes = self::routes();
        if (isset($routes[$activeNav]) && self::hasChildren($routes[$activeNav])) {
            return $activeNav;
        }
        return null;
    }

    public static function isParentActive(string $parentKey, string $activeNav): bool
    {
        if ($activeNav === $parentKey) {
            return true;
        }
        return str_starts_with($activeNav, $parentKey . '.');
    }

    public static function isChildActive(string $parentKey, string $childKey, string $activeNav): bool
    {
        return $activeNav === $parentKey . '.' . $childKey;
    }

    public static function initiallyOpenParents(string $activeNav): array
    {
        $parent = self::parentKey($activeNav);
        return $parent ? [$parent => true] : [];
    }
}
