<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $member->company_name }} · {{ config('membership.org_name') }} directory</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
<div class="mx-auto max-w-3xl px-4 pb-16 pt-6 md:px-0">
    <header class="mb-8 flex items-center justify-between gap-3 border-b border-[var(--color-rule)] pb-4">
        <a href="/" class="text-[.8125rem] font-medium text-[var(--color-ink-2)] no-underline hover:text-[var(--color-ink)]">
            {{ config('membership.org_name') }} directory
        </a>
    </header>

    <div class="mb-8 flex flex-wrap items-start gap-5">
        @if ($member->logo_path)
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($member->logo_path) }}"
                 alt="{{ $member->company_name }} logo"
                 class="h-20 w-20 flex-none border border-[var(--color-rule)] bg-white object-contain p-1">
        @endif
        <div class="min-w-0">
            <h1 class="text-2xl font-semibold tracking-tight">{{ $member->company_name }}</h1>
            @if ($member->businessType)
                <div class="mt-1 text-[.9375rem] text-[var(--color-ink-2)]">{{ $member->businessType->name }}</div>
            @endif
        </div>
    </div>

    <section class="panel">
        <header><h2 class="text-[1.0625rem] font-semibold">Contact</h2></header>
        <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1.5 p-[1.1rem] text-sm">
            @if ($member->contact_person)
                <dt class="text-[var(--color-ink-2)]">Contact</dt>
                <dd class="font-medium">
                    {{ $member->contact_person }}
                    @if ($member->contact_person_position)
                        <span class="text-[var(--color-ink-2)]">· {{ $member->contact_person_position }}</span>
                    @endif
                </dd>
            @endif
            @if ($member->email)
                <dt class="text-[var(--color-ink-2)]">Email</dt>
                <dd class="font-medium"><a href="mailto:{{ $member->email }}">{{ $member->email }}</a></dd>
            @endif
            @if ($member->phone)
                <dt class="text-[var(--color-ink-2)]">Phone</dt>
                <dd class="font-medium">{{ $member->phone }}</dd>
            @endif
        </dl>
    </section>

    <section class="panel !mb-0">
        <header>
            <h2 class="text-[1.0625rem] font-semibold">Products &amp; services</h2>
            <span class="hint">{{ $member->products->count() }} listed</span>
        </header>
        @if ($member->products->isEmpty())
            <div class="empty"><strong>Nothing listed yet.</strong>Check back later.</div>
        @else
            <div class="grid grid-cols-2 gap-px bg-[var(--color-rule)] sm:grid-cols-3">
                @foreach ($member->products as $product)
                    <div class="bg-[var(--color-card)] p-3">
                        @if ($product->isImage() && $product->fileUrl())
                            <img src="{{ $product->fileUrl() }}" alt="{{ $product->product_name }}"
                                 class="mb-2 aspect-square w-full border border-[var(--color-rule)] object-cover">
                        @elseif ($product->fileUrl())
                            <a href="{{ $product->fileUrl() }}" target="_blank" rel="noopener"
                               class="mb-2 flex aspect-square w-full items-center justify-center border border-[var(--color-rule)] bg-[var(--color-wash)] text-[.8125rem] text-[var(--color-ink-2)] no-underline">
                                View document
                            </a>
                        @endif
                        <div class="text-[.9375rem] font-medium">{{ $product->product_name }}</div>
                        @if ($product->description)
                            <div class="mt-0.5 text-[.8125rem] text-[var(--color-ink-2)]">{{ $product->description }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>
</body>
</html>
