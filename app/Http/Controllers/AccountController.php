<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function show()
    {
        $user = Auth::user();

        return $this->resolveProfileView($user, compact('user'));
    }

    public function edit()
    {
        $user = Auth::user();

        return $this->resolveProfileView($user, compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'full_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $user->update($validated);

        return redirect()
            ->route('user.account.detail')
            ->with('success', 'Cập nhật thông tin cá nhân thành công.');
    }

    public function resetPassword(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()
            ->route('user.account.general')
            ->with('success', 'Đã đặt lại mật khẩu thành công.');
    }

    private function resolveProfileView($user, array $data)
    {
        if ($user->role === 'ADMIN') {
            return view('user.account.profile', $data);
        }

        if ($user->role === 'STAFF') {
            return view('user.account.staff-profile', $data);
        }

        return view('user.account.customer-profile', $data);
    }
}