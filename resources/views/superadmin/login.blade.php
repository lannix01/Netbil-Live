<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SuperAdmin Login - NetBil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">

<div class="card p-4 shadow-sm" style="width: 360px;">
    <h3 class="text-center mb-4">SuperAdmin Login</h3>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('superadmin.login.post') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" 
                   name="email" 
                   id="email" 
                   class="form-control" 
                   placeholder="authenticator@example.com" 
                   required 
                   autofocus
                   value="{{ old('email') }}">
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" 
                   name="password" 
                   id="password" 
                   class="form-control" 
                   placeholder="Password" 
                   required>
        </div>

        <button type="submit" class="btn btn-primary w-100">Login</button>
    </form>
</div>

</body>
</html>
