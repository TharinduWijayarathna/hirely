<?php

namespace App\Http\Controllers;

use App\Services\StripeWebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, StripeWebhookService $webhooks)
    {
        if (! config('payments.webhook_enabled')) {
            return response()->json(['received' => true, 'handled' => false]);
        }

        $secret = config('services.stripe.webhook_secret');

        if (! $secret) {
            Log::error('Stripe webhook secret is not configured');

            return response('Webhook secret not configured', 500);
        }

        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        try {
            $event = Webhook::constructEvent($payload, $signature ?? '', $secret);
        } catch (UnexpectedValueException $e) {
            return response('Invalid payload', 400);
        } catch (SignatureVerificationException $e) {
            return response('Invalid signature', 400);
        }

        $webhooks->handle($event->toArray());

        return response()->json(['received' => true]);
    }
}
