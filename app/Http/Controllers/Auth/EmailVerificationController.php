<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function notice(): View
    {
        return view('auth.verify-email');
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended($this->verifiedRedirect($request));
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect()->intended($this->verifiedRedirect($request))->with('status', 'Je e-mailadres is bevestigd.');
    }

    public function send(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended($this->verifiedRedirect($request));
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'Verificatielink opnieuw verzonden.');
    }

    private function verifiedRedirect(Request $request): string
    {
        return $request->user()->hasRole('entertainer')
            ? route('dashboard')
            : route('customer-portal.index');
    }
}
