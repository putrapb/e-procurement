<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

/**
 * TicketPolicy — Authorization rules berdasarkan role dan status ticket.
 *
 * Setiap action dijaga ketat sesuai flow multi-role approval pipeline:
 *   Staff     → create, view own, edit (revision), run validation
 *   PFA       → view all, review document, generate PO
 *   Head Dept → view all, forward ticket
 *   Head Div  → view all, approve/decline ticket
 */
class TicketPolicy
{
    /**
     * Staff bisa membuat ticket baru.
     */
    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Siapa yang bisa melihat detail tiket?
     * - Staff: hanya tiket miliknya sendiri
     * - Head Dept / Head Div / PFA: bisa lihat semua tiket
     */
    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->isStaff()) {
            return (int) $user->id === (int) $ticket->user_id;
        }

        // Head Dept, Head Div, PFA bisa lihat semua tiket
        return true;
    }

    /**
     * Staff hanya bisa edit tiket yang berstatus 'revision' (revisi dokumen).
     */
    public function update(User $user, Ticket $ticket): bool
    {
        return $user->isStaff()
            && (int) $user->id === (int) $ticket->user_id
            && $ticket->isRevision();
    }

    /**
     * Staff hanya bisa delete tiket miliknya yang masih pending_review.
     */
    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->isStaff()
            && (int) $user->id === (int) $ticket->user_id
            && $ticket->isPendingReview();
    }

    /**
     * PFA bisa review dokumen (approve/reject) saat status = pending_review.
     */
    public function reviewDocument(User $user, Ticket $ticket): bool
    {
        return $user->isPfa() && $ticket->isPendingReview();
    }

    /**
     * Staff bisa menjalankan 4-Gate validation saat status = need_to_validate.
     */
    public function runValidation(User $user, Ticket $ticket): bool
    {
        return $user->isStaff()
            && (int) $user->id === (int) $ticket->user_id
            && $ticket->needsValidation();
    }

    /**
     * Head Dept bisa meneruskan tiket saat status = pending_dept_head.
     */
    public function forward(User $user, Ticket $ticket): bool
    {
        return $user->isHeadDept() && $ticket->isPendingDeptHead();
    }

    /**
     * Head Div bisa menyetujui/menolak tiket saat status = pending_div_head.
     */
    public function decide(User $user, Ticket $ticket): bool
    {
        return $user->isHeadDiv() && $ticket->isPendingDivHead();
    }

    /**
     * PFA bisa generate PO saat status = approved.
     */
    public function generatePo(User $user, Ticket $ticket): bool
    {
        return $user->isPfa() && $ticket->isApproved();
    }
}
