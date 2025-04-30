<?php

namespace Plugin\SupportTicket\Models;

use Core\Models\User;
use Illuminate\Database\Eloquent\Model;

class TicketReplies extends Model
{
    protected $table = "tl_support_ticket_replies";

    /**
     * making relation with replied by user
     */
    public function repliedBy() {
        return $this->hasOne(User::class,'id','replied_by');
    }
}
