<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    <style>
        body{font-family:system-ui,sans-serif;background:#0a1e26;color:#dae7ea;display:grid;place-items:center;min-height:100vh;margin:0}
        .box{text-align:center;max-width:560px;padding:32px}
        a{color:#d6a24c}
        code{background:#14333e;padding:2px 6px;border-radius:4px}
    </style>
</head>
<body>
<div class="box">
    <h1>{{ config('app.name') }}</h1>
    <p>Site önyüzü <strong>Faz 2</strong>'de yazılacak. Şu an yalnızca iskelet ayakta.</p>
    <p>
        <a href="/yonetim">Yönetim paneli</a> ·
        <a href="/yat-sahibi">Yat sahibi paneli</a>
    </p>
</div>
</body>
</html>
