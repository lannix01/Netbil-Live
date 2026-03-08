@forelse ($recentChatsPaginated as $chat)
    <div class="d-flex justify-content-between align-items-center mb-2">
        <button class="btn btn-outline-secondary w-75 text-start"
                onclick="fillPhone('{{ $chat['phone'] }}')">
            {{ $chat['phone'] }}
        </button>
        <div class="btn-group">
            <button class="btn btn-sm btn-outline-secondary" disabled title="Add to Contacts">
                <i class="bi bi-person-plus"></i>
            </button>
            <button class="btn btn-sm btn-outline-success"
                    onclick="viewSmsThread('{{ $chat['phone'] }}')"
                    title="View SMS thread">
                <i class="bi bi-chat-dots"></i>
            </button>
        </div>
    </div>
@empty
    <p class="text-center text-muted">No recent contacts.</p>
@endforelse

<div class="mt-3 d-flex justify-content-center">
    {{ $recentChatsPaginated->appends(['tab' => 'contacts'])->links('vendor.pagination.simple-numbers') }}
</div>
