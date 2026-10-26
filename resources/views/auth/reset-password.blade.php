@extends('layouts.app')
@section('title', 'パスワードの設定 | Forge')
@section('description', '新しいパスワードを設定します。')
@section('no_loader', '1')
@section('content')
<section class="section"><div class="wrap auth-card">
  <p class="eyebrow">PASSWORD</p>
  <h1>パスワードの設定</h1>
  <p class="auth-lead">10文字以上のパスワードを設定してください。</p>
  <form method="post" action="{{ route('password.update') }}" class="auth-form">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <label>メールアドレス<input type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="username"></label>
    @error('email')<p class="auth-error" role="alert">{{ $message }}</p>@enderror
    <label>新しいパスワード<input type="password" name="password" required minlength="10" autocomplete="new-password" autofocus></label>
    @error('password')<p class="auth-error" role="alert">{{ $message }}</p>@enderror
    <label>新しいパスワード（確認）<input type="password" name="password_confirmation" required minlength="10" autocomplete="new-password"></label>
    <button type="submit" class="button button-dark">パスワードを設定する <span>↗</span></button>
  </form>
</div></section>
@endsection
