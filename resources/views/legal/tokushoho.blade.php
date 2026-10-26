@extends('layouts.legal')
@section('title', '特定商取引法に基づく表記 | Forge')
@section('description', 'Forgeの特定商取引法に基づく表記。')
@section('heading', '特定商取引法に基づく表記')
@section('legal')
@php($c = config('forge.company'))
<p>Forge（forge.smartsprouts.jp）で販売するWordPressテーマなどのデジタル商品について、特定商取引に関する法律に基づき、次のとおり表示します。</p>
<table class="legal-table">
  <tr><th>販売業者</th><td>{{ $c['name'] }}（ブランド名: {{ $c['brand'] }}）</td></tr>
  <tr><th>運営統括責任者</th><td>{{ $c['representative'] }}</td></tr>
  <tr><th>所在地</th><td>{{ $c['address'] }}</td></tr>
  <tr><th>電話番号</th><td>{{ $c['contact'] }}<br><small>お問い合わせは、原則として下記のメールアドレスにお願いします。</small></td></tr>
  <tr><th>メールアドレス</th><td>{{ $c['email'] }}</td></tr>
  @if($c['invoice_number'])
  <tr><th>適格請求書発行事業者の登録番号</th><td>{{ $c['invoice_number'] }}</td></tr>
  @endif
  <tr><th>販売URL</th><td>{{ url('/') }}</td></tr>
  <tr><th>販売価格</th><td>各商品ページに表示する価格（消費税込み）。</td></tr>
  <tr><th>商品代金以外に必要な料金</th><td>インターネットに接続するための通信料など（お客様のご負担です）。</td></tr>
  <tr><th>お支払方法</th><td>クレジットカード（決済サービス Stripe を利用。Visa、Mastercard、American Express など Stripe が対応するブランド）。</td></tr>
  <tr><th>お支払時期</th><td>購入のお手続き時にお支払いが確定します。実際の引き落とし日は、ご利用のカード会社の規約によります。</td></tr>
  <tr><th>商品の引渡時期</th><td>お支払いの完了後すぐに、アカウントページからダウンロードできます（購入完了メールでご案内します）。</td></tr>
  <tr><th>返品・キャンセル</th><td>デジタル商品の性質上、お支払い後の返品・キャンセルはお受けしていません。ただし、<a href="{{ route('legal.refund') }}">返金ポリシー</a>に定める場合（二重のご請求、ダウンロードできない、重大な不具合を解消できない場合など）は返金します。この内容は、特定商取引法に基づく返品に関する特約です。</td></tr>
  <tr><th>動作環境</th><td>{{ config('forge.requirements') }}。ご利用にはWordPressが動作するサーバーと独自ドメインが別途必要です。</td></tr>
  <tr><th>サポート・アップデート</th><td>購入日から12か月間（メールによるサポートと、無償アップデートの提供）。詳しくは<a href="{{ route('legal.terms') }}">利用規約</a>をご覧ください。</td></tr>
  <tr><th>ライセンス</th><td>1ライセンスにつき1サイトでご利用いただけます。詳しくは利用規約をご覧ください。</td></tr>
</table>
@endsection
