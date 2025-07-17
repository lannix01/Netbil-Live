<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Message;

class ChatsController extends Controller
{
  public function index(Request $request)
{
    // Prepare recent contacts list (same logic)
    $recentChats = Message::select('phone')
        ->distinct()
        ->get()
        ->map(function ($msg) {
            $last = Message::where('phone', $msg->phone)->latest()->first();
            return [
                'phone' => $msg->phone,
                'last_message' => $last->text ?? '',
                'sent_at' => optional($last->sent_at)->diffForHumans() ?? 'N/A',
            ];
        });

    $recentChats = collect($recentChats)->sortByDesc('sent_at')->values();

    // Paginate recent contacts (separate pagination key: contacts_page)
    $recentChatsPaginated = new \Illuminate\Pagination\LengthAwarePaginator(
        $recentChats->slice($request->get('contacts_page', 1) * 4 - 4, 4)->values(),
        $recentChats->count(),
        5,
        $request->get('contacts_page', 1),
        ['path' => $request->url(), 'query' => $request->query()]
    );

    // Paginate messages with separate page key: messages_page
    $allMessages = Message::latest()->paginate(10, ['*'], 'messages_page');

    // ✅ AJAX request for MESSAGES pagination only
    if ($request->ajax() && $request->has('messages_page')) {
        return view('chats.partials.messages-table', compact('allMessages'))->render();
    }

    // ✅ AJAX request for CONTACTS pagination only
    if ($request->ajax()) {
    return response()->json([
        'html' => view('chats.partials.recent-contacts', [
            'recentChats' => $recentChatsPaginated
        ])->render()
    ]);
}


    // Normal full-page load
    return view('chats.index', [
        'recentChats' => $recentChatsPaginated,
        'allMessages' => $allMessages,
    ]);
}



    public function send(Request $request)
    {
        $request->validate([
            'to' => 'required|string',
            'message' => 'required|string',
        ]);

        $to = $request->to;
        $text = $request->message;

        // Send via TextSMS Kenya API
        $response = Http::post('https://sms.textsms.co.ke/api/services/sendsms/', [
            'apikey'    => '322ea13eb89cbca982933457dc115265',
            'partnerID' => '12368',
            'message'   => $text,
            'shortcode' => 'AMAZONS LTD',
            'mobile'    => $to,
        ]);

        if ($response->successful()) {
            // Save the message
            Message::create([
                'phone'   => $to,
                'text'    => $text,
                'sender'  => 'admin',
                'sent_at' => now(),
            ]);

            return back()->with('status', 'Message sent successfully!');
        } else {
            return back()->withErrors(['error' => 'Failed to send message: ' . $response->body()]);
        }
    }

    public function delete($id)
    {
        $msg = Message::find($id);

        if (!$msg) {
            return back()->withErrors(['error' => 'Message not found.']);
        }

        $msg->delete();
        return back()->with('status', 'Message deleted successfully.');
    }
    public function destroy($id)
{
    $message = Message::findOrFail($id);
    $message->delete();

    return back()->with('status', 'Message deleted successfully!');
}

}