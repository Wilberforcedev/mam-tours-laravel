<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    private const VIEW_ROLES = ['admin', 'booking', 'finance'];
    private const MANAGE_ROLES = ['admin', 'booking'];

    public function view(User $user, Booking $booking): bool
    {
        return in_array($user->role, self::VIEW_ROLES, true) || $booking->isOwnedBy($user);
    }

    public function cancel(User $user, Booking $booking): bool
    {
        return in_array($user->role, self::MANAGE_ROLES, true) || $booking->isOwnedBy($user);
    }

    public function confirm(User $user, Booking $booking): bool
    {
        return in_array($user->role, self::MANAGE_ROLES, true);
    }

    public function pay(User $user, Booking $booking): bool
    {
        return $booking->isOwnedBy($user);
    }
}
