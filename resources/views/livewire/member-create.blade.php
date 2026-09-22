<div>
    @auth
        <header class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-semibold tracking-tight">Add member</h1>
            <a href="{{ route('members') }}" class="btn btn-quiet no-underline">Back to members</a>
        </header>
    @else
        <h1 class="mb-6 text-2xl font-semibold tracking-tight">Membership registration</h1>
    @endauth

    @if ($submitted)
        <div class="notice notice-good max-w-2xl" role="status">
            <strong>Thanks — your registration has been received.</strong>
            <div class="mt-1.5 text-[.8125rem] text-[var(--color-ink-2)]">
                Someone from {{ config('membership.org_name') }} will review it and follow up by email.
            </div>
        </div>
    @else
        <section class="panel max-w-2xl">
            <form wire:submit="save" enctype="multipart/form-data" class="p-[1.1rem]">
                <div class="field">
                    <label for="f-company">Company name</label>
                    <input id="f-company" wire:model="company_name">
                    @error('company_name') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label for="f-business-type">Business type</label>
                    <select id="f-business-type" wire:model="business_type_id">
                        <option value="">—</option>
                        @foreach ($businessTypes as $businessType)
                            <option value="{{ $businessType->id }}">{{ $businessType->name }}</option>
                        @endforeach
                    </select>
                    @error('business_type_id') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label for="f-email">Email</label>
                    <input id="f-email" type="email" wire:model="email">
                    <div class="note">Used for announcements and payment reminders.</div>
                    @error('email') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label for="f-phone">Phone No.</label>
                    <input id="f-phone" wire:model="phone">
                    @error('phone') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div class="grid gap-x-4 sm:grid-cols-2">
                    <div class="field">
                        <label for="f-contact-person">Contact person</label>
                        <input id="f-contact-person" wire:model="contact_person">
                        @error('contact_person') <div class="error">{{ $message }}</div> @enderror
                    </div>
                    <div class="field">
                        <label for="f-contact-phone">Contact person's phone No.</label>
                        <input id="f-contact-phone" wire:model="contact_person_phone">
                        @error('contact_person_phone') <div class="error">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="field">
                    <label for="f-contact-position">Contact person's position</label>
                    <input id="f-contact-position" wire:model="contact_person_position">
                    @error('contact_person_position') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div class="grid gap-x-4 sm:grid-cols-2">
                    <div class="field">
                        <label for="f-type">Member type</label>
                        <select id="f-type" wire:model.live="member_type_id">
                            <option value="">—</option>
                            @foreach ($memberTypes as $memberType)
                                <option value="{{ $memberType->id }}">{{ $memberType->name }}</option>
                            @endforeach
                        </select>
                        @error('member_type_id') <div class="error">{{ $message }}</div> @enderror
                    </div>
                    <div class="field">
                        <label for="f-fee">Member fee</label>
                        <input id="f-fee" value="{{ $selectedFee !== null ? config('membership.currency_symbol') . number_format((float) $selectedFee, 2) : '—' }}" readonly>
                        <div class="note">Set by the member type. Choose a type to see it.</div>
                    </div>
                </div>

                <div class="field">
                    <label for="f-join">Join date</label>
                    <input id="f-join" type="date" wire:model="join_date">
                    <div class="note">The first unpaid period starts here.</div>
                    @error('join_date') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label for="f-address">Address</label>
                    <textarea id="f-address" rows="2" wire:model="address"></textarea>
                    @error('address') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label for="f-about">About the company</label>
                    <textarea id="f-about" rows="6" wire:model.live.debounce.400ms="about"></textarea>
                    <div class="note @if ($about !== '' && ($aboutWordCount < 100 || $aboutWordCount > 200)) text-[var(--color-stamp)] @endif">
                        {{ $aboutWordCount }} words — between 100 and 200, if filled in.
                    </div>
                    @error('about') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label for="f-logo">Company logo</label>
                    <input id="f-logo" type="file" wire:model="logo" accept="image/*">
                    <div class="note">JPG, PNG, WebP or SVG, up to 2&nbsp;MB.</div>
                    @error('logo') <div class="error">{{ $message }}</div> @enderror
                    <div wire:loading wire:target="logo" class="note">Uploading…</div>
                    @if ($logo)
                        <img src="{{ $logo->temporaryUrl() }}" alt="Logo preview"
                             class="mt-2 h-20 w-20 border border-[var(--color-rule)] bg-white object-contain p-1">
                    @endif
                </div>

                <div class="field">
                    <label for="f-registration">Company / business registration</label>
                    <input id="f-registration" type="file" wire:model="registration_document" accept=".pdf,.jpg,.jpeg,.png">
                    <div class="note">PDF, JPG or PNG, up to 5&nbsp;MB. Kept private — staff only.</div>
                    @error('registration_document') <div class="error">{{ $message }}</div> @enderror
                    <div wire:loading wire:target="registration_document" class="note">Uploading…</div>
                    @if ($registration_document)
                        <div class="note">Selected: {{ $registration_document->getClientOriginalName() }}</div>
                    @endif
                </div>

                <div class="field">
                    <label for="f-notes">Notes</label>
                    <textarea id="f-notes" rows="3" wire:model="notes"></textarea>
                </div>

                <div class="field">
                    <label class="flex items-start gap-2 text-sm font-normal">
                        <input type="checkbox" wire:model="marketing_opt_in" class="mt-1 w-auto">
                        <span>Send announcements about events and activities. Payment reminders are sent either way.</span>
                    </label>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="submit" class="btn" wire:loading.attr="disabled" wire:target="save">
                        {{ auth()->check() ? 'Add member' : 'Submit registration' }}
                    </button>
                    @auth
                        <a href="{{ route('members') }}" class="btn btn-quiet no-underline">Cancel</a>
                    @endauth
                </div>
            </form>
        </section>
    @endif
</div>
