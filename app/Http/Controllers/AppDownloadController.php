<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AppDownloadController extends Controller
{
    public function pettycash(): BinaryFileResponse
    {
        $candidates = [
            storage_path('app/public/apps/pettycash.apk'),
            storage_path('app/public/pettycash.apk'),
            storage_path('apps/pettycash.apk'),
            storage_path('pettycash.apk'),
        ];

        $apkPath = collect($candidates)->first(static fn (string $path): bool => is_file($path));

        if ($apkPath === null) {
            abort(404, 'PettyCash APK not found. Upload to storage/app/public/apps/pettycash.apk');
        }

        return response()->download(
            $apkPath,
            'pettycash.apk',
            [
                'Content-Type' => 'application/vnd.android.package-archive',
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ],
        );
    }
}
