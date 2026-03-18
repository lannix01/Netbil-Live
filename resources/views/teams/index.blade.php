@extends('inventory::layout')

@section('page-title', 'Skybrix Teams')
@section('page-subtitle', 'Create, search, and manage teams used for assignments and deployments.')

@section('page-actions')
    <a class="btn btn-dark btn-sm" href="{{ route('inventory.teams.create') }}" data-inv-loading>+ New Team</a>
@endsection

@section('inventory-content')
@php
    // Safety: ensure these exist even if controller forgets
    $q = $q ?? '';
    $status = $status ?? 'active';
@endphp

<div class="inv-card inv-table-card">

    <div class="inv-table-head">
        <div>
            <p class="inv-table-title mb-0">Teams</p>
            <div class="inv-table-sub">
                {{ $status === 'all' ? 'Showing: All teams' : 'Showing: '.ucfirst($status).' teams' }}
                @if($q !== '')
                    • Search: <span class="inv-chip">“{{ $q }}”</span>
                @endif
            </div>
        </div>

        <div class="inv-table-tools">
            <form class="d-flex gap-2 flex-wrap" method="GET" action="{{ route('inventory.teams.index') }}" data-inv-loading data-inv-autofilter>
                <input
                    class="form-control form-control-sm"
                    name="q"
                    value="{{ $q }}"
                    placeholder="Search name/code…"
                    style="min-width:220px;"
                >

                <select class="form-select form-select-sm" name="status" style="min-width:150px;">
                    <option value="active" @selected($status==='active')>Active</option>
                    <option value="inactive" @selected($status==='inactive')>Inactive</option>
                    <option value="all" @selected($status==='all')>All</option>
                </select>

                <button class="btn btn-sm btn-dark">Filter</button>

                @if($q !== '' || $status !== 'active')
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('inventory.teams.index') }}" data-inv-loading>Reset</a>
                @endif
            </form>
        </div>
    </div>

    <div class="inv-table-body">
        {{-- Lightweight loader/skeleton (safe; no JS dependency).
             If you later add AJAX, you can toggle this block and hide the real table. --}}
        <div id="invTeamsSkeleton" class="p-3 d-none">
            <div class="inv-skeleton inv-skel-row lg"></div>
            <div class="inv-skeleton inv-skel-row"></div>
            <div class="inv-skeleton inv-skel-row"></div>
            <div class="inv-skeleton inv-skel-row"></div>
            <div class="inv-skeleton inv-skel-row sm" style="width:72%;"></div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm align-middle table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width:70px;">#</th>
                        <th>Team</th>
                        <th style="width:160px;">Code</th>
                        <th style="width:140px;">Status</th>
                        <th style="width:220px;">Created By</th>
                        <th class="text-end" style="width:170px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($teams as $t)
                        @php
                            $isActive = (bool) $t->is_active;
                            $desc = trim((string) ($t->description ?? ''));
                        @endphp

                        <tr>
                            <td class="text-muted">{{ $t->id }}</td>

                            <td>
                                <div class="fw-semibold d-flex align-items-center gap-2">
                                    <span class="inv-chip" title="Team ID">#{{ $t->id }}</span>
                                    <span>{{ $t->name }}</span>
                                </div>
                                @if($desc !== '')
                                    <div class="inv-muted" style="font-size:12px; margin-top:4px;">
                                        {{ \Illuminate\Support\Str::limit($desc, 90) }}
                                    </div>
                                @else
                                    <div class="inv-muted" style="font-size:12px; margin-top:4px;">
                                        No description yet.
                                    </div>
                                @endif
                            </td>

                            <td>
                                <span class="inv-chip">
                                    {{ $t->code ?? '—' }}
                                </span>
                            </td>

                            <td>
                                @if($isActive)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>

                            <td>
                                <div class="fw-semibold">{{ $t->creator?->name ?? '—' }}</div>
                                <div class="inv-muted" style="font-size:12px;">
                                    {{ $t->created_at?->format('M j, Y') ?? '' }}
                                </div>
                            </td>

                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('inventory.teams.edit', $t) }}" data-inv-loading>
                                    Manage
                                </a>

                                <form
                                    class="d-inline"
                                    method="POST"
                                    action="{{ route('inventory.teams.destroy', $t) }}"
                                    onsubmit="return confirm('Delete this team?')"
                                    data-inv-loading
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-0">
                                <div class="inv-empty">
                                    <div class="inv-empty-ico">👥</div>
                                    <p class="inv-empty-title mb-0">No teams yet</p>
                                    <div class="inv-empty-sub">
                                        Teams help you organize assignments and deployments.
                                        Create the first one to get started.
                                    </div>
                                    <div class="mt-3">
                                        <a class="btn btn-dark btn-sm" href="{{ route('inventory.teams.create') }}" data-inv-loading>
                                            + Create first team
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($teams, 'links'))
            <div class="p-3">
                {{ $teams->links() }}
            </div>
        @endif
    </div>
</div>

<script>
    // Optional: show skeleton on hard navigations if you want instant feedback.
    // This won't break anything; it's purely cosmetic.
    (function(){
        const form = document.querySelector('form[action="{{ route('inventory.teams.index') }}"]');
        if(form){
            form.addEventListener('submit', function(){
                const sk = document.getElementById('invTeamsSkeleton');
                if(sk) sk.classList.remove('d-none');
            });
        }
    })();
</script>
@endsection
