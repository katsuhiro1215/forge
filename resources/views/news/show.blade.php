@extends('layouts.app')
@section('title', $post->title.' | Forge')
@section('description', $post->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($post->bodyHtml()), 120))
@section('no_loader', '1')
@section('content')
<article class="section"><div class="wrap news-article">
  <p class="eyebrow"><a href="{{ route('news.index') }}">NEWS</a></p>
  <h1>{{ $post->title }}</h1>
  <p class="news-meta">
    <time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->format('Y年n月j日') }}</time>
    <span class="news-type news-type-{{ $post->type }}">{{ $post->typeLabel() }}</span>
    @if($post->product)<a href="{{ route('theme.show', $post->product) }}">{{ $post->product->name }}</a>@endif
  </p>
  <div class="prose">{{ $post->bodyHtml() }}</div>
  <p class="news-back"><a class="text-link" href="{{ route('news.index') }}">お知らせ一覧へ <span class="arrow circle">↗</span></a></p>
</div></article>
@endsection
