@foreach($users as $index => $user)
<tr>
    <td>{{ $index + 1 }}</td>
    <td>{{ $user->name }}</td>
    <td>{{ $user->email }}</td>
    <td>{{ ucfirst($user->role ?? 'user') }}</td>
    <td>
        @if($user->is_active)
            <span class="badge bg-success">Active</span>
        @else
            <span class="badge bg-secondary">Inactive</span>
        @endif
    </td>
    <td>{{ $user->last_login_at ?? 'N/A' }}</td>
</tr>
@endforeach
