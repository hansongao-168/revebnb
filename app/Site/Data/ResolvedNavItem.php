<?php

namespace App\Site\Data;

readonly class ResolvedNavItem
{
    /**
     * @param  array<string, mixed>|null  $routeParams
     */
    public function __construct(
        public string $title,
        public ?string $url = null,
        public ?string $routeName = null,
        public ?array $routeParams = null,
        public string $target = '_self',
        public ?string $icon = null,
        public ?string $styleVariant = null,
        public bool $isActive = false,
    ) {}

    /**
     * @param  array<string, mixed>  $mergeRouteParams
     */
    public function href(array $mergeRouteParams = []): string
    {
        if ($this->url !== null) {
            return $this->url;
        }

        if ($this->routeName !== null) {
            return route($this->routeName, array_merge($this->routeParams ?? [], $mergeRouteParams));
        }

        return '#';
    }
}
