<div class="p-3" style="max-height: 480px; overflow:auto; background:#f3f4f6; border-radius:14px;">
    <div class="mb-2 text-muted small">Thread: {{ $phone }}</div>

    @foreach($messages as $m)
        @php
            $isOutgoing = ($m->sender ?? '') !== 'user'; // tweak to your logic
            $status = $m->status ?? 'SENT';
        @endphp

        <div class="d-flex mb-2 {{ $isOutgoing ? 'justify-content-end' : 'justify-content-start' }}">
            <div style="
                max-width:70%;
                padding:10px 12px;
                border-radius:16px;
                background: {{ $isOutgoing ? '#DCF8C6' : '#FFFFFF' }};
                box-shadow: 0 2px 6px rgba(0,0,0,0.06);
            ">
                <div class="small text-dark">{{ $m->text }}</div>
                <div class="d-flex justify-content-end gap-2 mt-1">
                    <span class="text-muted small">{{ optional($m->sent_at)->format('H:i') }}</span>
                    <span class="badge bg-secondary small">{{ $status }}</span>
                </div>
            </div>
        </div>
    @endforeach
</div>
