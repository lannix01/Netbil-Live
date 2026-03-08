<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Verification - SuperAdmin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">NetBil SuperAdmin</a>
        <form action="{{ route('superadmin.logout') }}" method="POST" class="d-flex">
            @csrf
            <button class="btn btn-outline-light btn-sm" type="submit">Logout</button>
        </form>
    </div>
</nav>

<div class="container my-4">

    <h2 class="mb-4">User Verification Dashboard</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <!-- Unverified Users -->
    <div class="card mb-4">
        <div class="card-header bg-warning text-dark">
           Pending Unverified Users
        </div>
        <div class="card-body p-0">
            @if($unverifiedUsers->isEmpty())
                <p class="text-center p-3 mb-0">No unverified users.</p>
            @else
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Name</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($unverifiedUsers as $user)
                            <tr>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->name ?? '-' }}</td>
                                <td>
                                    <form action="{{ route('authentication.verify', $user) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm">Verify</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <!-- Verified Users -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            Verified Users
        </div>
        <div class="card-body p-0">
            @if($verifiedUsers->isEmpty())
                <p class="text-center p-3 mb-0">No verified users.</p>
            @else
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Name</th>
                            <th>Verified At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($verifiedUsers as $user)
                            <tr>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->name ?? '-' }}</td>
                                <td>{{ $user->email_verified_at->format('Y-m-d H:i:s') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

</div>

</body>
</html>