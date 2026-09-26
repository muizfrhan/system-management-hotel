<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|string|max:254|email',
            'password' => 'required|string|max:1024',
        ]);

        $result = $this->authService->attempt($request->email, $request->password);

        return response()->json($result);
    }

    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return response()->json(['message' => 'Logout berhasil.']);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json($request->user()?->only(['id', 'name', 'email', 'role']));
    }
}
