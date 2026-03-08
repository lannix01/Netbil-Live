<?php

namespace App\Http\Controllers;

use App\Models\Paystackpayment;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /**
     * Payments landing page (later: STK push form + Paystack/M-Pesa chooser).
     */
    public function index(Request $request)
    {
        return view('payments.index');
    }

    /**
     * Start a Paystack payment.
     * Example:
     *   /payment/paystack/start?amount=100&email=test@example.com&purpose=topup&item_code=NETBIL_TOPUP
     *
     * amount is in major units, we convert to kobo.
     */
    public function startPaystack(Request $request, PaystackService $paystack)
    {
        $user = auth()->user();

        $email = $request->input('email', $user?->email);
        $amountMajor = (float) $request->input('amount', 0);
        $purpose = $request->input('purpose', 'topup');
        $itemCode = $request->input('item_code');

        if (!$email) {
            return back()->with('error', 'Email is required to start payment.');
        }
        if ($amountMajor <= 0) {
            return back()->with('error', 'Amount must be greater than 0.');
        }

        $reference = 'NB_' . Str::upper(Str::random(14));
        $amountKobo = (int) round($amountMajor * 100);

        // Create local pending record first
        $payment = Paystackpayment::create([
            'user_id' => $user?->id,
            'reference' => $reference,
            'amount' => $amountKobo,
            'status' => 'pending',
            'purpose' => $purpose,
            'item_code' => $itemCode,
            'customer_email' => $email,
            'meta' => [
                'requested_amount_major' => $amountMajor,
                'ip' => $request->ip(),
            ],
        ]);

        $payload = [
            'email' => $email,
            'amount' => $amountKobo,
            'reference' => $reference,
            'callback_url' => config('paystack.callback_url'),
            'metadata' => [
                'paystackpayment_id' => $payment->id,
                'user_id' => $user?->id,
                'purpose' => $purpose,
                'item_code' => $itemCode,
            ],
        ];

        $resp = $paystack->initializeTransaction($payload);

        if (!$resp['ok'] || !($resp['body']['status'] ?? false)) {
            return back()->with('error', 'Paystack init failed: ' . json_encode($resp['body']));
        }

        $authUrl = $resp['body']['data']['authorization_url'] ?? null;

        if (!$authUrl) {
            return back()->with('error', 'Paystack did not return authorization_url.');
        }

        return redirect()->away($authUrl);
    }

    /**
     * Paystack redirects here: /payment/success?reference=xxxx
     * We verify the transaction by reference.
     */
    public function paystackSuccess(Request $request, PaystackService $paystack)
    {
        $reference = $request->query('reference');

        if (!$reference) {
            return view('payments.success', [
                'ok' => false,
                'message' => 'Missing reference in callback URL.',
                'reference' => null,
            ]);
        }

        $resp = $paystack->verifyTransaction($reference);

        if (!$resp['ok'] || !($resp['body']['status'] ?? false)) {
            return view('payments.success', [
                'ok' => false,
                'message' => 'Could not verify payment with Paystack.',
                'reference' => $reference,
                'raw' => $resp['body'],
            ]);
        }

        $data = $resp['body']['data'] ?? [];
        $psStatus = $data['status'] ?? 'failed';

        $payment = Paystackpayment::where('reference', $reference)->first();
        $finalStatus = $psStatus === 'success' ? 'success' : 'failed';

        if ($payment) {
            $payment->update([
                'status' => $finalStatus,
                'channel' => $data['channel'] ?? $payment->channel,
                'currency' => $data['currency'] ?? $payment->currency,
                'amount' => $data['amount'] ?? $payment->amount,
                'authorization_code' => $data['authorization']['authorization_code'] ?? $payment->authorization_code,
                'customer_email' => $data['customer']['email'] ?? $payment->customer_email,
                'paid_at' => $finalStatus === 'success' ? now() : $payment->paid_at,
                'meta' => array_merge($payment->meta ?? [], [
                    'verified_payload' => $data,
                ]),
            ]);
        } else {
            $payment = Paystackpayment::create([
                'reference' => $reference,
                'status' => $finalStatus,
                'channel' => $data['channel'] ?? null,
                'currency' => $data['currency'] ?? null,
                'amount' => $data['amount'] ?? 0,
                'authorization_code' => $data['authorization']['authorization_code'] ?? null,
                'customer_email' => $data['customer']['email'] ?? null,
                'paid_at' => $finalStatus === 'success' ? now() : null,
                'meta' => ['verified_payload' => $data],
            ]);
        }

        // ✅ NETBIL HOOK
        if ($payment->status === 'success') {
            // app(NetbilBillingService::class)->applyPaystackPayment($payment);
        }

        return view('payments.success', [
            'ok' => $payment->status === 'success',
            'message' => $payment->status === 'success'
                ? 'Payment verified successfully.'
                : 'Payment was not successful.',
            'reference' => $reference,
            'payment' => $payment,
        ]);
    }

    /**
     * Paystack Webhook endpoint.
     */
    public function paystackWebhook(Request $request, PaystackService $paystack)
    {
        $raw = $request->getContent();
        $signature = $request->header('x-paystack-signature');

        if (!$paystack->validateWebhookSignature($raw, $signature)) {
            return response()->json(['ok' => false, 'message' => 'Invalid signature'], 400);
        }

        $event = $request->input('event');
        $data = $request->input('data', []);

        if ($event === 'charge.success') {
            $reference = $data['reference'] ?? null;

            if ($reference) {
                $payment = Paystackpayment::where('reference', $reference)->first();

                if ($payment) {
                    if ($payment->status !== 'success') {
                        $payment->update([
                            'status' => 'success',
                            'channel' => $data['channel'] ?? $payment->channel,
                            'currency' => $data['currency'] ?? $payment->currency,
                            'amount' => $data['amount'] ?? $payment->amount,
                            'authorization_code' => $data['authorization']['authorization_code'] ?? $payment->authorization_code,
                            'customer_email' => $data['customer']['email'] ?? $payment->customer_email,
                            'paid_at' => now(),
                            'meta' => array_merge($payment->meta ?? [], [
                                'webhook_payload' => $data,
                            ]),
                        ]);

                        // ✅ NETBIL HOOK
                        // app(NetbilBillingService::class)->applyPaystackPayment($payment);
                    }
                } else {
                    $payment = Paystackpayment::create([
                        'reference' => $reference,
                        'status' => 'success',
                        'channel' => $data['channel'] ?? null,
                        'currency' => $data['currency'] ?? null,
                        'amount' => $data['amount'] ?? 0,
                        'authorization_code' => $data['authorization']['authorization_code'] ?? null,
                        'customer_email' => $data['customer']['email'] ?? null,
                        'paid_at' => now(),
                        'meta' => ['webhook_payload' => $data],
                    ]);

                    // ✅ NETBIL HOOK
                    // app(NetbilBillingService::class)->applyPaystackPayment($payment);
                }
            }
        }

        return response()->json(['ok' => true]);
    }
}
