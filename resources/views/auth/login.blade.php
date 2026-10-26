@extends('layouts.app')
@section('title', 'ログイン | Forge')
@section('description', 'Forgeのアカウントにログインします。')
@section('no_loader', '1')
@section('content')
<section class="section"><div class="wrap auth-card">
  <p class="eyebrow">LOGIN</p>
  <h1>ログイン</h1>
  <p class="auth-lead">ご購入時のメールアドレスでログインできます。初めての方は、購入完了メールのリンクからパスワードを設定してください。</p>
  @if(session('status'))<p class="auth-status" role="status">{{ session('status') }}</p>@endif
  <form method="post" action="{{ route('login') }}" class="auth-form">
    @csrf
    <label>メールアドレス<input type="email" name="email" value="{{ old('email') }}" required autocomplete="username" autofocus></label>
    @error('email')<p class="auth-error" role="alert">{{ $message }}</p>@enderror
    <label>パスワード<input type="password" name="password" required autocomplete="current-password"></label>
    <label class="auth-check"><input type="checkbox" name="remember" value="1"> ログインしたままにする</label>
    <button type="submit" class="button button-dark">ログイン <span>↗</span></button>
  </form>
  <p class="auth-sub"><a href="{{ route('password.request') }}">パスワードをお忘れの方</a></p>
</div></section>
@endsection
