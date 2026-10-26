@extends('layouts.app')
@section('title', 'お知らせ | Forge')
@section('description', 'Forgeからのお知らせ、テーマのアップデート情報。')
@section('no_loader', '1')
@section('content')
<section class="section"><div class="wrap news-page">
  <p class="eyebrow">NEWS</p>
  <h1>お知らせ</h1>
  <p class="auth-lead">テーマのアップデートや、サービスからのお知らせをお届けします。</p>
  @if($posts->isEmpty())
    <p class="auth-lead">現在お知らせはありません。</p>
  @else
    @include('news._list', ['posts' => $posts])
    @if($posts->hasPages())
      <nav class="news-pager" aria-label="ページ送り">
        @if($posts->previousPageUrl())<a href="{{ $posts->previousPageUrl() }}" rel="prev">← 新しいお知らせ</a>@endif
        <span>{{ $posts->currentPage() }} / {{ $posts->lastPage() }}</span>
        @if($posts->nextPageUrl())<a href="{{ $posts->nextPageUrl() }}" rel="next">過去のお知らせ →</a>@endif
      </nav>
    @endif
  @endif
</div></section>
@endsection
