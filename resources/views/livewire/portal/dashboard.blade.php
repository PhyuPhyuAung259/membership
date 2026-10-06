<div>
    <header class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight">{{ $member->company_name }}</h1>
    </header>

    <section class="panel">
        <header><h2 class="text-[1.0625rem] font-semibold">Membership status</h2></header>
        <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1.5 p-[1.1rem] text-sm">
            <dt class="text-[var(--color-ink-2)]">Standing</dt>
            <dd class="font-medium">
                <span class="mark mark-{{ $member->billingState() }}">{{ $member->billingLabel() }}</span>
                @if ($member->billingState() === 'overdue')
                    <span class="late-days">{{ $member->daysOverdue() }} days late</span>
                @endif
            </dd>
            <dt class="text-[var(--color-ink-2)]">Paid through</dt>
            <dd class="font-medium">{{ $member->paid_through?->format('j M Y') ?? '—' }}</dd>
            @if ($member->status !== 'cancelled')
                <dt class="text-[var(--color-ink-2)]">Next due</dt>
                <dd class="font-medium">{{ \Carbon\Carbon::parse($member->dueOn())->format('j M Y') }}</dd>
            @endif
        </dl>
    </section>

    <div class="grid gap-3 sm:grid-cols-2">
        @if (! $member->about || ! $member->logo_path)
            <section class="panel !mb-0">
                <div class="p-[1.1rem]">
                    <div class="font-medium">Finish your profile</div>
                    <p class="mt-1 text-[.8125rem] text-[var(--color-ink-2)]">
                        Add a logo and an "About" blurb so your public profile is ready to share with customers and agents.
                    </p>
                    <a href="{{ route('portal.profile') }}" class="mt-2 inline-block">Edit profile →</a>
                </div>
            </section>
        @endif

        @if (! $member->registration_document_path)
            <section class="panel !mb-0">
                <div class="p-[1.1rem]">
                    <div class="font-medium">Upload your registration document</div>
                    <p class="mt-1 text-[.8125rem] text-[var(--color-ink-2)]">
                        Keep your business registration document on file so it's ready whenever it's needed.
                    </p>
                    <a href="{{ route('portal.documents') }}" class="mt-2 inline-block">Upload document →</a>
                </div>
            </section>
        @endif
    </div>
</div>
