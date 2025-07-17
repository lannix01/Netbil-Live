@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2>MikroTik Connection Test</h2>

    @if($connected)
        <div class="alert alert-success">
            ✅ Successfully connected to MikroTik at <strong>{{ $host }}</strong>
        </div>

        <h4 class="mt-4">📊 System Status</h4>
        <ul class="list-group">
            <li class="list-group-item"><strong>Identity:</strong> {{ $system['identity'] }}</li>
            <li class="list-group-item"><strong>Board:</strong> {{ $system['boardname'] }}</li>
            <li class="list-group-item"><strong>RouterOS Version:</strong> {{ $system['version'] }}</li>
            <li class="list-group-item"><strong>Uptime:</strong> {{ $system['uptime'] }}</li>
            <li class="list-group-item"><strong>CPU:</strong> {{ $system['cpu'] }} ({{ $system['cpuLoad'] }}% load)</li>
            <li class="list-group-item"><strong>Memory:</strong> {{ $system['totalMem'] }} MB total / {{ $system['freeMem'] }} MB free</li>
        </ul>
    @else
        <div class="alert alert-danger">
            ❌ Failed to connect to MikroTik at <strong>{{ $host }}</strong>
        </div>
    @endif
</div>
@endsection
