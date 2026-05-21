<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('subject', 'PiShift Notification')</title>
    <style>
        body { margin: 0; padding: 0; background: #faf9f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #141413; }
        .wrapper { max-width: 580px; margin: 40px auto; background: #fff; border-radius: 8px; overflow: hidden; border: 1px solid #e5e4df; border-top: 3px solid #1a1a1a; }
        .header { background: #141413; padding: 24px 32px; }
        .header-logo { color: #fff; font-size: 18px; font-weight: 700; letter-spacing: -0.3px; text-decoration: none; }
        .header-logo span { color: #D97757; }
        .body { padding: 32px; }
        .body h1 { font-size: 20px; font-weight: 700; color: #141413; margin: 0 0 8px; }
        .body p { font-size: 15px; color: #5c5c5a; line-height: 1.6; margin: 0 0 16px; }
        .button { display: inline-block; background: #D97757; color: #fff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-size: 14px; font-weight: 600; margin: 8px 0 16px; }
        .meta { background: #F5F4EF; border-radius: 6px; padding: 16px; margin: 16px 0; font-size: 13px; color: #5c5c5a; }
        .meta strong { color: #141413; }
        .divider { border: none; border-top: 1px solid #e5e4df; margin: 24px 0; }
        .footer { padding: 16px 32px 24px; text-align: center; font-size: 12px; color: #8c8c8a; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <a href="{{ config('app.url') }}">
                <img src="{{ config('app.url') }}/images/logo-w.png" alt="PiShift" style="height:32px;width:auto;display:block;">
            </a>
        </div>
        <div class="body">
            @yield('content')
        </div>
        <div class="footer">
            <p>You are receiving this because you are a member of {{ config('app.name') }}.</p>
            <p>© {{ date('Y') }} PiShift. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
