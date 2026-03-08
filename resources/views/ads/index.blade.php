@extends('layouts.app')

@section('content')
<div class="container py-4">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold"> Captive Ads Manager</h2>
            <p class="text-muted mb-0">
                Control what users see before connecting to the internet
            </p>
        </div>

        <button
            class="btn btn-primary"
            data-bs-toggle="modal"
            data-bs-target="#createAdModal"
        >
            <i class="bi bi-plus-circle me-1"></i> New Ad
        </button>
    </div>

    {{-- Ads Table --}}
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Title</th>
                        <th>Schedule</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($ads as $ad)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $ad->title }}</div>
                                <small class="text-muted">
                                    {{ Str::limit($ad->content, 60) }}
                                </small>
                            </td>

                            <td>
                                <small>
                                    {{ $ad->starts_at?->format('d M Y H:i') ?? '—' }}
                                    →
                                    {{ $ad->ends_at?->format('d M Y H:i') ?? '—' }}
                                </small>
                            </td>

                            <td>
                                <span class="badge bg-secondary">
                                    {{ $ad->priority }}
                                </span>
                            </td>

                            <td>
                                @if($ad->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-danger">Inactive</span>
                                @endif
                            </td>

                            <td class="text-end">
                                <form
                                    method="POST"
                                    action="{{ route('ads.toggle', $ad->id) }}"
                                    class="d-inline"
                                >
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn-sm btn-outline-warning">
                                        Toggle
                                    </button>
                                </form>

                                <form
                                    method="POST"
                                    action="{{ route('ads.destroy', $ad->id) }}"
                                    class="d-inline"
                                    onsubmit="return confirm('Delete this ad?')"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                No ads created yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- CREATE AD MODAL --}}
<div class="modal fade" id="createAdModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('ads.store') }}" class="modal-content">
            @csrf

            <div class="modal-header">
                <h5 class="modal-title">Create New Ad</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input
                        type="text"
                        name="title"
                        class="form-control"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Content</label>
                    <textarea
                        name="content"
                        class="form-control"
                        rows="3"
                        required
                    ></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Start Time</label>
                        <input
                            type="datetime-local"
                            name="starts_at"
                            class="form-control"
                        >
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">End Time</label>
                        <input
                            type="datetime-local"
                            name="ends_at"
                            class="form-control"
                        >
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Priority</label>
                    <input
                        type="number"
                        name="priority"
                        class="form-control"
                        value="0"
                    >
                </div>

                <div class="form-check">
                    <input
                        type="checkbox"
                        name="is_active"
                        class="form-check-input"
                        checked
                    >
                    <label class="form-check-label">
                        Activate immediately
                    </label>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">
                    Cancel
                </button>
                <button class="btn btn-primary">
                    Save Ad
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
