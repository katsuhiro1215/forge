<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="theme-color" content="#222520"><meta name="description" content="@yield('description')">
  <title>@yield('title', 'Forge')</title>
  @hasSection('robots')<meta name="robots" content="@yield('robots')">@endif
  <script>try{if(sessionStorage.getItem('forge-opening')){document.documentElement.classList.add('no-opening')}else{sessionStorage.setItem('forge-opening','1')}}catch(e){}</script>
  <link rel="icon" href="{{ asset('favicon/favicon.ico') }}" sizes="any">
  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon/favicon-32x32.png') }}">
  <link rel="apple-touch-icon" href="{{ asset('favicon/apple-touch-icon-180x180.png') }}">
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body @hasSection('body_class') class="@yield('body_class')" @endif>
  @unless(View::hasSection('no_loader'))
  <div class="loader" aria-hidden="true"><div class="loader-inner"><span class="loader-caption">CRAFTED FOR YOUR NEXT CHAPTER</span><span class="loader-word">Forge<span class="logo-dot">.</span></span><span class="loader-line"></span><span class="loader-bottom">WORDPRESS THEMES BY SMARTSPROUTS</span></div></div>
  @endunless
  <a href="#main" class="skip">本文へスキップ</a>
  <header class="site-header" id="top"><a class="logo" href="{{ route('home') }}" aria-label="Forge トップ">Forge<span class="logo-dot">.</span></a><a class="header-descriptor" href="https://smartsprouts.jp" target="_blank" rel="noopener" aria-label="運営: SmartSprouts"><img src="{{ asset('images/smartsprouts-mark.svg') }}" alt="" width="26" height="25"><span>WORDPRESS THEMES<br>BY SMARTSPROUTS</span></a><button class="menu-toggle" aria-label="メニューを開く" aria-expanded="false" aria-controls="site-nav"><span></span><span></span></button>
    <nav id="site-nav" class="site-nav" aria-label="メインナビゲーション">
      <a class="nav-account" href="{{ auth()->check() ? route('account') : route('login') }}">{{ auth()->check() ? 'アカウント' : 'ログイン' }}</a>
      @hasSection('nav')
        @yield('nav')
      @else
        <a href="#collection">テーマを探す</a><a href="#philosophy">Forgeについて</a><a href="#guide">はじめての方へ</a><a href="#faq">よくあるご質問</a><a class="nav-cta" href="{{ route('theme.show', 'ork') }}">Orkを見る <span aria-hidden="true">↗</span></a>
      @endif
    </nav>
  </header>
  <main id="main">
    @yield('content')
  </main>
  <footer class="site-footer"><div class="wrap"><div class="footer-top"><div><a class="logo" href="{{ route('home') }}">Forge<span class="logo-dot">.</span></a><p>想いをかたちにする、WordPressテーマ。</p></div><nav aria-label="フッターナビゲーション"><a href="{{ route('home') }}#collection">テーマ一覧</a><a href="{{ route('home') }}#philosophy">Forgeについて</a><a href="{{ route('home') }}#guide">はじめての方へ</a><a href="@yield('faq_href', '#faq')">よくあるご質問</a><a href="{{ route('news.index') }}">お知らせ</a></nav><a class="parent-brand" href="https://smartsprouts.jp" target="_blank" rel="noopener"><img src="{{ asset('images/smartsprouts-mark.svg') }}" alt="" width="30" height="29"><span>A BRAND BY<br><strong>SmartSprouts <span>↗</span></strong></span></a></div><div class="footer-bottom"><p class="legal-links"><a href="{{ route('legal.tokushoho') }}">特定商取引法に基づく表記</a><a href="{{ route('legal.terms') }}">利用規約</a><a href="{{ route('legal.privacy') }}">プライバシーポリシー</a><a href="{{ route('legal.refund') }}">返金ポリシー</a></p><span>© 2026 SmartSprouts.</span><button class="replay-loader">オープニングを再生 <span>↻</span></button></div></div></footer>
  @stack('after')
</body>
</html>
