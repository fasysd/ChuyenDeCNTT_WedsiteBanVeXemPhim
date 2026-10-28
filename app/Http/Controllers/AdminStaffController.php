<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminStaffController extends Controller
{
    public function index()
    {
        $staffMembers = User::query()
            ->where('role', 'STAFF')
            ->latest()
            ->get();

        return view('admin.staff.index', compact('staffMembers'));
    }

    public function create()
    {
        $staff = new User([
            'role' => 'STAFF',
        ]);

        return view('admin.staff.form', [
            'staff' => $staff,
            'title' => 'Thêm nhân viên mới',
            'action' => route('admin.staff.store'),
            'method' => 'POST',
            'isEdit' => false,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'full_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'username.required' => 'Vui lòng nhập tên đăng nhập.',
            'username.unique' => 'Tên đăng nhập đã tồn tại.',
            'email.required' => 'Vui lòng nhập email.',
            'email.unique' => 'Email đã được sử dụng.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.min' => 'Mật khẩu phải từ 6 ký tự trở lên.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
        ]);

        User::create([
            'username' => $validated['username'],
            'email' => $validated['email'],
            'full_name' => $validated['full_name'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => 'STAFF',
        ]);

        return redirect()
            ->route('admin.staff.index')
            ->with('success', 'Đã thêm nhân viên mới thành công.');
    }

    public function edit(User $staff)
    {
        $this->ensureStaff($staff);

        return view('admin.staff.form', [
            'staff' => $staff,
            'title' => 'Sửa thông tin nhân viên',
            'action' => route('admin.staff.update', $staff),
            'method' => 'PUT',
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, User $staff)
    {
        $this->ensureStaff($staff);

        $validated = $request->validate([
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'username')->ignore($staff->id),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($staff->id),
            ],
            'full_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
        ], [
            'username.required' => 'Vui lòng nhập tên đăng nhập.',
            'username.unique' => 'Tên đăng nhập đã tồn tại.',
            'email.required' => 'Vui lòng nhập email.',
            'email.unique' => 'Email đã được sử dụng.',
        ]);

        $staff->update($validated);

        return redirect()
            ->route('admin.staff.index')
            ->with('success', 'Đã cập nhật thông tin nhân viên.');
    }

    public function resetPassword(Request $request, User $staff)
    {
        $this->ensureStaff($staff);

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'password.required' => 'Vui lòng nhập mật khẩu mới.',
            'password.min' => 'Mật khẩu phải từ 6 ký tự trở lên.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
        ]);

        $staff->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()
            ->route('admin.staff.edit', $staff)
            ->with('success', 'Đã đặt lại mật khẩu cho nhân viên.');
    }

    private function ensureStaff(User $staff): void
    {
        abort_unless($staff->role === 'STAFF', 404);
    }
}
