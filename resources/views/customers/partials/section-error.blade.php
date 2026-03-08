{{-- resources/views/customers/partials/section-error.blade.php --}}
<div class="p-3">
    <div class="alert alert-warning mb-0 d-flex align-items-start gap-2" role="alert" style="border-radius:14px;">
        <i class="bi bi-exclamation-triangle" style="font-size:1.2rem;"></i>
        <div>
            <div class="fw-bold">{{ $title ?? 'Error' }}</div>
            <div class="small">{{ $message ?? 'Something went wrong.' }}</div>
        </div>
    </div>
</div>
