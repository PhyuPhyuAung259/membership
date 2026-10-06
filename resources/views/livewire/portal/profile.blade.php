<div>
    @include('partials.flash')
    <header class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight">Profile</h1>
        <p class="mt-1 text-[.8125rem] text-[var(--color-ink-2)]">
            This is what appears on your public directory page. To change your login email or membership tier, contact {{ config('membership.org_name') }} staff.
        </p>
    </header>

    <section class="panel !mb-0">
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
