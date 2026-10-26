<?php

namespace App\Services;

use App\Mail\PurchaseCompleted;
use App\Models\License;
use App\Models\Order;
use App\Models\Plan;
use App\Models\StripeEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Stripeイベントを受けて、アカウント・注文・ライセンスを作る。
 * 権利付与はWebhook経由でのみ行い、同じイベントが何度届いても結果が変わらない（冪等）。
 */
class FulfillOrder
{
    public function handle(array $event): void
    {
        $record = StripeEvent::firstOrCreate(['event_id' => $event['id']], ['type' => $event['type']]);
        if ($record->processed_at) {
            return;
        }

        $object = $event['data']['object'];

        DB::transaction(function () use ($event, $object, $record) {
            match ($event['type']) {
                'checkout.session.completed' => $this->completeCheckout($object),
                'charge.refunded' => $this->refund($object),
                default => null,
            };
            $record->update(['processed_at' => now()]);
        });
    }

    private function completeCheckout(array $session): void
    {
        if (($session['payment_status'] ?? null) !== 'paid') {
            return;
        }

        $plan = Plan::with('product')->find($session['metadata']['plan_id'] ?? null);
        $email = strtolower(trim($session['customer_details']['email'] ?? ''));
        if (! $plan || $email === '') {
            throw new \RuntimeException('注文に必要な情報が不足しています: '.$session['id']);
        }

        if (Order::where('stripe_session_id', $session['id'])->exists()) {
            return;
        }

        $user = User::firstOrNew(['email' => $email]);
        $isNew = ! $user->exists;
        if ($isNew) {
            $user->name = $session['customer_details']['name'] ?: Str::before($email, '@');
            $user->password = Str::random(40);
        }
        if (! empty($session['customer']) && ! $user->stripe_customer_id) {
            $user->stripe_customer_id = $session['customer'];
        }
        $user->save();

        $paidAt = now();
        $order = Order::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'stripe_session_id' => $session['id'],
            'stripe_payment_intent' => $session['payment_intent'] ?? null,
            'amount_jpy' => $session['amount_total'],
            'status' => 'paid',
            'paid_at' => $paidAt,
            'terms_version' => $session['metadata']['terms_version'] ?? null,
        ]);

        $license = License::create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'product_id' => $plan->product_id,
            'plan_id' => $plan->id,
            'site_limit' => $plan->site_limit,
            'support_until' => $paidAt->copy()->addMonths($plan->support_months),
            'updates_until' => $paidAt->copy()->addMonths($plan->update_months),
            'status' => 'active',
        ]);

        $setPasswordUrl = $isNew
            ? url('/reset-password/'.Password::broker()->createToken($user)).'?email='.urlencode($user->email)
            : null;

        DB::afterCommit(fn () => Mail::to($user)->queue(new PurchaseCompleted($license, $setPasswordUrl)));
    }

    private function refund(array $charge): void
    {
        if (empty($charge['refunded']) || empty($charge['payment_intent'])) {
            return; // 一部返金は自動では失効させない（管理画面で判断）
        }

        $order = Order::where('stripe_payment_intent', $charge['payment_intent'])->first();
        if (! $order) {
            return;
        }

        $order->update(['status' => 'refunded']);
        $order->license?->update(['status' => 'revoked']);
    }
}
