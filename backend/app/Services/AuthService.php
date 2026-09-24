<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function attempt(string $email, string $password): array
    {
        $this->checkRateLimit($email);

        if (!Auth::attempt(['email' => $email, 'password' => $password])) {
            RateLimiter::hit($this->throttleKey($email), 900);
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        RateLimiter::clear($this->throttleKey($email));
        request()->session()->regenerate();

        return [
            'user' => Auth::user(),
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
        if (RateLimiter::tooManyAttempts($this->throttleKey($email), 5)) {
            $seconds = RateLimiter::availableIn($this->throttleKey($email));
            throw ValidationException::withMessages([
                'email' => ["Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik."],
            ]);
        }
    }

    protected function throttleKey(string $email): string
    {
        return 'login|' . $email . '|' . request()->ip();
    }
}
