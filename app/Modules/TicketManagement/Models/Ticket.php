<?php

namespace App\Modules\TicketManagement\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

class Ticket extends Model
{
    protected $fillable = [
        'ticket_number', 'subject', 'description', 'priority', 'category',
        'status', 'created_by', 'assigned_to', 'due_at',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'sla_escalated_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function agent(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function replies(): HasMany { return $this->hasMany(TicketReply::class); }
    public function activities(): HasMany { return $this->hasMany(TicketActivity::class); }
    public function attachments(): HasMany { return $this->hasMany(TicketAttachment::class); }

    public function getRouteKey(): string
    {
        return rtrim(strtr(base64_encode(Crypt::encryptString((string) $this->getKey())), '+/', '-_'), '=');
    }

    public function resolveRouteBinding($value, $field = null): ?self
    {
        try {
            $encoded = strtr((string) $value, '-_', '+/');
            $encoded .= str_repeat('=', (4 - strlen($encoded) % 4) % 4);
            $payload = base64_decode($encoded, true);
            if ($payload === false) {
                return null;
            }
            $id = Crypt::decryptString($payload);
        } catch (DecryptException|\ValueError) {
            return null;
        }

        return $this->where($field ?: $this->getRouteKeyName(), $id)->first();
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isCustomer()) {
            return $query->where('created_by', $user->id);
        }

        if ($user->isAgent()) {
            return $query->where(function (Builder $query) use ($user) {
                $query->where('assigned_to', $user->id)
                    ->orWhereNull('assigned_to');
            });
        }

        return $query;
    }
}
