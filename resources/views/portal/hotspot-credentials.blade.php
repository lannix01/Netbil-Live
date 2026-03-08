@extends('layouts.guest')

@section('content')
<div class="container py-5 text-center">
    <h4>Your internet credentials</h4>
    <p><strong>Username:</strong> {{ $username }}</p>
    <p><strong>Password:</strong> {{ $password }}</p>

    @if(!empty($link))
        <form id="loginForm" method="post" action="{{ $link }}">
            <input type="hidden" name="username" value="{{ $username }}">
            <input type="hidden" name="password" value="{{ $password }}">
            <button class="btn btn-primary">Complete login</button>
        </form>
        <script>document.getElementById('loginForm').submit();</script>
    @else
        <p>Use the above credentials on the hotspot login page.</p>
    @endif
</div>
@endsection
