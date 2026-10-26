<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class PasswordController extends Controller
{
    public function request()
    {
        return view('auth.forgot-password');
    }

    public function email(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink($request->only('email'));

        // 登録の有無を悟られないよう、結果にかかわらず同じ表示にする
        return back()->with('status', 'ご登録のメールアドレスに、パスワード再設定のご案内をお送りしました。');
    }

    public function reset(Request $request, string $token)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(10)],
        ]);

        $status = Password::reset($data, function (User $user, string $password) {
            $user->forceFill([
                'password' => $password,
                'remember_token' => Str::random(60),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
            event(new PasswordReset($user));
        });

        if ($status !== Password::PasswordReset) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'このリンクは無効か、有効期限が切れています。再設定をやり直してください。']);
        }

        return redirect()->route('login')->with('status', 'パスワードを設定しました。ログインしてください。');
    }
}
