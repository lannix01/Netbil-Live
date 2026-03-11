<?php

namespace App\Modules\PettyCash\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PettyCash\Models\Respondent;
use App\Modules\PettyCash\Models\PettyNotificationSetting;
use App\Services\Sms\AdvantaSmsService;
use App\Services\Sms\AmazonsSmsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RespondentController extends Controller
{
    public function index()
    {
        $respondents = Respondent::orderBy('name')->paginate(20)->withQueryString();
        return view('pettycash::respondents.index', compact('respondents'));
    }

    public function create()
    {
        return view('pettycash::respondents.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateProfilePayload($request, false);

        $payload = collect($data)->except(['profile_photo', 'remove_photo'])->toArray();
        $payload['profile_photo_path'] = $this->storeProfilePhotoIfPresent($request, null);

        Respondent::create($payload);

        return redirect()->route('petty.respondents.index')->with('success', 'Respondent added.');
    }

    public function show(Respondent $respondent)
    {
        $photoPreviewDataUri = $this->photoDataUri($respondent->profile_photo_path);
        $publicCardUrl = $respondent->card_public_token
            ? route('petty.respondents.card.public.show', ['token' => $respondent->card_public_token])
            : null;

        return view('pettycash::respondents.show', compact(
            'respondent',
            'photoPreviewDataUri',
            'publicCardUrl'
        ));
    }

    public function edit(Respondent $respondent)
    {
        return redirect()->route('petty.respondents.show', $respondent->id);
    }

    public function update(Request $request, Respondent $respondent)
    {
        $data = $this->validateProfilePayload($request, true);

        $payload = collect($data)->except(['profile_photo', 'remove_photo'])->toArray();
        $payload['profile_photo_path'] = $respondent->profile_photo_path;

        if ((bool)($data['remove_photo'] ?? false) && !empty($respondent->profile_photo_path)) {
            Storage::disk('public')->delete($respondent->profile_photo_path);
            $payload['profile_photo_path'] = null;
        }

        $newPhotoPath = $this->storeProfilePhotoIfPresent($request, $respondent->profile_photo_path);
        if ($newPhotoPath !== null) {
            $payload['profile_photo_path'] = $newPhotoPath;
        }

        $respondent->update($payload);

        return redirect()->route('petty.respondents.show', $respondent->id)->with('success', 'Respondent profile updated.');
    }

    public function generateCard(Respondent $respondent)
    {
        $normalizedPhone = $this->normalizePhone((string) $respondent->phone);
        if ($normalizedPhone === '') {
            return back()->with('error', 'Phone number is required before generating a downloadable verification card.');
        }

        $photoDataUri = $this->photoDataUri($respondent->profile_photo_path);
        $token = (string) ($respondent->card_public_token ?: Str::random(64));
        $publicCardUrl = route('petty.respondents.card.public.show', ['token' => $token]);
        $logoDataUri = $this->logoDataUri();
        $qrDataUri = $this->qrDataUri($publicCardUrl);
        $relativePath = 'apps/respondents/cards/respondent-card-' . $respondent->id . '-' . now()->format('Ymd-His') . '.pdf';

        $pdf = Pdf::loadView('pettycash::respondents.card_pdf', [
            'respondent' => $respondent,
            'photoDataUri' => $photoDataUri,
            'logoDataUri' => $logoDataUri,
            'qrDataUri' => $qrDataUri,
            'publicCardUrl' => $publicCardUrl,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        if (!empty($respondent->card_file_path) && Storage::disk('public')->exists((string) $respondent->card_file_path)) {
            Storage::disk('public')->delete((string) $respondent->card_file_path);
        }

        Storage::disk('public')->put($relativePath, $pdf->output());

        $respondent->update([
            'card_public_token' => $token,
            'card_file_path' => $relativePath,
            'card_generated_at' => now(),
        ]);

        return back()->with('success', 'Verification card generated.');
    }

    public function sendCardLinkSms(Respondent $respondent)
    {
        if (empty($respondent->card_public_token) || empty($respondent->card_file_path)) {
            return back()->with('error', 'Generate the verification card first.');
        }

        if (!Storage::disk('public')->exists((string) $respondent->card_file_path)) {
            return back()->with('error', 'Card file not found. Generate the card again.');
        }

        $phone = $this->normalizePhone((string) $respondent->phone);
        if ($phone === '') {
            return back()->with('error', 'Phone number is required to send SMS.');
        }

        $settings = PettyNotificationSetting::current();
        if (!$settings->sms_enabled) {
            return back()->with('error', 'SMS is currently disabled in PettyCash notification settings.');
        }

        $gateway = strtolower((string) ($settings->sms_gateway ?: 'advanta'));
        $service = $gateway === 'amazons'
            ? app(AmazonsSmsService::class)
            : app(AdvantaSmsService::class);

        $publicLink = route('petty.respondents.card.public.show', ['token' => $respondent->card_public_token]);
        $message = sprintf(
            'Hello %s, your SKYBRIX verified card is ready. Download link: %s. Password is your phone number.',
            trim((string) $respondent->name) !== '' ? trim((string) $respondent->name) : 'Respondent',
            $publicLink
        );

        try {
            $response = $service->send($phone, $message);
            if (!$this->isSmsSuccess((array) $response)) {
                return back()->with('error', 'SMS gateway rejected the message. Please try again.');
            }
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to send SMS: ' . $e->getMessage());
        }

        $respondent->update(['card_sms_sent_at' => now()]);

        return back()->with('success', 'Card link SMS sent successfully.');
    }

    public function publicCard(string $token)
    {
        $respondent = $this->findRespondentByCardToken($token);

        return view('pettycash::respondents.public_card', [
            'respondent' => $respondent,
            'token' => $token,
        ]);
    }

    public function publicCardDownload(Request $request, string $token)
    {
        $respondent = $this->findRespondentByCardToken($token);

        $data = $request->validate([
            'password' => ['required', 'string', 'max:40'],
        ]);

        $provided = $this->normalizePhone((string) $data['password']);
        $expected = $this->normalizePhone((string) $respondent->phone);

        if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
            return back()->withInput()->with('error', 'Invalid password. Use the registered phone number.');
        }

        $relativePath = (string) $respondent->card_file_path;
        if ($relativePath === '' || !Storage::disk('public')->exists($relativePath)) {
            throw new NotFoundHttpException('Card file not found.');
        }

        $nameForFile = trim((string) $respondent->name);
        if ($nameForFile === '') {
            $nameForFile = 'respondent-' . $respondent->id;
        }
        $downloadName = 'skybrix-verified-card-' . Str::slug($nameForFile) . '.pdf';

        return response()->download(
            Storage::disk('public')->path($relativePath),
            $downloadName,
            ['Content-Type' => 'application/pdf']
        );
    }

    private function validateProfilePayload(Request $request, bool $isUpdate): array
    {
        $rules = [
            'name' => ['required','string','max:150'],
            'phone' => ['nullable','string','max:50'],
            'category' => ['nullable','string','max:80'],
            'profile_title' => ['nullable', 'string', 'max:120'],
            'profile_email' => ['nullable', 'email', 'max:160'],
            'profile_location' => ['nullable', 'string', 'max:180'],
            'profile_notes' => ['nullable', 'string', 'max:1500'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];

        if ($isUpdate) {
            $rules['remove_photo'] = ['nullable', 'boolean'];
        }

        return $request->validate($rules);
    }

    private function storeProfilePhotoIfPresent(Request $request, ?string $oldPath): ?string
    {
        if (!$request->hasFile('profile_photo')) {
            return null;
        }

        $file = $request->file('profile_photo');
        if (!$file) {
            return null;
        }

        $base = Str::slug(pathinfo((string) $file->getClientOriginalName(), PATHINFO_FILENAME));
        if ($base === '') {
            $base = 'respondent-photo';
        }

        $filename = $base . '-' . now()->format('YmdHis') . '-' . Str::random(6) . '.' . strtolower((string) $file->getClientOriginalExtension());
        $path = $file->storeAs('apps/respondents/photos', $filename, 'public');

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return $path;
    }

    private function findRespondentByCardToken(string $token): Respondent
    {
        $respondent = Respondent::query()
            ->where('card_public_token', $token)
            ->first();

        if (!$respondent) {
            throw new NotFoundHttpException('Card link is invalid or expired.');
        }

        return $respondent;
    }

    private function photoDataUri(?string $relativePath): ?string
    {
        $path = trim((string) $relativePath);
        if ($path === '' || !Storage::disk('public')->exists($path)) {
            return null;
        }

        $binary = Storage::disk('public')->get($path);
        if ($binary === '') {
            return null;
        }

        $mime = Storage::disk('public')->mimeType($path) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode($binary);
    }

    private function logoDataUri(): ?string
    {
        $candidates = [
            base_path('skybrix-logo.png'),
            public_path('logo.png'),
            public_path('assets/images/logo.png'),
            public_path('assets/logo.png'),
            public_path('assets/images/avatar.png'),
        ];

        foreach ($candidates as $absolutePath) {
            if (!is_file($absolutePath)) {
                continue;
            }

            $binary = @file_get_contents($absolutePath);
            if ($binary === false || $binary === '') {
                continue;
            }

            $mime = mime_content_type($absolutePath) ?: 'image/png';

            return 'data:' . $mime . ';base64,' . base64_encode($binary);
        }

        return null;
    }

    private function qrDataUri(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $urls = [
            'https://quickchart.io/qr?size=220&margin=1&text=' . rawurlencode($value),
            'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . rawurlencode($value),
        ];

        foreach ($urls as $url) {
            try {
                $response = Http::timeout(8)->accept('image/png')->get($url);
            } catch (\Throwable $e) {
                continue;
            }

            if (!$response->successful()) {
                continue;
            }

            $binary = $response->body();
            if ($binary === '') {
                continue;
            }

            return 'data:image/png;base64,' . base64_encode($binary);
        }

        return null;
    }

    private function normalizePhone(string $phone): string
    {
        $phone = trim($phone);
        if ($phone === '') {
            return '';
        }

        if (str_starts_with($phone, '+')) {
            $phone = substr($phone, 1);
        }
        $phone = preg_replace('/\D+/', '', $phone) ?: '';

        if (preg_match('/^07\d{8}$/', $phone)) {
            return '254' . substr($phone, 1);
        }
        if (preg_match('/^01\d{8}$/', $phone)) {
            return '254' . substr($phone, 1);
        }

        return $phone;
    }

    private function isSmsSuccess(array $response): bool
    {
        if (array_key_exists('success', $response)) {
            return (bool) $response['success'];
        }

        if (isset($response['responses'][0]['response-code'])) {
            return (string) $response['responses'][0]['response-code'] === '200';
        }

        if (isset($response['response-code'])) {
            return (string) $response['response-code'] === '200';
        }

        if (isset($response['status'])) {
            return in_array(strtolower((string) $response['status']), ['ok', 'success', 'sent'], true);
        }

        return false;
    }
}
