@extends('inventory::layout')

@section('page-title', 'Tech Assignments')
@section('page-subtitle', 'Assign stock to technicians (bulk and serialized) and track what’s deployable.')

@section('page-actions')
    <a class="btn btn-outline-dark btn-sm" href="{{ route('inventory.items.index') }}" data-inv-loading>Items</a>
    <a class="btn btn-dark btn-sm" href="{{ route('inventory.receipts.create') }}" data-inv-loading>Receive Stock</a>
@endsection

@section('inventory-content')
@php
    $technicians = $technicians ?? collect();
    $items = $items ?? collect();
    $serialItems = $serialItems ?? collect();
    $availableUnits = $availableUnits ?? collect();
    $assignments = $assignments ?? collect();

    $hasErr = fn($key) => $errors->has($key);
    $errMsg = fn($key) => $errors->first($key);

    // Old input (for preserving form after validation errors)
    $oldTech = old('technician_id');
    $oldItem = old('item_id');
    $oldQty = old('qty_allocated', 1);
    $oldRef = old('reference');
    $oldNotes = old('notes');

    $oldUnitIds = old('unit_ids', []);
    if (!is_array($oldUnitIds)) $oldUnitIds = [];

    // Bulk list: only non-serialized items
    $bulkItems = $items->where('has_serial', false);

    // Optional skeleton preview: /inventory/assignments?loading=1
    $loading = request()->boolean('loading');
@endphp

<style>
    /* Assignments page (scoped) */
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

    /* Serialized units select */
    #serialUnitsSelect{ font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono","Courier New", monospace; }

    /* Table card tweaks */
    .inv-table-foot{
        padding: 12px 16px;
        border-top: 1px solid var(--border);
        background: #fbfcff;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:10px;
        flex-wrap:wrap;
    }

    /* Skeleton */
    .inv-skel{ display:none; padding:16px; }
    .inv-skel.show{ display:block; }

    @media (max-width: 575.98px){
        .hide-xs{ display:none; }
    }
</style>

<div class="row g-3">
    {{-- Left column: forms --}}
    <div class="col-lg-4">

        {{-- Bulk assignment --}}
        <div class="inv-card inv-panel mb-3">
            <div class="inv-panel-head">
                <div>
                    <p class="inv-panel-title mb-0">Assign Bulk Items</p>
                    <div class="inv-panel-sub">For non-serialized items (qty-based).</div>
                </div>
                <span class="inv-chip">Bulk</span>
            </div>

            <div class="inv-panel-body">
                @if($technicians->count() === 0)
                    <div class="inv-empty">
                        <div class="inv-empty-ico">🧑‍🔧</div>
                        <p class="inv-empty-title mb-0">No technicians found</p>
                        <div class="inv-empty-sub">You need technicians before assigning stock.</div>
                    </div>
                @elseif($bulkItems->count() === 0)
                    <div class="inv-empty">
                        <div class="inv-empty-ico">📦</div>
                        <p class="inv-empty-title mb-0">No bulk items available</p>
                        <div class="inv-empty-sub">Only non-serialized items appear here.</div>
                        <div class="mt-3">
                            <a class="btn btn-outline-dark btn-sm" href="{{ route('inventory.items.index') }}" data-inv-loading>View items</a>
                        </div>
                    </div>
                @else
                    <form method="POST" action="{{ route('inventory.assignments.store') }}" data-inv-loading>
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
                            <label class="form-label">Item (Bulk) <span class="text-danger">*</span></label>
                            <select class="form-select {{ $hasErr('item_id') ? 'inv-input-invalid' : '' }}" name="item_id" required>
                                <option value="">-- Select Item --</option>
                                @foreach($bulkItems as $i)
                                    <option value="{{ $i->id }}" @selected((string)$oldItem === (string)$i->id)>
                                        {{ $i->name }} (Store: {{ $i->qty_on_hand }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="inv-hint">This list only shows NON-serialized items.</div>
                            @if($hasErr('item_id'))
                                <div class="inv-error">{{ $errMsg('item_id') }}</div>
                            @endif
                        </div>

                        <div class="inv-field">
                            <label class="form-label">Qty to Assign <span class="text-danger">*</span></label>
                            <input
                                class="form-control {{ $hasErr('qty_allocated') ? 'inv-input-invalid' : '' }}"
                                type="number"
                                min="1"
                                name="qty_allocated"
                                value="{{ $oldQty }}"
                                required
                            />
                            <div class="inv-hint">Allocated qty will be tracked against deployments.</div>
                            @if($hasErr('qty_allocated'))
                                <div class="inv-error">{{ $errMsg('qty_allocated') }}</div>
                            @endif
                        </div>

                        <div class="inv-field">
                            <label class="form-label">Reference (optional)</label>
                            <input class="form-control {{ $hasErr('reference') ? 'inv-input-invalid' : '' }}" name="reference" value="{{ $oldRef }}" />
                            @if($hasErr('reference'))
                                <div class="inv-error">{{ $errMsg('reference') }}</div>
                            @endif
                        </div>

                        <div class="inv-field">
                            <label class="form-label">Notes (optional)</label>
                            <textarea class="form-control {{ $hasErr('notes') ? 'inv-input-invalid' : '' }}" name="notes" rows="2">{{ $oldNotes }}</textarea>
                            @if($hasErr('notes'))
                                <div class="inv-error">{{ $errMsg('notes') }}</div>
                            @endif
                        </div>

                        <button class="btn btn-dark inv-btn-wide">Assign Bulk</button>
                    </form>
                @endif
            </div>
        </div>

        {{-- Serialized assignment --}}
        <div class="inv-card inv-panel">
            <div class="inv-panel-head">
                <div>
                    <p class="inv-panel-title mb-0">Assign Serialized Items</p>
                    <div class="inv-panel-sub">Select serial numbers (units) for assignment.</div>
                </div>
                <span class="inv-chip">Serial</span>
            </div>

            <div class="inv-panel-body">
                @if($technicians->count() === 0)
                    <div class="inv-empty">
                        <div class="inv-empty-ico">🧑‍🔧</div>
                        <p class="inv-empty-title mb-0">No technicians found</p>
                        <div class="inv-empty-sub">Create technicians before assigning serial stock.</div>
                    </div>
                @elseif($serialItems->count() === 0)
                    <div class="inv-empty">
                        <div class="inv-empty-ico">🔢</div>
                        <p class="inv-empty-title mb-0">No serialized items available</p>
                        <div class="inv-empty-sub">Only items with serial tracking enabled appear here.</div>
                        <div class="mt-3">
                            <a class="btn btn-outline-dark btn-sm" href="{{ route('inventory.items.index') }}" data-inv-loading>View items</a>
                        </div>
                    </div>
                @else
                    <form method="POST" action="{{ route('inventory.assignments.store') }}" data-inv-loading>
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
                            <label class="form-label">Item (Serialized) <span class="text-danger">*</span></label>
                            <select
                                class="form-select {{ $hasErr('item_id') ? 'inv-input-invalid' : '' }}"
                                name="item_id"
                                id="serialItemSelect"
                                required
                                onchange="updateSerialOptions()"
                            >
                                <option value="">-- Select Serialized Item --</option>
                                @foreach($serialItems as $si)
                                    <option value="{{ $si->id }}" @selected((string)$oldItem === (string)$si->id)>
                                        {{ $si->name }} (Store: {{ $si->qty_on_hand }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="inv-hint">Pick item then select serial(s) below.</div>
                            @if($hasErr('item_id'))
                                <div class="inv-error">{{ $errMsg('item_id') }}</div>
                            @endif
                        </div>

                        <div class="inv-field">
                            <label class="form-label">Serial Numbers (multi-select) <span class="text-danger">*</span></label>
                            <select
                                class="form-select {{ $hasErr('unit_ids') ? 'inv-input-invalid' : '' }}"
                                name="unit_ids[]"
                                id="serialUnitsSelect"
                                multiple
                                size="8"
                                required
                            >
                                <option value="">-- Select item first --</option>
                            </select>
                            <div class="inv-hint">Hold Ctrl/⌘ to select multiple serials.</div>
                            @if($hasErr('unit_ids'))
                                <div class="inv-error">{{ $errMsg('unit_ids') }}</div>
                            @endif
                        </div>

                        <div class="inv-field">
                            <label class="form-label">Reference (optional)</label>
                            <input class="form-control {{ $hasErr('reference') ? 'inv-input-invalid' : '' }}" name="reference" value="{{ $oldRef }}" />
                            @if($hasErr('reference'))
                                <div class="inv-error">{{ $errMsg('reference') }}</div>
                            @endif
                        </div>

                        <div class="inv-field">
                            <label class="form-label">Notes (optional)</label>
                            <textarea class="form-control {{ $hasErr('notes') ? 'inv-input-invalid' : '' }}" name="notes" rows="2">{{ $oldNotes }}</textarea>
                            @if($hasErr('notes'))
                                <div class="inv-error">{{ $errMsg('notes') }}</div>
                            @endif
                        </div>

                        <button class="btn btn-dark inv-btn-wide">Assign Serialized</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    {{-- Right column: table --}}
    <div class="col-lg-8">
        <div class="inv-card inv-table-card">
            <div class="inv-table-head">
                <div>
                    <p class="inv-table-title mb-0">Assignments</p>
                    <div class="inv-table-sub">Allocated vs deployed quantities per technician.</div>
                </div>
                <div class="inv-table-tools">
                    <span class="inv-chip">Total: {{ method_exists($assignments, 'total') ? $assignments->total() : (method_exists($assignments, 'count') ? $assignments->count() : 0) }}</span>
                </div>
            </div>

            {{-- Optional skeleton preview --}}
            <div class="inv-skel {{ $loading ? 'show' : '' }}">
                <div class="inv-skeleton inv-skel-row lg" style="width:260px;"></div>
                <div class="inv-skeleton inv-skel-row" style="width:420px;"></div>
                <div class="inv-divider"></div>
                @for($r=0;$r<7;$r++)
                    <div class="d-flex gap-2 align-items-center mb-2">
                        <div class="inv-skeleton" style="width:170px; height:14px; border-radius:10px;"></div>
                        <div class="inv-skeleton hide-xs" style="width:110px; height:14px; border-radius:10px;"></div>
                        <div class="inv-skeleton" style="flex:1; height:14px; border-radius:10px;"></div>
                        <div class="inv-skeleton" style="width:80px; height:14px; border-radius:10px;"></div>
                    </div>
                @endfor
            </div>

            @if(!$loading)
                <div class="inv-table-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Technician</th>
                                    <th class="hide-xs">Dept</th>
                                    <th>Item</th>
                                    <th style="width:110px;">Allocated</th>
                                    <th style="width:110px;">Deployed</th>
                                    <th style="width:120px;">Available</th>
                                    <th class="hide-xs">Assigned By</th>
                                    <th style="width:160px;">Assigned At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($assignments as $a)
                                    @php
                                        $tech = $a->technician?->name ?? '—';
                                        $dept = $a->technician?->department?->name ?? '—';
                                        $itemName = $a->item?->name ?? '—';
                                        $alloc = (int)($a->qty_allocated ?? 0);
                                        $dep = (int)($a->qty_deployed ?? 0);
                                        $avail = method_exists($a, 'availableToDeploy') ? (int)$a->availableToDeploy() : max(0, $alloc - $dep);
                                        $assigner = $a->assigner?->name ?? '—';
                                        $at = $a->assigned_at?->format('Y-m-d H:i') ?? '—';
                                    @endphp

                                    <tr>
                                        <td style="font-weight:900;">{{ $tech }}</td>
                                        <td class="hide-xs">{{ $dept }}</td>
                                        <td>{{ $itemName }}</td>
                                        <td>{{ $alloc }}</td>
                                        <td>{{ $dep }}</td>
                                        <td>
                                            <span class="badge bg-dark">{{ $avail }}</span>
                                        </td>
                                        <td class="hide-xs">{{ $assigner }}</td>
                                        <td class="text-muted">{{ $at }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="p-0">
                                            <div class="inv-empty">
                                                <div class="inv-empty-ico">🧾</div>
                                                <p class="inv-empty-title mb-0">No assignments yet</p>
                                                <div class="inv-empty-sub">
                                                    Assign stock to a technician using the forms on the left.
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if(method_exists($assignments, 'links'))
                        <div class="inv-table-foot">
                            <div class="inv-muted" style="font-size:12px;">
                                Showing {{ method_exists($assignments, 'count') ? $assignments->count() : 0 }} record(s) on this page.
                            </div>
                            <div>
                                {{ $assignments->links() }}
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    // Map: item_id => [{id, serial_no}, ...]
    const availableUnitsByItem = @json(
        $availableUnits->map(function($units) {
            return $units->map(function($u) {
                return ['id' => $u->id, 'serial_no' => $u->serial_no];
            })->values();
        })
    );

    // Preserve old unit selections (validation errors)
    const oldUnitIds = @json($oldUnitIds);

    function updateSerialOptions() {
        const itemId = document.getElementById('serialItemSelect')?.value;
        const select = document.getElementById('serialUnitsSelect');
        if (!select) return;

        select.innerHTML = '';

        if (!itemId || !availableUnitsByItem[itemId] || availableUnitsByItem[itemId].length === 0) {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = '-- No available serials in store --';
            select.appendChild(opt);
            return;
        }

        availableUnitsByItem[itemId].forEach(u => {
            const opt = document.createElement('option');
            opt.value = u.id;
            opt.textContent = u.serial_no;

            if (Array.isArray(oldUnitIds) && oldUnitIds.map(String).includes(String(u.id))) {
                opt.selected = true;
            }

            select.appendChild(opt);
        });
    }

    // Initialize options if old item exists (after failed validation) or user preselected
    document.addEventListener('DOMContentLoaded', function(){
        updateSerialOptions();
    });
</script>
@endsection
