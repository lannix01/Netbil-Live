<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Connection Status</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        body {
            background: linear-gradient(145deg, #f1f5f9 0%, #e2e8f0 100%);
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }

        .card {
            max-width: 520px;
            margin: auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(15, 23, 42, .15);
            border: 1px solid #dbe2ef;
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            padding: 1.5rem;
            text-align: center;
        }

        .card-body {
            padding: 1.5rem;
        }

        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: .75rem;
            font-size: .95rem;
        }

        .label {
            color: #6b7280;
            font-weight: 600;
        }

        .value {
            font-weight: 700;
            color: #111827;
            text-align: right;
        }

        .badge {
            display: inline-block;
            padding: .35rem .75rem;
            border-radius: 999px;
            font-size: .8rem;
            font-weight: 800;
        }

        .badge.active {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .badge.expired {
            background: #fee2e2;
            color: #991b1b;
        }

        .footer {
            margin-top: 1.25rem;
            text-align: center;
            font-size: .85rem;
            color: #6b7280;
        }
    </style>
</head>
<body>

<div class="card">
    <div class="card-header">
        <h2>Connected</h2>
        <div class="badge {{ $connection->status }}">
            {{ strtoupper($connection->status) }}
        </div>
    </div>

    <div class="card-body">
        <div class="row">
            <span class="label">Package</span>
            <span class="value">{{ $package->name ?? 'N/A' }}</span>
        </div>

        <div class="row">
            <span class="label">Mode</span>
            <span class="value">{{ strtoupper($mode ?? 'hotspot') }}</span>
        </div>

        <div class="row">
            <span class="label">Speed</span>
            <span class="value">{{ $package->speed ?? ($package->rate_limit ?? 'N/A') }}</span>
        </div>

        <div class="row">
            <span class="label">Price</span>
            <span class="value">KES {{ number_format((float)($package->price ?? 0), 2) }}</span>
        </div>

        <div class="row">
            <span class="label">Started At</span>
            <span class="value">{{ $connection->started_at }}</span>
        </div>

        <div class="row">
            <span class="label">Expires At</span>
            <span class="value">
                @if(($mode ?? 'hotspot') === 'metered')
                    Billing-cycle controlled
                @else
                    {{ $connection->expires_at }}
                @endif
            </span>
        </div>

        <div class="row">
            <span class="label">IP Address</span>
            <span class="value">{{ $connection->ip_address }}</span>
        </div>

        <div class="row">
            <span class="label">MAC Address</span>
            <span class="value">{{ $connection->mac_address }}</span>
        </div>

        <div class="footer">
            Keep this page open to monitor your session.
        </div>
    </div>
</div>

</body>
</html>
