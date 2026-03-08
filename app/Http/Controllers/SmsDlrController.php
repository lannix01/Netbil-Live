<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SmsDlrController extends Controller
{
    // Advanta callback payload example:
    // {
    //   "description": "DeliveredToTerminal",
    //   "messageid": "1889191",
    //   "timeTaken": "22s",
    //   "timestamp": "2025-09-12 10:16:10"
    // }

    public function advanta(Request $request)
    {
        $data = $request->all();

        Log::info('Advanta DLR callback', $data);

        $messageId = $data['messageid'] ?? null;
        $desc = $data['description'] ?? null;

        if (!$messageId) {
            return response()->json(['ok' => false, 'error' => 'missing messageid'], 400);
        }

        $msg = Message::where('message_id', $messageId)->first();

        if (!$msg) {
            return response()->json(['ok' => true, 'note' => 'message not found'], 200);
        }

        // Map Advanta description to our status
        $status = match (strtolower((string) $desc)) {
            'deliveredtoterminal', 'delivered' => 'DELIVERED',
            'expired', 'undelivered', 'rejected' => 'FAILED',
            default => 'PENDING',
        };

        $msg->status = $status;

        // Optional: store callback payload inside gateway_response for audit
        $existing = is_array($msg->gateway_response) ? $msg->gateway_response : [];
        $existing['dlr_callback'] = $data;
        $msg->gateway_response = $existing;

        $msg->save();

        return response()->json(['ok' => true], 200);
    }
}
