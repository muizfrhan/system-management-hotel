<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class StaffController extends Controller
{
    public function index(): JsonResponse
    {
        $staff = User::latest()->paginate(10);

        return response()->json($staff);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:254|unique:users',
            'password' => 'required|string|min:12|max:1024',
            'role' => 'required|in:admin,receptionist,housekeeper',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $user = User::create($validated);

        return response()->json($user, 201);
    }

    public function update(Request $request, User $staff): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:254|unique:users,email,'.$staff->id,
            'password' => 'nullable|string|min:12|max:1024',
            'role' => 'sometimes|in:admin,receptionist,housekeeper',
        ]);

        $roleChanged = isset($validated['role']) && $validated['role'] !== $staff->role;
        $passwordChanged = isset($validated['password']);

        if ($roleChanged && $staff->isAdmin() && User::where('role', 'admin')->count() <= 1) {
            throw new ConflictHttpException('Admin terakhir tidak dapat diubah perannya.');
        }

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $staff->update($validated);

        if ($roleChanged || $passwordChanged) {
            DB::table('sessions')->where('user_id', $staff->id)->delete();
        }

        return response()->json($staff);
    }

    public function destroy(User $staff): JsonResponse
    {
        if ($staff->id === request()->user()?->id) {
            throw new ConflictHttpException('Akun yang sedang digunakan tidak dapat dihapus.');
        }

        if ($staff->isAdmin() && User::where('role', 'admin')->count() <= 1) {
            throw new ConflictHttpException('Admin terakhir tidak dapat dihapus.');
        }

        DB::table('sessions')->where('user_id', $staff->id)->delete();
        $staff->delete();

        return response()->json(['message' => 'Staf berhasil dihapus.']);
    }
}
