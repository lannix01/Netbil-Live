@extends('pettycash::layouts.app')
@section('title', 'Respondent Profile')

@push('styles')
<style>
.wrap{max-width:1150px;margin:0 auto}
.top{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap}
.profile-grid{display:grid;grid-template-columns:320px 1fr;gap:12px}
.card{background:#fff;border:1px solid #e7e9f2;border-radius:14px;padding:16px;box-shadow:0 8px 30px rgba(16,24,40,.06);margin-top:12px}
.muted{color:#667085;font-size:12px}
.badge{display:inline-flex;align-items:center;padding:4px 8px;border-radius:999px;font-size:11px;font-weight:800}
.badge-ok{background:#ecfdf3;color:#027a48;border:1px solid #abefc6}
.badge-off{background:#f2f4f7;color:#475467;border:1px solid #eaecf0}
.avatar{width:96px;height:96px;border-radius:50%;background:#eef4ff;color:#1849a9;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:30px;overflow:hidden;border:1px solid #d0d5dd}
.avatar img{width:100%;height:100%;object-fit:cover}
.k{font-size:11px;color:#667085;text-transform:uppercase;letter-spacing:.04em}
.v{font-size:14px;font-weight:700;color:#101828;margin-top:2px}
.stack{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;min-height:38px;padding:0 14px;border-radius:10px;border:1px solid #6941c6;background:#6941c6;color:#fff;text-decoration:none;font-size:13px;font-weight:800;line-height:1;cursor:pointer}
.btn2{display:inline-flex;align-items:center;justify-content:center;gap:6px;min-height:38px;padding:0 14px;border-radius:10px;border:1px solid #d0d5dd;background:#fff;color:#344054;text-decoration:none;font-size:13px;font-weight:800;line-height:1;cursor:pointer}
@media(max-width:980px){.profile-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
@php
    $initials = strtoupper(substr((string) $respondent->name, 0, 1));
    $pettyUser = auth('petty')->user();
    $canEditRespondent = \App\Modules\PettyCash\Support\PettyAccess::allows($pettyUser, 'respondents.edit');
@endphp
<div class="wrap">
    <div class="top">
        <div>
            <h2 style="margin:0">Respondent Profile</h2>
            <div class="muted">Update details, generate badge/profile card, and send downloadable SMS link.</div>
        </div>
        <a class="btn2" href="{{ route('petty.respondents.index') }}">Back to Respondents</a>
    </div>

    <div class="profile-grid">
        <div class="card">
            <div class="stack" style="justify-content:space-between;align-items:flex-start">
                <div class="avatar">
                    @if(!empty($photoPreviewDataUri))
                        <img src="{{ $photoPreviewDataUri }}" alt="Photo">
                    @else
                        {{ $initials }}
                    @endif
                </div>
                @if($respondent->card_generated_at)
                    <span class="badge badge-ok">Card Ready</span>
                @else
                    <span class="badge badge-off">Card Not Ready</span>
                @endif
            </div>

            <div style="margin-top:12px">
                <div class="k">Name</div>
                <div class="v">{{ $respondent->name }}</div>
            </div>
            <div style="margin-top:10px">
                <div class="k">Phone</div>
                <div class="v">{{ $respondent->phone ?: '-' }}</div>
            </div>
            <div style="margin-top:10px">
                <div class="k">Category</div>
                <div class="v">{{ $respondent->category ?: '-' }}</div>
            </div>
            <div style="margin-top:10px">
                <div class="k">Profile Title</div>
                <div class="v">{{ $respondent->profile_title ?: '-' }}</div>
            </div>

            @if($canEditRespondent)
                <div style="margin-top:14px" class="stack">
                    <form method="POST" action="{{ route('petty.respondents.card.generate', $respondent->id) }}" style="margin:0">
                        @csrf
                        <button class="btn" type="submit">Generate Card</button>
                    </form>
                    <form method="POST" action="{{ route('petty.respondents.card.sms', $respondent->id) }}" style="margin:0">
                        @csrf
                        <button class="btn2" type="submit">Send SMS Link</button>
                    </form>
                </div>
            @endif

            @if($publicCardUrl && $canEditRespondent)
                <div style="margin-top:12px">
                    <div class="k">Public Download Link</div>
                    <div style="margin-top:6px">
                        <a class="btn2" target="_blank" href="{{ $publicCardUrl }}">Open Public Link</a>
                    </div>
                    <div class="muted" style="margin-top:6px">Password for download: registered phone number.</div>
                    @if($respondent->card_sms_sent_at)
                        <div class="muted" style="margin-top:4px">Last SMS: {{ $respondent->card_sms_sent_at?->format('Y-m-d H:i') }}</div>
                    @endif
                </div>
            @endif
        </div>

        <div class="form-card" id="profile-form">
            @if($canEditRespondent)
                @if($errors->any())
                    <div class="err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
                @endif

                <form class="pc-form" method="POST" action="{{ route('petty.respondents.update', $respondent->id) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="pc-field">
                        <label>Name</label>
                        <input class="pc-input" name="name" value="{{ old('name', $respondent->name) }}" required>
                    </div>

                    <div class="pc-field">
                        <label>Phone (used as card download password)</label>
                        <input class="pc-input" name="phone" value="{{ old('phone', $respondent->phone) }}">
                    </div>

                    <div class="pc-field">
                        <label>Category</label>
                        <input class="pc-input" name="category" value="{{ old('category', $respondent->category) }}">
                    </div>

                    <div class="pc-field">
                        <label>Profile Title</label>
                        <input class="pc-input" name="profile_title" value="{{ old('profile_title', $respondent->profile_title) }}" placeholder="Supplier, Contractor, Agent...">
                    </div>

                    <div class="pc-field">
                        <label>Email</label>
                        <input class="pc-input" type="email" name="profile_email" value="{{ old('profile_email', $respondent->profile_email) }}">
                    </div>

                    <div class="pc-field">
                        <label>Location</label>
                        <input class="pc-input" name="profile_location" value="{{ old('profile_location', $respondent->profile_location) }}">
                    </div>

                    <div class="pc-field full">
                        <label>Notes</label>
                        <textarea class="pc-textarea" name="profile_notes" rows="3" placeholder="Any extra profile details">{{ old('profile_notes', $respondent->profile_notes) }}</textarea>
                    </div>

                    <div class="pc-field">
                        <label>Profile Photo</label>
                        <input class="pc-input" type="file" name="profile_photo" accept=".jpg,.jpeg,.png,.webp">
                    </div>

                    <div class="pc-field">
                        <label class="pc-check">
                            <input type="checkbox" name="remove_photo" value="1"> Remove current photo
                        </label>
                    </div>

                    <div class="pc-actions">
                        <button class="btn" type="submit">Save Profile</button>
                        <a class="btn2" href="{{ route('petty.respondents.index') }}">Cancel</a>
                    </div>
                </form>
            @else
                <div class="muted">You have read-only access to respondent profiles.</div>
            @endif
        </div>
    </div>
</div>
@endsection
