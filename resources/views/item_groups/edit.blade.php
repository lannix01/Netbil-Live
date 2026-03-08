@extends('inventory::layout')

@section('page-title', 'Edit Item Group')
@section('page-subtitle', 'Update group details. Items linked to this group will remain linked.')

@section('page-actions')
    <a class="btn btn-outline-secondary btn-sm" href="{{ route('inventory.item-groups.index') }}" data-inv-loading>Back to Groups</a>
@endsection

@section('inventory-content')
@php
    $hasErr = fn($key) => $errors->has($key);
    $errMsg = fn($key) => $errors->first($key);

    $oldName = old('name', $group->name);
    $oldCode = old('code', $group->code);
    $oldDesc = old('description', $group->description);
@endphp

<style>
    /* Item groups form (scoped) */
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
            <p class="inv-form-title mb-0">Edit Item Group</p>
            <div class="inv-form-sub">
                Group ID: <span class="inv-chip">#{{ $group->id }}</span>
                <span class="inv-chip">{{ $group->name }}</span>
            </div>
        </div>
        <div class="inv-actions-right">
            Changes apply to all items in this group.
        </div>
    </div>

    <div class="inv-form-body">
        <form method="POST" action="{{ route('inventory.item-groups.update', $group) }}" data-inv-loading>
            @csrf
            @method('PUT')

            <div class="inv-grid">
                <div class="inv-field">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input
                        class="form-control {{ $hasErr('name') ? 'inv-input-invalid' : '' }}"
                        name="name"
                        value="{{ $oldName }}"
                        required
                    />
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
                        placeholder="e.g. FIB"
                    />
                    @if($hasErr('code'))
                        <div class="inv-error">{{ $errMsg('code') }}</div>
                    @endif
                </div>

                <div class="inv-field" style="grid-column:1 / -1;">
                    <label class="form-label">Description</label>
                    <textarea
                        class="form-control {{ $hasErr('description') ? 'inv-input-invalid' : '' }}"
                        name="description"
                        rows="3"
                    >{{ $oldDesc }}</textarea>
                    <div class="inv-hint">Optional. Helps admins understand what belongs here.</div>
                    @if($hasErr('description'))
                        <div class="inv-error">{{ $errMsg('description') }}</div>
                    @endif
                </div>
            </div>

            <div class="inv-form-actions">
                <div class="inv-actions-left">
                    <button class="btn btn-dark">Update Group</button>
                    <a class="btn btn-outline-secondary" href="{{ route('inventory.item-groups.index') }}" data-inv-loading>Cancel</a>
                </div>
                <div class="inv-actions-right">
                    Updates saved immediately.
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
