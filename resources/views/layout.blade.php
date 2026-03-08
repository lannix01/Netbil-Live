@extends('inventory::layouts.app')

@php
    // Use these in any page:
    // @section('page-title', 'Items')
    // @section('page-subtitle', 'Manage your inventory')
    // @section('page-actions')
    //    <a class="btn btn-dark btn-sm" href="...">+ Create</a>
    // @endsection
@endphp

@section('content')
<div class="inv-page">

    <div class="inv-page-header inv-card inv-page-header-card">
        <div class="inv-page-titles">
            <h1 class="inv-h1">@yield('page-title', 'Inventory')</h1>
            @hasSection('page-subtitle')
                <div class="inv-sub">@yield('page-subtitle')</div>
            @else
                <div class="inv-sub"></div>
            @endif
        </div>

        <div class="inv-page-actions">
            @yield('page-actions')
        </div>
    </div>

    <div class="inv-page-body">
        @yield('inventory-content')
    </div>

</div>
@endsection
