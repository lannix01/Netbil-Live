<?php

namespace App\Modules\PettyCash\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PettyCash\Models\PettyNotification;
use App\Modules\PettyCash\Models\PettyNotificationAdminContact;
use App\Modules\PettyCash\Models\PettyNotificationSetting;
use App\Modules\PettyCash\Models\PettySmsTemplate;
use App\Modules\PettyCash\Models\PettySmsTemplateUsage;
use App\Modules\PettyCash\Services\TokenDueNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class NotificationsController extends Controller
{
    public function index(Request $request)
    {
        $this->purgeOldReadNotifications();

        $q = trim((string)$request->get('q', ''));
        $type = trim((string)$request->get('type', ''));
        $unread = $request->get('unread', '');

        $items = PettyNotification::query()
            ->where('module', 'pettycash')
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('hostel_name', 'like', "%{$q}%")
                        ->orWhere('meter_no', 'like', "%{$q}%")
                        ->orWhere('phone_no', 'like', "%{$q}%")
                        ->orWhere('title', 'like', "%{$q}%");
                });
            })
            ->when($type !== '', fn($qq) => $qq->where('type', $type))
            ->when($unread === '1', fn($qq) => $qq->where('is_read', false))
            ->when($unread === '0', fn($qq) => $qq->where('is_read', true))
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        $unreadCount = PettyNotification::where('module', 'pettycash')->where('is_read', false)->count();

        $settings = Schema::hasTable('petty_notification_settings')
            ? PettyNotificationSetting::current()
            : new PettyNotificationSetting([
                'sms_gateway' => 'advanta',
                'sms_enabled' => true,
                'low_balance_threshold' => 0,
                'low_credit_threshold' => 0,
            ]);

        $adminContacts = Schema::hasTable('petty_notification_admin_contacts')
            ? PettyNotificationAdminContact::query()->orderByDesc('id')->get()
            : collect();

        $templates = Schema::hasTable('petty_sms_templates')
            ? PettySmsTemplate::query()->orderByDesc('id')->get()
            : collect();

        $eventOptions = self::templateEventOptions();

        $templateUsage = (Schema::hasTable('petty_sms_template_usages') && Schema::hasTable('petty_sms_templates'))
            ? PettySmsTemplateUsage::query()->pluck('template_id', 'event_key')->toArray()
            : [];

        $placeholderCards = self::placeholderCards();
        $autoCheckResult = session('auto_check_result');

        return view('pettycash::notifications.index', compact(
            'items',
            'unreadCount',
            'q',
            'type',
            'unread',
            'settings',
            'adminContacts',
            'templates',
            'eventOptions',
            'templateUsage',
            'placeholderCards',
            'autoCheckResult'
        ));
    }

    public function runAutoCheck(Request $request, TokenDueNotificationService $service)
    {
        $data = $request->validate([
            'notify_admins' => ['nullable', 'boolean'],
        ]);

        $notifyAdmins = (bool)($data['notify_admins'] ?? false);

        $result = $service->runTomorrowShortfallCheck($notifyAdmins);

        $flash = $result['has_shortfall']
            ? (
                $notifyAdmins
                    ? ('Auto-check complete. Shortfall detected. SMS sent: ' . ((int)($result['sent_sms'] ?? 0)) . '.')
                    : 'Auto-check complete. Shortfall detected. Choose "Notify Admins Now" to send SMS.'
            )
            : 'Auto-check complete. Tomorrow amount is covered by current balance.';

        return back()->with([
            'success' => $flash,
            'open_sms_settings' => true,
            'auto_check_result' => $result,
        ]);
    }

    public function storeAdminContact(Request $request)
    {
        $request->merge([
            'phone_no' => $this->normalizePhone((string)$request->input('phone_no', '')),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'role' => ['nullable', 'string', 'max:120'],
            'phone_no' => ['required', 'string', 'max:32', 'unique:petty_notification_admin_contacts,phone_no'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        PettyNotificationAdminContact::create([
            'name' => $data['name'],
            'role' => $data['role'] ?? null,
            'phone_no' => $data['phone_no'],
            'is_active' => (bool)($data['is_active'] ?? true),
            'created_by' => auth('petty')->id(),
        ]);

        return back()->with([
            'success' => 'Admin contact saved.',
            'open_sms_settings' => true,
        ]);
    }

    public function destroyAdminContact(PettyNotificationAdminContact $contact)
    {
        $contact->delete();

        return back()->with([
            'success' => 'Admin contact removed.',
            'open_sms_settings' => true,
        ]);
    }

    public function storeSmsTemplate(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        PettySmsTemplate::create([
            'name' => $data['name'],
            'body' => $data['body'],
            'is_active' => (bool)($data['is_active'] ?? true),
            'created_by' => auth('petty')->id(),
        ]);

        return back()->with([
            'success' => 'SMS template saved.',
            'open_sms_settings' => true,
        ]);
    }

    public function destroySmsTemplate(PettySmsTemplate $template)
    {
        $template->delete();

        return back()->with([
            'success' => 'SMS template removed.',
            'open_sms_settings' => true,
        ]);
    }

    public function saveSmsSettings(Request $request)
    {
        $rules = [
            'sms_gateway' => ['required', 'in:advanta,amazons'],
            'sms_enabled' => ['nullable', 'boolean'],
            'low_balance_threshold' => ['nullable', 'numeric', 'min:0'],
            'low_credit_threshold' => ['nullable', 'numeric', 'min:0'],
            'template_usage' => ['nullable', 'array'],
        ];

        foreach (array_keys(self::templateEventOptions()) as $eventKey) {
            $rules['template_usage.' . $eventKey] = ['nullable', 'integer', 'exists:petty_sms_templates,id'];
        }

        $data = $request->validate($rules);

        $settings = PettyNotificationSetting::current();
        $settings->fill([
            'sms_gateway' => $data['sms_gateway'],
            'sms_enabled' => (bool)($data['sms_enabled'] ?? false),
            'low_balance_threshold' => (float)($data['low_balance_threshold'] ?? 0),
            'low_credit_threshold' => (float)($data['low_credit_threshold'] ?? 0),
            'updated_by' => auth('petty')->id(),
        ]);
        $settings->save();

        $selected = (array)($data['template_usage'] ?? []);
        foreach (array_keys(self::templateEventOptions()) as $eventKey) {
            PettySmsTemplateUsage::updateOrCreate(
                ['event_key' => $eventKey],
                [
                    'template_id' => !empty($selected[$eventKey]) ? (int)$selected[$eventKey] : null,
                    'updated_by' => auth('petty')->id(),
                ]
            );
        }

        return back()->with([
            'success' => 'SMS settings saved.',
            'open_sms_settings' => true,
        ]);
    }

    public function markRead(PettyNotification $notification)
    {
        $notification->is_read = true;
        $notification->read_at = now();
        $notification->save();
        $this->purgeOldReadNotifications();

        return back()->with('success', 'Notification marked as read.');
    }

    public function markAllRead()
    {
        PettyNotification::where('module', 'pettycash')
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        $this->purgeOldReadNotifications();

        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * @return array<string, string>
     */
    public static function templateEventOptions(): array
    {
        return [
            'token_due_3' => 'Token due in 3 days',
            'token_due_2' => 'Token due in 2 days',
            'token_due_1' => 'Token due tomorrow',
            'token_due_today' => 'Token due today',
            'token_overdue' => 'Token overdue',
            'due_tomorrow_shortfall' => 'Due tomorrow shortfall (auto-check)',
            'low_balance' => 'Low balance',
            'low_credit' => 'Low credit',
        ];
    }

    /**
     * @return array<int, array{key:string,label:string}>
     */
    public static function placeholderCards(): array
    {
        return [
            ['key' => '{{name}}', 'label' => 'Recipient/Admin name'],
            ['key' => '{{role}}', 'label' => 'Recipient role'],
            ['key' => '{{phone}}', 'label' => 'Recipient phone'],
            ['key' => '{{hostel_name}}', 'label' => 'Hostel name'],
            ['key' => '{{meter_no}}', 'label' => 'Hostel meter number'],
            ['key' => '{{hostel_phone}}', 'label' => 'Hostel phone number'],
            ['key' => '{{amount_due}}', 'label' => 'Hostel amount due'],
            ['key' => '{{due_date}}', 'label' => 'Due date'],
            ['key' => '{{days_to_due}}', 'label' => 'Days to due (negative = overdue)'],
            ['key' => '{{balance}}', 'label' => 'Current petty cash balance'],
            ['key' => '{{credit_balance}}', 'label' => 'Credit pool balance'],
            ['key' => '{{low_balance_threshold}}', 'label' => 'Configured low balance threshold'],
            ['key' => '{{low_credit_threshold}}', 'label' => 'Configured low credit threshold'],
            ['key' => '{{total_hostels}}', 'label' => 'Total hostels'],
            ['key' => '{{due_today_count}}', 'label' => 'Hostels due today count'],
            ['key' => '{{due_tomorrow_count}}', 'label' => 'Hostels due tomorrow count'],
            ['key' => '{{due_tomorrow_total_amount}}', 'label' => 'Total amount due tomorrow'],
            ['key' => '{{expected_amount_tomorrow}}', 'label' => 'Alias for due_tomorrow_total_amount'],
            ['key' => '{{due_tomorrow_shortfall}}', 'label' => 'How much tomorrow exceeds current balance'],
            ['key' => '{{shortfall_amount}}', 'label' => 'Alias for due_tomorrow_shortfall'],
            ['key' => '{{due_2_days_count}}', 'label' => 'Hostels due in 2 days count'],
            ['key' => '{{due_3_days_count}}', 'label' => 'Hostels due in 3 days count'],
            ['key' => '{{overdue_count}}', 'label' => 'Overdue hostels count'],
            ['key' => '{{due_today_list}}', 'label' => 'Comma-separated hostels due today'],
            ['key' => '{{due_tomorrow_list}}', 'label' => 'Comma-separated hostels due tomorrow'],
            ['key' => '{{due_2_days_list}}', 'label' => 'Comma-separated hostels due in 2 days'],
            ['key' => '{{due_3_days_list}}', 'label' => 'Comma-separated hostels due in 3 days'],
            ['key' => '{{overdue_list}}', 'label' => 'Comma-separated overdue hostels'],
            ['key' => '{{total_due_today_amount}}', 'label' => 'Total amount due today'],
            ['key' => '{{amounts}}', 'label' => 'Alias for total_due_today_amount'],
            ['key' => '{{generated_at}}', 'label' => 'Generation date/time'],
        ];
    }

    private function normalizePhone(string $phone): string
    {
        $phone = trim($phone);
        $phone = preg_replace('/\s+/', '', $phone) ?: '';

        if (str_starts_with($phone, '+')) {
            $phone = substr($phone, 1);
        }

        if (preg_match('/^07\d{8}$/', $phone)) {
            $phone = '254' . substr($phone, 1);
        }

        return $phone;
    }

    private function purgeOldReadNotifications(): void
    {
        $cutoff = now()->subDay();

        PettyNotification::query()
            ->where('module', 'pettycash')
            ->where('is_read', true)
            ->where(function ($q) use ($cutoff) {
                $q->where('read_at', '<=', $cutoff)
                    ->orWhere(function ($fallback) use ($cutoff) {
                        $fallback->whereNull('read_at')
                            ->where('updated_at', '<=', $cutoff);
                    });
            })
            ->delete();
    }
}
