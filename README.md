# Forge 販売サイト

WordPressテーマ・ブロック・UIキット・ダッシュボードのダウンロード販売サイト（<https://forge.smartsprouts.jp>）のソースコードです。第1弾の商品は、コーポレートサイト向けWordPressテーマ「Ork」です。

運営: かつコード（ブランド名: SmartSprouts / Forge）

## 技術構成

- Laravel 13 / PHP 8.3 以上
- 画面: Blade + Vanilla JS（Vite）
- DB: SQLite（WAL）
- 決済: Stripe Checkout（カード情報は自サーバーを通りません）。権利付与は署名検証したWebhookでのみ行います
- 管理画面: Filament v5（`/admin`。管理者は購入者とは別テーブル・別ガード）
- メール・キュー: `database` ドライバ

## 主な機能

- 商品ページ、お知らせ（`/news`）、法務ページ（`/legal/*`）
- ゲスト購入 → アカウント自動作成 → ライセンス発行（1ライセンス=1サイト、購入から12か月のサポート・無償アップデート）
- アカウントページ、権利判定つきのZIPダウンロード（非公開ストレージ）
- 管理画面: 商品・プラン・価格、リリースZIP、注文、ライセンス（手動付与・失効）、お知らせ、売上ダッシュボード

## セットアップ

```sh
composer install
npm ci
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed          # 商品・プランの初期データ（何度実行しても重複しません）
npm run build
php artisan serve
```

- `.env` に Stripe のテストキー（`STRIPE_PUBLIC_KEY` / `STRIPE_SECRET_KEY`）と会社情報（`COMPANY_*`）を設定します。**`.env` は絶対にコミットしないでください。**
- 管理者の作成: `php artisan forge:create-admin <メールアドレス>`
- Webhookの受信（ローカル）: `stripe listen --api-key <sk_test> --forward-to localhost:8000/webhook/stripe` を実行し、表示された `whsec_...` を `STRIPE_WEBHOOK_SECRET` に設定します
- 購入完了メールの送信: `php artisan queue:work`

## テスト

```sh
php artisan test
```

購入フロー（署名検証・冪等性・返金）、権利判定、管理画面、お知らせ、法務ページを自動テストでカバーしています。

## 注意

- 製品ページのデモ画像（`public/images/*.jpg`）は、再配布ライセンスが未確認のためリポジトリに含めていません（`.gitignore` で除外）。
- 開発では `migrate:fresh` を使わないでください。

## ドキュメント

設計書・法務の確認事項・進捗ログなどの社内ドキュメントは、このリポジトリの外（社内の共有フォルダ）で管理しています。
