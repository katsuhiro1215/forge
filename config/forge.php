<?php

return [
    // デモサイト（WordPress）のベースURL。製品ページからのリンクにだけ使う
    'demo_url' => env('FORGE_DEMO_URL', 'https://demo.forge.smartsprouts.jp'),

    // 特商法表記・メールフッター用（本番では実際の住所を.envに設定）
    'company' => [
        'name' => env('COMPANY_NAME'),
        'brand' => env('COMPANY_BRAND', 'SmartSprouts / Forge'), // ブランド名。親サイト側は「SmartSprouts」で表記を揃える
        'representative' => env('COMPANY_REPRESENTATIVE'),
        'address' => env('COMPANY_ADDRESS'),
        'contact' => env('COMPANY_CONTACT'), // 電話番号
        'email' => env('COMPANY_EMAIL', env('MAIL_FROM_ADDRESS')),
        'invoice_number' => env('COMPANY_INVOICE_NUMBER'), // 適格請求書発行事業者の登録番号（登録している場合のみ）
    ],

    // 商品の動作環境（特商法・製品ページに表示。実際に検証した環境に合わせること）
    'requirements' => env('FORGE_REQUIREMENTS', 'WordPress 6.0 以上 / PHP 8.0 以上'),

    // 法務ページ。reviewed が false の間は「草案」表示＋検索エンジン非公開（noindex）にする
    'legal' => [
        'version' => '2026-10-09', // 規約を改定したら更新（購入時の同意記録に残る）
        'effective_date' => env('FORGE_LEGAL_EFFECTIVE_DATE'), // 例: 2026-11-01
        'reviewed' => (bool) env('FORGE_LEGAL_REVIEWED', false),
    ],
];
