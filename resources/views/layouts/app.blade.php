<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ config('membership.org_name') }} · Membership</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
<div class="grid min-h-screen md:grid-cols-[13.5rem_minmax(0,1fr)]">

    <nav class="sticky top-0 flex h-auto flex-row items-center gap-3 overflow-x-auto bg-[var(--color-ink)] px-4 py-3 text-[#e3eaf1] md:h-screen md:flex-col md:items-stretch md:gap-7 md:py-6">
        <div class="md:px-5">
            <b class="block text-[.9375rem] font-semibold">{{ config('membership.org_name') }}</b>
            <span class="hidden text-xs text-[#94a8bc] md:block">Membership register</span>
        </div>

        @php
            $user = auth()->user();
            $links = ['dashboard' => 'Dashboard'];
            if ($user->can('manage-members')) $links['members'] = 'Members';
            if ($user->can('manage-business-types')) $links['business-types'] = 'Business types';
            if ($user->can('manage-events')) $links['events'] = 'Events';
            if ($user->can('send-announcements')) $links['announcements'] = 'Email';
            if ($user->can('manage-member-types')) $links['member-types'] = 'Member types';
            if ($user->can('manage-staff')) $links['users'] = 'Staff accounts';
            if ($user->can('manage-roles')) $links['roles'] = 'Roles & permissions';
        @endphp

        <div class="flex flex-row md:flex-col">
            @foreach ($links as $route => $label)
                <a href="{{ route($route) }}"
                   @class([
                       'whitespace-nowrap px-3 py-1.5 text-[.9375rem] no-underline md:px-5 md:py-2',
                       'border-b-[3px] md:border-b-0 md:border-l-[3px]' => true,
                       'border-[var(--color-stamp)] font-medium text-white' => request()->routeIs($route),
                       'border-transparent text-[#b9c9d9] hover:text-white' => ! request()->routeIs($route),
                   ])
                   @if (request()->routeIs($route)) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </div>

        <a href="{{ route('directory.index') }}" target="_blank" rel="noopener"
           class="whitespace-nowrap px-3 py-1.5 text-[.9375rem] text-[#b9c9d9] no-underline hover:text-white md:px-5 md:py-2">
            Public directory ↗
        </a>

        <div class="ml-auto text-xs text-[#94a8bc] md:ml-0 md:mt-auto md:px-5">
            <span class="hidden md:block">{{ auth()->user()?->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="mt-1 bg-transparent text-[#b9c9d9] underline hover:text-white">Sign out</button>
            </form>
        </div>
    </nav>

    <main class="min-w-0 px-4 pb-16 pt-5 md:px-9 md:pt-8">
        {{-- Each full-page component includes resources/views/partials/flash.blade.php
             itself, not here — Livewire's AJAX updates only re-render the
             component's own template, never this layout, so a flash shown
             only here would never appear for an action that stays on the
             same page. --}}
        {{ $slot }}
    </main>
</div>
@livewireScripts
</body>
</html>
