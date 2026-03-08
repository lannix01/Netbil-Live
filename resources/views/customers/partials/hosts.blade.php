<table class="table table-striped table-hover display w-100" id="hostsTable">
    <thead>
        <tr>
            <th>MAC Address</th>
            <th>IP Address</th>
            <th>Server</th>
            <th>Active Sessions</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($hosts as $host)
            @php $mac = (string)($host['mac-address'] ?? ''); @endphp
            <tr>
                <td>{{ $mac !== '' ? $mac : '-' }}</td>
                <td>{{ $host['address'] ?? '-' }}</td>
                <td>{{ $host['server'] ?? '-' }}</td>
                <td>{{ $host['active-sessions'] ?? '0' }}</td>
                <td>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Actions
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item action-filter-sessions-by-mac"
                                   href="javascript:void(0);"
                                   data-mac="{{ $mac }}">
                                    View Sessions
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item action-view-traffic"
                                   href="javascript:void(0);"
                                   data-username=""
                                   data-mac="{{ $mac }}">
                                    View Traffic
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item action-disconnect-session"
                                   href="javascript:void(0);"
                                   data-username=""
                                   data-mac="{{ $mac }}">
                                    Disconnect
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item action-block-host"
                                   href="javascript:void(0);"
                                   data-mac="{{ $mac }}">
                                    Block
                                </a>
                            </li>
                        </ul>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td class="text-muted">No hosts found.</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        @endforelse
    </tbody>
</table>
