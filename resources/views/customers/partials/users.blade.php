@php
    $usersCollection = collect($users ?? []);
    $onlineCount = $usersCollection->where('status', 'active')->count();
    $offlineCount = $usersCollection->where('status', 'offline')->count();
    $disabledCount = $usersCollection->where('status', 'disabled')->count();
    $temporaryCount = $usersCollection->where('account_type', 'hotspot_temporary')->count();
    $meteredCount = $usersCollection->where('account_type', 'metered_static')->count();
    $hotspotCount = $usersCollection->whereIn('account_type', ['hotspot', 'hotspot_static'])->count();
@endphp

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 px-3 py-2 border-bottom bg-light">
    <div class="small text-muted">
        Online {{ $onlineCount }} | Offline {{ $offlineCount }} | Disabled {{ $disabledCount }} | Temporary {{ $temporaryCount }} | Hotspot {{ $hotspotCount }} | Metered {{ $meteredCount }}
    </div>
    <div class="btn-group btn-group-sm user-filter-group" role="group" aria-label="User filters">
        <button type="button" class="btn btn-outline-secondary user-filter-btn active" data-filter="all">All</button>
        <button type="button" class="btn btn-outline-secondary user-filter-btn" data-filter="online">Online</button>
        <button type="button" class="btn btn-outline-secondary user-filter-btn" data-filter="offline">Offline</button>
        <button type="button" class="btn btn-outline-secondary user-filter-btn" data-filter="temporary">Temporary</button>
        <button type="button" class="btn btn-outline-secondary user-filter-btn" data-filter="hotspot">Hotspot</button>
        <button type="button" class="btn btn-outline-secondary user-filter-btn" data-filter="metered">Metered</button>
    </div>
</div>

<table class="table table-striped table-hover display w-100" id="usersTable">
    <thead>
        <tr>
            <th>Username</th>
            <th>Type</th>
            <th>Status</th>
            <th>Profile</th>
            <th>Uptime</th>
            <th>Last Seen</th>
            <th>MAC</th>
            <th>Usage</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($users as $user)
            @php
                $status = strtolower((string)($user['status'] ?? 'offline'));
                $statusLabel = (string)($user['status_label'] ?? ($status === 'active' ? 'Online' : ($status === 'disabled' ? 'Disabled' : 'Offline')));
                $accountType = strtolower((string)($user['account_type'] ?? 'hotspot'));
                $typeLabel = (string)($user['account_type_label'] ?? 'Hotspot');
                $mac = (string)($user['mac-address'] ?? '-');
                $bytesIn = (int)($user['bytes-in'] ?? 0);
                $bytesOut = (int)($user['bytes-out'] ?? 0);
                $usageBytes = $bytesIn + $bytesOut;
                $usageMb = $usageBytes > 0 ? number_format($usageBytes / 1048576, 2) : '0.00';
                $usageInMb = $bytesIn > 0 ? number_format($bytesIn / 1048576, 2) : '0.00';
                $usageOutMb = $bytesOut > 0 ? number_format($bytesOut / 1048576, 2) : '0.00';

                $statusClass = $status === 'active'
                    ? 'bg-success-subtle text-success border border-success-subtle'
                    : ($status === 'disabled'
                        ? 'bg-danger-subtle text-danger border border-danger-subtle'
                        : 'bg-secondary-subtle text-secondary border border-secondary-subtle');

                $typeClass = $accountType === 'metered_static'
                    ? 'bg-primary-subtle text-primary border border-primary-subtle'
                    : ($accountType === 'hotspot_temporary'
                        ? 'bg-warning-subtle text-warning border border-warning-subtle'
                        : 'bg-info-subtle text-info border border-info-subtle');
            @endphp
            <tr data-user-status="{{ $statusLabel }}" data-user-type="{{ $typeLabel }}">
                <td>{{ $user['username'] ?? 'N/A' }}</td>
                <td><span class="badge rounded-pill {{ $typeClass }}">{{ $typeLabel }}</span></td>
                <td><span class="badge rounded-pill {{ $statusClass }}">{{ $statusLabel }}</span></td>
                <td>{{ $user['profile'] ?? '-' }}</td>
                <td>{{ $user['uptime'] ?? '-' }}</td>
                <td>{{ $user['last-seen'] ?? '-' }}</td>
                <td>{{ $mac }}</td>
                <td>
                    <div>{{ $usageMb }} MB</div>
                    <small class="text-muted">IN {{ $usageInMb }} | OUT {{ $usageOutMb }}</small>
                </td>
                <td>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Actions
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item action-view-user"
                                   href="javascript:void(0);"
                                   data-username="{{ $user['username'] ?? '' }}">
                                    View Details
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item action-view-traffic"
                                   href="javascript:void(0);"
                                   data-username="{{ $user['username'] ?? '' }}"
                                   data-mac="{{ $mac }}">
                                    View Traffic
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item action-disconnect-session"
                                   href="javascript:void(0);"
                                   data-username="{{ $user['username'] ?? '' }}"
                                   data-mac="{{ $mac }}">
                                    Disconnect
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item action-edit-user"
                                   href="javascript:void(0);"
                                   data-username="{{ $user['username'] ?? '' }}"
                                   data-profile="{{ $user['profile'] ?? '' }}"
                                   data-limit-uptime="{{ $user['limit-uptime'] ?? '' }}"
                                   data-comment="{{ $user['comment'] ?? '' }}">
                                    Edit
                                </a>
                            </li>
                            <li>
                                @if($status === 'disabled')
                                    <a class="dropdown-item text-success action-enable-user"
                                       href="javascript:void(0);"
                                       data-username="{{ $user['username'] ?? '' }}"
                                       data-mac="{{ $mac }}">
                                        Enable
                                    </a>
                                @else
                                    <a class="dropdown-item action-disable-user"
                                       href="javascript:void(0);"
                                       data-username="{{ $user['username'] ?? '' }}"
                                       data-mac="{{ $mac }}">
                                        Disable
                                    </a>
                                @endif
                            </li>
                        </ul>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td class="text-muted">No hotspot users found.</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        @endforelse
    </tbody>
</table>
