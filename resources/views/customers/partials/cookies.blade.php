<table class="table table-striped table-hover display w-100" id="cookiesTable">
    <thead class="table-dark">
        <tr>
            <th>User</th>
            <th>MAC</th>
            <th>Host</th>
            <th>Timeout</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($cookies as $c)
            @php
                $username = (string)($c['user'] ?? '');
                $mac = (string)($c['mac-address'] ?? '');
            @endphp
            <tr>
                <td>{{ $username !== '' ? $username : 'N/A' }}</td>
                <td>{{ $mac !== '' ? $mac : '-' }}</td>
                <td>{{ $c['domain'] ?? '-' }}</td>
                <td>{{ $c['expires in'] ?? '-' }}</td>
                <td>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Actions
                        </button>
                        <ul class="dropdown-menu">
                            @if($username !== '')
                                <li>
                                    <a class="dropdown-item action-view-user"
                                       href="javascript:void(0);"
                                       data-username="{{ $username }}">
                                        View Details
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item action-view-traffic"
                                       href="javascript:void(0);"
                                       data-username="{{ $username }}"
                                       data-mac="{{ $mac }}">
                                        View Traffic
                                    </a>
                                </li>
                            @endif
                            <li>
                                <a class="dropdown-item action-disconnect-session"
                                   href="javascript:void(0);"
                                   data-username="{{ $username }}"
                                   data-mac="{{ $mac }}">
                                    Disconnect
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item text-danger action-delete-cookie"
                                   href="javascript:void(0);"
                                   data-username="{{ $username }}"
                                   data-mac="{{ $mac }}">
                                    Delete Cookie
                                </a>
                            </li>
                        </ul>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td class="text-muted">No cookies found.</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        @endforelse
    </tbody>
</table>
