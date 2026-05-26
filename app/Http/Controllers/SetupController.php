<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class SetupController extends Controller
{
    public function create(): RedirectResponse|View
    {
        if (User::query()->exists()) {
            return redirect()->route('login')->with('status', 'Setup is al uitgevoerd.');
        }

        return view('auth.setup');
    }

    public function store(Request $request): RedirectResponse
    {
        if (User::query()->exists()) {
            return redirect()->route('login')->with('status', 'Setup is al uitgevoerd.');
        }

        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::query()->create($attributes);

        $adminRole = Role::query()->firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $user->assignRole($adminRole);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
