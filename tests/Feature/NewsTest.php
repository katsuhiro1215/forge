<?php

namespace Tests\Feature;

use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Models\Admin;
use App\Models\Post;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NewsTest extends TestCase
{
    use RefreshDatabase;

    private function makePost(array $attrs = []): Post
    {
        return Post::create($attrs + [
            'title' => 'Ork 1.1.0 を公開しました', 'slug' => 'ork-1-1-0', 'type' => 'update',
            'body' => "## 変更点\n\n- ヘッダーを追加\n- 不具合を修正", 'status' => 'published',
            'published_at' => now()->subHour(),
        ]);
    }

    public function test_published_post_is_listed_and_readable(): void
    {
        $this->makePost();

        $this->get('/news')->assertOk()->assertSee('Ork 1.1.0 を公開しました')->assertSee('アップデート');
        $this->get('/news/ork-1-1-0')->assertOk()->assertSee('変更点')->assertSee('ヘッダーを追加');
    }

    public function test_draft_and_scheduled_posts_are_hidden(): void
    {
        $this->makePost(['slug' => 'draft', 'title' => '下書き記事', 'status' => 'draft']);
        $this->makePost(['slug' => 'future', 'title' => '予約記事', 'published_at' => now()->addDay()]);

        $this->get('/news')->assertOk()->assertDontSee('下書き記事')->assertDontSee('予約記事');
        $this->get('/news/draft')->assertNotFound();
        $this->get('/news/future')->assertNotFound();

        $this->travel(2)->days(); // 予約日時を過ぎると公開される
        $this->get('/news/future')->assertOk()->assertSee('予約記事');
    }

    public function test_raw_html_in_body_is_not_rendered(): void
    {
        $this->makePost(['slug' => 'xss', 'body' => "<script>alert(1)</script>\n\n[x](javascript:alert(1))\n\n通常の文章"]);

        $this->get('/news/xss')->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('javascript:alert', false)
            ->assertSee('通常の文章');
    }

    public function test_home_shows_latest_news_only_when_present(): void
    {
        $this->seed(ProductSeeder::class);
        $this->get('/')->assertOk()->assertDontSee('id="news"', false);

        $this->makePost();
        $this->get('/')->assertOk()->assertSee('id="news"', false)->assertSee('Ork 1.1.0 を公開しました');
    }

    public function test_admin_can_create_a_post(): void
    {
        $this->seed(ProductSeeder::class);
        $this->actingAs(Admin::create(['name' => 'a', 'email' => 'a@example.com', 'password' => 'admin-password-123']), 'admin');

        Livewire::test(CreatePost::class)
            ->fillForm([
                'title' => 'メンテナンスのお知らせ', 'slug' => 'maintenance-1', 'type' => 'maintenance',
                'status' => 'published', 'published_at' => now()->subMinute()->toDateTimeString(), 'body' => '深夜に作業します。',
            ])->call('create')->assertHasNoFormErrors();

        $this->get('/news/maintenance-1')->assertOk()->assertSee('メンテナンスのお知らせ');
    }

    public function test_pagination_links_work_with_many_posts(): void
    {
        foreach (range(1, 12) as $i) {
            $this->makePost(['slug' => "p{$i}", 'title' => "記事{$i}", 'published_at' => now()->subMinutes($i)]);
        }

        $this->get('/news')->assertOk()->assertSee('記事1')->assertSee('過去のお知らせ')->assertDontSee('記事12');
        $this->get('/news?page=2')->assertOk()->assertSee('記事12')->assertSee('新しいお知らせ');
    }
}
