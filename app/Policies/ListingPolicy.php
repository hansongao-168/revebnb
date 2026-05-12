<?php

namespace App\Policies;

use App\Models\Listing;
use App\Models\User;

class ListingPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->is_admin;
    }

    public function view(User $user, Listing $listing): bool
    {
        return (bool) $user->is_admin;
    }

    public function create(User $user): bool
    {
        return (bool) $user->is_admin;
    }

    public function update(User $user, Listing $listing): bool
    {
        return (bool) $user->is_admin;
    }

    public function delete(User $user, Listing $listing): bool
    {
        return (bool) $user->is_admin;
    }

    public function restore(User $user, Listing $listing): bool
    {
        return (bool) $user->is_admin;
    }

    public function forceDelete(User $user, Listing $listing): bool
    {
        return (bool) $user->is_admin;
    }
}
