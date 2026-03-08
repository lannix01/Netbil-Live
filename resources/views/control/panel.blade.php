@extends('layouts.app')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<div class="container-fluid mt-4">
    <h3 class="fw-bold text-primary mb-3">System Users</h3>

    <table class="table table-hover table-striped" id="usersTable">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Role</th>
                <th>Status</th>
                <th>Login</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        @foreach($users as $i => $user)
            <tr data-id="{{ $user->id }}">
                <td>{{ $i+1 }}</td>
                <td class="name">{{ $user->name }}</td>
                <td class="email">{{ $user->email }}</td>
                <td class="phone">{{ $user->phone ?? '—' }}</td>
                <td class="role">{{ $user->role }}</td>
                <td class="status">
                    <span class="badge {{ $user->is_active ? 'bg-success' : 'bg-secondary' }}">
                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td class="login">
                    <span class="badge {{ $user->can_login ? 'bg-success' : 'bg-danger' }}">
                        {{ $user->can_login ? 'Yes' : 'No' }}
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-outline-primary edit-user" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-warning toggle-login" title="Toggle Login">
                        <i class="bi bi-shield-lock"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger reset-password" title="Reset Password">
                        <i class="bi bi-key"></i>
                    </button>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

@include('control.user-modal')

<script>
$('#usersTable').DataTable();

const csrf = '{{ csrf_token() }}';

// Edit
$('.edit-user').click(function () {
    const row = $(this).closest('tr');
    const id = row.data('id');

    $.get(`/control/users/${id}`, user => {
        $('#userId').val(user.id);
        $('#name').val(user.name);
        $('#email').val(user.email);
        $('#phone').val(user.phone);
        $('#role').val(user.role);
        $('#is_active').prop('checked', user.is_active);
        $('#can_login').prop('checked', user.can_login);
        new bootstrap.Modal('#userModal').show();
    });
});

// Save
$('#saveUser').click(() => {
    const id = $('#userId').val();

    $.post(`/control/users/update/${id}`, {
        _token: csrf,
        name: $('#name').val(),
        email: $('#email').val(),
        phone: $('#phone').val(),
        role: $('#role').val(),
        is_active: $('#is_active').is(':checked') ? 1 : 0,
        can_login: $('#can_login').is(':checked') ? 1 : 0,
    }, () => location.reload());
});

// Toggle login
$('.toggle-login').click(function () {
    const id = $(this).closest('tr').data('id');
    $.post(`/control/users/toggle-login/${id}`, {_token: csrf}, () => location.reload());
});

// Reset password
$('.reset-password').click(function () {
    const id = $(this).closest('tr').data('id');
    if (confirm('Send password reset email?')) {
        $.post(`/control/users/reset-password/${id}`, {_token: csrf}, res => alert(res.message));
    }
});
</script>
@endsection
