<?php

namespace App\Policies;

use App\Models\Landlord;
use App\Models\ListingUnavailabilityBlock;
use App\Models\SaasUser;
use App\Models\User;

class ListingUnavailabilityBlockPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User|SaasUser|Landlord $user): bool
    {
        if ($user instanceof User) {
            return (bool) $user->is_admin;
        }

        if ($user instanceof SaasUser) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User|SaasUser|Landlord $user, ListingUnavailabilityBlock $block): bool
    {
        if ($user instanceof User) {
            return (bool) $user->is_admin;
        }

        if ($user instanceof SaasUser) {
            return false;
        }

        if (! $block->relationLoaded('listing')) {
            $block->load('listing');
        }

        return $block->listing->landlord_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User|SaasUser|Landlord $user): bool
    {
        if ($user instanceof User) {
            return (bool) $user->is_admin;
        }

        if ($user instanceof SaasUser) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User|SaasUser|Landlord $user, ListingUnavailabilityBlock $block): bool
    {
        if ($user instanceof User) {
            return (bool) $user->is_admin;
        }

        if ($user instanceof SaasUser) {
            return false;
        }

        if (! $block->relationLoaded('listing')) {
            $block->load('listing');
        }

        return $block->listing->landlord_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User|SaasUser|Landlord $user, ListingUnavailabilityBlock $block): bool
    {
        if ($user instanceof User) {
            return (bool) $user->is_admin;
        }

        if ($user instanceof SaasUser) {
            return false;
        }

        if (! $block->relationLoaded('listing')) {
            $block->load('listing');
        }

        return $block->listing->landlord_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User|SaasUser|Landlord $user, ListingUnavailabilityBlock $block): bool
    {
        if ($user instanceof User) {
            return (bool) $user->is_admin;
        }

        if ($user instanceof SaasUser) {
            return false;
        }

        if (! $block->relationLoaded('listing')) {
            $block->load('listing');
        }

        return $block->listing->landlord_id === $user->id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User|SaasUser|Landlord $user, ListingUnavailabilityBlock $block): bool
    {
        if ($user instanceof User) {
            return (bool) $user->is_admin;
        }

        if ($user instanceof SaasUser) {
            return false;
        }

        if (! $block->relationLoaded('listing')) {
            $block->load('listing');
        }

        return $block->listing->landlord_id === $user->id;
    }
}
