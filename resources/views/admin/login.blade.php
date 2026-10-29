<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Administrator portal · UECFI</title>
    <link rel="stylesheet" href="{{ asset('css/admin-login.css') }}">
</head>

<body class="login-body">
    <main class="login-panel"><a href="/"><img src="{{ $site['logo_url'] }}" width="110" height="110"
                alt="Official UECFI logo"></a>
        <p class="eyebrow">UECFI · Administrator portal</p>
        <h1>Welcome back.</h1>
        <p>Sign in to care for your community's stories.</p>
        @if ($errors->any())
            <div class="errors" role="alert">{{ $errors->first() }}</div>
        @endif
        <form action="/admin/login" method="post">@csrf<label>Email<input type="email" name="email"
                    value="{{ old('email') }}" required autocomplete="username" autofocus></label><label>Password<input
                    type="password" name="password" required autocomplete="current-password"></label><label
                class="check"><input type="checkbox" name="remember" value="1"> Remember me</label><button
                class="primary">Sign in →</button></form><a class="back-link" href="/">← Back to website</a>
    </main>
</body>

</html>
