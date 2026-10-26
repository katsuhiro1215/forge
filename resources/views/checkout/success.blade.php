@extends('layouts.app')

@section('title', 'ご購入ありがとうございます | Forge')
@section('description', 'ご購入手続きが完了しました。')

@section('no_loader', '1')
@section('content')
<section class="section"><div class="wrap" style="max-width:720px;padding:80px 0">
  <p class="eyebrow">THANK YOU</p>
  <h1 style="font:400 36px/1.5 Georgia,serif;margin:20px 0">ご購入ありがとうございます。</h1>
  <p style="line-height:2">ご入力いただいたメールアドレスに、購入完了のメールをお送りします。<br>
  メールの案内にしたがってアカウントページからテーマをダウンロードしてください。</p>
  <p style="line-height:2;color:var(--muted)">数分たってもメールが届かない場合は、迷惑メールフォルダをご確認ください。</p>
  <p style="margin-top:40px"><a class="text-link" href="{{ route('home') }}">トップへ戻る <span class="arrow circle">↗</span></a></p>
</div></section>
@endsection
