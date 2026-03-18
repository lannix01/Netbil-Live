@extends('inventory::layouts.app')

@section('content')
<div class="inv-page">
    @php($pageToolbar = trim($__env->yieldContent('page-toolbar')))
    <div class="inv-page-header inv-card inv-page-header-card">
        <div class="inv-page-header-top">
            <div class="inv-page-titles">
                <h1 class="inv-h1">@yield('page-title', 'Inventory')</h1>
                @hasSection('page-subtitle')
                    <div class="inv-sub">@yield('page-subtitle')</div>
                @endif
            </div>

            <div class="inv-page-actions">
                @yield('page-actions')
            </div>
        </div>

        @if($pageToolbar !== '')
            <div class="inv-page-toolbar">
                {!! $pageToolbar !!}
            </div>
        @endif
    </div>

    <div class="inv-page-body">
        @yield('inventory-content')
    </div>
</div>
@endsection
