@extends('inventory::layout')

@section('page-title', 'Manage Team')
@section('page-subtitle', 'Edit team details and manage team members.')

@section('page-actions')
    <a class="btn btn-outline-secondary btn-sm" href="{{ route('inventory.teams.index') }}" data-inv-loading>Back to Teams</a>
@endsection

@section('inventory-content')
@php
    $technicians = $technicians ?? collect();

    $hasErr = fn($key) => $errors->has($key);
    $errMsg = fn($key) => $errors->first($key);

    // Old input preservation for team update
    $oldName = old('name', $team->name);
    $oldCode = old('code', $team->code);
    $oldDesc = old('description', $team->description);
    $oldActive = old('is_active', $team->is_active ? '1' : '0');

    // Old input preservation for member add/update
    $oldTech = old('technician_id');
    $oldRole = old('role', 'member');
    $oldMemberActive = old('is_active', '1'); // yes, name collides; form submits separately, still safe.

    $teamName = $team?->name ?? 'Team';
    $teamCode = $team?->code ?? '';
@endphp

<style>
    /* Teams edit (scoped) */
    .inv-panel{ padding:0; overflow:hidden; }
    .inv-panel-head{
        padding: 14px 16px;
        border-bottom: 1px solid var(--border);
        background: #fbfcff;
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:12px;
        flex-wrap:wrap;
    }
    .inv-panel-title{ font-weight: 900; margin:0; font-size: 14px; }
    .inv-panel-sub{ color: var(--muted); font-size: 12px; margin-top: 3px; }
    .inv-panel-body{ padding: 16px; }

    .inv-field{
        background:#fff;
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 12px;
        margin-bottom: 12px;
    }
    .inv-field .form-label{
        font-weight: 900;
        font-size: 12px;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: var(--muted);
        margin-bottom: 6px;
    }
    .inv-hint{
        font-size: 12px;
        color: var(--muted);
        margin-top: 6px;
        line-height: 1.35;
    }
    .inv-error{
        margin-top: 6px;
        font-size: 12px;
        color: #b91c1c;
        font-weight: 700;
    }
    .inv-input-invalid{
        border-color: rgba(220,38,38,.55) !important;
        box-shadow: 0 0 0 .15rem rgba(220,38,38,.10) !important;
    }

    .inv-mini{
        display:flex;
        gap: 8px;
        flex-wrap:wrap;
        align-items:center;
        justify-content:flex-end;
    }

    .inv-btn-wide{ width:100%; border-radius: 14px; font-weight: 900; }

    @media (max-width: 575.98px){
        .hide-xs{ display:none; }
    }
</style>

<div class="row g-3">
    {{-- Left: Team edit + add member --}}
    <div class="col-lg-5">
        <div class="inv-card inv-panel">
            <div class="inv-panel-head">
                <div>
                    <p class="inv-panel-title mb-0">Edit Team</p>
                    <div class="inv-panel-sub">
                        Managing: <span class="inv-chip">{{ $teamName }}</span>
                        @if($teamCode !== '')
                            <span class="inv-chip">{{ $teamCode }}</span>
                        @endif
                        <span class="inv-chip">ID: #{{ $team->id }}</span>
                    </div>
                </div>

                <div class="inv-mini">
                    @if($team->is_active)
                        <span class="badge bg-success">Active</span>
                    @else
                        <span class="badge bg-secondary">Inactive</span>
                    @endif
                </div>
            </div>

            <div class="inv-panel-body">
                <form method="POST" action="{{ route('inventory.teams.update', $team) }}" data-inv-loading>
                    @csrf
                    @method('PUT')

                    <div class="inv-field">
                        <label class="form-label">Team Name <span class="text-danger">*</span></label>
                        <input
                            class="form-control {{ $hasErr('name') ? 'inv-input-invalid' : '' }}"
                            name="name"
                            value="{{ $oldName }}"
                            required
                        >
                        @if($hasErr('name'))
                            <div class="inv-error">{{ $errMsg('name') }}</div>
                        @endif
                    </div>

                    <div class="inv-field">
                        <label class="form-label">Code (optional)</label>
                        <input
                            class="form-control {{ $hasErr('code') ? 'inv-input-invalid' : '' }}"
                            name="code"
                            value="{{ $oldCode }}"
                            placeholder="e.g. FIELD-1"
                        >
                        <div class="inv-hint">Used for quick identification and filtering.</div>
                        @if($hasErr('code'))
                            <div class="inv-error">{{ $errMsg('code') }}</div>
                        @endif
                    </div>

                    <div class="inv-field">
                        <label class="form-label">Description (optional)</label>
                        <textarea
                            class="form-control {{ $hasErr('description') ? 'inv-input-invalid' : '' }}"
                            name="description"
                            rows="3"
                        >{{ $oldDesc }}</textarea>
                        @if($hasErr('description'))
                            <div class="inv-error">{{ $errMsg('description') }}</div>
                        @endif
                    </div>

                    <div class="inv-field">
                        <label class="form-label">Active?</label>
                        <select class="form-select {{ $hasErr('is_active') ? 'inv-input-invalid' : '' }}" name="is_active">
                            <option value="1" @selected((string)$oldActive === '1')>Yes</option>
                            <option value="0" @selected((string)$oldActive === '0')>No</option>
                        </select>
                        @if($hasErr('is_active'))
                            <div class="inv-error">{{ $errMsg('is_active') }}</div>
                        @endif
                    </div>

                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-dark">Save Changes</button>
                        <a class="btn btn-outline-secondary" href="{{ route('inventory.teams.index') }}" data-inv-loading>Cancel</a>
                    </div>
                </form>

                <div class="inv-divider"></div>

                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                    <div style="font-weight:900;">Add / Update Member</div>
                    <span class="inv-chip">Applies immediately</span>
                </div>

                <form method="POST" action="{{ route('inventory.teams.members.store', $team) }}" class="mt-2" data-inv-loading>
                    @csrf

                    <div class="inv-field">
                        <label class="form-label">Technician <span class="text-danger">*</span></label>
                        <select class="form-select {{ $hasErr('technician_id') ? 'inv-input-invalid' : '' }}" name="technician_id" required>
                            <option value="">-- Select Technician --</option>
                            @foreach($technicians as $t)
                                <option value="{{ $t->id }}" @selected((string)$oldTech === (string)$t->id)>
                                    {{ $t->name }} ({{ $t->department?->name ?? 'No Dept' }})
                                </option>
                            @endforeach
                        </select>
                        @if($hasErr('technician_id'))
                            <div class="inv-error">{{ $errMsg('technician_id') }}</div>
                        @endif
                    </div>

                    <div class="inv-field">
                        <label class="form-label">Role</label>
                        <select class="form-select {{ $hasErr('role') ? 'inv-input-invalid' : '' }}" name="role">
                            <option value="member" @selected((string)$oldRole === 'member')>Member</option>
                            <option value="leader" @selected((string)$oldRole === 'leader')>Leader</option>
                        </select>
                        <div class="inv-hint">Leader can be used later for approvals/workflow (if you add it).</div>
                        @if($hasErr('role'))
                            <div class="inv-error">{{ $errMsg('role') }}</div>
                        @endif
                    </div>

                    <div class="inv-field">
                        <label class="form-label">Active?</label>
                        <select class="form-select {{ $hasErr('is_active') ? 'inv-input-invalid' : '' }}" name="is_active">
                            <option value="1" @selected((string)$oldMemberActive === '1')>Yes</option>
                            <option value="0" @selected((string)$oldMemberActive === '0')>No</option>
                        </select>
                        @if($hasErr('is_active'))
                            <div class="inv-error">{{ $errMsg('is_active') }}</div>
                        @endif
                    </div>

                    <button class="btn btn-dark inv-btn-wide">Add / Update Member</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Right: Members table + next --}}
    <div class="col-lg-7">
        <div class="inv-card inv-table-card">
            <div class="inv-table-head">
                <div>
                    <p class="inv-table-title mb-0">Members</p>
                    <div class="inv-table-sub">Technicians attached to this team.</div>
                </div>
                <div class="inv-table-tools">
                    <span class="inv-chip">
                        Total: {{ method_exists($team->members, 'count') ? $team->members->count() : 0 }}
                    </span>
                </div>
            </div>

            <div class="inv-table-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Technician</th>
                                <th style="width:130px;">Role</th>
                                <th style="width:130px;">Status</th>
                                <th class="text-end" style="width:130px;">Remove</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($team->members as $m)
                                @php
                                    $techName = $m->technician?->name ?? '—';
                                    $dept = $m->technician?->department?->name ?? '';
                                    $role = (string)($m->role ?? 'member');
                                    $active = (bool)($m->is_active ?? false);
                                @endphp

                                <tr>
                                    <td>
                                        <div style="font-weight:900;">{{ $techName }}</div>
                                        <div class="inv-muted" style="font-size:12px;">
                                            {{ $dept !== '' ? $dept : '—' }}
                                        </div>
                                    </td>

                                    <td>
                                        @if($role === 'leader')
                                            <span class="badge bg-primary">Leader</span>
                                        @else
                                            <span class="badge bg-secondary">Member</span>
                                        @endif
                                    </td>

                                    <td>
                                        @if($active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </td>

                                    <td class="text-end">
                                        <form
                                            method="POST"
                                            action="{{ route('inventory.teams.members.destroy', [$team, $m]) }}"
                                            onsubmit="return confirm('Remove this member?')"
                                            data-inv-loading
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="p-0">
                                        <div class="inv-empty">
                                            <div class="inv-empty-ico">🧑‍🤝‍🧑</div>
                                            <p class="inv-empty-title mb-0">No members yet</p>
                                            <div class="inv-empty-sub">
                                                Add technicians to this team to support team deployments and tracking.
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="inv-card inv-panel mt-3">
            <div class="inv-panel-head">
                <div>
                    <p class="inv-panel-title mb-0">Next</p>
                    <div class="inv-panel-sub">Planned improvements</div>
                </div>
                <span class="inv-chip">Soon</span>
            </div>
            <div class="inv-panel-body">
                <div class="inv-note" style="border-color: var(--infoBd); background: var(--infoBg); color: var(--infoTx);">
                    Next we will update Assignments + Deployments to allow selecting <strong>Team</strong>
                    (system can assign to team, then members can deploy).
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
