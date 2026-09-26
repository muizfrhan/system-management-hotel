<?php

use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->append(SecurityHeaders::class);
        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $exception): bool => $request->is('api/*') || $request->expectsJson()
        );

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'Autentikasi diperlukan.'], 401);
            }
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            $status = $exception->getStatusCode();
            $message = match ($status) {
                401 => 'Autentikasi diperlukan.',
                403 => 'Akses ditolak.',
                404 => 'Endpoint tidak ditemukan.',
                405 => 'Metode tidak diizinkan.',
                419 => 'Sesi kedaluwarsa. Silakan muat ulang halaman.',
                default => $status >= 500 ? 'Terjadi kesalahan pada server.' : ($exception->getMessage() ?: 'Permintaan tidak dapat diproses.'),
            };

            if ($status >= 500) {
                report($exception);
            }

            return response()->json(['message' => $message], $status, $exception->getHeaders());
        });

        $exceptions->render(function (Throwable $exception, Request $request) {
            $isApiRequest = $request->is('api/*') || $request->expectsJson();
            $isHttpException = $exception instanceof HttpExceptionInterface;

            if ($isApiRequest && ! $isHttpException && ! $exception instanceof AuthenticationException && ! $exception instanceof ValidationException) {
                report($exception);

                return response()->json(['message' => 'Terjadi kesalahan pada server.'], 500);
            }
        });
    })->create();
