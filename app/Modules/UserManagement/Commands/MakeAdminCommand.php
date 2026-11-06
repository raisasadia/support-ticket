<?php

namespace App\Modules\UserManagement\Commands;

use App\Models\User;
use App\Models\Role;
use Illuminate\Console\Command;

class MakeAdminCommand extends Command
{
    protected $signature = 'users:make-admin {email : The existing user email address}';
    protected $description = 'Promote an existing user to administrator';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (!$user) {
            $this->error('No user was found with that email address.');

            return self::FAILURE;
        }

        $role = Role::where('role_name', 'admin')->firstOrFail();
        $user->forceFill([
            'role_id' => $role->id,
            'is_active' => true,
        ])->save();

        $this->info("{$user->email} is now an active administrator.");

        return self::SUCCESS;
    }
}
