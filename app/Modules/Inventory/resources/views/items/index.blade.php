@extends('inventory::layout')

@section('page-title', 'Items')
@section('page-subtitle', 'Manage stock items, groups, reorder levels, and activation status.')

@section('page-actions')
    <a class="btn btn-dark btn-sm" href="{{ route('inventory.items.create') }}" data-inv-loading>
        <i class="bi bi-plus-lg me-1"></i> New Item
    </a>
@endsection

@section('inventory-content')
@php
    $items = $items ?? collect();
@endphp

<style>
    .inv-items-name{ font-weight: 900; }
    .inv-items-sub{ font-size: 12px; color: var(--muted); margin-top: 2px; }
    .inv-items-actions .btn{ border-radius: 12px; }
    .inv-kpi{ display:inline-flex; align-items:center; gap:.35rem; }
    .inv-kpi i{ opacity:.85; }
</style>

<div class="inv-card inv-table-card">
    <div class="inv-table-head">
        <div>
            <p class="inv-table-title mb-0">Items</p>
            <div class="inv-table-sub">All inventory items tracked in store and field allocations.</div>
        </div>

        <div class="inv-table-tools">
            <a class="btn btn-sm btn-outline-dark" href="{{ route('inventory.alerts.low_stock') }}" data-inv-loading>
                <i class="bi bi-bell me-1"></i> Low stock
            </a>
        </div>
    </div>

    <div class="inv-table-body">
        <div class="table-responsive">
            <table class="table table-sm align-middle table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width:80px;">#</th>
                        <th>Item</th>
                        <th style="width:180px;">Group</th>
                        <th style="width:160px;">SKU</th>
                        <th style="width:120px;">Unit</th>
                        <th style="width:110px;">Serial?</th>
                        <th style="width:110px;">Reorder</th>
                        <th style="width:130px;">Store Qty</th>
                        <th style="width:120px;">Status</th>
                        <th class="text-end" style="width:190px;">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($items as $i)
                        @php
                            // --- Make this view work for BOTH:
                            // 1) Eloquent Item model
                            // 2) stdClass from DB::table()
                            $itemId = $i->id ?? null;

                            $name = (string)($i->name ?? '—');
                            $sku  = (string)($i->sku ?? '');
                            $unit = (string)($i->unit ?? '—');

                            // Group name: either relation (Eloquent) or field if controller joined it
                            $groupName = $i->group?->name
                                ?? ($i->group_name ?? null)
                                ?? '—';

                            $hasSerial = (bool)($i->has_serial ?? false);
                            $isActive  = (bool)($i->is_active ?? true);

                            $reorder = (int)($i->reorder_level ?? 0);
                            $qty     = (int)($i->qty_on_hand ?? 0);

                            // Low stock rule (fallback if model method doesn't exist)
                            $isLow = $qty <= $reorder;
                        @endphp

                        <tr>
                            <td class="text-muted">
                                <span class="inv-chip">#{{ $itemId }}</span>
                            </td>

                            <td>
                                <div class="inv-items-name">{{ $name }}</div>
                                <div class="inv-items-sub">
                                    <span class="inv-kpi">
                                        <i class="bi bi-tag"></i> {{ $sku !== '' ? $sku : 'No SKU' }}
                                    </span>
                                </div>
                            </td>

                            <td>
                                <span class="inv-chip">
                                    <i class="bi bi-collection me-1"></i> {{ $groupName }}
                                </span>
                            </td>

                            <td>
                                {{ $sku !== '' ? $sku : '—' }}
                            </td>

                            <td>{{ $unit }}</td>

                            <td>
                                @if($hasSerial)
                                    <span class="badge bg-info">
                                        <i class="bi bi-upc-scan me-1"></i> Yes
                                    </span>
                                @else
                                    <span class="badge bg-secondary">
                                        <i class="bi bi-box me-1"></i> No
                                    </span>
                                @endif
                            </td>

                            <td>
                                <span class="badge bg-secondary">{{ $reorder }}</span>
                            </td>

                            <td>
                                @if($isLow)
                                    <span class="badge bg-danger">
                                        <i class="bi bi-exclamation-triangle me-1"></i> {{ $qty }}
                                    </span>
                                @else
                                    <span class="badge bg-success">
                                        <i class="bi bi-check2 me-1"></i> {{ $qty }}
                                    </span>
                                @endif
                            </td>

                            <td>
                                @if($isActive)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>

                            <td class="text-end inv-items-actions">
                                {{-- IMPORTANT: pass ID, not the object --}}
                                <a class="btn btn-sm btn-outline-secondary"
                                   href="{{ route('inventory.items.edit', $itemId) }}"
                                   data-inv-loading>
                                    <i class="bi bi-pencil-square me-1"></i> Edit
                                </a>

                                <form class="d-inline"
                                      method="POST"
                                      action="{{ route('inventory.items.destroy', $itemId) }}"
                                      onsubmit="return confirm('Delete this item?')"
                                      data-inv-loading>
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash3 me-1"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="p-0">
                                <div class="inv-empty">
                                    <div class="inv-empty-ico">
                                        <i class="bi bi-box-seam" style="font-size:22px;"></i>
                                    </div>
                                    <p class="inv-empty-title mb-0">No items yet</p>
                                    <div class="inv-empty-sub">
                                        Create your first item to start receiving and assigning stock.
                                    </div>
                                    <div class="mt-3">
                                        <a class="btn btn-dark btn-sm" href="{{ route('inventory.items.create') }}" data-inv-loading>
                                            <i class="bi bi-plus-lg me-1"></i> Create first item
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($items, 'links'))
            <div class="p-3">
                {{ $items->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
