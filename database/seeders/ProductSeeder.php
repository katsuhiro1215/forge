<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /** 何度実行しても重複・削除しない（updateOrCreate） */
    public function run(): void
    {
        $ork = Product::updateOrCreate(['slug' => 'ork'], [
            'type' => 'theme',
            'name' => 'Ork',
            'tagline' => '信頼を、美しく。',
            'status' => 'published',
            'demo_path' => 'ork',
            'sort' => 1,
        ]);

        // 価格は仮（19,800円が有力、確定は後日）。1ライセンス=1サイト、購入から1年のサポート・更新
        $ork->plans()->updateOrCreate(['code' => 'single'], [
            'name' => '1サイトライセンス',
            'price_jpy' => 19800,
            'site_limit' => 1,
            'support_months' => 12,
            'update_months' => 12,
            'status' => 'active',
        ]);
    }
}
