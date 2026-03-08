@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">

    {{-- PAGE TITLE --}}
    <h4 class="fw-semibold mb-3">Settings</h4>

    {{-- SETTINGS TABS --}}
    <div class="settings-tabs mb-4">
        <a href="{{ route('settings.index', ['tab' => 'mikrotiks']) }}"
           class="settings-tab {{ $activeTab === 'mikrotiks' ? 'active' : '' }}">
            <i class="bi bi-router"></i> Mikrotiks
        </a>

        <a href="{{ route('settings.index', ['tab' => 'packages']) }}"
           class="settings-tab {{ $activeTab === 'packages' ? 'active' : '' }}">
            <i class="bi bi-box-seam"></i> Packages
        </a>

        <a href="{{ route('settings.index', ['tab' => 'portal']) }}"
           class="settings-tab {{ $activeTab === 'portal' ? 'active' : '' }}">
            <i class="bi bi-globe"></i> Portal Preview
        </a>

        <a href="{{ route('settings.index', ['tab' => 'ads']) }}"
           class="settings-tab {{ $activeTab === 'ads' ? 'active' : '' }}">
            <i class="bi bi-credit-card"></i> Captive Ads
        </a>

        <a href="{{ route('settings.index', ['tab' => 'subs']) }}"
           class="settings-tab {{ $activeTab === 'subs' ? 'active' : '' }}">
            <i class="bi bi-people"></i> Subscriptions
        </a>
    </div>

    {{-- TAB CONTENT --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">

            @switch($activeTab)

    {{-- MIKROTIK --}}
    @case('mikrotiks')
        @include('settings.partials.mikrotiks', [
            'connected' => $connected ?? false,
            'host' => $host ?? 'Unknown',
            'webfigUrl' => $webfigUrl ?? null,
            'system' => $system ?? []
        ])
        @break

    {{-- PACKAGES --}}
    @case('packages')
        @if($action === 'create')
            @include('settings.partials.packages-create')
        @elseif($action === 'edit' && isset($editPackage))
            @include('settings.partials.packages-edit', ['package' => $editPackage])
        @else
            @include('settings.partials.packages', ['packages' => $packages])
        @endif
        @break

    {{-- PORTAL --}}
    @case('portal')
        @include('settings.partials.portal', ['portal' => $portal])
        @break

    {{-- ADS --}}
    @case('ads')
        @include('settings.partials.ads', ['ads' => $ads])
        @break

    {{-- SUBSCRIPTIONS --}}
    @case('subs')
        @include('settings.partials.subs', ['subs' => $subs])
        @break

    @default
        <div class="text-muted">
            Select a settings module above.
        </div>
@endswitch

        </div>
    </div>

</div>
@endsection
