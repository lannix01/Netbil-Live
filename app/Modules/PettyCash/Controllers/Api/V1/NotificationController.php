<?php

namespace App\Modules\PettyCash\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\PettyCash\Models\Hostel;
use App\Modules\PettyCash\Models\PettyNotification;
use App\Modules\PettyCash\Support\ApiResponder;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponder;

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $type = trim((string) $request->query('type', ''));
        $unread = $request->query('unread');

        $perPageOptions = [15, 25, 30, 50, 100];
        $perPage = (int) $request->integer('per_page', 25);
        if (!in_array($perPage, $perPageOptions, true)) {
            $perPage = 25;
        }

        $notifications = PettyNotification::query()
            ->where('module', 'pettycash')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($nested) use ($q) {
                    $nested->where('title', 'like', '%' . $q . '%')
                        ->orWhere('message', 'like', '%' . $q . '%')
                        ->orWhere('hostel_name', 'like', '%' . $q . '%')
                        ->orWhere('meter_no', 'like', '%' . $q . '%')
                        ->orWhere('phone_no', 'like', '%' . $q . '%');
                });
            })
            ->when($type !== '', fn ($query) => $query->where('type', $type))
            ->when(in_array($unread, ['0', '1', 0, 1], true), function ($query) use ($unread) {
                $query->where('is_read', (string) $unread === '0');
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $unreadCount = (int) PettyNotification::query()
            ->where('module', 'pettycash')
            ->where('is_read', false)
            ->count();

        return $this->successResponse([
            'notifications' => collect($notifications->items())
                ->map(fn (PettyNotification $item) => $this->mapNotification($item))
                ->values(),
            'summary' => [
                'unread_count' => $unreadCount,
            ],
            'filters' => [
                'q' => $q,
                'type' => $type,
                'unread' => $unread,
                'per_page' => $perPage,
            ],
        ], 'Notifications fetched.', 200, [
            'pagination' => $this->paginationMeta($notifications),
        ]);
    }

    public function store(Request $request)
    {
        if ($deny = $this->denyIfRoleNotIn($request, ['admin', 'accountant', 'finance'])) {
            return $deny;
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:1000'],
            'type' => ['nullable', 'string', 'max:80'],
            'channel' => ['nullable', 'string', 'max:40'],
            'hostel_id' => ['nullable', 'integer', 'exists:petty_hostels,id'],
            'due_date' => ['nullable', 'date'],
            'days_to_due' => ['nullable', 'integer'],
            'hostel_name' => ['nullable', 'string', 'max:255'],
            'meter_no' => ['nullable', 'string', 'max:100'],
            'phone_no' => ['nullable', 'string', 'max:100'],
        ]);

        $hostel = null;
        if (!empty($data['hostel_id'])) {
            $hostel = Hostel::query()->find($data['hostel_id']);
        }

        $item = PettyNotification::query()->create([
            'module' => 'pettycash',
            'type' => $data['type'] ?? 'manual_notice',
            'channel' => $data['channel'] ?? 'app',
            'title' => $data['title'],
            'message' => $data['message'],
            'hostel_id' => $hostel?->id,
            'due_date' => $data['due_date'] ?? null,
            'days_to_due' => $data['days_to_due'] ?? null,
            'hostel_name' => $data['hostel_name'] ?? $hostel?->hostel_name,
            'meter_no' => $data['meter_no'] ?? $hostel?->meter_no,
            'phone_no' => $data['phone_no'] ?? $hostel?->phone_no,
            'is_read' => false,
            'read_at' => null,
            'sent_email_at' => null,
            'sent_sms_at' => null,
            'send_error' => null,
            'dedupe_key' => null,
        ]);

        return $this->successResponse([
            'notification' => $this->mapNotification($item),
        ], 'Notification created.', 201);
    }

    public function markRead(PettyNotification $notification)
    {
        if ($notification->module !== 'pettycash') {
            return $this->errorResponse('Notification not found.', 404);
        }

        $notification->is_read = true;
        $notification->read_at = now();
        $notification->save();

        return $this->successResponse([
            'notification' => $this->mapNotification($notification->fresh()),
        ], 'Notification marked as read.');
    }

    public function markAllRead()
    {
        $updated = PettyNotification::query()
            ->where('module', 'pettycash')
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return $this->successResponse([
            'updated_count' => (int) $updated,
        ], 'All notifications marked as read.');
    }

    private function mapNotification(PettyNotification $item): array
    {
        return [
            'id' => $item->id,
            'module' => $item->module,
            'type' => $item->type,
            'channel' => $item->channel,
            'title' => $item->title,
            'message' => $item->message,
            'hostel_id' => $item->hostel_id,
            'hostel_name' => $item->hostel_name,
            'meter_no' => $item->meter_no,
            'phone_no' => $item->phone_no,
            'due_date' => optional($item->due_date)->format('Y-m-d'),
            'days_to_due' => $item->days_to_due,
            'is_read' => (bool) $item->is_read,
            'read_at' => optional($item->read_at)->format('Y-m-d H:i:s'),
            'created_at' => optional($item->created_at)->format('Y-m-d H:i:s'),
        ];
    }

    private function denyIfRoleNotIn(Request $request, array $roles)
    {
        $user = $request->attributes->get('pettyUser');
        $role = (string) ($user?->role ?? '');

        if (!in_array($role, $roles, true)) {
            return $this->errorResponse('Forbidden.', 403);
        }

        return null;
    }
}
