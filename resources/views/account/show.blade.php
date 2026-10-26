@extends('layouts.app')
@section('title', 'アカウント | Forge')
@section('description', '購入履歴とダウンロード')
@section('no_loader', '1')
@section('content')
<section class="section"><div class="wrap account">
  <div class="account-head">
    <div><p class="eyebrow">MY ACCOUNT</p><h1>アカウント</h1><p class="auth-lead">{{ $user->name }} 様（{{ $user->email }}）</p></div>
    <form method="post" action="{{ route('logout') }}">@csrf<button type="submit" class="quiet-link">ログアウト <span>→</span></button></form>
  </div>

  @forelse($licenses as $license)
    <article class="license-card">
      <div class="license-top">
        <div><h2>{{ $license->product->name }}</h2><p>{{ $license->plan->name }}（{{ $license->site_limit ? $license->site_limit.'サイト' : '無制限' }}）</p></div>
        <span class="pill {{ $license->isActive() ? '' : 'pill-off' }}">{{ $license->isActive() ? '有効' : '無効' }}</span>
      </div>
      <dl class="license-meta">
        <div><dt>ご購入日</dt><dd>{{ $license->order->paid_at->format('Y年n月j日') }}（¥{{ number_format($license->order->amount_jpy) }}）</dd></div>
        <div><dt>無償アップデート・サポート</dt><dd>{{ $license->updates_until->format('Y年n月j日') }} まで</dd></div>
      </dl>

      <h3>ダウンロード</h3>
      @php($list = $releases->get($license->product_id, collect()))
      @forelse($list as $release)
        <div class="release-row">
          <div><strong>v{{ $release->version }}</strong><span>{{ $release->released_at->format('Y年n月j日') }}公開</span>
            @if($release->changelog)<p>{{ $release->changelog }}</p>@endif</div>
          @if($license->canDownload($release))
            <a class="button button-dark" href="{{ route('account.download', $release) }}">ダウンロード <span>↓</span></a>
          @else
            <span class="release-locked">更新期間外のバージョンです（有償アップデート）</span>
          @endif
        </div>
      @empty
        <p class="auth-lead">ダウンロード可能なバージョンを準備中です。公開され次第、こちらに表示されます。</p>
      @endforelse
    </article>
  @empty
    <p class="auth-lead">ご購入履歴はまだありません。</p>
  @endforelse
</div></section>
@endsection
