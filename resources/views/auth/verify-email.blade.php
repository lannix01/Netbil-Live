<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Your Email</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- TailwindCSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body {
            background: linear-gradient(to right, #e0eafc, #cfdef3);
            font-family: 'Inter', sans-serif;
        }
        .card {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            max-width: 500px;
            margin: 4rem auto;
        }
        .btn {
            transition: all 0.2s ease-in-out;
        }
        .btn:hover {
            transform: scale(1.02);
        }
    </style>
</head>
<body>

    <div class="card text-center">
        <h2 class="text-2xl font-bold mb-4 text-gray-800">Verify Your Email</h2>

        <p class="text-gray-600 mb-6">
            Thanks for signing up! Please verify your email address by clicking the link we just sent to your inbox.
            If you didn't get the email, we can send you another.
        </p>

        @if (session('status') === 'verification-link-sent')
            <div class="mb-4 text-green-600 font-semibold">
                A fresh verification link has been sent to your email.
            </div>
        @endif

        <div class="flex flex-col sm:flex-row justify-center gap-4">
            <!-- Resend Verification -->
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="btn bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded">
                    Resend Email
                </button>
            </form>

            <!-- Logout -->
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium py-2 px-4 rounded">
                    Log Out
                </button>
            </form>
        </div>
    </div>

</body>
</html>
