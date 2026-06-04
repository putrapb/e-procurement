<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    /**
     * Tentukan apakah user bisa melihat detail tiket.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        // Admin sistem yang tidak terikat divisi (division_id null) memiliki akses bypass
        if (is_null($user->division_id)) {
            return true;
        }

        // User biasa hanya bisa melihat tiket miliknya sendiri yang divisinya cocok
        return $user->id === $ticket->user_id && $user->division_id === $ticket->division_id;
    }

    /**
     * Tentukan apakah user bisa melakukan update tiket.
     */
    public function update(User $user, Ticket $ticket): bool
    {
        if (is_null($user->division_id)) {
            return true;
        }

        return $user->id === $ticket->user_id && $user->division_id === $ticket->division_id;
    }

    /**
     * Tentukan apakah user bisa menghapus tiket.
     */
    public function delete(User $user, Ticket $ticket): bool
    {
        if (is_null($user->division_id)) {
            return true;
        }

        return $user->id === $ticket->user_id && $user->division_id === $ticket->division_id;
    }

    /**
     * Tentukan apakah user bisa menyetujui atau menolak tiket (hanya admin).
     */
    public function approve(User $user, Ticket $ticket): bool
    {
        return is_null($user->division_id);
    }
}
