<?php

namespace Plugin\SupportTicket\Models;

use Core\Models\User;
use Plugin\SupportTicket\Models\TicketCategory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $table = "tl_support_tickets";

    /**
     * relationship with created by user
     */
    public function createdBy()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    /**
     * relationship with assigned user
     */
    public function assignedTo()
    {
        return $this->hasOne(User::class, 'id', 'assigned_to');
    }

    /**
     * relationship with ticket category
     */
    public function categoryDetails()
    {
        return $this->hasOne(TicketCategory::class, 'id', 'category');
    }

    /**
     * relation with ticket replays
     */
    public function replays()
    {
        return $this->hasMany(TicketReplies::class, 'ticket_id', 'id');
    }

    /**
     * Get the last reply for the ticket
     */
    public function lastReply()
    {
        return $this->replays()->latest('created_at')->first();
    }
}
