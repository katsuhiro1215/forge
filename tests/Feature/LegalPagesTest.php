<?php

namespace Tests\Feature;

use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_legal_pages_render(): void
    {
        foreach (['tokushoho' => '販売業者', 'terms' => '第2条（ライセンス）', 'privacy' => '取得する情報', 'refund' => '返金する場合'] as $slug => $needle) {
            $this->get('/legal/'.$slug)->assertOk()->assertSee($needle);
        }
    }

    public function test_tokushoho_shows_company_info_from_config(): void
    {
        config(['forge.company' => [
            'name' => 'テスト事業者', 'brand' => 'テストブランド / Forge', 'representative' => '山田 太郎', 'address' => '東京都テスト区1-2-3',
            'contact' => '03-0000-0000', 'email' => 'support@example.test', 'invoice_number' => 'T1234567890123',
        ]]);

        $this->get('/legal/tokushoho')->assertOk()
            ->assertSee('テスト事業者（ブランド名: テストブランド / Forge）', false)->assertSee('山田 太郎')->assertSee('東京都テスト区1-2-3')
            ->assertSee('support@example.test')->assertSee('T1234567890123');
    }

    public function test_invoice_row_is_hidden_when_not_registered(): void
    {
        config(['forge.company.invoice_number' => null]);

        $this->get('/legal/tokushoho')->assertOk()->assertDontSee('適格請求書発行事業者');
    }

    public function test_unreviewed_pages_show_draft_banner_and_noindex(): void
    {
        config(['forge.legal.reviewed' => false]);

        $this->get('/legal/terms')->assertOk()->assertSee('草案')->assertSee('noindex', false);
    }

    public function test_reviewed_pages_are_indexable_without_banner(): void
    {
        config(['forge.legal.reviewed' => true, 'forge.legal.effective_date' => '2026-11-01']);

        $this->get('/legal/terms')->assertOk()->assertDontSee('草案')->assertDontSee('noindex', false)
            ->assertSee('制定日: 2026年11月1日');
    }

    public function test_rules_in_terms_match_what_the_system_enforces(): void
    {
        // 規約の「購入から12か月」「1ライセンス1サイト」が、実際の仕様（シーダーのプラン）と食い違わないことを保証する
        $this->seed(ProductSeeder::class);
        $plan = \App\Models\Plan::first();
        $this->assertSame(12, $plan->support_months);
        $this->assertSame(12, $plan->update_months);
        $this->assertSame(1, $plan->site_limit);

        $this->get('/legal/terms')->assertSee('購入日から12か月')->assertSee('1つのウェブサイト');
    }

    public function test_product_page_shows_terms_checkbox_and_no_sample_wording(): void
    {
        $this->seed(ProductSeeder::class);

        $this->get('/theme/ork')->assertOk()
            ->assertSee('name="agree"', false)->assertSee('/legal/refund', false)
            ->assertSee('1サイト（1ライセンス）')->assertSee('購入から12か月')
            ->assertDontSee('UIサンプル')->assertDontSee('販売開始時にご案内');
    }

    public function test_footer_links_to_legal_pages_on_every_page(): void
    {
        $this->seed(ProductSeeder::class);

        foreach (['/', '/login', '/news'] as $url) {
            $this->get($url)->assertOk()->assertSee('/legal/tokushoho', false)->assertSee('/legal/terms', false)->assertSee('/legal/privacy', false);
        }
    }
}
