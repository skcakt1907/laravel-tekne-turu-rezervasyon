<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

/**
 * Müşteri şifre sıfırlama. Panel tarafında Filament'in kendi akışı var;
 * site tarafında olan bu.
 */
class PasswordResetController extends Controller
{
    public function requestForm()
    {
        return view('account.forgot-password');
    }

    public function sendLink(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink($request->only('email'));

        // Kayıtlı olmayan e-posta için de aynı mesaj: hangi adreslerin sistemde
        // olduğu dışarıdan anlaşılmasın.
        return back()->with('status', __('site.password.sent'));
    }

    public function resetForm(Request $request, string $token)
    {
        return view('account.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PasswordReset) {
            return back()->withErrors(['email' => __($status)]);
        }

        return redirect()->to(lroute('account.login'))->with('status', __('site.password.reset_done'));
    }
}
