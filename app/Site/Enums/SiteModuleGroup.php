<?php

namespace App\Site\Enums;

enum SiteModuleGroup: string
{
    case Stays = 'stays';
    case Bookings = 'bookings';
    case Account = 'account';
    case Docs = 'docs';
    case Landlord = 'landlord';
    case Uniapp = 'uniapp';
}
