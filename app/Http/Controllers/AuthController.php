<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $attemptKey = 'login:'.hash('sha256', strtolower($credentials['email']).'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($attemptKey, 5)) {
            throw ValidationException::withMessages(['email' => 'Demasiados intentos de inicio de sesión. Vuelva a intentarlo en un minuto.']);
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            if (! $user->is_active) {
                Auth::logout();
                RateLimiter::hit($attemptKey);
                throw ValidationException::withMessages([
                    'email' => 'Su cuenta se encuentra desactivada por la administración.',
                ]);
            }

            RateLimiter::clear($attemptKey);
            $user->update(['last_login_at' => now()]);
            $request->session()->regenerate();

            AuditLog::log('login', "Inicio de sesión de {$user->name} ({$user->email})");

            return redirect()->intended(route('dashboard'));
        }

        RateLimiter::hit($attemptKey);

        throw ValidationException::withMessages([
            'email' => 'Las credenciales proporcionadas no coinciden con nuestros registros.',
        ]);
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            AuditLog::log('logout', "Cierre de sesión de {$user->name}");
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Sesión cerrada correctamente.');
    }
}
