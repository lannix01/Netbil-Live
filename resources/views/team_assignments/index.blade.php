@extends('inventory::layout')

@section('page-title', 'Team Assignments')
@section('page-subtitle', 'Allocate bulk stock to team pools and track what’s deployable.')

@section('page-actions')
    <a class="btn btn-outline-secondary btn-sm" href="{{ route('inventory.team_deployments.index') }}" data-inv-loading>Team Deploy</a>
    <a class="btn btn-outline-dark btn-sm" href="{{ route('inventory.logs.index') }}" data-inv-loading>Logs</a>
@endsection

@section('inventory-content')
@php
    $teams = $teams ?? collect();
    $items = $items ?? collect();
    $assignments = $assignments ?? collect();

    $hasErr = fn($key) => $errors->has($key);
    $errMsg = fn($key) => $errors->first($key);

    // Preserve old input after validation errors
    $oldTeam = old('team_id');
    $oldItem = old('item_id');
    $oldQty = old('qty_allocated', 1);
    $oldRef = old('reference');
    $oldNotes = old('notes');

    // Build item lookup once (avoid DB queries inside the table loop)
    $itemNamesById = $items->mapWithKeys(function($i){
        return [$i->id => $i->name];
    });

    // Optional skeleton preview: /inventory/team-assignments?loading=1
    $loading = request()->boolean('loading');
@endphp

<style>
    /* Team assignments (scoped) */
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

    .inv-skel{ display:none; padding:16px; }
    .inv-skel.show{ display:block; }

    @media (max-width: 575.98px){
        .hide-xs{ display:none; }
    }
</style>

<div class="row g-3">
    {{-- Left: assign form + quick links --}}
    <div class="col-lg-4">
        <div class="inv-card inv-panel">
            <div class="inv-panel-head">
                <div>
                    <p class="inv-panel-title mb-0">Assign Items to Team (Bulk)</p>
                    <div class="inv-panel-sub">Team assignment is currently bulk-only (serialized to teams can come later).</div>
                </div>
                <span class="inv-chip">Bulk</span>
            </div>

            <div class="inv-panel-body">
                @if($teams->count() === 0)
                    <div class="inv-empty">
                        <div class="inv-empty-ico">👥</div>
                        <p class="inv-empty-title mb-0">No teams found</p>
                        <div class="inv-empty-sub">Create teams before assigning stock to team pools.</div>
                        <div class="mt-3">
                            <a class="btn btn-dark btn-sm" href="{{ route('inventory.teams.create') }}" data-inv-loading>+ Create team</a>
                            <a class="btn btn-outline-secondary btn-sm ms-1" href="{{ route('inventory.teams.index') }}" data-inv-loading>View teams</a>
                        </div>
                    </div>
                @elseif($items->count() === 0)
                    <div class="inv-empty">
                        <div class="inv-empty-ico">📦</div>
                        <p class="inv-empty-title mb-0">No items available</p>
                        <div class="inv-empty-sub">Create items first, then receive stock, then assign to teams.</div>
                        <div class="mt-3">
                            <a class="btn btn-dark btn-sm" href="{{ route('inventory.items.create') }}" data-inv-loading>+ Create item</a>
                            <a class="btn btn-outline-dark btn-sm ms-1" href="{{ route('inventory.receipts.create') }}" data-inv-loading>Receive stock</a>
                        </div>
                    </div>
                @else
                    <form method="POST" action="{{ route('inventory.team_assignments.store') }}" data-inv-loading>
                        @csrf

                        <div class="inv-field">
                            <label class="form-label">Team <span class="text-danger">*</span></label>
                            <select class="form-select {{ $hasErr('team_id') ? 'inv-input-invalid' : '' }}" name="team_id" required>
                                <option value="">-- Select Team --</option>
                                @foreach($teams as $t)
                                    <option value="{{ $t->id }}" @selected((string)$oldTeam === (string)$t->id)>
                                        {{ $t->name }} {{ $t->code ? '(' . $t->code . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @if($hasErr('team_id'))
                                <div class="inv-error">{{ $errMsg('team_id') }}</div>
                            @endif
                        </div>

                        <div class="inv-field">
                            <label class="form-label">Item <span class="text-danger">*</span></label>
                            <select class="form-select {{ $hasErr('item_id') ? 'inv-input-invalid' : '' }}" name="item_id" required>
                                <option value="">-- Select Item --</option>
                                @foreach($items as $i)
                                    <option value="{{ $i->id }}" @selected((string)$oldItem === (string)$i->id)>
                                        {{ $i->name }} (Store: {{ $i->qty_on_hand }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="inv-hint">Team assignment is bulk (serialized to teams comes next).</div>
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
                            >
                            @if($hasErr('qty_allocated'))
                                <div class="inv-error">{{ $errMsg('qty_allocated') }}</div>
                            @endif
                        </div>

                        <div class="inv-field">
                            <label class="form-label">Reference (optional)</label>
                            <input class="form-control {{ $hasErr('reference') ? 'inv-input-invalid' : '' }}" name="reference" value="{{ $oldRef }}">
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

                        <button class="btn btn-dark inv-btn-wide">Assign to Team</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="inv-card inv-panel mt-3">
            <div class="inv-panel-head">
                <div>
                    <p class="inv-panel-title mb-0">Quick Links</p>
                    <div class="inv-panel-sub">Jump to related workflows.</div>
                </div>
                <span class="inv-chip">⚡</span>
            </div>
            <div class="inv-panel-body d-flex gap-2 flex-wrap">
                <a class="btn btn-outline-secondary" href="{{ route('inventory.team_deployments.index') }}" data-inv-loading>Team Deploy</a>
                <a class="btn btn-outline-dark" href="{{ route('inventory.logs.index') }}" data-inv-loading>Logs</a>
                <a class="btn btn-outline-dark" href="{{ route('inventory.teams.index') }}" data-inv-loading>Teams</a>
            </div>
        </div>
    </div>

    {{-- Right: assignments table --}}
    <div class="col-lg-8">
        <div class="inv-card inv-table-card">
            <div class="inv-table-head">
                <div>
                    <p class="inv-table-title mb-0">Team Assignments</p>
                    <div class="inv-table-sub">Allocated vs deployed quantities for each team pool.</div>
                </div>
                <div class="inv-table-tools">
                    <span class="inv-chip">
                        Total: {{ method_exists($assignments, 'total') ? $assignments->total() : (method_exists($assignments, 'count') ? $assignments->count() : 0) }}
                    </span>
                </div>
            </div>

            {{-- Optional skeleton preview --}}
            <div class="inv-skel {{ $loading ? 'show' : '' }}">
                <div class="inv-skeleton inv-skel-row lg" style="width:260px;"></div>
                <div class="inv-skeleton inv-skel-row" style="width:420px;"></div>
                <div class="inv-divider"></div>
                @for($r=0;$r<7;$r++)
                    <div class="d-flex gap-2 align-items-center mb-2">
                        <div class="inv-skeleton" style="width:180px; height:14px; border-radius:10px;"></div>
                        <div class="inv-skeleton" style="flex:1; height:14px; border-radius:10px;"></div>
                        <div class="inv-skeleton hide-xs" style="width:90px; height:14px; border-radius:10px;"></div>
                        <div class="inv-skeleton" style="width:120px; height:14px; border-radius:10px;"></div>
                    </div>
                @endfor
            </div>

            @if(!$loading)
                <div class="inv-table-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Team</th>
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
                                        $teamName = $a->team?->name ?? '—';
                                        $teamCode = $a->team?->code ?? '';
                                        $itemName = $itemNamesById[$a->item_id] ?? ('Item #'.$a->item_id);

                                        $alloc = (int)($a->qty_allocated ?? 0);
                                        $dep = (int)($a->qty_deployed ?? 0);
                                        $avail = method_exists($a, 'availableToDeploy') ? (int)$a->availableToDeploy() : max(0, $alloc - $dep);

                                        $assigner = $a->assigner?->name ?? '—';
                                        $at = $a->assigned_at?->format('Y-m-d H:i') ?? '—';
                                    @endphp

                                    <tr>
                                        <td>
                                            <div style="font-weight:900;">{{ $teamName }}</div>
                                            <div class="inv-muted" style="font-size:12px;">
                                                {{ $teamCode !== '' ? $teamCode : '—' }}
                                            </div>
                                        </td>

                                        <td>{{ $itemName }}</td>
                                        <td>{{ $alloc }}</td>
                                        <td>{{ $dep }}</td>
                                        <td><span class="badge bg-dark">{{ $avail }}</span></td>
                                        <td class="hide-xs">{{ $assigner }}</td>
                                        <td class="text-muted">{{ $at }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="p-0">
                                            <div class="inv-empty">
                                                <div class="inv-empty-ico">👥</div>
                                                <p class="inv-empty-title mb-0">No team assignments yet</p>
                                                <div class="inv-empty-sub">
                                                    Assign stock to a team pool using the form on the left.
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
@endsection
