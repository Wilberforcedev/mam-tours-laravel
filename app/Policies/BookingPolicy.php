<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function view(User $user, Booking $booking): bool
    {
        return $user->isAdmin() || $booking->isOwnedBy($user);
    }

    public function cancel(User $user, Booking $booking): bool
    {
        return $user->isAdmin() || $booking->isOwnedBy($user);
    }

    public function confirm(User $user, Booking $booking): bool
    {
        return $user->isAdmin();
    }

    public function pay(User $user, Booking $booking): bool
    {
        return $booking->isOwnedBy($user);
    }
}
