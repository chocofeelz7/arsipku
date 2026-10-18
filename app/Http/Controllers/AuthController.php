<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Hash};
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function login() { return view('auth.login'); }

    public function authenticate(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $data['email'] = mb_strtolower(trim($data['email']));
        if (! Auth::attempt([...$data, 'is_active' => true])) {
            return back()->withErrors(['email' => 'Email atau kata sandi tidak sesuai, atau akun tidak aktif.'])->onlyInput('email');
        }
        $request->session()->regenerate();
        Activity::record($request->user(), 'Masuk aplikasi');
        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Activity::record($request->user(), 'Keluar aplikasi');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    public function password() { return view('auth.password'); }

    public function updatePassword(Request $request)
    {
        $data = $request->validate(['current_password' => ['required', 'current_password'], 'password' => ['required', 'confirmed', 'different:current_password', Password::min(12)->letters()->numbers()]]);
        $request->user()->forceFill(['password' => Hash::make($data['password']), 'must_change_password' => false])->save();
        // Revoke other browser sessions after a password change.
        if (config('session.driver') === 'database') {
            \Illuminate\Support\Facades\DB::table('sessions')->where('user_id', $request->user()->id)->where('id', '!=', $request->session()->getId())->delete();
        }
        $request->session()->regenerate();
        Activity::record($request->user(), 'Kata sandi diubah');
        return redirect()->route('dashboard')->with('success', 'Kata sandi berhasil diperbarui.');
    }
}
