<?php

namespace Tests\Feature;

use App\Models\Product;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ProductSeeder::class);
    }

    public function test_home_shows_price_from_database(): void
    {
        $this->get('/')->assertOk()->assertSee('¥19,800');
    }

    public function test_price_change_is_reflected_on_product_page(): void
    {
        Product::where('slug', 'ork')->first()->plans()->update(['price_jpy' => 24800]);

        $this->get('/theme/ork')->assertOk()->assertSee('¥24,800')->assertDontSee('¥19,800');
    }

    public function test_product_page_links_to_demo(): void
    {
        $this->get('/theme/ork')->assertOk()->assertSee('https://demo.forge.smartsprouts.jp/ork/', false);
    }

    public function test_draft_or_unknown_product_is_404(): void
    {
        $this->get('/theme/unknown')->assertNotFound();

        Product::where('slug', 'ork')->update(['status' => 'draft']);
        $this->get('/theme/ork')->assertNotFound();
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(ProductSeeder::class);

        $this->assertSame(1, Product::count());
        $this->assertSame(1, Product::first()->plans()->count());
    }
}
