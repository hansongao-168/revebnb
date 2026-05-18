<?php

namespace App\Site\Enums;

enum SiteNavLinkType: string
{
    case SitePage = 'site_page';
    case NamedRoute = 'named_route';
    case ExternalUrl = 'external_url';
}
