<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>OAuth Consent</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: #f4f7fb;
            color: #0f172a;
            font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
        }

        .card {
            width: min(560px, calc(100vw - 32px));
            background: #fff;
            border: 1px solid #dbe4f0;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        }

        h1 {
            margin: 0 0 8px;
            font-size: 22px;
        }

        p {
            margin: 0;
            color: #334155;
        }

        .actions {
            margin-top: 20px;
            display: flex;
            gap: 12px;
        }

        button {
            appearance: none;
            border: 0;
            border-radius: 8px;
            padding: 10px 16px;
            font-weight: 600;
            cursor: pointer;
        }

        .allow {
            background: #0284c7;
            color: #fff;
        }

        .deny {
            background: #e2e8f0;
            color: #0f172a;
        }
    </style>
</head>
<body>
<div class="card">
    <h1>Consent Required</h1>
    <p>Claude MCP wants to access your Command Center.</p>

    <div class="actions">
        <form method="POST" action="{{ route('oauth.authorize.handle') }}">
            @csrf
            <input type="hidden" name="action" value="allow">
            <button type="submit" class="allow">Allow</button>
        </form>

        <form method="POST" action="{{ route('oauth.authorize.handle') }}">
            @csrf
            <input type="hidden" name="action" value="deny">
            <button type="submit" class="deny">Deny</button>
        </form>
    </div>
</div>
</body>
</html>
