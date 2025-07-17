<table class="table table-sm table-striped mb-0">
    <thead>
        <tr>
            <th>MAC</th>
            <th>IP</th>
            <th>To Address</th>
            <th>Uptime</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($hosts as $host)
            <tr>
                <td>{{ $host['mac-address'] ?? '-' }}</td>
                <td>{{ $host['address'] ?? '-' }}</td>
                <td>{{ $host['to-address'] ?? '-' }}</td>
                <td>{{ $host['uptime'] ?? '-' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
