<table class="table table-striped table-hover display w-100" style="width:100%">
    <thead class="table-dark">
        <tr>
            <th>User</th>
            <th>Status</th>
            <th>MAC</th>
            <th>IP</th>
            <th>Uptime</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($activeSessions as $session)
            @php
                $userKey = (string)($session['username'] ?? $session['user'] ?? 'N/A');
                $mac = (string)($session['mac-address'] ?? '-');
                $ip = (string)($session['address'] ?? '-');
                $uptime = (string)($session['uptime'] ?? '-');
                $isTracked = !empty($session['tracked-connection']);
            @endphp
            <tr>
                <td>{{ $userKey }}</td>
                <td>
                    <span class="badge rounded-pill {{ $isTracked ? 'bg-info-subtle text-info border border-info-subtle' : 'bg-success-subtle text-success border border-success-subtle' }}">
                        {{ $isTracked ? 'Tracked' : 'Online' }}
                    </span>
                </td>
                <td>{{ $mac }}</td>
                <td>{{ $ip }}</td>
                <td>{{ $uptime }}</td>
                <td>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Actions
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item action-view-user"
                                   href="javascript:void(0);"
                                   data-username="{{ $userKey }}">
                                    View Details
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item action-view-traffic"
                                   href="javascript:void(0);"
                                   data-username="{{ $userKey }}"
                                   data-mac="{{ $mac }}">
                                    View Traffic
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item action-disconnect-session"
                                   href="javascript:void(0);"
                                   data-username="{{ $userKey }}"
                                   data-mac="{{ $mac }}">
                                    Disconnect
                                </a>
                            </li>
                        </ul>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td class="text-muted">No active sessions found.</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        @endforelse
    </tbody>
</table>
