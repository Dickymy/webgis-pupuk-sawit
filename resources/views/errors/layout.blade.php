<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') — SawitGIS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8fafc;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .error-container {
            text-align: center;
            max-width: 420px;
        }
        .error-code {
            font-size: 5rem;
            font-weight: 700;
            color: #94a3b8;
            line-height: 1;
        }
        .error-message {
            font-size: 1.25rem;
            margin-top: 1rem;
            color: #475569;
        }
        .error-detail {
            margin-top: 0.75rem;
            color: #64748b;
            font-size: 0.95rem;
        }
        .error-action {
            margin-top: 2rem;
        }
        .error-action a {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #16a34a;
            color: #fff;
            text-decoration: none;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: background 0.2s;
        }
        .error-action a:hover {
            background: #15803d;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">@yield('code')</div>
        <div class="error-message">@yield('message')</div>
        @hasSection('detail')
            <div class="error-detail">@yield('detail')</div>
        @endif
        <div class="error-action">
            <a href="{{ url('/dashboard') }}">Kembali ke Dashboard</a>
        </div>
    </div>
</body>
</html>
