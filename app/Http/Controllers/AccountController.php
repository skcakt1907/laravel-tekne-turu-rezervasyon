<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Müşteri tarafı. Üyelik **isteğe bağlı**: rezervasyon talebi üyeliksiz gönderilir,
 * üye olan müşterinin geçmiş talepleri e-posta eşleşmesiyle hesabına bağlanır.
 */
class AccountController extends Controller
{
    public function loginForm()
    {
        return view('account.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($data, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __('site.account.failed')]);
        }

        $request->session()->regenerate();

        return redirect()->intended(lroute('account'));
    }

    public function registerForm()
    {
        return view('account.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:32'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'locale' => app()->getLocale(),
        ]);

        // Üyeliksiz gönderilmiş geçmiş talepleri hesaba bağla
        Reservation::where('customer_email', $user->email)
            ->whereNull('user_id')
            ->update(['user_id' => $user->id]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->to(lroute('account'))->with('status', __('site.account.welcome'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->to(lroute('home'));
    }

    /** Hesabım — geçmiş ve güncel rezervasyonlar tek listede. */
    public function index(Request $request)
    {
        $user = $request->user();

        $reservations = Reservation::query()
            ->with('yacht')
            ->where(fn ($q) => $q->where('user_id', $user->id)->orWhere('customer_email', $user->email))
            ->orderByDesc('starts_at')
            ->paginate(10);

        return view('account.index', compact('reservations'));
    }

    public function profile()
    {
        return view('account.profile');
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:32'],
            'whatsapp_no' => ['nullable', 'string', 'max:32'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'current_password' => ['required_with:password', 'nullable', 'string'],
        ]);

        if (filled($data['password'] ?? null)) {
            if (! Hash::check($data['current_password'], $user->password)) {
                return back()->withErrors(['current_password' => __('site.account.wrong_password')]);
            }

            $user->password = $data['password'];
        }

        $user->fill([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'whatsapp_no' => $data['whatsapp_no'] ?? null,
        ])->save();

        return back()->with('status', __('site.account.saved'));
    }
}
