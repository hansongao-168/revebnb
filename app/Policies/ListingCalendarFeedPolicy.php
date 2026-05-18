<?php

namespace App\Policies;

use App\Models\ListingCalendarFeed;
use App\Models\User;

class ListingCalendarFeedPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->is_admin;
    }

    public function view(User $user, ListingCalendarFeed $listingCalendarFeed): bool
    {
        return (bool) $user->is_admin;
    }

    public function create(User $user): bool
    {
        return (bool) $user->is_admin;
    }

    public function update(User $user, ListingCalendarFeed $listingCalendarFeed): bool
    {
        return (bool) $user->is_admin;
    }

    public function delete(User $user, ListingCalendarFeed $listingCalendarFeed): bool
    {
        return (bool) $user->is_admin;
    }
}
