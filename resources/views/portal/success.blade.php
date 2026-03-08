@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="alert alert-success text-center" role="alert">
                <h1 class="mb-3">
                    <i class="fas fa-check-circle"></i>
                </h1>
                <h2>Connection Successful</h2>
                <p class="mt-3">Your internet connection has been established successfully.</p>
                <hr>
                <p class="text-muted mb-0">You are now online and ready to browse.</p>
            </div>
            
            <div class="text-center mt-4">
                <a href="/" class="btn btn-primary">Go to Dashboard</a>
            </div>
        </div>
    </div>
</div>
@endsection