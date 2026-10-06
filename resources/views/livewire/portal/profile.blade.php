<div>
    @include('partials.flash')
    <header class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight">Profile</h1>
        <p class="mt-1 text-[.8125rem] text-[var(--color-ink-2)]">
            Everything on file for your membership. The fields below the overview appear on your public directory page and are yours to edit; your login email, tier, fee and status are set by {{ config('membership.org_name') }} staff.
        </p>
    </header>

    <section class="panel">
        <header><h2 class="text-[1.0625rem] font-semibold">Account overview</h2></header>
        <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1.5 p-[1.1rem] text-sm">
            <dt class="text-[var(--color-ink-2)]">Company name</dt>
            <dd class="font-medium">{{ $member->company_name }}</dd>
            <dt class="text-[var(--color-ink-2)]">Login email</dt>
            <dd class="font-medium">{{ $member->email }}</dd>
            @if ($member->phone)
                <dt class="text-[var(--color-ink-2)]">Phone</dt>
                <dd class="font-medium">{{ $member->phone }}</dd>
            @endif
            <dt class="text-[var(--color-ink-2)]">Business type</dt>
            <dd class="font-medium">{{ $member->businessType?->name ?? '—' }}</dd>
            <dt class="text-[var(--color-ink-2)]">Member type</dt>
            <dd class="font-medium">{{ $member->memberType?->name ?? '—' }}</dd>
            <dt class="text-[var(--color-ink-2)]">Monthly fee</dt>
            <dd class="font-medium">{{ config('membership.currency_symbol') }}{{ number_format($member->effectiveMonthlyFee(), 2) }}</dd>
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
            <dt class="text-[var(--color-ink-2)]">Joined</dt>
            <dd class="font-medium">{{ $member->join_date->format('j M Y') }}</dd>
            @if ($member->contact_person)
                <dt class="text-[var(--color-ink-2)]">Contact</dt>
                <dd class="font-medium">
                    {{ $member->contact_person }}
                    @if ($member->contact_person_phone) · {{ $member->contact_person_phone }} @endif
                    @if ($member->contact_person_position) · {{ $member->contact_person_position }} @endif
                </dd>
            @endif
            @if ($member->address)
                <dt class="text-[var(--color-ink-2)]">Address</dt>
                <dd class="font-medium whitespace-pre-line">{{ $member->address }}</dd>
            @endif
            <dt class="text-[var(--color-ink-2)]">Registration document</dt>
            <dd class="font-medium">
                @if ($member->registration_document_path)
                    <a href="{{ route('portal.documents.registration') }}" target="_blank" rel="noopener">View document</a>
                    <span class="text-[var(--color-ink-2)]">— uploaded {{ $member->registration_document_updated_at?->format('j M Y') ?? 'a while ago' }}</span>
                @else
                    — <a href="{{ route('portal.documents') }}">Upload one</a>
                @endif
            </dd>
            <dt class="text-[var(--color-ink-2)]">Announcements</dt>
            <dd class="font-medium">{{ $member->unsubscribed_at ? 'Unsubscribed' : ($member->marketing_opt_in ? 'Subscribed' : 'Opted out') }}</dd>
            <dt class="text-[var(--color-ink-2)]">Products listed</dt>
            <dd class="font-medium">{{ $member->products->count() }} — <a href="{{ route('portal.products') }}">Manage</a></dd>
        </dl>

        @if ($member->about)
            <div class="border-t border-[var(--color-rule)] p-[1.1rem]">
                <div class="mb-1 text-[.8125rem] font-medium text-[var(--color-ink-2)]">About</div>
                <div class="text-sm whitespace-pre-line">{{ $member->about }}</div>
            </div>
        @endif
    </section>

    <section class="panel !mb-0">
        <header><h2 class="text-[1.0625rem] font-semibold">Edit profile</h2></header>
        <form wire:submit="save" class="p-[1.1rem]">
            <div class="field">
                @if ($member->logo_path)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($member->logo_path) }}"
                         alt="Current logo" class="mb-2 h-16 w-16 border border-[var(--color-rule)] bg-white object-contain p-1">
                @endif
                <label for="p-logo">Logo</label>
                <input id="p-logo" type="file" wire:model="logo" accept=".jpg,.jpeg,.png,.webp,.gif">
                <div wire:loading wire:target="logo" class="note">Uploading…</div>
                @error('logo') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="p-company">Company name</label>
                <input id="p-company" wire:model="form.company_name">
                @error('form.company_name') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="p-business-type">Business type</label>
                <select id="p-business-type" wire:model="form.business_type_id">
                    <option value="">—</option>
                    @foreach ($businessTypes as $businessType)
                        <option value="{{ $businessType->id }}">{{ $businessType->name }}</option>
                    @endforeach
                </select>
                @error('form.business_type_id') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="p-phone">Phone</label>
                <input id="p-phone" wire:model="form.phone">
            </div>

            <div class="grid gap-x-4 sm:grid-cols-2">
                <div class="field">
                    <label for="p-contact">Contact person</label>
                    <input id="p-contact" wire:model="form.contact_person">
                </div>
                <div class="field">
                    <label for="p-contact-phone">Contact person's phone</label>
                    <input id="p-contact-phone" wire:model="form.contact_person_phone">
                </div>
            </div>

            <div class="field">
                <label for="p-contact-position">Contact person's position</label>
                <input id="p-contact-position" wire:model="form.contact_person_position">
            </div>

            <div class="field">
                <label for="p-address">Address</label>
                <textarea id="p-address" rows="2" wire:model="form.address"></textarea>
                @error('form.address') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                @php $aboutWords = str_word_count(strip_tags($form['about'] ?? '')); @endphp
                <label for="p-about">About the company</label>
                <textarea id="p-about" rows="6" wire:model.live.debounce.400ms="form.about"></textarea>
                <div class="note @if (($form['about'] ?? '') !== '' && ($aboutWords < 100 || $aboutWords > 200)) text-[var(--color-stamp)] @endif">
                    {{ $aboutWords }} words — between 100 and 200, if filled in. Shown on your public profile.
                </div>
                @error('form.about') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label class="flex items-start gap-2 text-sm font-normal">
                    <input type="checkbox" wire:model="form.marketing_opt_in" class="mt-1 w-auto">
                    <span>Receive announcements about events and activities. Payment reminders are sent either way.</span>
                </label>
            </div>

            <button type="submit" class="btn" wire:loading.attr="disabled">Save changes</button>
        </form>
    </section>
</div>
