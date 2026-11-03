<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>{{ $subject ?? 'Notification' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: #f4f4f5; font-family: 'Segoe UI', Arial, sans-serif; color: #1a1a2e; }
        .wrapper { max-width: 620px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #006A4E 0%, #008B5E 100%); padding: 36px 40px; text-align: center; }
        .header h1 { color: #ffffff; font-size: 24px; font-weight: 700; letter-spacing: 0.3px; }
        .header p { color: rgba(255,255,255,0.8); font-size: 13px; margin-top: 6px; }
        .body { padding: 40px; }
        .greeting { font-size: 17px; font-weight: 600; color: #1a1a2e; margin-bottom: 16px; }
        .body p { font-size: 15px; line-height: 1.7; color: #444; margin-bottom: 14px; }
        .highlight-box { background: #f0fdf4; border-left: 4px solid #006A4E; border-radius: 6px; padding: 16px 20px; margin: 20px 0; }
        .highlight-box p { margin: 0; color: #065f46; font-weight: 500; }
        .highlight-box.warning { background: #fffbeb; border-color: #f59e0b; }
        .highlight-box.warning p { color: #92400e; }
        .highlight-box.danger { background: #fef2f2; border-color: #ef4444; }
        .highlight-box.danger p { color: #991b1b; }
        .divider { border: none; border-top: 1px solid #f0f0f0; margin: 28px 0; }
        .signature { font-size: 14px; color: #666; line-height: 1.6; }
        .signature strong { color: #1a1a2e; }
        .footer { background: #f9f9fb; padding: 24px 40px; text-align: center; border-top: 1px solid #ebebeb; }
        .footer p { font-size: 12px; color: #999; line-height: 1.6; }
        .footer a { color: #006A4E; text-decoration: none; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 13px; font-weight: 600; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger  { background: #fee2e2; color: #991b1b; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-info    { background: #dbeafe; color: #1e40af; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>{{ config('app.name') }}</h1>
        <p>{{ $subject ?? '' }}</p>
    </div>
    <div class="body">
        @yield('content')
        <hr class="divider">
        <p class="signature">
            Best regards,<br>
            <strong>@yield('team', config('app.name') . ' Team')</strong>
        </p>
    </div>
    <div class="footer">
        <p>
            This email was sent to <strong>{{ $user->email ?? '' }}</strong><br>
            © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.<br>
            <a href="#">Privacy Policy</a>
        </p>
    </div>
</div>
</body>
</html>
