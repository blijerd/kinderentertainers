<?php

namespace App\Http\Controllers\Auth;

use App\Actions\RegisterAccount;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request, RegisterAccount $registerAccount): RedirectResponse
    {
        $validated = $request->validate([
            'account_type' => ['required', 'in:klant,entertainer'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'city' => ['nullable', 'required_if:account_type,entertainer', 'string', 'max:255'],
            'region' => ['nullable', 'required_if:account_type,entertainer', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
        ]);

        $user = $registerAccount->handle($validated);

        Auth::login($user);

        return redirect()->route('verification.notice');
    }
}
