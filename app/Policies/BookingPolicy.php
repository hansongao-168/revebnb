<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\Landlord;
use App\Models\SaasUser;
use App\Models\User;

class BookingPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User|SaasUser|Landlord $user): bool
    {
        if ($user instanceof User) {
            return (bool) $user->is_admin;
        }

        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User|SaasUser|Landlord $user, Booking $booking): bool
    {
        if ($user instanceof User) {
            return (bool) $user->is_admin;
        }

        if (! $booking->relationLoaded('listing')) {
            $booking->load('listing');
        }

        if ($user instanceof SaasUser) {
            return $booking->listing->tenant_id === $user->tenant_id;
        }

        return $booking->listing->landlord_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User|SaasUser|Landlord $user): bool
    {
        if ($user instanceof User) {
            return (bool) $user->is_admin;
        }

        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User|SaasUser|Landlord $user, Booking $booking): bool
    {
        if ($user instanceof User) {
            return (bool) $user->is_admin;
        }

        if (! $booking->relationLoaded('listing')) {
            $booking->load('listing');
        }

        if ($user instanceof SaasUser) {
            return $booking->listing->tenant_id === $user->tenant_id;
        }

        return $booking->listing->landlord_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User|SaasUser|Landlord $user, Booking $booking): bool
    {
        if ($user instanceof User) {
            return (bool) $user->is_admin;
        }

        if (! $booking->relationLoaded('listing')) {
            $booking->load('listing');
        }

        if ($user instanceof SaasUser) {
            return $booking->listing->tenant_id === $user->tenant_id;
        }

        return $booking->listing->landlord_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User|SaasUser|Landlord $user, Booking $booking): bool
    {
        if ($user instanceof User) {
            return (bool) $user->is_admin;
        }

        if (! $booking->relationLoaded('listing')) {
            $booking->load('listing');
        }

        if ($user instanceof SaasUser) {
            return $booking->listing->tenant_id === $user->tenant_id;
        }

        return $booking->listing->landlord_id === $user->id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User|SaasUser|Landlord $user, Booking $booking): bool
    {
        if ($user instanceof User) {
            return (bool) $user->is_admin;
        }

        if (! $booking->relationLoaded('listing')) {
            $booking->load('listing');
        }

        if ($user instanceof SaasUser) {
            return $booking->listing->tenant_id === $user->tenant_id;
        }

        return $booking->listing->landlord_id === $user->id;
    }
}
