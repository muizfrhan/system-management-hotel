<?php

namespace App\Services;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class AuthService
{
    private const DECAY_SECONDS = 900;

    public function attempt(string $email, string $password): array
    {
        if (! request()->hasSession()) {
            throw new AuthenticationException('Sesi autentikasi tidak tersedia.');
        }

        $email = mb_strtolower(trim($email));
        $this->checkRateLimit($email);

        if (! Auth::guard('web')->attempt(['email' => $email, 'password' => $password])) {
            RateLimiter::hit($this->ipThrottleKey($email), self::DECAY_SECONDS);
            RateLimiter::hit($this->accountThrottleKey($email), self::DECAY_SECONDS);

            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        $user = Auth::guard('web')->user();

        if (! in_array($user->role, ['admin', 'receptionist', 'housekeeper'], true)) {
            Auth::guard('web')->logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            throw new AccessDeniedHttpException('Akun tidak memiliki akses portal staf.');
        }

        RateLimiter::clear($this->ipThrottleKey($email));
        RateLimiter::clear($this->accountThrottleKey($email));
        request()->session()->regenerate();

        return [
            'user' => $user->only(['id', 'name', 'email', 'role']),
            'message' => 'Login berhasil.',
        ];
    }

    public function logout(): void
    {
        Auth::guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }

    protected function checkRateLimit(string $email): void
    {
        foreach ([[$this->ipThrottleKey($email), 5], [$this->accountThrottleKey($email), 20]] as [$key, $maxAttempts]) {
            if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
                $seconds = RateLimiter::availableIn($key);

                throw new TooManyRequestsHttpException(
                    $seconds,
                    "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.",
                    headers: ['Retry-After' => (string) $seconds],
                );
            }
        }
    }

    protected function ipThrottleKey(string $email): string
    {
        return 'login-ip|'.hash('sha256', $email).'|'.request()->ip();
    }

    protected function accountThrottleKey(string $email): string
    {
        return 'login-account|'.hash('sha256', $email);
    }
}
