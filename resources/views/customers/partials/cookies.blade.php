<table class="table table-sm table-bordered mb-0">
    <thead>
        <tr>
            <th>MAC ADDRESS</th>
            <th>Comment</th>
            <th>Expires In</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($cookies as $cookie)
            <tr>
                <td>{{ $cookie['mac-address'] ?? '-' }}</td>
                <td>{{ $cookie['comment'] ?? '-' }}</td>
                <td>{{ $cookie['Expires In'] ?? '-' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
