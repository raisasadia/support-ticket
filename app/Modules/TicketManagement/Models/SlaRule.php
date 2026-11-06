<?php

namespace App\Modules\TicketManagement\Models;

use Illuminate\Database\Eloquent\Model;

class SlaRule extends Model
{
    protected $fillable = ['priority', 'resolution_minutes', 'business_days', 'business_start', 'business_end'];
    protected function casts(): array { return ['resolution_minutes' => 'integer']; }
}
