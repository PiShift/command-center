<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="margin:0;padding:0;background:#faf9f5;font-family:Inter,sans-serif">
    <div style="max-width:480px;margin:40px auto;background:#fff;border:1px solid #e5e4df;border-radius:14px;overflow:hidden">
        <div style="background:#D97757;padding:24px 32px">
            <p style="margin:0;font-size:18px;font-weight:600;color:#fff">Command Center</p>
        </div>
        <div style="padding:32px">
            <p style="margin:0 0 8px;font-size:16px;font-weight:600;color:#141413">Hi {{ $userName }},</p>
            <p style="margin:0 0 24px;font-size:14px;color:#5c5c5a;line-height:1.6">
                Use the code below to complete your login. This code expires in 10 minutes.
            </p>
            <div style="background:#faf9f5;border:1px solid #e5e4df;border-radius:10px;padding:20px;text-align:center;margin-bottom:24px">
                <p style="margin:0;font-size:36px;font-weight:700;letter-spacing:0.2em;color:#141413">{{ $code }}</p>
            </div>
            <p style="margin:0;font-size:12px;color:#8c8c8a;line-height:1.5">
                If you did not try to sign in, you can safely ignore this email. Your account is secure.
            </p>
        </div>
    </div>
</body>
</html>
