<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * CRUD for admin users. Super admin only.
 */
class AdminUserController extends Controller
{
    /**
     * List all admin users.
     */
    public function index(): View
    {
        $users = User::orderBy('is_super_admin', 'desc')
            ->orderBy('name')
            ->get();

        return view('admin.admins.index', compact('users'));
    }

    /**
     * Show create form.
     */
    public function create(): View
    {
        return view('admin.admins.form', ['user' => null]);
    }

    /**
     * Store a new admin.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:64', 'unique:users,username'],
            'email' => ['nullable', 'email', 'max:255'],
            'whatsapp_phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', 'in:admin,editor,writer'],
        ]);
        $data['whatsapp_phone'] = $data['whatsapp_phone'] ? normalizeUkWhatsAppPhone($data['whatsapp_phone']) : null;

        $data['password'] = Hash::make($data['password']);
        unset($data['password_confirmation']);
        $data['is_super_admin'] = false;

        User::create($data);

        return redirect()->route('admin.admins.index')
            ->with('success', __('app.admin_created'));
    }

    /**
     * Show a single admin.
     */
    public function show(User $admin): View
    {
        $this->guardSuperAdmin($admin);

        return view('admin.admins.show', compact('admin'));
    }

    /**
     * Show edit form.
     */
    public function edit(User $admin): View
    {
        $this->guardSuperAdmin($admin);

        return view('admin.admins.form', ['user' => $admin]);
    }

    /**
     * Update an admin.
     */
    public function update(Request $request, User $admin): RedirectResponse
    {
        $this->guardSuperAdmin($admin);

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:64', 'unique:users,username,'.$admin->id],
            'email' => ['nullable', 'email', 'max:255'],
            'whatsapp_phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', 'in:admin,editor,writer'],
        ];

        if ($request->filled('password')) {
            $rules['password'] = ['required', 'confirmed', Password::defaults()];
        }

        $data = $request->validate($rules);
        $data['whatsapp_phone'] = ! empty($data['whatsapp_phone'])
            ? normalizeUkWhatsAppPhone($data['whatsapp_phone'])
            : null;

        if ($request->filled('password')) {
            $data['password'] = Hash::make($data['password']);
        }
        unset($data['password_confirmation']);

        $admin->update($data);

        return redirect()->route('admin.admins.index')
            ->with('success', __('app.admin_updated'));
    }

    /**
     * Delete an admin.
     */
    public function destroy(Request $request, User $admin): RedirectResponse
    {
        $this->guardSuperAdmin($admin);

        if ($admin->is_super_admin) {
            abort(403, 'Cannot delete the super admin.');
        }

        $admin->delete();

        return redirect()->route('admin.admins.index')
            ->with('success', __('app.admin_deleted'));
    }

    /**
     * Prevent editing/deleting the super admin (except by themselves for limited edits).
     */
    private function guardSuperAdmin(User $admin): void
    {
        if ($admin->is_super_admin && $admin->id !== auth()->id()) {
            throw new AuthorizationException('Cannot modify the super admin account.');
        }
    }
}
