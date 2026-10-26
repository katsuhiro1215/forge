<?php

namespace App\Http\Controllers;

use App\Services\FulfillOrder;
use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, FulfillOrder $fulfill)
    {
        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature'),
                (string) config('services.stripe.webhook_secret'),
            );
        } catch (\UnexpectedValueException|SignatureVerificationException) {
            return response('invalid', 400);
        }

        $fulfill->handle($event->toArray());

        return response('ok');
    }
}
