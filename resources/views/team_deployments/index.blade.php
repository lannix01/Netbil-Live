@extends('inventory::layout')

@section('page-title', 'Team Deploy')
@section('page-subtitle', 'Deploy bulk items from a team pool to a site and reduce available allocation.')

@section('page-actions')
    <a class="btn btn-outline-secondary btn-sm" href="{{ route('inventory.team_assignments.index') }}" data-inv-loading>Team Assignments</a>
    <a class="btn btn-outline-dark btn-sm" href="{{ route('inventory.logs.index') }}" data-inv-loading>Logs</a>
@endsection

@section('inventory-content')
@php
    $teams = $teams ?? collect();
    $teamAssignments = $teamAssignments ?? [];

    $hasErr = fn($key) => $errors->has($key);
    $errMsg = fn($key) => $errors->first($key);

    // Old input preservation
    $oldTeam = old('team_id');
    $oldItem = old('item_id');
    $oldQty = old('qty', 1);
    $oldSite = old('site_name');
    $oldSiteCode = old('site_code');
    $oldRef = old('reference');
    $oldNotes = old('notes');

    // Optional skeleton preview: /inventory/team-deployments?loading=1
    $loading = request()->boolean('loading');
@endphp

<style>
    /* Team deploy (scoped) */
    .inv-panel{ padding:0; overflow:hidden; }
    .inv-panel-head{
        padding: 14px 16px;
        border-bottom: 1px solid var(--border);
        background: #fbfcff;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:10px;
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

    .inv-btn-wide{ width:100%; border-radius: 14px; font-weight: 900; }

    .inv-note{
        padding: 14px 16px;
        border-radius: 14px;
        border: 1px solid var(--infoBd);
        background: var(--infoBg);
        color: var(--infoTx);
        font-size: 13px;
        line-height: 1.4;
        font-weight: 700;
    }

    .inv-quick{
        display:flex;
        gap: 8px;
        flex-wrap:wrap;
        align-items:center;
    }

    .inv-skel{ display:none; padding:16px; }
    .inv-skel.show{ display:block; }
</style>

<div class="row g-3">
    {{-- Left: deploy form --}}
    <div class="col-lg-5">
        <div class="inv-card inv-panel">
            <div class="inv-panel-head">
                <div>
                    <p class="inv-panel-title mb-0">Deploy From Team Stock (Bulk)</p>
                    <div class="inv-panel-sub">Deployment reduces team available allocation.</div>
                </div>
                <span class="inv-chip">Bulk</span>
            </div>

            <div class="inv-panel-body">
                @if($teams->count() === 0)
                    <div class="inv-empty">
                        <div class="inv-empty-ico">👥</div>
                        <p class="inv-empty-title mb-0">No teams available</p>
                        <div class="inv-empty-sub">Create a team and assign items before deploying.</div>
                        <div class="mt-3">
                            <a class="btn btn-dark btn-sm" href="{{ route('inventory.teams.create') }}" data-inv-loading>+ Create team</a>
                            <a class="btn btn-outline-secondary btn-sm ms-1" href="{{ route('inventory.team_assignments.index') }}" data-inv-loading>Team assignments</a>
                        </div>
                    </div>
                @else
                    <form method="POST" action="{{ route('inventory.team_deployments.store') }}" data-inv-loading>
                        @csrf

                        <div class="inv-field">
                            <label class="form-label">Team <span class="text-danger">*</span></label>
                            <select
                                class="form-select {{ $hasErr('team_id') ? 'inv-input-invalid' : '' }}"
                                name="team_id"
                                id="teamSelect"
                                required
                                onchange="syncTeamItems()"
                            >
                                <option value="">-- Select Team --</option>
                                @foreach($teams as $t)
                                    <option value="{{ $t->id }}" @selected((string)$oldTeam === (string)$t->id)>
                                        {{ $t->name }} {{ $t->code ? '(' . $t->code . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="inv-hint">Pick the team pool you are deploying from.</div>
                            @if($hasErr('team_id'))
                                <div class="inv-error">{{ $errMsg('team_id') }}</div>
                            @endif
                        </div>

                        <div class="inv-field">
                            <label class="form-label">Item <span class="text-danger">*</span></label>
                            <select
                                class="form-select {{ $hasErr('item_id') ? 'inv-input-invalid' : '' }}"
                                name="item_id"
                                id="itemSelect"
                                required
                            >
                                <option value="">-- Select Team first --</option>
                            </select>
                            <div class="inv-hint">Only items allocated to the selected team will appear here.</div>
                            @if($hasErr('item_id'))
                                <div class="inv-error">{{ $errMsg('item_id') }}</div>
                            @endif
                        </div>

                        <div class="inv-field">
                            <label class="form-label">Qty <span class="text-danger">*</span></label>
                            <input
                                class="form-control {{ $hasErr('qty') ? 'inv-input-invalid' : '' }}"
                                type="number"
                                min="1"
                                name="qty"
                                value="{{ $oldQty }}"
                                required
                            >
                            <div class="inv-hint">Enter how many units you are deploying to the site.</div>
                            @if($hasErr('qty'))
                                <div class="inv-error">{{ $errMsg('qty') }}</div>
                            @endif
                        </div>

                        <div class="inv-field">
                            <label class="form-label">Site Name <span class="text-danger">*</span></label>
                            <input
                                class="form-control {{ $hasErr('site_name') ? 'inv-input-invalid' : '' }}"
                                name="site_name"
                                value="{{ $oldSite }}"
                                placeholder="e.g. Kileleshwa Node 7"
                                required
                            >
                            <div class="inv-hint">Human-friendly site identifier.</div>
                            @if($hasErr('site_name'))
                                <div class="inv-error">{{ $errMsg('site_name') }}</div>
                            @endif
                        </div>

                        <div class="inv-field">
                            <label class="form-label">Site Code (optional)</label>
                            <input
                                class="form-control {{ $hasErr('site_code') ? 'inv-input-invalid' : '' }}"
                                name="site_code"
                                value="{{ $oldSiteCode }}"
                                placeholder="e.g. NBI-KLS-007"
                            >
                            @if($hasErr('site_code'))
                                <div class="inv-error">{{ $errMsg('site_code') }}</div>
                            @endif
                        </div>

                        <div class="inv-field">
                            <label class="form-label">Reference (optional)</label>
                            <input
                                class="form-control {{ $hasErr('reference') ? 'inv-input-invalid' : '' }}"
                                name="reference"
                                value="{{ $oldRef }}"
                                placeholder="e.g. Job card, ticket number…"
                            >
                            @if($hasErr('reference'))
                                <div class="inv-error">{{ $errMsg('reference') }}</div>
                            @endif
                        </div>

                        <div class="inv-field">
                            <label class="form-label">Notes (optional)</label>
                            <textarea
                                class="form-control {{ $hasErr('notes') ? 'inv-input-invalid' : '' }}"
                                name="notes"
                                rows="2"
                                placeholder="Anything important about the deployment…"
                            >{{ $oldNotes }}</textarea>
                            @if($hasErr('notes'))
                                <div class="inv-error">{{ $errMsg('notes') }}</div>
                            @endif
                        </div>

                        <button class="btn btn-dark inv-btn-wide">Deploy</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    {{-- Right: notes + quick links --}}
    <div class="col-lg-7">
        <div class="inv-card inv-panel">
            <div class="inv-panel-head">
                <div>
                    <p class="inv-panel-title mb-0">Notes</p>
                    <div class="inv-panel-sub">Important behavior of this workflow.</div>
                </div>
                <span class="inv-chip">Info</span>
            </div>
            <div class="inv-panel-body">
                <div class="inv-note">
                    This is bulk deployment from a team pool. Serialized deployment from team pool can be added later (requires team-based unit assignment).
                </div>

                <div class="inv-divider"></div>

                <div class="inv-quick">
                    <a class="btn btn-outline-secondary" href="{{ route('inventory.team_assignments.index') }}" data-inv-loading>Team Assignments</a>
                    <a class="btn btn-outline-dark" href="{{ route('inventory.logs.index') }}" data-inv-loading>Logs</a>
                    <a class="btn btn-outline-dark" href="{{ route('inventory.teams.index') }}" data-inv-loading>Teams</a>
                </div>
            </div>
        </div>

        {{-- Optional: A small “recent activity” placeholder card --}}
        <div class="inv-card inv-panel mt-3">
            <div class="inv-panel-head">
                <div>
                    <p class="inv-panel-title mb-0">Next upgrades</p>
                    <div class="inv-panel-sub">Planned improvements for team deployments.</div>
                </div>
                <span class="inv-chip">Soon</span>
            </div>
            <div class="inv-panel-body">
                <div class="inv-muted" style="font-size:13px;">
                    • Serialized deployment from team pool<br>
                    • Site directory / autocomplete<br>
                    • Validation: prevent deploying above available qty (UI + server-side)<br>
                    • Deployment history table
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const teamAssignments = @json($teamAssignments);

    // Old values (for restoring after validation errors)
    const oldTeamId = @json($oldTeam);
    const oldItemId = @json($oldItem);

    function syncTeamItems() {
        const teamId = document.getElementById('teamSelect')?.value;
        const itemSelect = document.getElementById('itemSelect');
        if(!itemSelect) return;

        itemSelect.innerHTML = '';

        if (!teamId || !teamAssignments[teamId] || teamAssignments[teamId].length === 0) {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = '-- No assigned items for this team --';
            itemSelect.appendChild(opt);
            return;
        }

        const opt0 = document.createElement('option');
        opt0.value = '';
        opt0.textContent = '-- Select Item --';
        itemSelect.appendChild(opt0);

        teamAssignments[teamId].forEach(i => {
            const opt = document.createElement('option');
            opt.value = i.item_id;
            opt.textContent = `${i.item_name} (Available: ${i.available})`;
            if (String(oldItemId) === String(i.item_id)) {
                opt.selected = true;
            }
            itemSelect.appendChild(opt);
        });
    }

    // Initialize on load (supports old values after validation errors)
    document.addEventListener('DOMContentLoaded', function(){
        const teamSelect = document.getElementById('teamSelect');
        if(teamSelect && oldTeamId){
            teamSelect.value = String(oldTeamId);
        }
        syncTeamItems();
    });
</script>
@endsection
