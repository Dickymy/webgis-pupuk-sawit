<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class SettingController extends Controller
{
    public function index()
    {
        $admin = Auth::guard('admin')->user();

        return view('settings.index', compact('admin'));
    }

    public function updatePassword(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        $request->validate([
            'password_lama' => ['required', 'string', 'current_password:admin'],
            'password_baru' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)->letters()->numbers(),
                'different:password_lama',
            ],
            'password_baru_confirmation' => ['required', 'string'],
        ], [
            'password_lama.required' => 'Password lama wajib diisi.',
            'password_lama.current_password' => 'Password lama yang Anda masukkan salah.',
            'password_baru.required' => 'Password baru wajib diisi.',
            'password_baru.confirmed' => 'Konfirmasi password baru tidak cocok.',
            'password_baru.different' => 'Password baru tidak boleh sama dengan password lama.',
        ]);

        $admin->update([
            'password' => Hash::make($request->password_baru),
        ]);

        $request->session()->regenerateToken();

        return redirect()->route('settings.index')
            ->with('success', 'Password berhasil diperbarui. Gunakan password baru saat login berikutnya.');
    }

    public function updateTheme(Request $request)
    {
        $validated = $request->validate([
            'tema' => ['required', 'in:light,dark,system'],
        ]);

        $admin = Auth::guard('admin')->user();
        $admin->update(['tema' => $validated['tema']]);

        // Cookie diset oleh JS (theme.js applyTheme), server hanya simpan ke DB
        return response()->json(['success' => true, 'tema' => $validated['tema']]);
    }
}
