<?php

namespace Tests\Feature;

use App\Models\Download;
use App\Models\License;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Release;
use App\Models\User;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ProductSeeder::class);
        $this->product = Product::firstWhere('slug', 'ork');
        Storage::fake('local');
    }

    private function licenseFor(User $user, array $attrs = []): License
    {
        $plan = Plan::first();
        $order = Order::create([
            'user_id' => $user->id, 'plan_id' => $plan->id,
            'stripe_session_id' => 'cs_'.uniqid(), 'amount_jpy' => $plan->price_jpy, 'paid_at' => now(),
        ]);

        return License::create($attrs + [
            'user_id' => $user->id, 'order_id' => $order->id,
            'product_id' => $this->product->id, 'plan_id' => $plan->id, 'site_limit' => 1,
            'support_until' => now()->addYear(), 'updates_until' => now()->addYear(), 'status' => 'active',
        ]);
    }

    private function release(string $version, $releasedAt, bool $withFile = true): Release
    {
        $path = "releases/ork/{$version}.zip";
        if ($withFile) {
            Storage::disk('local')->put($path, 'zip-content-'.$version);
        }

        return Release::create([
            'product_id' => $this->product->id, 'version' => $version,
            'file_path' => $path, 'released_at' => $releasedAt,
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $release = $this->release('1.0.0', now());

        $this->get('/account')->assertRedirect('/login');
        $this->get(route('account.download', $release))->assertRedirect('/login');
    }

    public function test_owner_can_download_and_it_is_logged_without_raw_ip(): void
    {
        $user = User::factory()->create();
        $license = $this->licenseFor($user);
        $release = $this->release('1.0.0', now()->subDay());

        $this->actingAs($user)->get(route('account.download', $release))
            ->assertOk()->assertDownload('ork-1.0.0.zip');

        $log = Download::first();
        $this->assertSame($license->id, $log->license_id);
        $this->assertSame(64, strlen($log->ip_hash));
        $this->assertStringNotContainsString('127.0.0.1', $log->ip_hash);
    }

    public function test_other_users_license_does_not_grant_access(): void
    {
        $owner = User::factory()->create();
        $this->licenseFor($owner);
        $stranger = User::factory()->create();
        $release = $this->release('1.0.0', now()->subDay());

        $this->actingAs($stranger)->get(route('account.download', $release))->assertForbidden();
    }

    public function test_revoked_license_is_denied(): void
    {
        $user = User::factory()->create();
        $this->licenseFor($user, ['status' => 'revoked']);
        $release = $this->release('1.0.0', now()->subDay());

        $this->actingAs($user)->get(route('account.download', $release))->assertForbidden();
    }

    public function test_release_after_update_period_is_denied_but_earlier_one_stays_available(): void
    {
        $user = User::factory()->create();
        $this->licenseFor($user, ['updates_until' => now()->subDay()]); // 期限切れ
        $old = $this->release('1.0.0', now()->subYear());
        $new = $this->release('2.0.0', now());

        $this->actingAs($user)->get(route('account.download', $old))->assertOk();
        $this->actingAs($user)->get(route('account.download', $new))->assertForbidden();
    }

    public function test_unpublished_release_is_denied(): void
    {
        $user = User::factory()->create();
        $this->licenseFor($user);
        $release = $this->release('1.0.0', null);

        $this->actingAs($user)->get(route('account.download', $release))->assertForbidden();
    }

    public function test_missing_file_returns_404(): void
    {
        $user = User::factory()->create();
        $this->licenseFor($user);
        $release = $this->release('1.0.0', now()->subDay(), withFile: false);

        $this->actingAs($user)->get(route('account.download', $release))->assertNotFound();
    }

    public function test_account_shows_only_own_licenses(): void
    {
        $me = User::factory()->create(['email' => 'me@example.com']);
        $this->licenseFor($me);
        $this->release('1.2.3', now()->subDay());
        $other = User::factory()->create(['email' => 'other@example.com']);
        $this->licenseFor($other);

        $this->actingAs($me)->get('/account')->assertOk()
            ->assertSee('me@example.com')->assertSee('v1.2.3')->assertDontSee('other@example.com');
    }

    public function test_login_success_and_failure(): void
    {
        User::factory()->create(['email' => 'a@example.com', 'password' => 'correct-password-1']);

        $this->post('/login', ['email' => 'a@example.com', 'password' => 'wrong'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/login', ['email' => 'a@example.com', 'password' => 'correct-password-1'])
            ->assertRedirect('/account');
        $this->assertAuthenticated();
    }

    public function test_purchase_mail_password_link_works_and_token_is_single_use(): void
    {
        $user = User::factory()->create(['email' => 'new@example.com']);
        $token = Password::broker()->createToken($user);

        $this->get('/reset-password/'.$token.'?email=new@example.com')->assertOk();

        $payload = ['token' => $token, 'email' => 'new@example.com',
            'password' => 'brand-new-password', 'password_confirmation' => 'brand-new-password'];
        $this->post('/reset-password', $payload)->assertRedirect('/login');
        $this->post('/login', ['email' => 'new@example.com', 'password' => 'brand-new-password'])->assertRedirect('/account');

        auth()->logout();
        $this->post('/reset-password', $payload)->assertSessionHasErrors('email'); // 再利用不可
    }

    public function test_short_password_is_rejected(): void
    {
        $user = User::factory()->create(['email' => 'new@example.com']);
        $token = Password::broker()->createToken($user);

        $this->post('/reset-password', ['token' => $token, 'email' => 'new@example.com',
            'password' => 'short', 'password_confirmation' => 'short'])->assertSessionHasErrors('password');
    }

    public function test_forgot_password_does_not_reveal_whether_email_exists(): void
    {
        User::factory()->create(['email' => 'real@example.com']);

        $a = $this->post('/forgot-password', ['email' => 'real@example.com']);
        $b = $this->post('/forgot-password', ['email' => 'nobody@example.com']);

        $this->assertSame(session('status'), $a->getSession()->get('status'));
        $b->assertSessionHas('status', $a->getSession()->get('status'));
    }
}
