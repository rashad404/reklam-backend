<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class BootstrapAdmin extends Command
{
    protected $signature = 'product:bootstrap-admin {email}';

    protected $description = 'Create a dedicated administrator without changing existing user privileges';

    public function handle(): int
    {
        $email = $this->argument('email');
        $password = env('REKLAM_ADMIN_PASSWORD');
        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password ?? '') < 24) {
            $this->error('A valid email and REKLAM_ADMIN_PASSWORD of at least 24 characters are required.');

            return self::FAILURE;
        }
        if (User::where('email', $email)->exists()) {
            $this->error('Account already exists. No account was changed.');

            return self::FAILURE;
        }
        User::create(['email' => $email, 'name' => 'Reklam.biz administrator', 'password' => $password, 'is_admin' => true]);
        $this->info('Dedicated administrator created.');

        return self::SUCCESS;
    }
}
