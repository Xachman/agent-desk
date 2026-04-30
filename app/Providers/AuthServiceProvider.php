<?php

namespace App\Providers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy uses for authorization.
     *
     * @var array<string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function register(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
    }

    /**
     * Bootstrap any authentication / authorization services.
     *
     * @throws AuthorizationException
     */
    public function boot(): void
    {
        $this->registerPolicies();

        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            return (new \Illuminate\Notifications\Messages\MailMessage)
                ->subject('Verify Email Address')
                ->line('Click the button below to verify your email address.')
                ->action('Verify Email Address', $url);
        });

        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            return (new \Illuminate\Notifications\Messages\MailMessage)
                ->subject('Reset Your Password')
                ->line('You are receiving this email because we received a password reset request for your account.')
                ->action('Reset Password', url(route('password.reset', $token, false)))
                ->line('If you did not request a password reset, no further action is required.');
        });
    }
}
