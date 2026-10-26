<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\StripeCheckout;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function start(Request $request, Product $product, StripeCheckout $checkout)
    {
        $request->validate(['agree' => ['accepted']], ['agree.accepted' => '利用規約・プライバシーポリシー・特定商取引法に基づく表記への同意が必要です。']);

        abort_unless($product->status === 'published', 404);
        $plan = $product->singlePlan() ?? abort(404);

        return redirect()->away($checkout->createSessionUrl($plan, config('forge.legal.version')));
    }

    public function success()
    {
        return view('checkout.success');
    }
}
