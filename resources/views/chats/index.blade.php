@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="text-center mb-4">
        <h1 class="h3 fw-bold text-primary">Messages Panel</h1>
        <p class="text-muted">Send SMS, view chats, and control your communication game</p>
    </div>

    <div class="row g-4 mb-4">
        <!-- New Message Form -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h4 class="card-title text-secondary">New Message</h4>
                    <form id="smsForm" action="{{ route('chats.send') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="to" class="form-label">Phone Number</label>
                            <input type="text" name="to" id="to" placeholder="e.g. 07xx..." class="form-control">
                        </div>

                        <div class="mb-3">
                            <label for="message" class="form-label">Message Body</label>
                            <textarea name="message" id="message" rows="4" class="form-control" placeholder="Type your message..."></textarea>
                            <div class="form-text" id="charCount">0 characters</div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary" id="sendBtn">
                                <span id="sendBtnText"><i class="bi bi-send me-1"></i>Send</span>
                                <span id="sendBtnSpinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            </button>

                        </div>
                    </form>
                </div>
            </div>
        </div>

  <!-- Recent Contacts -->
<div class="col-lg-4">
    <div class="card shadow-sm border-0 h-100">
        <div class="card-body">
            <h5 class="card-title d-flex justify-content-between align-items-center">
                Recent Contacts
                <span>
                    <i class="bi bi-person-plus text-muted me-2" title="Add to contacts (disabled)"></i>
                    <i class="bi bi-chat-dots text-primary" title="Chat View (see below)"></i>
                </span>
            </h5>
            <hr>

            <div id="recentContactsWrapper">
    <div id="recentContactsContent">
        @include('chats.partials.recent-contacts')
    </div>

    <div id="recentContactsLoader" class="text-center d-none mt-2">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>
</div>


        </div>
    </div>
</div>


    </div>

    <!-- Search and Table -->
<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0">All Sent Messages</h5>
            <input type="text" id="searchInput" class="form-control w-25" placeholder="Search messages...">
        </div>

        <div id="messagesLoader" class="text-center d-none mb-3">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>

        <div id="messagesTableWrapper">
            @include('chats.partials.messages-table')
        </div>
    </div>
</div>

</div>

<!-- Modal for Full Message -->
<div class="modal fade" id="fullMessageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content shadow-lg">
      <div class="modal-header">
        <h5 class="modal-title">Full Message</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p id="modalMessageText" class="text-dark"></p>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary" id="resendToSame">Resend to Same</button>
        <button class="btn btn-secondary" id="resendNew">Send to Different</button>
      </div>
    </div>
  </div>
</div>

<script>
    const msgInput = document.getElementById('message');
    const phoneInput = document.getElementById('to');
    const charCounter = document.getElementById('charCount');

    msgInput?.addEventListener('input', function () {
        charCounter.textContent = `${this.value.length} characters`;
    });

    function fillPhone(phone) {
        if (confirm(`Send new SMS to ${phone}?`)) {
            phoneInput.value = phone;
            phoneInput.focus();
        }
    }
function viewSmsThread(phone) {
    alert("TODO: Fetch & display SMS for " + phone);
    // You could replace alert with modal logic and AJAX fetch for message history
}

    function resendMessage(message, phone) {
        phoneInput.value = phone;
        msgInput.value = message;
        charCounter.textContent = `${message.length} characters`;
        phoneInput.focus();
    }

    let modalMessage = '';
    let modalPhone = '';

    function showFullMessage(message, phone) {
        modalMessage = message;
        modalPhone = phone;
        document.getElementById('modalMessageText').textContent = message;
        new bootstrap.Modal(document.getElementById('fullMessageModal')).show();
    }

    document.getElementById('resendToSame').addEventListener('click', () => {
        resendMessage(modalMessage, modalPhone);
        bootstrap.Modal.getInstance(document.getElementById('fullMessageModal')).hide();
    });

    document.getElementById('resendNew').addEventListener('click', () => {
        resendMessage(modalMessage, '');
        bootstrap.Modal.getInstance(document.getElementById('fullMessageModal')).hide();
    });

    // Live search
    document.getElementById('searchInput').addEventListener('input', function () {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll("#messagesTable tbody tr");
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? "" : "none";
        });
    });
    document.getElementById('smsForm')?.addEventListener('submit', function () {
    const btn = document.getElementById('sendBtn');
    document.getElementById('sendBtnText').classList.add('d-none');
    document.getElementById('sendBtnSpinner').classList.remove('d-none');
    btn.disabled = true;
});
document.querySelectorAll('.delete-form').forEach(form => {
    form.addEventListener('submit', function () {
        const btn = form.querySelector('.delete-btn');
        btn.querySelector('.delete-text').classList.add('d-none');
        btn.querySelector('.spinner-border').classList.remove('d-none');
    });
});


</script>
@if (session('status'))
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1050;">
        <div id="liveToast" class="toast align-items-center text-white bg-success border-0 show fade" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    {{ session('status') }}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <script>
        const toastLive = document.getElementById('liveToast');
        const toast = new bootstrap.Toast(toastLive);
        toast.show();
    </script>

@endif
<script>
document.addEventListener("DOMContentLoaded", function () {
    document.body.addEventListener('click', function (e) {
        if (e.target.closest('.pagination a')) {
            e.preventDefault();
            const url = e.target.closest('a').getAttribute('href');
            loadRecentContacts(url);
        }
    });

    function loadRecentContacts(url) {
        const wrapper = document.getElementById('recentContactsContent');
        const loader = document.getElementById('recentContactsLoader');

        wrapper.classList.add('opacity-50');
        loader.classList.remove('d-none');

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            wrapper.innerHTML = data.html;
        })
        .catch(err => {
            console.error(err);
            wrapper.innerHTML = '<p class="text-danger">Oooops!!! Failed to load contacts.</p>';
        })
        .finally(() => {
            wrapper.classList.remove('opacity-50');
            loader.classList.add('d-none');
        });
    }
});
</script>

<script>
    // Intercept pagination for messages
    document.addEventListener('click', function (e) {
        if (e.target.closest('#messagesTableWrapper .pagination a')) {
            e.preventDefault();
            const url = e.target.closest('a').getAttribute('href');
            if (!url) return;

            const loader = document.getElementById('messagesLoader');
            const wrapper = document.getElementById('messagesTableWrapper');

            loader.classList.remove('d-none');
            wrapper.style.opacity = 0.3;

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.text())
            .then(html => {
                wrapper.innerHTML = html;
                wrapper.style.opacity = 1;
                loader.classList.add('d-none');
            })
            .catch(err => {
                console.error("Error loading paginated messages:", err);
                loader.classList.add('d-none');
                wrapper.style.opacity = 1;
            });
        }
    });
</script>


@endsection
