<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Join {{ config('membership.org_name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
<div class="mx-auto max-w-2xl px-4 pb-16 pt-8 md:px-0">
    <header class="mb-8 border-b border-[var(--color-rule)] pb-4">
        <b class="text-[1.0625rem]">{{ config('membership.org_name') }}</b>
        <div class="text-[.8125rem] text-[var(--color-ink-2)]">Membership registration</div>
    </header>

    {{ $slot }}
</div>
@livewireScripts
</body>
</html>
