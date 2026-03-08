@extends('layouts.portal')

@section('content')
<div class="container py-5">

    <div class="card shadow-sm border-0">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">Connected – Hotspot Package</h5>
        </div>

        <div class="card-body">
            <p><strong>Package:</strong> {{ $package->name ?? 'Unknown' }}</p>
            <p><strong>Speed:</strong> {{ $package->speed }}</p>
            <p><strong>Session IP:</strong> {{ $session['ip'] }}</p>
            <p><strong>MAC:</strong> {{ $session['mac-address'] }}</p>
            <p><strong>Uptime:</strong> {{ $session['uptime'] }}</p>

            <hr>

            <form method="post" action="{{ route('connect.reconnect') }}">
                @csrf
                <input type="hidden" name="username" value="{{ $session['user'] }}">
                <input type="hidden" name="ip" value="{{ $session['ip'] }}">
                <button class="btn btn-outline-primary w-100 mb-2">
                     Reconnect
                </button>
            </form>

            <a href="{{ route('connect.renew') }}" class="btn btn-danger w-100">
                 Renew Package
            </a>
        </div>
    </div>

</div>
@endsection