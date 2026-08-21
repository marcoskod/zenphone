<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Creates (or promotes, if it already exists) a single admin@zensms.test account with
     * a freshly generated random password, printed once to the console. Deliberately
     * gated to local/testing - this must never run against a real/production database,
     * and never leaves a guessable default password lying around: run it again to get a
     * new random password if the old one is lost.
     *
     * php artisan db:seed --class=Database\\Seeders\\AdminUserSeeder
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->error('AdminUserSeeder only runs in local/testing environments - refusing to run here.');

            return;
        }

        $password = Str::password(20);

        $admin = User::updateOrCreate(
            ['email' => 'admin@zensms.test'],
            [
                'name' => 'Admin Zen_Sms',
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ],
        );

        $admin->is_admin = true;
        $admin->is_suspended = false;
        $admin->save();

        $this->command?->info('Admin account ready:');
        $this->command?->info("  email:    {$admin->email}");
        $this->command?->info("  password: {$password}");
        $this->command?->warn('This password is only shown once - store it now or re-run the seeder to reset it.');
    }
}
