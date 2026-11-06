<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = ['role_name', 'name', 'type'];

    protected $table = 'roles';

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role_id');
    }
}
