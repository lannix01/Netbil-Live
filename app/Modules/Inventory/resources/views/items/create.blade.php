@extends('inventory::layout')

@section('page-title', 'Create Item')
@section('page-subtitle', 'Add a new inventory item, define reorder level, and tracking rules.')

@section('page-actions')
    <a class="btn btn-outline-secondary btn-sm" href="{{ route('inventory.items.index') }}" data-inv-loading>Back to Items</a>
@endsection

@section('inventory-content')
@php
    $groups = $groups ?? collect();

    $hasErr = fn($key) => $errors->has($key);
    $errMsg = fn($key) => $errors->first($key);

    // old() helpers
    $oldGroup = old('item_group_id');
    $oldName = old('name');
    $oldSku = old('sku');
    $oldUnit = old('unit', 'pcs');
    $oldSerial = old('has_serial', '0');
    $oldReorder = old('reorder_level', 0);
    $oldDesc = old('description');
    $oldActive = old('is_active', '1');
@endphp

<style>
    /* Create item (scoped) */
    .inv-form-card{ padding: 0; overflow:hidden; }
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
            <p class="inv-form-title mb-0">Create Item</p>
            <div class="inv-form-sub">Define how this item is tracked in store and when it should trigger restocking.</div>
        </div>
        <div class="inv-actions-right">
            Fields marked required must be filled.
        </div>
    </div>

    <div class="inv-form-body">
        <form method="POST" action="{{ route('inventory.items.store') }}" data-inv-loading>
            @csrf

            <div class="inv-grid">

                {{-- Group --}}
                <div class="inv-field">
                    <label class="form-label">Group <span class="text-danger">*</span></label>
                    <select
                        class="form-select {{ $hasErr('item_group_id') ? 'inv-input-invalid' : '' }}"
                        name="item_group_id"
                        required
                    >
                        <option value="">-- Select Group --</option>
                        @foreach($groups as $g)
                            <option value="{{ $g->id }}" @selected((string)$oldGroup === (string)$g->id)>{{ $g->name }}</option>
                        @endforeach
                    </select>
                    <div class="inv-hint">Pick the category this item belongs to (used for filtering and reporting).</div>
                    @if($hasErr('item_group_id'))
                        <div class="inv-error">{{ $errMsg('item_group_id') }}</div>
                    @endif
                </div>

                {{-- Item Name --}}
                <div class="inv-field">
                    <label class="form-label">Item Name <span class="text-danger">*</span></label>
                    <input
                        class="form-control {{ $hasErr('name') ? 'inv-input-invalid' : '' }}"
                        name="name"
                        value="{{ $oldName }}"
                        placeholder="e.g. Drop wire, ONU, Router..."
                        required
                    />
                    <div class="inv-hint">Use a clear name that technicians will recognize instantly.</div>
                    @if($hasErr('name'))
                        <div class="inv-error">{{ $errMsg('name') }}</div>
                    @endif
                </div>

                {{-- SKU --}}
                <div class="inv-field">
                    <label class="form-label">SKU (optional)</label>
                    <input
                        class="form-control {{ $hasErr('sku') ? 'inv-input-invalid' : '' }}"
                        name="sku"
                        value="{{ $oldSku }}"
                        placeholder="e.g. SKY-ONU-001"
                    />
                    <div class="inv-hint">Internal code for quick identification. Leave blank if not used.</div>
                    @if($hasErr('sku'))
                        <div class="inv-error">{{ $errMsg('sku') }}</div>
                    @endif
                </div>

                {{-- Unit --}}
                <div class="inv-field">
                    <label class="form-label">Unit <span class="text-danger">*</span></label>
                    <input
                        class="form-control {{ $hasErr('unit') ? 'inv-input-invalid' : '' }}"
                        name="unit"
                        value="{{ $oldUnit }}"
                        placeholder="pcs / meters / rolls..."
                        required
                    />
                    <div class="inv-hint">How the item is counted in store (e.g. pcs, meters, rolls).</div>
                    @if($hasErr('unit'))
                        <div class="inv-error">{{ $errMsg('unit') }}</div>
                    @endif
                </div>

                {{-- Has serial --}}
                <div class="inv-field">
                    <label class="form-label">Has Serial Numbers?</label>
                    <select
                        class="form-select {{ $hasErr('has_serial') ? 'inv-input-invalid' : '' }}"
                        name="has_serial"
                    >
                        <option value="0" @selected((string)$oldSerial === '0')>No</option>
                        <option value="1" @selected((string)$oldSerial === '1')>Yes</option>
                    </select>
                    <div class="inv-hint">
                        If enabled, receiving/assigning/deploying can track serial numbers per unit (ideal for ONUs/routers).
                    </div>
                    @if($hasErr('has_serial'))
                        <div class="inv-error">{{ $errMsg('has_serial') }}</div>
                    @endif
                </div>

                {{-- Reorder --}}
                <div class="inv-field">
                    <label class="form-label">Reorder Level <span class="text-danger">*</span></label>
                    <input
                        class="form-control {{ $hasErr('reorder_level') ? 'inv-input-invalid' : '' }}"
                        type="number"
                        min="0"
                        name="reorder_level"
                        value="{{ $oldReorder }}"
                        required
                    />
                    <div class="inv-hint">
                        When store quantity hits this level (or below), it should be considered low stock.
                    </div>
                    @if($hasErr('reorder_level'))
                        <div class="inv-error">{{ $errMsg('reorder_level') }}</div>
                    @endif
                </div>

                {{-- Description (full width) --}}
                <div class="inv-field" style="grid-column: 1 / -1;">
                    <label class="form-label">Description</label>
                    <textarea
                        class="form-control {{ $hasErr('description') ? 'inv-input-invalid' : '' }}"
                        name="description"
                        rows="3"
                        placeholder="Notes, brand/model, special handling..."
                    >{{ $oldDesc }}</textarea>
                    <div class="inv-hint">Optional notes for technicians/admins.</div>
                    @if($hasErr('description'))
                        <div class="inv-error">{{ $errMsg('description') }}</div>
                    @endif
                </div>

                {{-- Active --}}
                <div class="inv-field">
                    <label class="form-label">Active?</label>
                    <select
                        class="form-select {{ $hasErr('is_active') ? 'inv-input-invalid' : '' }}"
                        name="is_active"
                    >
                        <option value="1" @selected((string)$oldActive === '1')>Yes</option>
                        <option value="0" @selected((string)$oldActive === '0')>No</option>
                    </select>
                    <div class="inv-hint">Inactive items remain in history but can be hidden from daily operations.</div>
                    @if($hasErr('is_active'))
                        <div class="inv-error">{{ $errMsg('is_active') }}</div>
                    @endif
                </div>

                {{-- Tiny guidance card --}}
                <div class="inv-field">
                    <label class="form-label">Heads up</label>
                    <div style="font-weight:900;">Good defaults = fewer headaches</div>
                    <div class="inv-hint">
                        Recommended: set unit properly, enable serial only for truly serial-tracked stock, and keep reorder > 0 for fast alerts.
                    </div>
                </div>

            </div>

            <div class="inv-form-actions">
                <div class="inv-actions-left">
                    <button class="btn btn-dark">Save Item</button>
                    <a class="btn btn-outline-secondary" href="{{ route('inventory.items.index') }}" data-inv-loading>Cancel</a>
                </div>
                <div class="inv-actions-right">
                    Tip: saving triggers the global loader automatically.
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
