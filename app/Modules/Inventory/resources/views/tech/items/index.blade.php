@extends('inventory::layout')

@section('page-title', 'Technician Dashboard')
@section('page-subtitle', 'Only items and routers assigned to you are visible here. Open an item to deploy it to a registered ONT/site.')

@section('page-toolbar')
    <div class="tech-toolbar">
        <span class="tech-toolbar-chip">{{ number_format((int) ($summary['assigned_items'] ?? 0)) }} Assigned</span>
        <span class="tech-toolbar-chip">{{ number_format((int) ($summary['deployable_total'] ?? 0)) }} Ready To Deploy</span>
        <span class="tech-toolbar-chip">{{ number_format((int) ($summary['allocated_total'] ?? 0)) }} Allocated</span>
        <span class="tech-toolbar-chip">{{ number_format((int) ($summary['serialized_items'] ?? 0)) }} Serialized</span>
        <span class="tech-toolbar-chip">{{ number_format((int) ($summary['assigned_routers'] ?? 0)) }} Routers</span>
    </div>
@endsection

@section('inventory-content')
@php
    $assignments = $assignments ?? collect();
    $assignedSerialCounts = $assignedSerialCounts ?? collect();
    $recentDeployments = $recentDeployments ?? collect();
    $routerSummary = $routerSummary ?? [];
    $access = \App\Modules\Inventory\Support\InventoryAccess::class;
    $canDeploy = $access::allows(auth('inventory')->user(), 'deployments.manage');
    $canRouters = $access::allows(auth('inventory')->user(), 'routers.view');
@endphp

<style>
    .tech-toolbar{
        display:flex;
        gap:10px;
        flex-wrap:wrap;
    }
    .tech-toolbar-chip{
        display:inline-flex;
        align-items:center;
        padding:8px 14px;
        border-radius:999px;
        border:1px solid rgba(38, 83, 64, .14);
        background:rgba(255,255,255,.78);
        color:#214636;
        font-size:12px;
        font-weight:800;
        letter-spacing:.04em;
    }
    .tech-layout{
        display:grid;
        gap:18px;
    }
    .tech-mobile-head{
        display:none;
        align-items:center;
        gap:12px;
        margin-bottom:8px;
    }
    .tech-burger{
        width:42px;
        height:42px;
        border-radius:14px;
        border:1px solid rgba(38, 83, 64, .18);
        background:#ffffff;
        color:#153728;
        display:grid;
        place-items:center;
        box-shadow:0 10px 18px rgba(22, 52, 37, .06);
        flex:0 0 auto;
    }
    .tech-mobile-title{
        margin:0;
        font-family:"Space Grotesk", sans-serif;
        font-size:20px;
        letter-spacing:-.03em;
        color:#153728;
    }
    .tech-stack{
        display:grid;
        gap:16px;
    }
    .tech-card{
        border:1px solid rgba(38, 83, 64, .12);
        border-radius:24px;
        background:linear-gradient(180deg, rgba(255,255,255,.98) 0%, rgba(247,252,248,.98) 100%);
        box-shadow:0 18px 44px rgba(73, 98, 82, .08);
        padding:18px;
    }
    .tech-head{
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:14px;
        margin-bottom:16px;
        flex-wrap:wrap;
    }
    .tech-title{
        margin:0;
        font-size:22px;
        line-height:1.1;
        font-weight:900;
        color:#153728;
    }
    .tech-sub{
        margin-top:6px;
        color:#5d7568;
        font-size:13px;
    }
    .tech-summary{
        display:grid;
        gap:14px;
        grid-template-columns:repeat(4, minmax(0, 1fr));
    }
    .tech-summary-box{
        border:1px solid rgba(38, 83, 64, .10);
        border-radius:20px;
        background:rgba(255,255,255,.9);
        padding:16px;
    }
    .tech-summary-link{
        display:flex;
        flex-direction:column;
        justify-content:space-between;
        min-height:100%;
        text-decoration:none;
        transition:transform .15s ease, box-shadow .15s ease, border-color .15s ease;
    }
    .tech-summary-link:hover{
        transform:translateY(-2px);
        box-shadow:0 14px 26px rgba(53, 93, 74, .10);
        border-color:rgba(31, 141, 95, .22);
    }
    .tech-summary-label{
        margin:0;
        font-size:11px;
        font-weight:800;
        letter-spacing:.14em;
        text-transform:uppercase;
        color:#78907f;
    }
    .tech-summary-value{
        margin-top:8px;
        font-size:30px;
        line-height:1;
        font-weight:900;
        color:#16382a;
    }
    .tech-summary-tagline{
        margin-top:6px;
        font-size:12px;
        font-weight:800;
        letter-spacing:.04em;
        text-transform:uppercase;
        color:#1f8d5f;
    }
    .tech-summary-note{
        margin-top:10px;
        display:flex;
        flex-wrap:wrap;
        gap:8px;
        color:#5d7568;
        font-size:12px;
        font-weight:700;
    }
    .tech-summary-note strong{
        color:#16382a;
    }
    .tech-summary-link-text{
        margin-top:12px;
        display:inline-flex;
        align-items:center;
        gap:8px;
        color:#1f8d5f;
        font-size:12px;
        font-weight:800;
        letter-spacing:.04em;
        text-transform:uppercase;
    }
    .tech-assignment-list{
        display:grid;
        gap:14px;
    }
    .tech-assignment{
        border:1px solid rgba(38, 83, 64, .12);
        border-radius:22px;
        background:rgba(255,255,255,.92);
        padding:16px;
    }
    .tech-assignment-top{
        display:flex;
        justify-content:space-between;
        gap:14px;
        align-items:flex-start;
        flex-wrap:wrap;
    }
    .tech-item-name{
        margin:0;
        font-size:20px;
        line-height:1.15;
        font-weight:900;
        color:#173829;
    }
    .tech-item-sub{
        margin-top:6px;
        display:flex;
        gap:8px;
        flex-wrap:wrap;
        color:#5e776a;
        font-size:12px;
        font-weight:700;
    }
    .tech-pill{
        display:inline-flex;
        align-items:center;
        padding:5px 10px;
        border-radius:999px;
        background:rgba(26, 130, 89, .10);
        color:#1a6648;
        border:1px solid rgba(26, 130, 89, .12);
        font-size:11px;
        font-weight:800;
        letter-spacing:.04em;
    }
    .tech-ready{
        background:#214636;
        color:#fff;
        border-color:#214636;
    }
    .tech-stats{
        margin-top:14px;
        display:grid;
        gap:10px;
        grid-template-columns:repeat(3, minmax(0, 1fr));
    }
    .tech-stat{
        border-radius:18px;
        border:1px solid rgba(38, 83, 64, .08);
        background:rgba(243, 249, 245, .95);
        padding:12px 14px;
    }
    .tech-stat-label{
        font-size:11px;
        font-weight:800;
        letter-spacing:.12em;
        text-transform:uppercase;
        color:#78907f;
    }
    .tech-stat-value{
        margin-top:6px;
        font-size:22px;
        font-weight:900;
        color:#153728;
        line-height:1;
    }
    .tech-actions{
        display:flex;
        gap:10px;
        flex-wrap:wrap;
        margin-top:14px;
    }
    .tech-btn{
        display:inline-flex;
        justify-content:center;
        align-items:center;
        min-height:44px;
        padding:0 16px;
        border-radius:14px;
        font-size:14px;
        font-weight:800;
        text-decoration:none;
        border:1px solid transparent;
        transition:transform .15s ease, box-shadow .15s ease;
    }
    .tech-btn:hover{
        transform:translateY(-1px);
        box-shadow:0 12px 24px rgba(53, 93, 74, .10);
    }
    .tech-btn-primary{
        background:#1f8d5f;
        color:#fff;
    }
    .tech-btn-secondary{
        background:#fff;
        color:#214636;
        border-color:rgba(38, 83, 64, .16);
    }
    .tech-recent-list{
        display:grid;
        gap:12px;
    }
    .tech-side-stack{
        display:grid;
        gap:18px;
    }
    .tech-recent-row{
        display:grid;
        gap:8px;
        border:1px solid rgba(38, 83, 64, .10);
        border-radius:20px;
        background:rgba(255,255,255,.88);
        padding:14px 16px;
    }
    .tech-router-row{
        display:grid;
        gap:10px;
        border:1px solid rgba(38, 83, 64, .10);
        border-radius:20px;
        background:rgba(255,255,255,.88);
        padding:14px 16px;
    }
    .tech-recent-top{
        display:flex;
        justify-content:space-between;
        gap:12px;
        align-items:flex-start;
        flex-wrap:wrap;
    }
    .tech-router-meta{
        display:flex;
        flex-wrap:wrap;
        gap:8px;
        margin-top:8px;
        color:#5e776a;
        font-size:12px;
        font-weight:700;
    }
    .tech-empty{
        padding:26px 20px;
        border-radius:20px;
        border:1px dashed rgba(38, 83, 64, .18);
        background:rgba(255,255,255,.76);
        text-align:center;
        color:#5d7568;
    }
    @media (min-width: 992px){
        .tech-layout{
            grid-template-columns:minmax(0, 1.4fr) minmax(320px, .8fr);
            align-items:start;
        }
    }
    @media (max-width: 767.98px){
        body.inv-tech-compact .inv-page-header{
            display:none;
        }
        .tech-mobile-head{
            display:flex;
        }
        .tech-toolbar{
            display:none;
        }
        .tech-card{
            padding:14px;
            border-radius:20px;
        }
        .tech-head{
            margin-bottom:10px;
        }
        .tech-sub{
            display:none;
        }
        .tech-summary,
        .tech-stats{
            grid-template-columns:repeat(2, minmax(0, 1fr));
        }
        .tech-summary-box.is-deployments{
            display:none;
        }
        .tech-summary-note,
        .tech-summary-link-text{
            display:none;
        }
        .tech-title{
            font-size:20px;
        }
        .tech-summary-value{
            font-size:24px;
        }
        .tech-item-name{
            font-size:18px;
        }
        .tech-item-sub,
        .tech-stats{
            display:none;
        }
        .tech-actions{
            flex-direction:column;
            margin-top:10px;
        }
        .tech-btn{
            width:100%;
        }
        .tech-btn-secondary{
            display:none;
        }
        .tech-side-stack{
            display:none;
        }
    }
</style>

<div class="tech-mobile-head">
    <button class="tech-burger" type="button" onclick="invToggleSidebar()" aria-label="Open menu">
        <i class="bi bi-list"></i>
    </button>
    <h2 class="tech-mobile-title">Dashboard</h2>
</div>

<div class="tech-stack">
    <div class="tech-card">
        <div class="tech-head">
            <div>
                <h2 class="tech-title">Assigned Inventory</h2>
                <div class="tech-sub">Open an item to pick the assigned serial or quantity, then deploy it to a registered ONT/site.</div>
            </div>
        </div>

            <div class="tech-summary">
                <div class="tech-summary-box">
                    <p class="tech-summary-label">Assigned Items</p>
                    <div class="tech-summary-value">{{ number_format((int) ($summary['assigned_items'] ?? 0)) }}</div>
                    <div class="tech-summary-tagline">View items</div>
                </div>
                <div class="tech-summary-box is-deployments">
                    <p class="tech-summary-label">Deployments Recorded</p>
                    <div class="tech-summary-value">{{ number_format((int) ($summary['deployments_recorded'] ?? 0)) }}</div>
                </div>
                @if($canRouters)
                    <a class="tech-summary-box tech-summary-link" href="{{ route('inventory.routers.index') }}" data-inv-loading>
                        <div>
                            <p class="tech-summary-label">Assigned Routers</p>
                            <div class="tech-summary-value">{{ number_format((int) ($routerSummary['assigned'] ?? 0)) }}</div>
                            <div class="tech-summary-tagline">View routers</div>
                            <div class="tech-summary-note">
                                <span><strong>{{ number_format((int) ($routerSummary['batch_count'] ?? 0)) }}</strong> batches</span>
                                <span>
                                    <strong>
                                        {{ !empty($routerSummary['latest_assigned_at']) ? \Illuminate\Support\Carbon::parse($routerSummary['latest_assigned_at'])->format('d M, H:i') : 'Not assigned yet' }}
                                    </strong>
                                </span>
                            </div>
                        </div>
                    </a>
                @else
                    <div class="tech-summary-box">
                        <p class="tech-summary-label">Assigned Routers</p>
                        <div class="tech-summary-value">{{ number_format((int) ($routerSummary['assigned'] ?? 0)) }}</div>
                        <div class="tech-summary-tagline">View routers</div>
                        <div class="tech-summary-note">
                            <span><strong>{{ number_format((int) ($routerSummary['batch_count'] ?? 0)) }}</strong> batches</span>
                            <span>
                                <strong>
                                    {{ !empty($routerSummary['latest_assigned_at']) ? \Illuminate\Support\Carbon::parse($routerSummary['latest_assigned_at'])->format('d M, H:i') : 'Not assigned yet' }}
                                </strong>
                            </span>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="tech-layout">
            <div class="tech-card">
            <div class="tech-head">
                <div>
                    <h2 class="tech-title">Your Items</h2>
                    <div class="tech-sub">Only active assignments under your account appear here.</div>
                </div>
            </div>

            <div class="tech-assignment-list">
                @forelse($assignments as $assignment)
                    @php
                        $item = $assignment->item;
                        $allocated = (int) ($assignment->qty_allocated ?? 0);
                        $deployed = (int) ($assignment->qty_deployed ?? 0);
                        $available = method_exists($assignment, 'availableToDeploy') ? (int) $assignment->availableToDeploy() : max(0, $allocated - $deployed);
                        $serialCount = (int) ($assignedSerialCounts[$assignment->item_id] ?? 0);
                    @endphp

                    <article class="tech-assignment">
                        <div class="tech-assignment-top">
                            <div>
                                <h3 class="tech-item-name">{{ $item?->name ?? 'Item' }}</h3>
                                <div class="tech-item-sub">
                                    <span class="tech-pill">{{ $item?->group?->name ?? 'Ungrouped' }}</span>
                                    <span class="tech-pill">{{ $item?->has_serial ? 'Serialized' : 'Bulk' }}</span>
                                    @if($item?->has_serial)
                                        <span class="tech-pill">{{ number_format($serialCount) }} Serials Ready</span>
                                    @endif
                                </div>
                            </div>

                            <span class="tech-pill {{ $available > 0 ? 'tech-ready' : '' }}">
                                {{ number_format($available) }} Ready
                            </span>
                        </div>

                        <div class="tech-stats">
                            <div class="tech-stat">
                                <div class="tech-stat-label">Allocated</div>
                                <div class="tech-stat-value">{{ number_format($allocated) }}</div>
                            </div>
                            <div class="tech-stat">
                                <div class="tech-stat-label">Deployed</div>
                                <div class="tech-stat-value">{{ number_format($deployed) }}</div>
                            </div>
                            <div class="tech-stat">
                                <div class="tech-stat-label">Last Update</div>
                                <div class="tech-stat-value" style="font-size:16px; line-height:1.2;">
                                    {{ optional($assignment->updated_at)->format('d M, H:i') ?? 'Now' }}
                                </div>
                            </div>
                        </div>

                        <div class="tech-actions">
                            <a class="tech-btn tech-btn-primary" href="{{ route('inventory.tech.items.show', $assignment->item_id) }}" data-inv-loading>
                                {{ $canDeploy ? 'Deploy Item' : 'View Item' }}
                            </a>
                            <a class="tech-btn tech-btn-secondary" href="{{ route('inventory.tech.items.show', $assignment->item_id) }}" data-inv-loading>
                                View Details
                            </a>
                        </div>
                    </article>
                @empty
                    <div class="tech-empty">
                        No assigned inventory is active under your account yet. Once an admin assigns stock, it will appear here for deployment.
                    </div>
                @endforelse
            </div>

            @if(method_exists($assignments, 'links'))
                <div class="pt-3">
                    {{ $assignments->links() }}
                </div>
            @endif
        </div>

        <div class="tech-side-stack">
            <div class="tech-card">
                <div class="tech-head">
                    <div>
                        <h2 class="tech-title">Recent Deployments</h2>
                        <div class="tech-sub">Latest records under your account.</div>
                    </div>
                </div>

                <div class="tech-recent-list">
                    @forelse($recentDeployments as $deployment)
                        <article class="tech-recent-row">
                            <div class="tech-recent-top">
                                <div>
                                    <strong style="display:block; color:#153728;">{{ $deployment->item?->name ?? 'Item' }}</strong>
                                    <div class="tech-sub" style="margin-top:4px;">{{ $deployment->site_name ?: 'Site not recorded' }}</div>
                                </div>
                                <span class="tech-pill">{{ number_format((int) ($deployment->qty ?? 0)) }}</span>
                            </div>
                            <div class="tech-item-sub">
                                <span class="tech-pill">Site ID {{ $deployment->site_code ?: 'N/A' }}</span>
                                <span class="tech-pill">{{ optional($deployment->created_at)->format('d M Y, H:i') ?? 'Just now' }}</span>
                            </div>
                        </article>
                    @empty
                        <div class="tech-empty">
                            No deployment has been recorded under your account yet.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.body.classList.add('inv-tech-compact');
    });
</script>
@endsection
