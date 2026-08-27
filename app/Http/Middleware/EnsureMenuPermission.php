<?php

namespace App\Http\Middleware;

use App\Models\Menu;
use App\Models\RolePermission;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforce RBAC per-menu permission based on the request path and HTTP method.
 *
 * Mapping:
 *  - GET/HEAD  -> can_read
 *  - POST      -> can_create
 *  - PUT/PATCH -> can_update
 *  - DELETE    -> can_delete
 *
 * Menu lookup uses longest-prefix match against the menus.url column.
 * Routes without any matching menu are allowed by default (open routes such
 * as lookups, print endpoints, and module sub-routes that are not yet
 * represented in the menus table).
 */
class EnsureMenuPermission
{
    /**
     * Routes that must bypass menu permission checks entirely.
     * These are auth/account flows or dashboard support endpoints.
     */
    private const BYPASS_PATHS = [
        '/logout',
        '/api/alerts',
        '/login',
        '/',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $path = '/' . ltrim($request->path(), '/');

        // Bypass auth/account/dashboard support routes.
        if (in_array($path, self::BYPASS_PATHS, true)) {
            return $next($request);
        }

        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $action = $this->actionForMethod($request->method());
        if ($action === null) {
            return $next($request);
        }

        $menuId = $this->resolveMenuId($path);
        if ($menuId === null) {
            // No menu registered for this path -> allow (open route).
            // Future: register menus for service-orders, stock-adjustments,
            // warehouse-transfers, supplier-payables to tighten this.
            return $next($request);
        }

        if ($this->userHasPermission($user, $menuId, $action)) {
            return $next($request);
        }

        abort(403, 'You do not have permission to access this menu.');
    }

    private function actionForMethod(string $method): ?string
    {
        return match (strtoupper($method)) {
            'GET', 'HEAD' => 'can_read',
            'POST' => 'can_create',
            'PUT', 'PATCH' => 'can_update',
            'DELETE' => 'can_delete',
            default => null,
        };
    }

    /**
     * Resolve the request path to a menu id using longest-prefix match.
     * Strips trailing path segments until a matching menu.url is found.
     */
    private function resolveMenuId(string $path): ?int
    {
        $menus = Cache::remember('menus:url-map', now()->addMinutes(5), function () {
            return Menu::whereNotNull('url')
                ->where('url', '!=', '')
                ->pluck('id', 'url')
                ->all();
        });

        $candidate = $path;
        while ($candidate !== '' && $candidate !== '/') {
            if (isset($menus[$candidate])) {
                return (int) $menus[$candidate];
            }

            // Strip last segment.
            $pos = strrpos($candidate, '/');
            if ($pos === false) {
                break;
            }
            $candidate = substr($candidate, 0, $pos);
        }

        // Root dashboard menu.
        return $menus['/'] ?? null;
    }

    /**
     * Check whether the user (via primary role_id + any role_user roles)
     * has the requested permission flag on the given menu.
     */
    private function userHasPermission($user, int $menuId, string $action): bool
    {
        $roleIds = $user->roles()->pluck('roles.id')->all();
        if ($user->role_id && ! in_array($user->role_id, $roleIds, true)) {
            $roleIds[] = $user->role_id;
        }

        if (empty($roleIds)) {
            return false;
        }

        return Cache::remember(
            "permission:user-{$user->id}:menu-{$menuId}:{$action}",
            now()->addMinutes(5),
            fn () => RolePermission::whereIn('role_id', $roleIds)
                ->where('menu_id', $menuId)
                ->where($action, true)
                ->exists(),
        );
    }

    /**
     * Flush all cached menu URL map and per-user permission entries.
     * Call this whenever menus, role_permissions, or user roles change.
     */
    public static function flushPermissionCache(): void
    {
        Cache::forget('menus:url-map');

        // Flush per-user permission cache entries. Cache::flush is too broad
        // for production but acceptable here because the app uses
        // CACHE_STORE=file and permission changes are rare admin operations.
        // For tighter scope, switch to cache tags when a taggable store is
        // configured (Redis/Memcached).
        if (Cache::getStore() instanceof \Illuminate\Cache\TaggableStore) {
            Cache::tags('menu-permissions')->flush();
        } else {
            // Best-effort: clear the whole file cache for the permission keys.
            // The file driver does not support prefix-based forget, so we
            // flush the entire cache. This is acceptable for an admin-only
            // operation that runs infrequently.
            Cache::flush();
        }
    }
}
