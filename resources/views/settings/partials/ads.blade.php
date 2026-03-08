@php
    $now = now();
@endphp

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="fw-bold mb-0">Captive Ads Management</h5>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAdModal">
            <i class="bi bi-plus-circle"></i> Add New Ad
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <div class="fw-semibold mb-1">Could not save ad.</div>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Content / Preview</th>
                    <th>CTA</th>
                    <th>Schedule</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ads as $ad)
                    @php
                        $mediaType = $ad->resolved_media_type ?? 'text';
                        $mediaUrl = $ad->resolved_media_url;
                        $ctaUrl = $ad->resolved_cta_url;
                        $ctaText = $ad->resolved_cta_text;
                        $startsAt = $ad->starts_at;
                        $endsAt = $ad->ends_at;
                        $isLiveNow = (!$startsAt || $startsAt->copy()->startOfDay()->lte($now)) && (!$endsAt || $endsAt->copy()->endOfDay()->gte($now));
                        $isUpcoming = $startsAt && $startsAt->copy()->startOfDay()->gt($now);
                        $isExpired = $endsAt && $endsAt->copy()->endOfDay()->lt($now);
                    @endphp
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <div class="fw-semibold">{{ $ad->title }}</div>
                        </td>
                        <td>
                            @if($mediaType === 'video')
                                <span class="badge bg-info text-dark">Video</span>
                            @elseif($mediaType === 'image')
                                <span class="badge bg-primary">Image</span>
                            @else
                                <span class="badge bg-secondary">Text</span>
                            @endif
                        </td>
                        <td style="min-width: 260px;">
                            @if($mediaType === 'video' && $mediaUrl)
                                <video src="{{ $mediaUrl }}" class="w-100 rounded border mb-2" style="max-height: 140px;" controls muted preload="metadata"></video>
                            @elseif($mediaType === 'image' && $mediaUrl)
                                <img src="{{ $mediaUrl }}" alt="Ad image" class="img-fluid rounded border mb-2" style="max-height: 90px;">
                            @endif
                            <div class="small text-muted">{{ \Illuminate\Support\Str::limit((string)($ad->content ?? ''), 110) ?: 'No text content.' }}</div>
                        </td>
                        <td style="min-width: 180px;">
                            @if($ctaUrl)
                                <a href="{{ $ctaUrl }}" target="_blank" rel="noopener noreferrer">
                                    {{ $ctaText ?: \Illuminate\Support\Str::limit($ctaUrl, 28) }}
                                </a>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td style="min-width: 220px;">
                            <div class="small">
                                {{ $startsAt?->format('Y-m-d') ?? 'Any time' }} - {{ $endsAt?->format('Y-m-d') ?? 'No end date' }}
                            </div>
                            <div class="mt-1">
                                @if($isLiveNow)
                                    <span class="badge bg-success">Live now</span>
                                @elseif($isUpcoming)
                                    <span class="badge bg-warning text-dark">Upcoming</span>
                                @elseif($isExpired)
                                    <span class="badge bg-dark">Expired</span>
                                @else
                                    <span class="badge bg-light text-dark">Not scheduled</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-dark">{{ (int)$ad->priority }}</span>
                            <div class="small text-muted mt-1">Higher shows first</div>
                        </td>
                        <td>
                            @if($ad->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>
                        <td class="d-flex gap-1">
                            <form method="POST" action="{{ route('ads.toggle', $ad) }}">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-warning" title="Toggle status">
                                    <i class="bi bi-toggle-on"></i>
                                </button>
                            </form>

                            <form method="POST" action="{{ route('ads.destroy', $ad) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this ad?')" title="Delete ad">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted">No ads found. Add a new one to get started.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="createAdModal" tabindex="-1" aria-labelledby="createAdModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form method="POST" action="{{ route('ads.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createAdModalLabel">Add New Captive Ad</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" class="form-control" required value="{{ old('title') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Ad Type</label>
                            <select name="ad_type" id="adType" class="form-select">
                                <option value="text" @selected(old('ad_type', 'text') === 'text')>Text</option>
                                <option value="image" @selected(old('ad_type') === 'image')>Image</option>
                                <option value="video" @selected(old('ad_type') === 'video')>Video</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Content / Caption</label>
                            <textarea name="content" id="adContent" class="form-control" rows="3" placeholder="Message shown on portal">{{ old('content') }}</textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Media URL (for image/video ads)</label>
                            <input type="url" name="media_url" id="adMediaUrl" class="form-control" value="{{ old('media_url') }}" placeholder="https://example.com/ad.mp4 or https://example.com/ad.jpg">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">CTA Label (optional)</label>
                            <input type="text" name="cta_text" class="form-control" maxlength="80" value="{{ old('cta_text') }}" placeholder="Learn more">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">CTA Link (optional)</label>
                            <input type="url" name="cta_url" class="form-control" value="{{ old('cta_url') }}" placeholder="https://your-link.example">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Priority</label>
                            <input type="number" name="priority" class="form-control" min="0" value="{{ old('priority', 0) }}">
                            <div class="form-text">Higher number displays before lower priority ads.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="starts_at" class="form-control" value="{{ old('starts_at') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">End Date</label>
                            <input type="date" name="ends_at" class="form-control" value="{{ old('ends_at') }}">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save Ad</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
(() => {
    const typeInput = document.getElementById('adType');
    const contentInput = document.getElementById('adContent');
    const mediaInput = document.getElementById('adMediaUrl');

    if (!typeInput || !contentInput || !mediaInput) {
        return;
    }

    const syncFields = () => {
        const type = (typeInput.value || 'text').toLowerCase();
        if (type === 'text') {
            contentInput.required = true;
            mediaInput.required = false;
            mediaInput.placeholder = 'Optional for text ads';
            return;
        }

        contentInput.required = false;
        mediaInput.required = true;
        mediaInput.placeholder = type === 'video'
            ? 'https://example.com/ad.mp4'
            : 'https://example.com/ad.jpg';
    };

    typeInput.addEventListener('change', syncFields);
    syncFields();
})();
</script>
