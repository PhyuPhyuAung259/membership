<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ config('membership.org_name') }} · Member portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[var(--color-paper)]">
<div class="mx-auto flex min-h-screen max-w-sm flex-col justify-center px-4 py-10">
    <div class="mb-6 text-center">
        <b class="text-[1.0625rem] font-semibold">{{ config('membership.org_name') }}</b>
        <div class="text-[.8125rem] text-[var(--color-ink-2)]">Member portal</div>
    </div>

    <div class="panel !mb-0">
        <div class="p-[1.1rem]">
            {{ $slot }}
        </div>
    </div>
</div>
</body>
</html>
