@extends('layouts.app')
@section('no_loader', '1')
@if(! config('forge.legal.reviewed'))
  @section('robots', 'noindex,nofollow')
@endif
@section('content')
<article class="section"><div class="wrap news-article legal">
  @unless(config('forge.legal.reviewed'))
    <p class="draft-banner" role="note"><strong>草案</strong>　このページの内容は確認中のため、公開時に変更される場合があります。</p>
  @endunless
  <p class="eyebrow">LEGAL</p>
  <h1>@yield('heading')</h1>
  <div class="prose">@yield('legal')</div>
  <p class="legal-date">
    @if(config('forge.legal.effective_date'))
      制定日: {{ \Illuminate\Support\Carbon::parse(config('forge.legal.effective_date'))->format('Y年n月j日') }}
    @else
      制定日: 公開日に定めます
    @endif
  </p>
  <nav class="legal-nav" aria-label="法務ページ">
    <a href="{{ route('legal.tokushoho') }}">特定商取引法に基づく表記</a>
    <a href="{{ route('legal.terms') }}">利用規約</a>
    <a href="{{ route('legal.privacy') }}">プライバシーポリシー</a>
    <a href="{{ route('legal.refund') }}">返金ポリシー</a>
  </nav>
</div></article>
@endsection
