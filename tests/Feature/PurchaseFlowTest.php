<?php

namespace Tests\Feature;

use App\Mail\PurchaseCompleted;
use App\Models\License;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use App\Services\StripeCheckout;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PurchaseFlowTest extends TestCase
{
    use RefreshDatabase;

    private string $secret = 'whsec_test_secret';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.stripe.webhook_secret' => $this->secret]);
        $this->seed(ProductSeeder::class);
        Mail::fake();
    }

    private function post_event(array $event, ?string $secret = null)
    {
        $payload = json_encode($event);
        $t = time();
        $sig = hash_hmac('sha256', $t.'.'.$payload, $secret ?? $this->secret);

        return $this->call('POST', '/webhook/stripe', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => "t={$t},v1={$sig}",
            'CONTENT_TYPE' => 'application/json',
        ], $payload);
    }

    private function sessionEvent(string $id = 'evt_1', string $email = 'Buyer@Example.com', string $paid = 'paid'): array
    {
        $plan = Plan::first();

        return [
            'id' => $id,
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id' => 'cs_test_1',
                'object' => 'checkout.session',
                'payment_status' => $paid,
                'amount_total' => $plan->price_jpy,
                'payment_intent' => 'pi_1',
                'customer' => 'cus_1',
                'customer_details' => ['email' => $email, 'name' => '購入 太郎'],
                'metadata' => ['plan_id' => (string) $plan->id],
            ]],
        ];
    }

    public function test_invalid_signature_is_rejected_and_grants_nothing(): void
    {
        $this->post_event($this->sessionEvent(), 'whsec_wrong')->assertStatus(400);

        $this->assertSame(0, Order::count());
        $this->assertSame(0, License::count());
    }

    public function test_paid_session_creates_user_order_and_license(): void
    {
        $this->post_event($this->sessionEvent())->assertOk();

        $user = User::firstWhere('email', 'buyer@example.com');
        $this->assertNotNull($user);
        $this->assertSame('cus_1', $user->stripe_customer_id);

        $license = License::first();
        $this->assertSame('active', $license->status);
        $this->assertSame(1, $license->site_limit);
        $this->assertSame(19800, $license->order->amount_jpy);
        $this->assertEqualsWithDelta(
            now()->addMonths(12)->timestamp, $license->updates_until->timestamp, 5
        );
        $this->assertEqualsWithDelta(
            now()->addMonths(12)->timestamp, $license->support_until->timestamp, 5
        );

        Mail::assertQueued(PurchaseCompleted::class, fn ($m) => $m->hasTo('buyer@example.com') && $m->setPasswordUrl !== null);
    }

    public function test_same_event_twice_does_not_duplicate(): void
    {
        $this->post_event($this->sessionEvent())->assertOk();
        $this->post_event($this->sessionEvent())->assertOk();

        $this->assertSame(1, Order::count());
        $this->assertSame(1, License::count());
        Mail::assertQueuedCount(1);
    }

    public function test_same_session_with_different_event_id_does_not_duplicate(): void
    {
        $this->post_event($this->sessionEvent('evt_1'))->assertOk();
        $this->post_event($this->sessionEvent('evt_2'))->assertOk();

        $this->assertSame(1, Order::count());
        $this->assertSame(1, License::count());
    }

    public function test_unpaid_session_grants_nothing(): void
    {
        $this->post_event($this->sessionEvent('evt_1', 'a@example.com', 'unpaid'))->assertOk();

        $this->assertSame(0, Order::count());
        $this->assertSame(0, License::count());
    }

    public function test_existing_user_gets_license_without_password_link(): void
    {
        $user = User::factory()->create(['email' => 'buyer@example.com']);

        $this->post_event($this->sessionEvent())->assertOk();

        $this->assertSame(1, User::count());
        $this->assertSame($user->id, License::first()->user_id);
        Mail::assertQueued(PurchaseCompleted::class, fn ($m) => $m->setPasswordUrl === null);
    }

    public function test_full_refund_revokes_license(): void
    {
        $this->post_event($this->sessionEvent())->assertOk();

        $this->post_event([
            'id' => 'evt_refund', 'object' => 'event', 'type' => 'charge.refunded',
            'data' => ['object' => ['id' => 'ch_1', 'refunded' => true, 'payment_intent' => 'pi_1']],
        ])->assertOk();

        $this->assertSame('refunded', Order::first()->status);
        $this->assertSame('revoked', License::first()->status);
    }

    public function test_partial_refund_keeps_license(): void
    {
        $this->post_event($this->sessionEvent())->assertOk();

        $this->post_event([
            'id' => 'evt_partial', 'object' => 'event', 'type' => 'charge.refunded',
            'data' => ['object' => ['id' => 'ch_1', 'refunded' => false, 'payment_intent' => 'pi_1']],
        ])->assertOk();

        $this->assertSame('active', License::first()->status);
    }

    public function test_checkout_start_redirects_to_stripe(): void
    {
        $this->app->instance(StripeCheckout::class, new class extends StripeCheckout
        {
            public function __construct() {}

            public function createSessionUrl(Plan $plan, ?string $termsVersion = null): string
            {
                return 'https://checkout.stripe.test/session?terms='.$termsVersion;
            }
        });

        $this->post('/checkout/ork', ['agree' => '1'])
            ->assertRedirect('https://checkout.stripe.test/session?terms='.config('forge.legal.version'));
    }

    public function test_checkout_requires_agreement_to_terms(): void
    {
        $this->app->instance(StripeCheckout::class, new class extends StripeCheckout
        {
            public function __construct() {}

            public function createSessionUrl(Plan $plan, ?string $termsVersion = null): string
            {
                throw new \LogicException('同意なしでStripeに進んではいけない');
            }
        });

        $this->post('/checkout/ork')->assertSessionHasErrors('agree');
        $this->post('/checkout/ork', ['agree' => '0'])->assertSessionHasErrors('agree');
    }

    public function test_agreed_terms_version_is_recorded_on_the_order(): void
    {
        $event = $this->sessionEvent();
        $event['data']['object']['metadata']['terms_version'] = '2026-10-09';

        $this->post_event($event)->assertOk();

        $this->assertSame('2026-10-09', Order::first()->terms_version);
    }

    public function test_checkout_params_collect_address_and_company_name_only_at_stripe(): void
    {
        $params = app(StripeCheckout::class)->buildParams(Plan::first(), '2026-10-09');

        $this->assertSame('auto', $params['billing_address_collection']);
        $this->assertSame('company_name', $params['custom_fields'][0]['key']);
        $this->assertTrue($params['custom_fields'][0]['optional']);
        $this->assertSame('jpy', $params['line_items'][0]['price_data']['currency']);
        $this->assertSame(19800, $params['line_items'][0]['price_data']['unit_amount']);
        $this->assertSame('2026-10-09', $params['metadata']['terms_version']);
    }

    public function test_address_and_company_name_are_not_stored_in_our_database(): void
    {
        $event = $this->sessionEvent();
        $event['data']['object']['customer_details']['address'] = ['country' => 'JP', 'state' => '奈良県', 'line1' => '1-1-1'];
        $event['data']['object']['custom_fields'] = [['key' => 'company_name', 'text' => ['value' => 'テスト株式会社']]];

        $this->post_event($event)->assertOk();

        $dump = json_encode([User::first()->getAttributes(), Order::first()->getAttributes(), License::first()->getAttributes()]);
        $this->assertStringNotContainsString('1-1-1', $dump);
        $this->assertStringNotContainsString('テスト株式会社', $dump);
        $this->assertStringNotContainsString('奈良県', $dump);
    }
}
