<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Services\Sms\AdvantaSmsService;
use App\Services\Sms\AmazonsSmsService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ChatsController extends Controller
{
    /* =====================================================
       CHATS INDEX + MINI DASHES
    ===================================================== */
    public function index(Request $request)
    {
        $hasPhoneNorm = Schema::hasColumn('messages', 'phone_norm');
        $hasPhoneRaw  = Schema::hasColumn('messages', 'phone_raw');

        // ----- Recent chats (unique phones, latest message per phone) -----
        // Use normalized column if available; otherwise compute in memory (still works).
        if ($hasPhoneNorm) {
            $recentChats = Message::select('phone_norm')
                ->whereNotNull('phone_norm')
                ->distinct()
                ->get()
                ->map(function ($msg) {
                    $last = Message::where('phone_norm', $msg->phone_norm)
                        ->latest('sent_at')
                        ->first();

                    return [
                        'phone' => $last?->phone_raw ?? $last?->phone ?? $msg->phone_norm,
                        'phone_norm' => $msg->phone_norm,
                        'last_message' => $last?->text,
                        'sent_at' => $last?->sent_at,
                    ];
                })
                ->sortByDesc('sent_at')
                ->values();
        } else {
            // Fallback: current behavior but normalize to reduce duplicates + thread mismatch
            $recentChats = Message::select('phone')
                ->distinct()
                ->get()
                ->map(function ($msg) {
                    $norm = $this->normalizePhone($msg->phone);
                    $last = Message::whereIn('phone', $this->phoneCandidates($norm))
                        ->latest('sent_at')
                        ->first();

                    return [
                        'phone' => $last?->phone ?? $msg->phone,
                        'phone_norm' => $norm,
                        'last_message' => $last?->text,
                        'sent_at' => $last?->sent_at,
                    ];
                })
                ->sortByDesc('sent_at')
                ->values()
                ->unique('phone_norm')
                ->values();
        }

        $contactsPage = (int) $request->get('contacts_page', 1);
        $perPage = 10;

        $recentChatsPaginated = new LengthAwarePaginator(
            $recentChats->forPage($contactsPage, $perPage),
            $recentChats->count(),
            $perPage,
            $contactsPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        // ----- All messages table -----
        $allMessages = Message::latest('sent_at')
            ->paginate(15, ['*'], 'messages_page');

        // ----- Mini dash stats (safe even if columns don't exist yet) -----
        $sentToday = Message::whereDate('created_at', today())->count();

        $hasStatus = Schema::hasColumn('messages', 'status');
        $delivered = $hasStatus ? Message::where('status', 'DELIVERED')->count() : 0;
        $failedCount = $hasStatus ? Message::whereIn('status', ['FAILED', 'PENDING'])->count() : 0;

        $total = Message::count();
        $deliveryRate = $total > 0 ? round(($delivered / $total) * 100) : 0;

        // ----- Advanta balance -----
        $advantaBalance = null;
        try {
            $balanceRes = app(AdvantaSmsService::class)->balance();
            if (is_array($balanceRes) && (($balanceRes['response-code'] ?? null) == 200)) {
                $advantaBalance = isset($balanceRes['credit']) ? (float) $balanceRes['credit'] : null;
            }
        } catch (\Throwable $e) {
            $advantaBalance = null;
        }

        return view('chats.index', compact(
            'recentChatsPaginated',
            'allMessages',
            'sentToday',
            'deliveryRate',
            'failedCount',
            'advantaBalance'
        ));
    }

    /* =====================================================
       SEND MESSAGE (ADVANTA DEFAULT)
    ===================================================== */
    public function send(Request $request)
    {
        $data = $request->validate([
            'to' => 'required|string',
            'message' => 'required|string',
            'gateway' => 'nullable|in:amazons,advanta',
        ]);

        $gatewayName = $data['gateway'] ?? 'advanta';
        $gateway = $gatewayName === 'amazons'
            ? app(\App\Services\Sms\AmazonsSmsService::class)
            : app(\App\Services\Sms\AdvantaSmsService::class);

        // Normalize phone ONCE and store it (this fixes your thread)
        $rawPhone = trim($data['to']);
        $normPhone = $this->normalizePhone($rawPhone);

        // create DB record first
        $payload = [
            // IMPORTANT: store canonical in phone so old queries keep working
            'phone' => $normPhone,
            'text' => $data['message'],
            'sender' => $gatewayName,
            'status' => 'PENDING',
            'sent_at' => now(),
        ];

        // If you later add these columns, we populate them automatically
        if (Schema::hasColumn('messages', 'phone_raw')) {
            $payload['phone_raw'] = $rawPhone;
        }
        if (Schema::hasColumn('messages', 'phone_norm')) {
            $payload['phone_norm'] = $normPhone;
        }

        $msg = Message::create($payload);

        // Try immediate send (send to the normalized/canonical)
        $res = $gateway->send($normPhone, $msg->text);

        if (($res['success'] ?? false) === true) {
            $msg->status = 'SENT';
            $msg->message_id = $res['message_id'] ?? null;
            $msg->gateway_response = $res;
            $msg->save();

            return back()->with('status', 'Message sent immediately.');
        }

        // If failed: queue a retry after 30 seconds
        $msg->status = 'FAILED';
        $msg->gateway_response = $res;
        $msg->save();

        dispatch((new \App\Jobs\SendSmsJob($msg->id))->delay(now()->addSeconds(30)));

        return back()->withErrors('Send failed. Queued for retry in 30 seconds.');
    }

    /* =====================================================
       RETRY FAILED / PENDING
    ===================================================== */
    public function retry($id)
    {
        $msg = Message::findOrFail($id);

        $hasStatus = Schema::hasColumn('messages', 'status');
        if ($hasStatus && !in_array($msg->status, ['FAILED', 'PENDING'])) {
            return back()->withErrors('Only FAILED/PENDING messages can be retried.');
        }

        $gatewayName = $msg->sender ?? 'advanta';

        if (class_exists(\App\Jobs\SendSmsJob::class)) {
            if ($hasStatus) {
                $msg->status = 'PENDING';
                $msg->save();
            }
            dispatch(new \App\Jobs\SendSmsJob($msg->id));
            return back()->with('status', 'Retry queued.');
        }

        $gateway = match ($gatewayName) {
            'amazons' => app(AmazonsSmsService::class),
            default   => app(AdvantaSmsService::class),
        };

        // Always retry using canonical normalized phone
        $normPhone = $this->normalizePhone($msg->phone);
        $response = $gateway->send($normPhone, $msg->text);

        if (Schema::hasColumn('messages', 'gateway_response')) {
            $existing = is_array($msg->gateway_response) ? $msg->gateway_response : [];
            $existing['retry'] = $response;
            $msg->gateway_response = $existing;
        }

        if (!($response['success'] ?? false)) {
            if ($hasStatus) $msg->status = 'FAILED';
            $msg->save();
            return back()->withErrors('Retry failed.');
        }

        if ($hasStatus) $msg->status = 'SENT';
        if (Schema::hasColumn('messages', 'message_id')) {
            $msg->message_id = $response['message_id'] ?? $msg->message_id;
        }
        $msg->sent_at = now();
        $msg->save();

        return back()->with('status', 'Retry sent successfully.');
    }

    /* =====================================================
       THREAD VIEW (AJAX Partial)
       - FIXED: match BOTH stored formats
    ===================================================== */
    public function thread($phone)
    {
        $norm = $this->normalizePhone($phone);

        // Match all likely stored formats (handles old rows saved as 07xx)
        $candidates = $this->phoneCandidates($norm);

        $messages = Message::whereIn('phone', $candidates)
            ->orderBy('sent_at', 'asc')
            ->get();

        // Display label: show canonical
        $displayPhone = $norm;

        return view('chats.partials.thread', [
            'messages' => $messages,
            'phone' => $displayPhone,
        ]);
    }

    /* =====================================================
       DELETE MESSAGE
    ===================================================== */
    public function destroy($id)
    {
        Message::findOrFail($id)->delete();
        return back()->with('status', 'Message deleted successfully.');
    }

    /* =====================================================
       Helpers
    ===================================================== */

    /**
     * Canonical normalize:
     * - digits only (keeps simple)
     * - 07xx -> 2547xx
     * - +254 -> 254
     */
    private function normalizePhone(string $phone): string
    {
        $p = trim($phone);

        // remove spaces and common separators
        $p = str_replace([' ', '-', '(', ')'], '', $p);

        // drop leading +
        if (Str::startsWith($p, '+')) $p = substr($p, 1);

        // keep digits only (helps a LOT)
        $p = preg_replace('/\D+/', '', $p);

        // Convert 07xx / 01xx -> 2547xx / 2541xx
        if (Str::startsWith($p, '0') && strlen($p) >= 10) {
            $p = '254' . substr($p, 1);
        }

        // If user typed 7xxxxxxxx without 0
        if (Str::startsWith($p, '7') && (strlen($p) === 9 || strlen($p) === 10)) {
            $p = '254' . $p;
        }

        return $p;
    }

    /**
     * Given canonical 2547..., return possible stored variants:
     * - 2547...
     * - +2547... (rare in DB but safe)
     * - 07... (common old storage)
     */
    private function phoneCandidates(string $canonical): array
    {
        $c = trim($canonical);
        if ($c === '') return [];

        $cand = [$c];

        // +254...
        $cand[] = '+' . $c;

        // 2547xxxxxxxx -> 07xxxxxxxx
        if (Str::startsWith($c, '254') && strlen($c) >= 12) {
            $cand[] = '0' . substr($c, 3);
        }

        // Deduplicate
        return array_values(array_unique($cand));
    }
}
