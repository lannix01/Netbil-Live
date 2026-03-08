@extends('inventory::layout')

@section('page-title', 'Create Team')
@section('page-subtitle', 'Create a team used for assignments and deployments.')

@section('page-actions')
    <a class="btn btn-outline-secondary btn-sm" href="{{ route('inventory.teams.index') }}" data-inv-loading>Back to Teams</a>
@endsection

@section('inventory-content')
@php
    $hasErr = fn($key) => $errors->has($key);
    $errMsg = fn($key) => $errors->first($key);

    $oldName = old('name');
    $oldCode = old('code');
    $oldDesc = old('description');
    $oldActive = old('is_active', '1');
@endphp

<style>
    /* Teams create (scoped) */
    .inv-form-card{ padding:0; overflow:hidden; }
    .inv-form-head{
        padding: 14px 16px;
        border-bottom: 1px solid var(--border);
        background: #fbfcff;
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:12px;
        flex-wrap:wrap;
    }
    .inv-form-title{ font-weight: 900; margin:0; font-size: 14px; }
    .inv-form-sub{ color: var(--muted); font-size: 12px; margin-top: 3px; }

    .inv-form-body{ padding: 16px; }

    .inv-grid{
        display:grid;
        gap: 14px;
        grid-template-columns: 1fr 1fr;
    }
    @media (max-width: 991.98px){
        .inv-grid{ grid-template-columns: 1fr; }
    }

    .inv-field{
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 12px;
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

    .inv-form-actions{
        padding: 14px 16px;
        border-top: 1px solid var(--border);
        background: #fbfcff;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap: 12px;
        flex-wrap:wrap;
    }

    .inv-actions-left{
        display:flex;
        gap: 8px;
        flex-wrap:wrap;
        align-items:center;
    }
    .inv-actions-right{
        color: var(--muted);
        font-size: 12px;
    }
</style>

<div class="inv-card inv-form-card">
    <div class="inv-form-head">
        <div>
            <p class="inv-form-title mb-0">Create Team</p>
            <div class="inv-form-sub">Define a team name, code, and whether it’s active.</div>
        </div>
        <div class="inv-actions-right">
            Tip: keep codes short (FIELD-1, NOC-A).
        </div>
    </div>

    <div class="inv-form-body">
        <form method="POST" action="{{ route('inventory.teams.store') }}" data-inv-loading>
            @csrf

            <div class="inv-grid">
                <div class="inv-field">
                    <label class="form-label">Team Name <span class="text-danger">*</span></label>
                    <input
                        class="form-control {{ $hasErr('name') ? 'inv-input-invalid' : '' }}"
                        name="name"
                        value="{{ $oldName }}"
                        placeholder="e.g. Field Team A"
                        required
                    >
                    <div class="inv-hint">This name appears in assignments and deployments.</div>
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
                        placeholder="e.g. NOC-A"
                    >
                    <div class="inv-hint">Unique short code, useful on reports and quick search.</div>
                    @if($hasErr('code'))
                        <div class="inv-error">{{ $errMsg('code') }}</div>
                    @endif
                </div>

                <div class="inv-field" style="grid-column: 1 / -1;">
                    <label class="form-label">Description (optional)</label>
                    <textarea
                        class="form-control {{ $hasErr('description') ? 'inv-input-invalid' : '' }}"
                        name="description"
                        rows="3"
                        placeholder="Team purpose, region, coverage…"
                    >{{ $oldDesc }}</textarea>
                    <div class="inv-hint">Optional notes for admins.</div>
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
                    <div class="inv-hint">Inactive teams can be hidden from daily workflows.</div>
                    @if($hasErr('is_active'))
                        <div class="inv-error">{{ $errMsg('is_active') }}</div>
                    @endif
                </div>

                <div class="inv-field">
                    <label class="form-label">What’s next</label>
                    <div style="font-weight:900;">After creating a team</div>
                    <div class="inv-hint">
                        Assign stock to the team pool, then deploy to sites from team allocation.
                    </div>
                    <div class="mt-2">
                        <a class="btn btn-sm btn-outline-dark" href="{{ route('inventory.team_assignments.index') }}" data-inv-loading>Team assignments</a>
                    </div>
                </div>
            </div>

            <div class="inv-form-actions">
                <div class="inv-actions-left">
                    <button class="btn btn-dark">Create Team</button>
                    <a class="btn btn-outline-secondary" href="{{ route('inventory.teams.index') }}" data-inv-loading>Cancel</a>
                </div>
                <div class="inv-actions-right">
                    Creates the team and enables it for assignments.
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
