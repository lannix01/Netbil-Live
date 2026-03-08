@php
    $previewUrl = (string)($portal['preview_url'] ?? route('connect.index'));
    $openUrl = (string)($portal['open_url'] ?? route('connect.index'));
    $hotspotPlans = (int)($portal['hotspot_plans'] ?? 0);
    $meteredPlans = (int)($portal['metered_plans'] ?? 0);
    $generatedAt = (string)($portal['generated_at'] ?? now()->toDateTimeString());
@endphp

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <h5 class="mb-1">Captive Portal Preview</h5>
        <div class="text-muted small">This is the live public portal page rendered inside Settings.</div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ $openUrl }}" target="_blank" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-box-arrow-up-right me-1"></i>Open Live Portal
        </a>
        <a href="{{ $previewUrl }}" target="_blank" class="btn btn-primary btn-sm">
            <i class="bi bi-eye me-1"></i>Open Preview Link
        </a>
    </div>
</div>

<div class="row g-2 mb-3">
    <div class="col-md-4">
        <div class="border rounded p-2 bg-light">
            <div class="small text-muted">Hotspot Plans</div>
            <div class="fw-bold fs-5">{{ number_format($hotspotPlans) }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="border rounded p-2 bg-light">
            <div class="small text-muted">Metered Plans</div>
            <div class="fw-bold fs-5">{{ number_format($meteredPlans) }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="border rounded p-2 bg-light">
            <div class="small text-muted">Preview Generated</div>
            <div class="fw-bold">{{ $generatedAt }}</div>
        </div>
    </div>
</div>

<div class="border rounded overflow-hidden" style="height:78vh; min-height:560px;">
    <iframe
        src="{{ $previewUrl }}"
        title="Captive Portal Preview"
        loading="lazy"
        style="width:100%; height:100%; border:0;">
    </iframe>
</div>
