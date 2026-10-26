<?php

namespace Tests\Feature;

use App\Filament\Resources\Licenses\Pages\CreateLicense;
use App\Filament\Resources\Licenses\Pages\ListLicenses;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\RelationManagers\PlansRelationManager;
use App\Filament\Resources\Products\RelationManagers\ReleasesRelationManager;
use App\Filament\Widgets\LatestOrders;
use App\Filament\Widgets\MonthlySalesChart;
use App\Filament\Widgets\SalesStats;
use App\Models\Admin;
use App\Models\License;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Release;
use App\Models\User;
use Database\Seeders\ProductSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ProductSeeder::class);
        Storage::fake('local');
    }

    private function asAdmin(): Admin
    {
        $admin = Admin::create(['name' => '管理者', 'email' => 'admin@example.com', 'password' => 'admin-password-123']);
        $this->actingAs($admin, 'admin');

        return $admin;
    }

    public function test_guest_and_buyers_cannot_open_admin(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');

        $this->actingAs(User::factory()->create()); // 購入者はadminガードでは認証されない
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_admin_can_login_and_see_dashboard_and_pages(): void
    {
        $this->asAdmin();

        foreach (['/admin', '/admin/products', '/admin/orders', '/admin/licenses', '/admin/licenses/create', '/admin/downloads'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/admin/products/'.Product::first()->getRouteKey().'/edit')->assertOk();
    }

    public function test_dashboard_widgets_render_and_exclude_refund_and_manual_from_sales(): void
    {
        $this->asAdmin();
        $user = User::factory()->create();
        $plan = Plan::first();
        Order::create(['user_id' => $user->id, 'plan_id' => $plan->id, 'stripe_session_id' => 'cs_1', 'amount_jpy' => 19800, 'paid_at' => now()]);
        Order::create(['user_id' => $user->id, 'plan_id' => $plan->id, 'stripe_session_id' => 'cs_2', 'amount_jpy' => 19800, 'status' => 'refunded', 'paid_at' => now()]);
        Order::create(['user_id' => $user->id, 'plan_id' => $plan->id, 'stripe_session_id' => 'manual_x', 'amount_jpy' => 0, 'paid_at' => now()]);

        Livewire::test(SalesStats::class)->assertSee('¥19,800')->assertSee('1件');
        Livewire::test(MonthlySalesChart::class)->assertOk();
        Livewire::test(LatestOrders::class)->assertSee($user->email);
        Livewire::test(ListOrders::class)->assertCanSeeTableRecords(Order::all());
    }

    public function test_product_list_and_edit(): void
    {
        $this->asAdmin();
        $product = Product::first();

        Livewire::test(ListProducts::class)->assertCanSeeTableRecords([$product]);
        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['tagline' => '新しいキャッチ'])->call('save')->assertHasNoFormErrors();

        $this->assertSame('新しいキャッチ', $product->fresh()->tagline);
    }

    public function test_price_can_be_changed_from_plans_manager(): void
    {
        $this->asAdmin();
        $product = Product::first();
        $plan = $product->plans()->first();

        Livewire::test(PlansRelationManager::class, ['ownerRecord' => $product, 'pageClass' => EditProduct::class])
            ->callAction(TestAction::make('edit')->table($plan), ['price_jpy' => 24800])
            ->assertHasNoFormErrors();

        $this->assertSame(24800, $plan->fresh()->price_jpy);
        $this->get('/theme/ork')->assertSee('¥24,800');
    }

    public function test_release_zip_upload_records_size_and_hash(): void
    {
        $this->asAdmin();
        $product = Product::first();
        $file = UploadedFile::fake()->createWithContent('ork.zip', 'PK-fake-zip-content');

        Livewire::test(ReleasesRelationManager::class, ['ownerRecord' => $product, 'pageClass' => EditProduct::class])
            ->callAction(TestAction::make('create')->table(), [
                'version' => '1.0.0', 'released_at' => now()->toDateTimeString(),
                'file_path' => $file, 'changelog' => '初回リリース',
            ])
            ->assertHasNoFormErrors();

        $release = Release::firstWhere('version', '1.0.0');
        $this->assertNotNull($release);
        Storage::disk('local')->assertExists($release->file_path);
        $this->assertStringStartsWith('releases/ork/', $release->file_path);
        $this->assertSame(strlen('PK-fake-zip-content'), $release->size);
        $this->assertSame(hash('sha256', 'PK-fake-zip-content'), $release->sha256);
    }

    public function test_deleting_release_removes_the_zip(): void
    {
        $product = Product::first();
        Storage::disk('local')->put('releases/ork/x.zip', 'zip');
        $release = Release::create(['product_id' => $product->id, 'version' => '9.9.9', 'file_path' => 'releases/ork/x.zip']);

        $release->delete();

        Storage::disk('local')->assertMissing('releases/ork/x.zip');
    }

    public function test_manual_grant_creates_zero_yen_order_and_license(): void
    {
        $this->asAdmin();
        $user = User::factory()->create();
        $plan = Plan::first();

        Livewire::test(CreateLicense::class)
            ->fillForm([
                'user_id' => $user->id, 'product_id' => $plan->product_id, 'plan_id' => $plan->id,
                'support_until' => now()->addYear()->toDateTimeString(), 'updates_until' => now()->addYear()->toDateTimeString(),
            ])->call('create')->assertHasNoFormErrors();

        $license = License::first();
        $this->assertSame('active', $license->status);
        $this->assertSame($user->id, $license->user_id);
        $this->assertSame(0, $license->order->amount_jpy);
        $this->assertStringStartsWith('manual_', $license->order->stripe_session_id);
    }

    public function test_revoke_and_restore_license(): void
    {
        $this->asAdmin();
        $user = User::factory()->create();
        $plan = Plan::first();
        $order = Order::create(['user_id' => $user->id, 'plan_id' => $plan->id, 'stripe_session_id' => 'cs_1', 'amount_jpy' => 19800, 'paid_at' => now()]);
        $license = License::create([
            'user_id' => $user->id, 'order_id' => $order->id, 'product_id' => $plan->product_id, 'plan_id' => $plan->id,
            'site_limit' => 1, 'support_until' => now()->addYear(), 'updates_until' => now()->addYear(), 'status' => 'active',
        ]);

        Livewire::test(ListLicenses::class)->callAction(TestAction::make('revoke')->table($license));
        $this->assertSame('revoked', $license->fresh()->status);

        Livewire::test(ListLicenses::class)->callAction(TestAction::make('restore')->table($license));
        $this->assertSame('active', $license->fresh()->status);
    }
}
