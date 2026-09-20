<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeAdmin extends Command
{
    protected $signature = 'zensms:make-admin {email : Email of an existing account}';

    protected $description = 'Promote an existing account (register it on the site first) to administrator';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('No account with that email. Register it on the site first, then run this again.');

            return self::FAILURE;
        }

        $user->is_admin = true;
        $user->is_suspended = false;
        $user->save();

        $this->info("{$user->email} is now an administrator (/admin).");

        return self::SUCCESS;
    }
}
