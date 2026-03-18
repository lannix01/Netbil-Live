@extends('inventory::layout')

@section('page-title', 'Item Groups')
@section('page-subtitle', 'Organize items into logical groups for cleaner receiving, assignment, and reporting.')

@section('page-actions')
    <a class="btn btn-dark btn-sm" href="{{ route('inventory.item-groups.create') }}" data-inv-loading>+ New Group</a>
@endsection

@section('inventory-content')
@php
    $groups = $groups ?? collect();

    // Safe defaults (won’t break if controller doesn’t pass them)
    $q = $q ?? request('q', '');
    $loading = request()->boolean('loading');
@endphp

<style>
    /* Item groups index (scoped) */
    .inv-skel{ display:none; padding:16px; }
    .inv-skel.show{ display:block; }

    .inv-desc{
        max-width: 380px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        color: var(--muted);
        font-size: 12px;
        margin-top: 4px;
    }

    @media (max-width: 575.98px){
        .hide-xs{ display:none; }
    }
</style>

<div class="inv-card inv-table-card">
    <div class="inv-table-head">
        <div>
            <p class="inv-table-title mb-0">Groups</p>
            <div class="inv-table-sub">
                Categorize items (Fiber, Tools, Routers, Consumables…)
                @if(trim($q) !== '')
                    • Search: <span class="inv-chip">“{{ $q }}”</span>
                @endif
            </div>
        </div>

        <div class="inv-table-tools">
            <form class="d-flex gap-2 flex-wrap align-items-center" method="GET" action="{{ route('inventory.item-groups.index') }}" data-inv-loading data-inv-autofilter>
                <input
                    class="form-control form-control-sm"
                    name="q"
                    value="{{ $q }}"
                    placeholder="Search name/code/description…"
                    style="min-width:240px;"
                >
                <button class="btn btn-sm btn-dark">Filter</button>
                @if(trim($q) !== '')
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('inventory.item-groups.index') }}" data-inv-loading>Reset</a>
                @endif
            </form>
        </div>
    </div>

    {{-- Optional skeleton preview: /inventory/item-groups?loading=1 --}}
    <div class="inv-skel {{ $loading ? 'show' : '' }}">
        <div class="inv-skeleton inv-skel-row lg" style="width:260px;"></div>
        <div class="inv-skeleton inv-skel-row" style="width:420px;"></div>
        <div class="inv-divider"></div>
        @for($r=0;$r<6;$r++)
            <div class="d-flex gap-2 align-items-center mb-2">
                <div class="inv-skeleton" style="width:60px; height:14px; border-radius:10px;"></div>
                <div class="inv-skeleton" style="width:220px; height:14px; border-radius:10px;"></div>
                <div class="inv-skeleton hide-xs" style="width:130px; height:14px; border-radius:10px;"></div>
                <div class="inv-skeleton" style="flex:1; height:14px; border-radius:10px;"></div>
                <div class="inv-skeleton" style="width:140px; height:14px; border-radius:10px;"></div>
            </div>
        @endfor
    </div>

    @if(!$loading)
        <div class="inv-table-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width:90px;">#</th>
                            <th>Name</th>
                            <th class="hide-xs" style="width:160px;">Code</th>
                            <th class="hide-xs">Description</th>
                            <th class="text-end" style="width:180px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($groups as $g)
                            @php
                                $id = $g->id ?? null;
                                $code = trim((string)($g->code ?? ''));
                                $desc = trim((string)($g->description ?? ''));
                            @endphp

                            <tr>
                                <td class="text-muted">
                                    <span class="inv-chip">#{{ $id }}</span>
                                </td>

                                <td>
                                    <div style="font-weight:900;">{{ $g->name }}</div>
                                    <div class="inv-desc">
                                        @if($desc !== '')
                                            {{ \Illuminate\Support\Str::limit($desc, 90) }}
                                        @else
                                            No description yet.
                                        @endif
                                    </div>
                                </td>

                                <td class="hide-xs">
                                    <span class="inv-chip">{{ $code !== '' ? $code : '—' }}</span>
                                </td>

                                <td class="hide-xs">
                                    {{ $desc !== '' ? $desc : '—' }}
                                </td>

                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-secondary"
                                       href="{{ route('inventory.item-groups.edit', $id) }}"
                                       data-inv-loading>Edit</a>

                                    <form
                                        class="d-inline"
                                        method="POST"
                                        action="{{ route('inventory.item-groups.destroy', $id) }}"
                                        onsubmit="return confirm('Delete this group?')"
                                        data-inv-loading
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-0">
                                    <div class="inv-empty">
                                        <div class="inv-empty-ico">🧩</div>
                                        <p class="inv-empty-title mb-0">No groups yet</p>
                                        <div class="inv-empty-sub">
                                            Create groups to keep items organized (helps receiving, reports, and low stock review).
                                        </div>
                                        <div class="mt-3">
                                            <a class="btn btn-dark btn-sm" href="{{ route('inventory.item-groups.create') }}" data-inv-loading>+ Create first group</a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(method_exists($groups, 'links'))
                <div class="p-3">
                    {{ $groups->links() }}
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
