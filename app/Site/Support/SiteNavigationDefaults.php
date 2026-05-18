<?php

namespace App\Site\Support;

use App\Site\Enums\SiteNavLinkType;
use App\Site\Enums\SiteNavPlacement;

class SiteNavigationDefaults
{
    /**
     * Default navigation definitions mirroring legacy hardcoded Blade (preserves labels, icons, URLs).
     *
     * @return list<array<string, mixed>>
     */
    public static function items(): array
    {
        return array_merge(
            self::headerItems(),
            self::footerItems(),
            self::categoryStripItems(),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function headerItems(): array
    {
        return [
            [
                'placement' => SiteNavPlacement::Header,
                'footer_group' => null,
                'title' => '住宿',
                'link_type' => SiteNavLinkType::SitePage,
                'site_page_key' => 'stays.index',
                'route_name' => null,
                'route_params' => null,
                'external_url' => null,
                'icon' => null,
                'sort_order' => 10,
                'is_active' => true,
                'target' => '_self',
                'style_variant' => null,
                'active_match' => 'site.stays.*',
            ],
            [
                'placement' => SiteNavPlacement::Header,
                'footer_group' => null,
                'title' => '我的订单',
                'link_type' => SiteNavLinkType::SitePage,
                'site_page_key' => 'account.bookings',
                'route_name' => null,
                'route_params' => null,
                'external_url' => null,
                'icon' => null,
                'sort_order' => 20,
                'is_active' => true,
                'target' => '_self',
                'style_variant' => null,
                'active_match' => 'site.me.bookings',
            ],
            [
                'placement' => SiteNavPlacement::Header,
                'footer_group' => null,
                'title' => '体验',
                'link_type' => SiteNavLinkType::NamedRoute,
                'site_page_key' => null,
                'route_name' => 'site.stays.index',
                'route_params' => ['kind' => 'experiences'],
                'external_url' => null,
                'icon' => null,
                'sort_order' => 30,
                'is_active' => true,
                'target' => '_self',
                'style_variant' => 'muted',
                'active_match' => null,
            ],
            [
                'placement' => SiteNavPlacement::Header,
                'footer_group' => null,
                'title' => '长租',
                'link_type' => SiteNavLinkType::NamedRoute,
                'site_page_key' => null,
                'route_name' => 'site.stays.index',
                'route_params' => ['kind' => 'long-stay'],
                'external_url' => null,
                'icon' => null,
                'sort_order' => 40,
                'is_active' => true,
                'target' => '_self',
                'style_variant' => 'muted',
                'active_match' => null,
            ],
            [
                'placement' => SiteNavPlacement::Header,
                'footer_group' => null,
                'title' => '成为房东',
                'link_type' => SiteNavLinkType::ExternalUrl,
                'site_page_key' => null,
                'route_name' => null,
                'route_params' => null,
                'external_url' => '/landlord-portal/login',
                'icon' => null,
                'sort_order' => 50,
                'is_active' => true,
                'target' => '_self',
                'style_variant' => 'button',
                'active_match' => null,
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function footerItems(): array
    {
        $items = [
            ['group' => 'explore', 'title' => '所有住宿', 'link_type' => SiteNavLinkType::SitePage, 'site_page_key' => 'stays.index', 'route_name' => null, 'route_params' => null, 'external_url' => null, 'sort' => 10],
            ['group' => 'explore', 'title' => '编辑精选', 'link_type' => SiteNavLinkType::ExternalUrl, 'site_page_key' => null, 'route_name' => null, 'route_params' => null, 'external_url' => '#', 'sort' => 20],
            ['group' => 'explore', 'title' => '城市指南', 'link_type' => SiteNavLinkType::ExternalUrl, 'site_page_key' => null, 'route_name' => null, 'route_params' => null, 'external_url' => '#', 'sort' => 30],
            ['group' => 'explore', 'title' => '长期旅居', 'link_type' => SiteNavLinkType::ExternalUrl, 'site_page_key' => null, 'route_name' => null, 'route_params' => null, 'external_url' => '#', 'sort' => 40],
            ['group' => 'landlord', 'title' => '房东登录', 'link_type' => SiteNavLinkType::ExternalUrl, 'site_page_key' => null, 'route_name' => null, 'route_params' => null, 'external_url' => '/landlord-portal/login', 'sort' => 10],
            ['group' => 'landlord', 'title' => '挂牌房源', 'link_type' => SiteNavLinkType::ExternalUrl, 'site_page_key' => null, 'route_name' => null, 'route_params' => null, 'external_url' => '#', 'sort' => 20],
            ['group' => 'landlord', 'title' => '收益预估', 'link_type' => SiteNavLinkType::ExternalUrl, 'site_page_key' => null, 'route_name' => null, 'route_params' => null, 'external_url' => '#', 'sort' => 30],
            ['group' => 'support', 'title' => '帮助中心', 'link_type' => SiteNavLinkType::ExternalUrl, 'site_page_key' => null, 'route_name' => null, 'route_params' => null, 'external_url' => '#', 'sort' => 10],
            ['group' => 'support', 'title' => '退订政策', 'link_type' => SiteNavLinkType::ExternalUrl, 'site_page_key' => null, 'route_name' => null, 'route_params' => null, 'external_url' => '#', 'sort' => 20],
            ['group' => 'support', 'title' => '隐私与条款', 'link_type' => SiteNavLinkType::ExternalUrl, 'site_page_key' => null, 'route_name' => null, 'route_params' => null, 'external_url' => '#', 'sort' => 30],
        ];

        return array_map(fn (array $row): array => [
            'placement' => SiteNavPlacement::Footer,
            'footer_group' => $row['group'],
            'title' => $row['title'],
            'link_type' => $row['link_type'],
            'site_page_key' => $row['site_page_key'],
            'route_name' => $row['route_name'],
            'route_params' => $row['route_params'],
            'external_url' => $row['external_url'],
            'icon' => null,
            'sort_order' => $row['sort'],
            'is_active' => true,
            'target' => '_self',
            'style_variant' => null,
            'active_match' => null,
        ], $items);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function categoryStripItems(): array
    {
        $categories = [
            ['category' => null, 'label' => '全部', 'icon' => 'M3 11.5 12 4l9 7.5V20a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1v-8.5Z'],
            ['category' => 'editor-pick', 'label' => '编辑精选', 'icon' => 'M12 2 14.6 8.8 22 9.6l-5.6 4.9L18.1 22 12 18.3 5.9 22l1.7-7.5L2 9.6l7.4-.8L12 2Z'],
            ['category' => 'design', 'label' => '设计住宅', 'icon' => 'M4 21V7l8-4 8 4v14M9 21v-6h6v6'],
            ['category' => 'beachfront', 'label' => '海景房', 'icon' => 'M2 16c2 2 4 0 6 0s4 2 6 0 4 0 6 0M2 20c2 2 4 0 6 0s4 2 6 0 4 0 6 0M14 6a3 3 0 0 0-3-3 5 5 0 0 0-5 5'],
            ['category' => 'mountain', 'label' => '山景小屋', 'icon' => 'M3 20 9 9l4 6 3-4 5 9H3Z'],
            ['category' => 'city', 'label' => '都市公寓', 'icon' => 'M3 21V7l5-3 5 3v14M13 21V11l4-2 4 2v10M7 11v0M7 14v0M7 17v0'],
            ['category' => 'cabin', 'label' => '林间木屋', 'icon' => 'M3 20 12 5l9 15H3Zm5 0v-6h8v6'],
            ['category' => 'long-stay', 'label' => '长期旅居', 'icon' => 'M5 4h14v6H5zM5 14h14v6H5zM9 7h.01M9 17h.01'],
            ['category' => 'luxe', 'label' => 'Luxe 精品', 'icon' => 'M4 8h16l-2 12H6L4 8Zm4 0a4 4 0 0 1 8 0'],
            ['category' => 'wechat', 'label' => '微信好评', 'icon' => 'M9.5 4a7 7 0 0 0-6 11l-.7 3 3.2-1.2A7 7 0 1 0 9.5 4Z'],
            ['category' => 'unique', 'label' => '独特住宿', 'icon' => 'M6 19V10l6-6 6 6v9M9 19v-5h6v5'],
        ];

        $items = [];
        $order = 0;

        foreach ($categories as $category) {
            $params = ['category' => $category['category']];
            $items[] = [
                'placement' => SiteNavPlacement::CategoryStrip,
                'footer_group' => null,
                'title' => $category['label'],
                'link_type' => SiteNavLinkType::NamedRoute,
                'site_page_key' => null,
                'route_name' => 'site.stays.index',
                'route_params' => array_filter($params, fn ($value) => $value !== null),
                'external_url' => null,
                'icon' => $category['icon'],
                'sort_order' => $order,
                'is_active' => true,
                'target' => '_self',
                'style_variant' => null,
                'active_match' => null,
            ];
            $order += 10;
        }

        return $items;
    }
}
