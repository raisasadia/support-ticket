<?php

namespace App\Modules\TicketManagement\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketAttachment extends Model
{
    protected $fillable = ['ticket_id', 'reply_id', 'uploaded_by', 'disk', 'path', 'original_name', 'mime_type', 'size'];
    public function ticket(): BelongsTo { return $this->belongsTo(Ticket::class); }
    public function reply(): BelongsTo { return $this->belongsTo(TicketReply::class, 'reply_id'); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }
}
