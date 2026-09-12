<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class CheckCredentials extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auth:check
                            {email : The user email}
                            {password : The user password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify that the given email and password are correct';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $credentials = [
            'email' => $this->argument('email'),
            'password' => $this->argument('password'),
        ];

        if (Auth::validate($credentials)) {
            $this->info('Credentials are valid.');

            return self::SUCCESS;
        }

        $this->error('Invalid credentials.');

        return self::FAILURE;
    }
}
