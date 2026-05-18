<?php

namespace App\Site\Enums;

enum SiteNavPlacement: string
{
    case Header = 'header';
    case Footer = 'footer';
    case CategoryStrip = 'category_strip';
    case UserMenu = 'user_menu';
    case Hero = 'hero';
    case BookingFlow = 'booking_flow';
    case ListingCard = 'listing_card';
}
