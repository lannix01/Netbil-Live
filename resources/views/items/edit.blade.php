@extends('inventory::layout')

@section('page-title', 'Edit Item')
@section('page-subtitle', 'Update item details, reorder level, and tracking rules.')

@section('page-actions')
    <a class="btn btn-outline-secondary btn-sm" href="{{ route('inventory.items.index') }}" data-inv-loading>Back to Items</a>
@endsection

@section('inventory-content')
@php
    $groups = $groups ?? collect();

    $hasErr = fn($key) => $errors->has($key);
    $errMsg = fn($key) => $errors->first($key);

    // Current item details (for header)
    $itemName = $item?->name ?? 'Item';
    $itemSku = $item?->sku ?: '—';
    $itemGroup = $item?->group?->name ?? ($item?->item_group_id ? 'Group #'.$item->item_group_id : '—');

    // Old/model fallbacks
    $oldGroup   = old('item_group_id', $item->item_group_id);
    $oldName    = old('name', $item->name);
    $oldSku     = old('sku', $item->sku);
    $oldUnit    = old('unit', $item->unit);
    $oldSerial  = old('has_serial', $item->has_serial ? '1' : '0');
    $oldReorder = old('reorder_level', $item->reorder_level);
    $oldDesc    = old('description', $item->description);
    $oldActive  = old('is_active', $item->is_active ? '1' : '0');
@endphp

<style>
    /* Edit item (scoped) */
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

    .inv-mini{
        display:flex;
        gap: 8px;
        flex-wrap:wrap;
        align-items:center;
        justify-content:flex-end;
    }

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
            <p class="inv-form-title mb-0">Edit Item</p>
            <div class="inv-form-sub">
                Updating: <span class="inv-chip">{{ $itemName }}</span>
                <span class="inv-chip">SKU: {{ $itemSku }}</span>
                <span class="inv-chip">Group: {{ $itemGroup }}</span>
            </div>
        </div>

        <div class="inv-mini">
            <span class="inv-chip">ID: #{{ $item->id }}</span>
            @if($item->is_active)
                <span class="badge bg-success">Active</span>
            @else
                <span class="badge bg-secondary">Inactive</span>
            @endif
        </div>
    </div>

    <div class="inv-form-body">
        <form method="POST" action="{{ route('inventory.items.update', $item) }}" data-inv-loading>
            @csrf
            @method('PUT')

            <div class="inv-grid">

                {{-- Group --}}
                <div class="inv-field">
                    <label class="form-label">Group <span class="text-danger">*</span></label>
                    <select
                        class="form-select {{ $hasErr('item_group_id') ? 'inv-input-invalid' : '' }}"
                        name="item_group_id"
                        required
                    >
                        @foreach($groups as $g)
                            <option value="{{ $g->id }}" @selected((string)$oldGroup === (string)$g->id)>{{ $g->name }}</option>
                        @endforeach
                    </select>
                    <div class="inv-hint">Changing group affects filtering and reporting.</div>
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
                        required
                    />
                    <div class="inv-hint">Use a clear name technicians will recognize.</div>
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
                    <div class="inv-hint">Leave blank if SKU is not used.</div>
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
                        required
                    />
                    <div class="inv-hint">How the item is counted (pcs, meters, rolls...).</div>
                    @if($hasErr('unit'))
                        <div class="inv-error">{{ $errMsg('unit') }}</div>
                    @endif
                </div>

                {{-- Serial --}}
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
                        If enabled, receiving/assigning/deploying can track serial numbers per unit.
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
                    <div class="inv-hint">Low stock triggers when store quantity is at or below this value.</div>
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
                    >{{ $oldDesc }}</textarea>
                    <div class="inv-hint">Optional notes for admins/techs.</div>
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
                    <div class="inv-hint">Inactive items remain in history but can be hidden from daily flows.</div>
                    @if($hasErr('is_active'))
                        <div class="inv-error">{{ $errMsg('is_active') }}</div>
                    @endif
                </div>

                {{-- Warning/Info tile --}}
                <div class="inv-field">
                    <label class="form-label">Heads up</label>
                    <div style="font-weight:900;">Changes affect downstream flows</div>
                    <div class="inv-hint">
                        If you turn serial tracking ON/OFF after stock exists, ensure your receive/assign/deploy flows handle that correctly.
                        Keep reorder sensible to avoid false alerts.
                    </div>
                </div>

            </div>

            <div class="inv-form-actions">
                <div class="inv-actions-left">
                    <button class="btn btn-dark">Update Item</button>
                    <a class="btn btn-outline-secondary" href="{{ route('inventory.items.index') }}" data-inv-loading>Cancel</a>
                </div>
                <div class="inv-actions-right">
                    ID: #{{ $item->id }} • Last updated: {{ $item->updated_at?->format('M j, Y') ?? '—' }}
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
