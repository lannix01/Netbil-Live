<table class="table table-sm table-hover mb-0">
    <thead>
        <tr>
            <th>User</th>
            <th>MAC</th>
            <th>IP</th>
            <th>Uptime</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($activeSessions as $sess)
            <tr>
                <td>{{ $sess['user'] ?? '-' }}</td>
                <td>{{ $sess['mac-address'] ?? '-' }}</td>
                <td>{{ $sess['address'] ?? '-' }}</td>
                <td>{{ $sess['uptime'] ?? '-' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
