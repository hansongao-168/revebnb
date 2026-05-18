<?php

namespace App\Site\Services;

use App\Models\SiteNavigationItem;
use App\Site\Data\ResolvedNavItem;
use App\Site\Enums\SiteNavLinkType;
use App\Site\Enums\SiteNavPlacement;
use Illuminate\Support\Facades\Route;
use Throwable;

class SiteNavigationResolver
{
    public function resolve(SiteNavigationItem $item, ?string $currentRoute = null): ?ResolvedNavItem
    {
        $url = null;
        $routeName = null;
        $routeParams = null;

        try {
            match ($item->link_type) {
                SiteNavLinkType::SitePage => $this->resolveSitePage($item, $url, $routeName, $routeParams),
                SiteNavLinkType::NamedRoute => $this->resolveNamedRoute($item, $url, $routeName, $routeParams),
                SiteNavLinkType::ExternalUrl => $url = self::externalUrl($item->external_url),
            };
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }

        if ($url === null && $routeName === null) {
            return null;
        }

        if ($item->placement === SiteNavPlacement::CategoryStrip) {
            $url = null;
        } elseif ($url === null && $routeName !== null) {
            $url = route($routeName, $routeParams ?? []);
        }

        return new ResolvedNavItem(
            title: $item->title,
            url: $url,
            routeName: $routeName,
            routeParams: $routeParams,
            target: $item->target,
            icon: $item->icon,
            styleVariant: $item->style_variant,
            isActive: $this->matchesActive($currentRoute, $item->active_match),
        );
    }

    public function matchesActive(?string $currentRoute, ?string $pattern): bool
    {
        if ($currentRoute === null || $pattern === null || $pattern === '') {
            return false;
        }

        if (str_ends_with($pattern, '.*')) {
            $prefix = substr($pattern, 0, -2);

            return $currentRoute === $prefix || str_starts_with($currentRoute, $prefix.'.');
        }

        return $currentRoute === $pattern;
    }

    /**
     * @param  array<string, mixed>|null  $routeParams
     */
    private function resolveSitePage(SiteNavigationItem $item, ?string &$url, ?string &$routeName, ?array &$routeParams): void
    {
        $page = $item->sitePage;

        if ($page === null || ! $page->is_active) {
            throw new \RuntimeException('Site page missing or inactive.');
        }

        if ($page->web_route_name === null) {
            throw new \RuntimeException('Site page has no web route.');
        }

        if (! Route::has($page->web_route_name)) {
            throw new \RuntimeException('Site page route does not exist.');
        }

        $routeName = $page->web_route_name;
        $routeParams = array_merge($page->web_route_params ?? [], $item->route_params ?? []);
    }

    /**
     * @param  array<string, mixed>|null  $routeParams
     */
    private static function externalUrl(?string $externalUrl): ?string
    {
        if ($externalUrl === null || $externalUrl === '') {
            return null;
        }

        if (str_starts_with($externalUrl, 'http://') || str_starts_with($externalUrl, 'https://')) {
            return $externalUrl;
        }

        return url($externalUrl);
    }

    private function resolveNamedRoute(SiteNavigationItem $item, ?string &$url, ?string &$routeName, ?array &$routeParams): void
    {
        if ($item->route_name === null || ! Route::has($item->route_name)) {
            throw new \RuntimeException('Named route does not exist.');
        }

        $routeName = $item->route_name;
        $routeParams = $item->route_params ?? [];
    }
}
