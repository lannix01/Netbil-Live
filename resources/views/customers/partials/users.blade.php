<table class="table table-hover m-0">
    <thead class="table-light">
        <tr>
            <th>#</th>
            <th>Username</th>
            <th>MAC</th>
            <th>Profile</th>
            <th>Server</th>
            <th>Uptime</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($users as $index => $user)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td><a href="#" onclick="monitorTraffic('{{ $user['mac'] }}')">{{ $user['username'] }}</a></td>
                <td><a href="#" onclick="monitorTraffic('{{ $user['mac'] }}')">{{ $user['mac'] ?? '-' }}</a></td>
                <td>{{ $user['profile'] }}</td>
                <td>{{ $user['server'] }}</td>
                <td>{{ $user['uptime'] }}</td>
                <td>
                    <span class="badge bg-{{ $user['status'] === 'active' ? 'success' : ($user['status'] === 'inactive' ? 'secondary' : 'warning') }}">
                        {{ ucfirst($user['status']) }}
                    </span>
                </td>
                <td>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-dark dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-gear"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" onclick="refreshSection('users')">Refresh</a></li>
                            @if ($user['status'] === 'active')
                                <li><a class="dropdown-item text-danger" onclick="disconnectUser('{{ $user['mac'] }}')">Disconnect</a></li>
                            @endif
                            <li><a class="dropdown-item" onclick="monitorTrafficByUser('{{ $user['username'] }}')">Monitor Traffic</a></li>
                        </ul>
                    </div>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
