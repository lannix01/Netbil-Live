<div class="table-responsive">
    <table class="table table-hover table-bordered align-middle" id="messagesTable">
        <thead class="table-light">
            <tr>
                <th>Label</th>
                <th>Phone Number</th>
                <th>Message</th>
                <th>Sent At</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($allMessages as $msg)
                <tr>
                    <td>{{ substr($msg->phone, 0, 5) }}...{{ substr($msg->phone, -3) }}</td>
                    <td>{{ $msg->phone }}</td>
                    <td>
                        <span class="text-muted">{{ Str::limit($msg->text, 30) }}</span>
                        <a href="#" class="ms-2" onclick="showFullMessage(`{{ addslashes($msg->text) }}`, `{{ $msg->phone }}`)">[view]</a>
                    </td>
                    <td>{{ \Carbon\Carbon::parse($msg->sent_at)->diffForHumans() }}</td>
                    <td><span class="badge bg-success">Sent</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary me-2" onclick="resendMessage(`{{ addslashes($msg->text) }}`, `{{ $msg->phone }}`)">
                            <i class="bi bi-arrow-repeat"></i>
                        </button>
                        <form method="POST" action="{{ route('chats.delete', $msg->id) }}" class="d-inline delete-form">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger delete-btn">
                                <span class="delete-text"><i class="bi bi-trash"></i></span>
                                <span class="spinner-border spinner-border-sm text-danger d-none" role="status"></span>
                            </button>
                        </form>
                        @if(in_array($msg->status ?? 'SENT', ['FAILED','PENDING']))
    <form method="POST" action="{{ route('chats.retry', $msg->id) }}" class="d-inline">
        @csrf
        <button class="btn btn-sm btn-outline-warning" title="Retry">
            <i class="bi bi-arrow-clockwise"></i>
        </button>
    </form>
@endif

                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="mt-3 d-flex justify-content-center">
    {{ $allMessages->appends(['tab' => 'messages'])->links('vendor.pagination.simple-numbers') }}
</div>
