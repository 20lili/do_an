<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Mục 5.8 – Quản lý tài khoản người dùng (FR02). Chỉ Quản trị viên (Gate manage-users).
 * Không xóa tài khoản vì tài khoản gắn với đơn hàng và giao dịch kho đã lập; thay vào đó là KHÓA tài khoản.
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::withCount(['orders', 'inventoryTransactions'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $kw = '%' . trim($request->q) . '%';
                $q->where(fn ($w) => $w->where('name', 'like', $kw)->orWhere('username', 'like', $kw));
            })
            ->when(array_key_exists($request->role, User::ROLES), fn ($q) => $q->where('role', $request->role))
            ->orderByDesc('is_active')->orderBy('role')->orderBy('name')
            ->get();

        $counts = User::selectRaw('role, COUNT(*) as total')->groupBy('role')->pluck('total', 'role');

        return view('users.index', compact('users', 'counts'));
    }

    public function create(): View
    {
        return view('users.create', ['user' => new User(['role' => User::STAFF, 'is_active' => true])]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $data = $this->payload($request);
        $user = User::create($data + ['is_active' => true]);

        return redirect()->route('users.index')->with('success', 'Đã tạo tài khoản "' . $user->username . '".');
    }

    public function edit(User $user): View
    {
        return view('users.edit', compact('user'));
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $data = $this->payload($request);

        // Không tự hạ quyền của chính mình (tránh mất quyền quản trị)
        if ($user->is($request->user()) && $data['role'] !== User::ADMIN) {
            return back()->withInput()->withErrors(['role' => 'Bạn không thể tự thay đổi vai trò của chính mình.']);
        }
        // Hệ thống luôn còn ít nhất một quản trị viên đang hoạt động
        if ($user->isAdmin() && $data['role'] !== User::ADMIN && $this->activeAdmins() <= 1) {
            return back()->withInput()->withErrors(['role' => 'Phải còn ít nhất một quản trị viên đang hoạt động.']);
        }

        if (blank($data['password'] ?? null)) {
            unset($data['password']);               // để trống = giữ mật khẩu cũ
        }
        $user->update($data);

        return redirect()->route('users.index')->with('success', 'Đã cập nhật tài khoản "' . $user->username . '".');
    }

    /** Khóa / mở khóa tài khoản */
    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Bạn không thể khóa tài khoản đang đăng nhập.');
        }
        if ($user->is_active && $user->isAdmin() && $this->activeAdmins() <= 1) {
            return back()->with('error', 'Phải còn ít nhất một quản trị viên đang hoạt động.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', ($user->is_active ? 'Đã mở khóa' : 'Đã khóa') . ' tài khoản "' . $user->username . '".');
    }

    /** Dữ liệu ghi vào CSDL: quản trị viên có mọi quyền nên không cần lưu quyền bổ sung */
    private function payload(UserRequest $request): array
    {
        $data = $request->safe()->only(['name', 'username', 'email', 'role', 'password']);
        $data['permissions'] = $data['role'] === User::ADMIN ? [] : $request->validated('permissions', []);

        return $data;
    }

    private function activeAdmins(): int
    {
        return User::where('role', User::ADMIN)->where('is_active', true)->count();
    }
}
