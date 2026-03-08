<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Services\MikrotikService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PackageController extends Controller
{
    public function index()
    {
        $packages = Package::latest()->get();
        return view('packages.index', compact('packages'));
    }

    public function create()
    {
        return view('packages.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'speed'       => 'nullable|string|max:50',
            'duration'    => 'required|integer|min:1',
            'price'       => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        // 🔒 System-controlled fields
        $data['mk_profile'] = 'pkg_' . Str::slug($data['name']);
        $data['rate_limit'] = $data['speed'];
        $data['duration_minutes'] = $data['duration'] * 60;
        $data['auto_create_profile'] = true;
        $data['status'] = 'active';

        try {
            DB::beginTransaction();

            $package = Package::create($data);

            app(MikrotikService::class)
                ->syncHotspotProfile($package); // CREATE ONLY

            DB::commit();

            return redirect()
                ->route('settings.index', ['tab' => 'packages'])
                ->with('success', 'Package created and synced to MikroTik');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return back()
                ->withInput()
                ->withErrors([
                    'error' => 'Failed to create package'
                ]);
        }
    }

    public function edit(Package $package)
    {
        return view('packages.edit', compact('package'));
    }

    public function update(Request $request, Package $package)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'speed'       => 'nullable|string|max:50',
            'duration'    => 'required|integer|min:1',
            'price'       => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'status'      => 'required|in:active,disabled',
        ]);

        // Detect changes that affect MikroTik
        $profileChanged =
            $package->rate_limit !== $data['speed'] ||
            $package->duration   !== $data['duration'] ||
            $package->status     !== $data['status'];

        $data['rate_limit'] = $data['speed'];
        $data['duration_minutes'] = $data['duration'] * 60;

        try {
            DB::beginTransaction();

            $package->update($data);

            if ($profileChanged && $package->auto_create_profile) {
                app(MikrotikService::class)
                    ->updateHotspotProfile($package);
            }

            DB::commit();

            return redirect()
                ->route('settings.index', ['tab' => 'packages'])
                ->with('success', 'Package updated');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return back()
                ->withErrors([
                    'error' => 'Failed to update package'
                ]);
        }
    }

    public function destroy(Package $package)
    {
        // ❗ For now, DO NOT delete MikroTik profile automatically
        // We will handle safe cleanup later

        $package->delete();

        return redirect()
            ->route('settings.index', ['tab' => 'packages'])
            ->with('success', 'Package deleted');
    }
}
