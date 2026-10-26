<?php

namespace App\Services;

use App\Models\Plan;
use Stripe\StripeClient;

/** Stripe Checkoutセッションの作成。テストでは差し替え可能にするため薄く切り出している */
class StripeCheckout
{
    public function __construct(private readonly StripeClient $stripe)
    {
    }

    /** @return string Stripeホスト決済画面のURL */
    public function createSessionUrl(Plan $plan, ?string $termsVersion = null): string
    {
        return $this->stripe->checkout->sessions->create($this->buildParams($plan, $termsVersion))->url;
    }

    /** Checkoutセッションのパラメータ。請求先住所・会社名はStripe側にだけ保存し、当サイトのDBには持たない */
    public function buildParams(Plan $plan, ?string $termsVersion = null): array
    {
        $plan->loadMissing('product');
        $meta = ['plan_id' => (string) $plan->id, 'product_id' => (string) $plan->product_id];
        if ($termsVersion) {
            $meta['terms_version'] = $termsVersion; // 同意した規約の版を決済に残す
        }

        return [
            'mode' => 'payment',
            'locale' => 'ja',
            'customer_creation' => 'always',
            'billing_address_collection' => 'auto',
            'custom_fields' => [[
                'key' => 'company_name',
                'label' => ['type' => 'custom', 'custom' => '会社名・屋号（領収書の宛名・任意）'],
                'type' => 'text',
                'optional' => true,
                'text' => ['maximum_length' => 100],
            ]],
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => 'jpy',
                    'unit_amount' => $plan->price_jpy,
                    'product_data' => ['name' => $plan->product->name.' '.$plan->name],
                ],
            ]],
            'metadata' => $meta,
            'payment_intent_data' => ['metadata' => $meta],
            'success_url' => route('checkout.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('theme.show', $plan->product),
        ];
    }
}
