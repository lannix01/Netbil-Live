@extends('layouts.guest')

@section('content')
<div class="min-h-screen bg-gradient-to-b from-blue-50 to-white px-4 py-8 flex flex-col items-center" x-data="portalUI()">

    {{-- Animated Background Waves --}}
    <div class="fixed inset-0 overflow-hidden pointer-events-none opacity-20">
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-blue-300 rounded-full mix-blend-multiply filter blur-3xl"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-blue-200 rounded-full mix-blend-multiply filter blur-3xl"></div>
        <div class="absolute top-1/2 left-1/4 w-96 h-96 bg-blue-100 rounded-full mix-blend-multiply filter blur-3xl"></div>
    </div>

    {{-- Main Container --}}
    <div class="w-full max-w-3xl relative z-10">

        {{-- Header --}}
        <header class="text-center mb-12 pt-6">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-lg mb-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
            </div>
            <h1 class="text-3xl md:text-4xl font-bold text-blue-900">Get Connected</h1>
            <p class="text-blue-600 mt-2 max-w-md mx-auto">Choose your preferred connection method below</p>
        </header>

        {{-- Selection Cards --}}
        <div class="space-y-6 mb-12">

            {{-- PPPoE --}}
            <div class="p-6 border rounded-xl bg-white shadow hover:shadow-md cursor-pointer" @click="openTab('pppoe')">
                <h3 class="text-xl font-semibold text-gray-900 mb-2">PPPoE Connection</h3>
                <p class="text-gray-600 text-sm">Use your ISP credentials for dedicated broadband access</p>
                <div class="mt-4">
                    <button class="submit-btn w-full text-center">Get Started</button>
                </div>
            </div>

            {{-- Hotspot --}}
            <div class="p-6 border rounded-xl bg-white shadow hover:shadow-md cursor-pointer" @click="openTab('hotspot')">
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Hotspot Packages</h3>
                <p class="text-gray-600 text-sm">Prepaid packages with instant access</p>
                <div class="mt-4">
                    <button class="submit-btn w-full text-center">View Packages</button>
                </div>
            </div>

            {{-- Metered --}}
            <div class="p-6 border rounded-xl bg-white shadow hover:shadow-md cursor-pointer" @click="openTab('metered')">
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Metered Billing</h3>
                <p class="text-gray-600 text-sm">Postpaid connection with monthly billing cycle</p>
                <div class="mt-4">
                    <button class="submit-btn w-full text-center">Register</button>
                </div>
            </div>

        </div>

    </div>

    {{-- Modal Overlay --}}
    <div 
        x-show="tab !== ''"
        x-transition.opacity
        class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 px-4 py-6"
        @click.self="closeTab()"
    >
        <div 
            x-show="tab !== ''"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-4"
            class="w-full max-w-lg bg-white rounded-xl shadow-lg overflow-hidden"
        >

            {{-- Modal Header --}}
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-900" x-text="
                    tab === 'pppoe' ? 'PPPoE Setup' :
                    tab === 'hotspot' ? 'Hotspot Packages' :
                    'Metered Registration'
                "></h3>
                <button @click="closeTab()" class="text-gray-400 hover:text-gray-600 font-bold text-lg">✕</button>
            </div>

            {{-- Modal Body --}}
            <div class="p-6 max-h-[70vh] overflow-y-auto space-y-6">

                {{-- PPPoE Form --}}
                <div x-show="tab === 'pppoe'" x-transition.opacity>
                    <form action="{{ route('portal.pppoe') }}" method="POST" class="space-y-4">
                        @csrf
                        <input type="text" name="name" placeholder="Full Name" class="form-input" required>
                        <input type="tel" name="phone" placeholder="Phone Number" class="form-input">
                        <input type="text" name="mac_address" placeholder="MAC Address" value="{{ $prefill['mac'] ?? '' }}" class="form-input">
                        <input type="text" name="username" placeholder="PPPoE Username" class="form-input" required>
                        <input type="password" name="password" placeholder="PPPoE Password" class="form-input" required>
                        <div class="flex gap-3">
                            <button type="submit" class="submit-btn w-full">Activate</button>
                            <button type="button" @click="closeTab()" class="border px-4 py-2 rounded-lg w-full text-gray-700 hover:bg-gray-50">Cancel</button>
                        </div>
                    </form>
                </div>

                {{-- Hotspot Packages --}}
                <div x-show="tab === 'hotspot'" x-transition.opacity class="space-y-4">
                    @foreach($packages as $pkg)
                    <div class="p-4 border rounded-lg flex flex-col gap-3 hover:shadow-md">
                        <div class="flex justify-between items-center">
                            <div>
                                <h4 class="font-medium text-gray-900">{{ $pkg->name }}</h4>
                                <p class="text-sm text-gray-500">{{ $pkg->speed }} • One-time payment</p>
                            </div>
                            <div class="text-gray-700 font-semibold text-lg">KES {{ number_format($pkg->price, 2) }}</div>
                        </div>
                        <button @click="stkPay({{ $pkg->id }})" class="submit-btn w-full text-center">
                            Pay with M-Pesa
                        </button>
                    </div>
                    @endforeach
                    <button @click="closeTab()" class="border px-4 py-2 rounded-lg w-full text-gray-700 hover:bg-gray-50 mt-2">Back</button>
                </div>

                {{-- Metered Form --}}
                <div x-show="tab === 'metered'" x-transition.opacity>
                    <form action="{{ route('portal.metered') }}" method="POST" class="space-y-4">
                        @csrf
                        <input type="text" name="name" placeholder="Full Name" class="form-input" required>
                        <input type="tel" name="phone" placeholder="Phone Number" class="form-input" required>
                        <input type="text" name="mac_address" placeholder="MAC Address" class="form-input">
                        <select name="preferred_speed" class="form-input">
                            <option value="">Preferred Speed</option>
                            <option value="5Mbps">5 Mbps</option>
                            <option value="10Mbps">10 Mbps</option>
                            <option value="20Mbps">20 Mbps</option>
                            <option value="50Mbps">50 Mbps</option>
                            <option value="100Mbps">100 Mbps</option>
                        </select>
                        <div class="flex gap-3">
                            <button type="submit" class="submit-btn w-full">Register</button>
                            <button type="button" @click="closeTab()" class="border px-4 py-2 rounded-lg w-full text-gray-700 hover:bg-gray-50">Cancel</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

    {{-- Loading Overlay --}}
    <div 
        x-show="loading" 
        x-transition.opacity
        class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50"
    >
        <div class="bg-white rounded-2xl p-8 shadow-2xl flex flex-col items-center">
            <div class="loader mb-4"></div>
            <p class="text-gray-900 font-medium">Processing your request...</p>
            <p class="text-gray-600 text-sm mt-1">Please wait a moment</p>
        </div>
    </div>

</div>

<script>
function portalUI() {
    return {
        tab: '',
        loading: false,

        openTab(t) {
            this.tab = t;
            document.body.style.overflow = 'hidden';
        },

        closeTab() {
            this.tab = '';
            document.body.style.overflow = 'auto';
        },

        stkPay(id) {
            const phone = prompt('Enter your M-Pesa phone number (2547XXXXXXXX):');
            if (!phone || !/^254[17]\d{8}$/.test(phone)) {
                alert('Please enter a valid Kenyan phone number starting with 254');
                return;
            }
            
            this.loading = true;

            fetch("{{ route('portal.payment.stk') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({ 
                    package_id: id, 
                    phone: phone 
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success || data.checkoutRequestID) {
                    this.showNotification('Payment initiated! Check your phone for the M-Pesa prompt.', 'success');
                } else {
                    this.showNotification('Payment failed: ' + (data.message || 'Please try again'), 'error');
                }
            })
            .catch(error => {
                console.error('Payment error:', error);
                this.showNotification('Network error. Please try again.', 'error');
            })
            .finally(() => this.loading = false);
        },

        showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg text-white transform transition-all duration-300 ${
                type === 'success' ? 'bg-blue-500' :
                type === 'error' ? 'bg-red-500' : 'bg-blue-600'
            }`;
            notification.innerHTML = `<div class="flex items-center"><span>${message}</span></div>`;
            document.body.appendChild(notification);
            setTimeout(() => { notification.style.opacity = '0'; setTimeout(() => notification.remove(), 300); }, 4000);
        }
    }
}
</script>

<style>
/* Inputs and buttons */
.form-input {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #cbd5e1;
    border-radius: 0.75rem;
    font-size: 0.95rem;
    color: #1e293b;
    background-color: #f8fafc;
}
.form-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}
.submit-btn {
    padding: 0.75rem;
    border-radius: 0.75rem;
    font-weight: 500;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: white;
    transition: all 0.2s;
}
.submit-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59,130,246,0.3);
}

/* Loader */
.loader {
    width: 40px;
    height: 40px;
    border: 3px solid rgba(59,130,246,0.2);
    border-top: 3px solid #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
</style>
@endsection
