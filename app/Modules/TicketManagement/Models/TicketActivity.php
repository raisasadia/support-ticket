<?php

namespace App\Modules\TicketManagement\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketActivity extends Model
{
    protected $fillable = ['ticket_id', 'user_id', 'event', 'description', 'metadata'];
    protected function casts(): array { return ['metadata' => 'array']; }
    public function ticket(): BelongsTo { return $this->belongsTo(Ticket::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
