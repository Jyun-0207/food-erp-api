<?php

namespace App\Http\Controllers;

use App\Helpers\PermissionHelper;
use App\Http\Requests\UserRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('role')) {
            $query->where('role', $request->input('role'));
        }

        $users = $query->orderBy('createdAt', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json($users);
    }

    public function store(UserRequest $request)
    {
        $user = new User();
        $user->fill($request->validated());
        $user->password = Hash::make($request->password);
        $user->role = $request->input('role', 'staff');
        $user->permissions = $request->input('permissions') ?? PermissionHelper::getDefaultPermissions($user->role);
        $user->save();

        return response()->json($user, 201);
    }

    public function show(string $id)
    {
        $user = User::findOrFail($id);

        return response()->json($user);
    }

    public function update(UserUpdateRequest $request, string $id)
    {
        $user = User::findOrFail($id);
        $data = $request->validated();

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        if (isset($data['role'])) {
            $user->role = $data['role'];
            unset($data['role']);
        }
        if (array_key_exists('permissions', $data)) {
            $user->permissions = $data['permissions'];
            unset($data['permissions']);
        }
        $user->fill($data)->save();

        return response()->json($user);
    }

    public function destroy(Request $request, string $id)
    {
        if ($request->user()?->id === $id) {
            return response()->json(['message' => '不可刪除自己的帳號'], 403);
        }

        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => '刪除成功']);
    }
}
