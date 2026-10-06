<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('membership.org_name') }} directory</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
<div class="mx-auto max-w-4xl px-4 pb-16 pt-6 md:px-0">
    <header class="mb-8 flex flex-wrap items-start justify-between gap-3 border-b border-[var(--color-rule)] pb-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">{{ config('membership.org_name') }} directory</h1>
            <p class="mt-1 text-[.9375rem] text-[var(--color-ink-2)]">Browse member companies and their products, or search for one by name.</p>
        </div>
        <a href="{{ route('portal.login') }}" class="whitespace-nowrap text-[.8125rem]">Member login →</a>
    </header>

    <form method="GET" class="mb-6 flex flex-wrap items-end gap-2">
        <div class="max-w-sm flex-1 basis-56">
            <label class="sr-only" for="q">Search</label>
            <input id="q" name="q" type="search" value="{{ $filters['q'] ?? '' }}"
                   placeholder="Search company name"
                   class="w-full border border-[var(--color-rule)] bg-white px-2.5 py-2 text-[.9375rem]">
        </div>
        <div>
            <label class="sr-only" for="business_type">Industry</label>
            <select id="business_type" name="business_type" class="border border-[var(--color-rule)] bg-white px-2.5 py-2 text-[.9375rem]">
                <option value="">All industries</option>
                @foreach ($businessTypes as $businessType)
                    <option value="{{ $businessType->id }}" @selected(($filters['business_type'] ?? '') == $businessType->id)>{{ $businessType->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="sr-only" for="member_type">Member type</label>
            <select id="member_type" name="member_type" class="border border-[var(--color-rule)] bg-white px-2.5 py-2 text-[.9375rem]">
                <option value="">All member types</option>
                @foreach ($memberTypes as $memberType)
                    <option value="{{ $memberType->id }}" @selected(($filters['member_type'] ?? '') == $memberType->id)>{{ $memberType->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-sm">Search</button>
        @if (filled($filters['q'] ?? null) || filled($filters['business_type'] ?? null) || filled($filters['member_type'] ?? null))
            <a href="{{ route('directory.index') }}" class="btn btn-quiet btn-sm no-underline">Clear</a>
        @endif
    </form>

    <div class="mb-4 text-[.8125rem] text-[var(--color-ink-2)]">{{ $members->total() }} {{ Str::plural('member', $members->total()) }}</div>

    @if ($members->isEmpty())
        <div class="empty"><strong>No members match.</strong>Try a different search or filter.</div>
    @else
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach ($members as $member)
                <a href="{{ route('directory.show', $member) }}" class="panel !mb-0 block p-[1.1rem] no-underline hover:bg-[var(--color-wash)]">
                    <div class="flex items-start gap-3">
                        @if ($member->logo_path)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($member->logo_path) }}"
                                 alt="{{ $member->company_name }} logo"
                                 class="h-12 w-12 flex-none border border-[var(--color-rule)] bg-white object-contain p-1">
                        @endif
                        <div class="min-w-0">
                            <div class="font-medium text-[var(--color-ink)]">{{ $member->company_name }}</div>
                            @if ($member->businessType)
                                <div class="text-[.8125rem] text-[var(--color-ink-2)]">{{ $member->businessType->name }}</div>
                            @endif
                            @if ($member->memberType)
                                <div class="text-[.8125rem] text-[var(--color-ink-2)]">{{ $member->memberType->name }}</div>
                            @endif
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif

    <div class="mt-6">{{ $members->links() }}</div>
</div>
</body>
</html>
