<?php

namespace App\Http\Controllers;

use App\Actions\BootstrapPlatform;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use RuntimeException;

class SetupController extends Controller
{
    public function create(Request $request): RedirectResponse|View
    {
        $this->authorizeSetup($request);

        if (User::query()->exists()) {
            return redirect()->route('login')->with('status', 'Setup is al uitgevoerd.');
        }

        return view('auth.setup', [
            'setupToken' => $request->query('token'),
        ]);
    }

    public function store(Request $request, BootstrapPlatform $bootstrap): RedirectResponse
    {
        $this->authorizeSetup($request);

        if (User::query()->exists()) {
            return redirect()->route('login')->with('status', 'Setup is al uitgevoerd.');
        }

        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $user = $bootstrap->createFirstAdmin(
                $attributes['name'],
                $attributes['email'],
                $attributes['password'],
            );
        } catch (RuntimeException $exception) {
            return redirect()->route('login')->with('status', $exception->getMessage());
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    private function authorizeSetup(Request $request): void
    {
        if (! app()->isProduction()) {
            return;
        }

        $expected = (string) config('app.setup_token');
        $provided = (string) $request->input('token', $request->query('token'));

        if ($expected === '' || ! hash_equals($expected, $provided)) {
            abort(404);
        }
    }
}
