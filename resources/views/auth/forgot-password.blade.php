@extends('layouts.app')
@section('title', 'パスワードの再設定 | Forge')
@section('description', 'パスワードを再設定します。')
@section('no_loader', '1')
@section('content')
<section class="section"><div class="wrap auth-card">
  <p class="eyebrow">PASSWORD</p>
  <h1>パスワードの再設定</h1>
  <p class="auth-lead">ご登録のメールアドレスを入力してください。再設定用のリンクをお送りします。</p>
  @if(session('status'))<p class="auth-status" role="status">{{ session('status') }}</p>@endif
  <form method="post" action="{{ route('password.email') }}" class="auth-form">
    @csrf
    <label>メールアドレス<input type="email" name="email" value="{{ old('email') }}" required autocomplete="username" autofocus></label>
    @error('email')<p class="auth-error" role="alert">{{ $message }}</p>@enderror
    <button type="submit" class="button button-dark">再設定リンクを送る <span>↗</span></button>
  </form>
  <p class="auth-sub"><a href="{{ route('login') }}">ログインに戻る</a></p>
</div></section>
@endsection
