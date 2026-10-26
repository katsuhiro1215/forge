{{ $license->user->name }} 様

このたびは Forge「{{ $license->product->name }}」をご購入いただき、ありがとうございます。

■ ご購入内容
{{ $license->product->name }}（{{ $license->plan->name }}）
お支払い金額: ¥{{ number_format($license->order->amount_jpy) }}（税込）
サポート・無償アップデート期間: {{ $license->updates_until->format('Y年n月j日') }} まで

■ ダウンロード
アカウントページからダウンロードできます。
{{ route('account') }}

@if($setPasswordUrl)
初めてのご利用です。下のリンクからパスワードを設定してください。
{{ $setPasswordUrl }}

@endif
ご不明な点はこのメールへの返信でお問い合わせください。

—
{{ config('forge.company.name') }}
