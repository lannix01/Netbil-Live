<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body{
            font-family: DejaVu Sans, Arial, sans-serif;
            margin: 0;
            color: #0f172a;
            background: #edf2f8;
        }
        .sheet{
            padding: 18px;
        }
        .id-card{
            width: 690px;
            margin: 0 auto;
            border: 1px solid #cfd8e6;
            border-radius: 18px;
            background: #ffffff;
            overflow: hidden;
        }
        .logo-header{
            background: #ffffff;
            padding: 10px 18px 8px;
            border-bottom: 1px solid #e2e8f0;
        }
        .top-logo-wrap{
            width: 100%;
            height: 130px;
            text-align: center;
        }
        .top-logo-wrap table{
            width: 100%;
            height: 100%;
            border-collapse: collapse;
        }
        .top-logo-wrap td{
            vertical-align: middle;
            text-align: center;
        }
        .logo-img{
            width: auto;
            max-width: 95%;
            max-height: 100px;
            display: block;
            margin: 0 auto;
        }
        .logo-fallback{
            font-size: 48px;
            font-weight: 900;
            letter-spacing: .08em;
            color: #0f2f6a;
        }
        .meta-strip{
            background: #0f2f6a;
            color: #ffffff;
            padding: 7px 18px;
        }
        .band-row{
            width: 100%;
        }
        .band-sub{
            float: left;
            font-size: 11px;
            color: #ffffff;
            letter-spacing: .06em;
            text-transform: uppercase;
            font-weight: 700;
        }
        .status-pill{
            float: right;
            display: inline-block;
            padding: 6px 14px;
            border-radius: 999px;
            border: 1px solid #bcf3ce;
            background: #f0fff4;
            color: #116d37;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .08em;
        }
        .band-clear{
            clear: both;
        }
        .content{
            padding: 16px 18px;
        }
        .layout{
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .layout td{
            vertical-align: top;
        }
        .photo-col{
            width: 24%;
            padding-right: 12px;
        }
        .profile-photo{
            width: 126px;
            height: 150px;
            border-radius: 12px;
            border: 1px solid #cfd8e6;
            background: #eef3fb;
            text-align: center;
            line-height: 150px;
            font-size: 46px;
            font-weight: 900;
            color: #1e3a8a;
            overflow: hidden;
        }
        .profile-photo img{
            width: 126px;
            height: 150px;
            object-fit: cover;
        }
        .identity-col{
            width: 52%;
            padding-right: 10px;
        }
        .person-name{
            margin-top: 2px;
            font-size: 24px;
            line-height: 1.08;
            font-weight: 800;
            color: #0b1220;
            text-transform: uppercase;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .person-title{
            margin-top: 2px;
            font-size: 30px;
            line-height: 1.05;
            font-weight: 900;
            color: #0c4a8a;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .mini-line{
            margin-top: 9px;
            font-size: 12px;
            color: #334155;
        }
        .mini-label{
            display: inline-block;
            min-width: 54px;
            font-size: 10px;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .05em;
        }
        .qr-col{
            width: 24%;
            text-align: center;
        }
        .qr-wrap{
            margin-top: 2px;
            display: inline-block;
            border: 1px solid #c9d4e5;
            border-radius: 10px;
            padding: 6px;
            background: #ffffff;
        }
        .qr-img{
            width: 120px;
            height: 120px;
            display: block;
        }
        .qr-fallback{
            width: 120px;
            height: 120px;
            line-height: 120px;
            text-align: center;
            font-size: 10px;
            color: #475569;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
        }
        .scan-caption{
            margin-top: 6px;
            font-size: 10px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: .07em;
        }
        .issued{
            margin-top: 8px;
            text-align: right;
            font-size: 10px;
            color: #64748b;
        }
    </style>
</head>
<body>
<div class="sheet">
    <div class="id-card">
        <div class="logo-header">
            <div class="top-logo-wrap">
                <table>
                    <tr>
                        <td>
                        @if(!empty($logoDataUri))
                            <img class="logo-img" src="{{ $logoDataUri }}" alt="SKYBRIX Logo">
                        @else
                            <div class="logo-fallback">SKYBRIX</div>
                        @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="meta-strip">
            <div class="band-row">
                <div class="band-sub">STAFF IDENTIFICATION CARD</div>
                <span class="status-pill">Verified</span>
                <div class="band-clear"></div>
            </div>
        </div>

        <div class="content">
            <table class="layout">
                <tr>
                    <td class="photo-col">
                        <div class="profile-photo">
                            @if(!empty($photoDataUri))
                                <img src="{{ $photoDataUri }}" alt="Profile photo">
                            @else
                                {{ strtoupper(substr((string) $respondent->name, 0, 1)) }}
                            @endif
                        </div>
                    </td>
                    <td class="identity-col">
                        <div class="person-name">{{ $respondent->name ?: '-' }}</div>
                        <div class="person-title">{{ strtoupper((string) ($respondent->profile_title ?: 'Executive')) }}</div>

                        <div class="mini-line">
                            <span class="mini-label">Email</span>
                            {{ $respondent->profile_email ?: '-' }}
                        </div>
                        <div class="mini-line">
                            <span class="mini-label">Phone</span>
                            {{ $respondent->phone ?: '-' }}
                        </div>
                    </td>
                    <td class="qr-col">
                        <div class="qr-wrap">
                            @if(!empty($qrDataUri))
                                <img class="qr-img" src="{{ $qrDataUri }}" alt="Verification QR">
                            @else
                                <div class="qr-fallback">QR unavailable</div>
                            @endif
                        </div>
                        <div class="scan-caption">Scan To Verify</div>
                    </td>
                </tr>
            </table>

            <div class="issued">Issued: {{ $generatedAt->format('Y-m-d H:i') }}</div>
        </div>
    </div>
</div>
</body>
</html>
