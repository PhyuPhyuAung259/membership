<div>
    @include('partials.flash')
    <header class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold tracking-tight">Members</h1>
        <a href="{{ route('members.create') }}" class="btn no-underline">Add member</a>
    </header>

    <div class="mb-4 flex flex-wrap items-center gap-2">
        <div class="max-w-sm flex-1 basis-56">
            <label class="sr-only" for="search">Search members</label>
            <input id="search" type="search" wire:model.live.debounce.300ms="search"
                   placeholder="Search company name, email or phone"
                   class="w-full border border-[var(--color-rule)] bg-white px-2.5 py-2 text-[.9375rem]">
        </div>
        @foreach (['all' => 'Everyone', 'pending' => 'Pending review', 'overdue' => 'Overdue', 'due_soon' => 'Due soon', 'current' => 'Paid up', 'lapsed' => 'Lapsed', 'cancelled' => 'Cancelled'] as $key => $label)
            <button wire:click="setFilter('{{ $key }}')" class="chip" aria-pressed="{{ $filter === $key ? 'true' : 'false' }}">{{ $label }}</button>
        @endforeach
    </div>

    <section class="panel">
        <header>
            <h2 class="text-[1.0625rem] font-semibold">{{ $members->total() }} {{ Str::plural('member', $members->total()) }}</h2>
        </header>
        <div class="overflow-x-auto">
            <table class="ledger">
                <thead>
                    <tr><th>Member</th><th>Type</th><th>Standing</th><th>Paid through</th><th class="num">Monthly</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($members as $member)
                        <tr>
                            <td>
                                <button wire:click="view({{ $member->id }})"
                                        class="font-medium underline decoration-[var(--color-rule)] hover:decoration-[var(--color-ink)]">{{ $member->company_name }}</button>
                                <div class="text-[.8125rem] text-[var(--color-ink-2)]">
                                    {{ $member->email }}@if ($member->unsubscribed_at) · no announcements @endif
                                </div>
                            </td>
                            <td>{{ $member->memberType?->name ?? '—' }}</td>
                            <td>
                                <span class="mark mark-{{ $member->billingState() }}">{{ $member->billingLabel() }}</span>
                                @if ($member->billingState() === 'overdue')
                                    <span class="late-days">{{ $member->daysOverdue() }}d</span>
                                @endif
                            </td>
                            <td>{{ $member->paid_through?->format('j M Y') ?? '—' }}</td>
                            <td class="num">{{ config('membership.currency_symbol') }}{{ number_format($member->effectiveMonthlyFee(), 2) }}</td>
                            <td class="whitespace-nowrap">
                                @if ($member->status === 'pending')
                                    <button wire:click="activate({{ $member->id }})" class="btn btn-quiet btn-sm">Activate</button>
                                @else
                                    <button wire:click="startPayment({{ $member->id }})" class="btn btn-quiet btn-sm">Record payment</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="empty"><strong>No members match.</strong>{{ $search ? 'Try a different search.' : 'Add your first member to get started.' }}</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div>{{ $members->links() }}</div>

    {{-- ---------------------------------------------------------- payment --}}
    @if ($payingId && $periodPreview)
        <div class="scrim" wire:click.self="closeAll">
            <div class="sheet sheet-narrow" role="dialog" aria-modal="true" aria-label="Record payment">
                <header>
                    <h2 class="text-[1.0625rem] font-semibold">Record payment</h2>
                    <button wire:click="closeAll" class="btn btn-quiet btn-sm">Close</button>
                </header>
                <form wire:submit="savePayment" class="p-[1.1rem]">
                    <div class="notice">
                        This payment covers
                        <strong>{{ \Carbon\Carbon::parse($periodPreview['period_start'])->format('j M Y') }}
                        – {{ \Carbon\Carbon::parse($periodPreview['period_end'])->format('j M Y') }}</strong>.
                        <div class="mt-1.5 text-[.8125rem] text-[var(--color-ink-2)]">
                            Coverage continues from where it ended, so no time is lost even if the payment is late.
                        </div>
                    </div>

                    <div class="grid gap-x-4 sm:grid-cols-2">
                        <div class="field">
                            <label for="months">Months paid for</label>
                            <select id="months" wire:model.live="months">
                                @foreach ([1, 2, 3, 6, 12] as $n)
                                    <option value="{{ $n }}">{{ $n }} {{ Str::plural('month', $n) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label for="amount">Amount received</label>
                            <input id="amount" type="number" step="0.01" min="0" wire:model="amount">
                            @error('amount') <div class="error">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="grid gap-x-4 sm:grid-cols-2">
                        <div class="field">
                            <label for="paidOn">Date received</label>
                            <input id="paidOn" type="date" wire:model="paidOn">
                            <div class="note">The date the money arrived, not today's date.</div>
                            @error('paidOn') <div class="error">{{ $message }}</div> @enderror
                        </div>
                        <div class="field">
                            <label for="method">Method</label>
                            <select id="method" wire:model="method">
                                @foreach (\App\Models\Payment::METHODS as $m)
                                    <option value="{{ $m }}">{{ ucfirst(str_replace('_', ' ', $m)) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="field">
                        <label for="reference">Reference</label>
                        <input id="reference" wire:model="reference" placeholder="Transfer reference or receipt number">
                    </div>

                    <div class="field">
                        <label for="note">Note</label>
                        <input id="note" wire:model="note" placeholder="Optional">
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button type="submit" class="btn" wire:loading.attr="disabled">Save payment</button>
                        <button type="button" wire:click="closeAll" class="btn btn-quiet">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- ------------------------------------------------------ member form --}}
    @if ($form !== [])
        <div class="scrim" wire:click.self="closeAll">
            <div class="sheet sheet-narrow" role="dialog" aria-modal="true">
                <header>
                    <h2 class="text-[1.0625rem] font-semibold">Edit member</h2>
                    <button wire:click="closeAll" class="btn btn-quiet btn-sm">Close</button>
                </header>
                <form wire:submit="saveMember" class="p-[1.1rem]">
                    <div class="field">
                        <label for="f-company">Company name</label>
                        <input id="f-company" wire:model="form.company_name">
                        @error('form.company_name') <div class="error">{{ $message }}</div> @enderror
                    </div>
                    <div class="grid gap-x-4 sm:grid-cols-2">
                        <div class="field">
                            <label for="f-business-type">Business type</label>
                            <select id="f-business-type" wire:model="form.business_type_id">
                                <option value="">—</option>
                                @foreach ($businessTypes as $businessType)
                                    <option value="{{ $businessType->id }}">{{ $businessType->name }}</option>
                                @endforeach
                            </select>
                            @error('form.business_type_id') <div class="error">{{ $message }}</div> @enderror
                        </div>
                        <div class="field">
                            <label for="f-type">Member type</label>
                            <select id="f-type" wire:model.live="form.member_type_id">
                                <option value="">—</option>
                                @foreach ($memberTypes as $memberType)
                                    <option value="{{ $memberType->id }}">{{ $memberType->name }}</option>
                                @endforeach
                            </select>
                            @error('form.member_type_id') <div class="error">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="field">
                        <label for="f-email">Email</label>
                        <input id="f-email" type="email" wire:model="form.email">
                        <div class="note">Used for announcements and payment reminders.</div>
                        @error('form.email') <div class="error">{{ $message }}</div> @enderror
                    </div>
                    <div class="field">
                        <label for="f-phone">Phone</label>
                        <input id="f-phone" wire:model="form.phone">
                    </div>
                    <div class="grid gap-x-4 sm:grid-cols-2">
                        <div class="field">
                            <label for="f-contact-person">Contact person</label>
                            <input id="f-contact-person" wire:model="form.contact_person">
                            @error('form.contact_person') <div class="error">{{ $message }}</div> @enderror
                        </div>
                        <div class="field">
                            <label for="f-contact-phone">Contact person's phone No.</label>
                            <input id="f-contact-phone" wire:model="form.contact_person_phone">
                            @error('form.contact_person_phone') <div class="error">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="field">
                        <label for="f-contact-position">Contact person's position</label>
                        <input id="f-contact-position" wire:model="form.contact_person_position">
                        @error('form.contact_person_position') <div class="error">{{ $message }}</div> @enderror
                    </div>
                    <div class="grid gap-x-4 sm:grid-cols-2">
                        <div class="field">
                            <label for="f-fee">Monthly fee override</label>
                            <input id="f-fee" type="number" step="0.01" min="0" wire:model="form.monthly_fee"
                                   placeholder="{{ $memberTypes->firstWhere('id', (int) ($form['member_type_id'] ?: 0))?->monthly_fee ?? '0.00' }}">
                            <div class="note">Leave blank to use the member type's standard fee.</div>
                            @error('form.monthly_fee') <div class="error">{{ $message }}</div> @enderror
                        </div>
                        <div class="field">
                            <label for="f-join">Join date</label>
                            <input id="f-join" type="date" wire:model="form.join_date">
                            <div class="note">The first unpaid period starts here.</div>
                            @error('form.join_date') <div class="error">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="field">
                        <label for="f-address">Address</label>
                        <textarea id="f-address" rows="2" wire:model="form.address"></textarea>
                        @error('form.address') <div class="error">{{ $message }}</div> @enderror
                    </div>
                    <div class="field">
                        @php $aboutWords = str_word_count(strip_tags($form['about'] ?? '')); @endphp
                        <label for="f-about">About the company</label>
                        <textarea id="f-about" rows="6" wire:model.live.debounce.400ms="form.about"></textarea>
                        <div class="note @if (($form['about'] ?? '') !== '' && ($aboutWords < 100 || $aboutWords > 200)) text-[var(--color-stamp)] @endif">
                            {{ $aboutWords }} words — between 100 and 200, if filled in.
                        </div>
                        @error('form.about') <div class="error">{{ $message }}</div> @enderror
                    </div>
                    <div class="field">
                        <label for="f-notes">Notes</label>
                        <textarea id="f-notes" rows="3" wire:model="form.notes"></textarea>
                    </div>
                    <div class="field">
                        <label class="flex items-start gap-2 text-sm font-normal">
                            <input type="checkbox" wire:model="form.marketing_opt_in" class="mt-1 w-auto">
                            <span>Send announcements about events and activities. Payment reminders are sent either way.</span>
                        </label>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="submit" class="btn">Save changes</button>
                        <button type="button" wire:click="closeAll" class="btn btn-quiet">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- ----------------------------------------------------------- detail --}}
    @if ($detail)
        <div class="scrim" wire:click.self="closeAll">
            <div class="sheet" role="dialog" aria-modal="true" aria-label="{{ $detail->company_name }}">
                <header>
                    <div>
                        <h2 class="text-[1.0625rem] font-semibold">{{ $detail->company_name }}</h2>
                        <div class="text-[.8125rem] text-[var(--color-ink-2)]">
                            {{ $detail->email }}@if ($detail->phone) · {{ $detail->phone }} @endif
                        </div>
                    </div>
                    <button wire:click="closeAll" class="btn btn-quiet btn-sm">Close</button>
                </header>

                <div class="p-[1.1rem]">
                    @if ($detail->logo_path)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($detail->logo_path) }}"
                             alt="{{ $detail->company_name }} logo"
                             class="mb-4 h-16 w-16 border border-[var(--color-rule)] bg-white object-contain p-1">
                    @endif
                    <dl class="mb-6 grid grid-cols-[auto_1fr] gap-x-4 gap-y-1.5 text-sm">
                        <dt class="text-[var(--color-ink-2)]">Standing</dt>
                        <dd class="font-medium">
                            <span class="mark mark-{{ $detail->billingState() }}">{{ $detail->billingLabel() }}</span>
                            @if ($detail->billingState() === 'overdue')
                                <span class="late-days">{{ $detail->daysOverdue() }} days late</span>
                            @endif
                        </dd>
                        @if ($detail->status !== 'pending')
                            <dt class="text-[var(--color-ink-2)]">Paid through</dt>
                            <dd class="font-medium">{{ $detail->paid_through?->format('j M Y') ?? '—' }}</dd>
                            <dt class="text-[var(--color-ink-2)]">Next due</dt>
                            <dd class="font-medium">{{ \Carbon\Carbon::parse($detail->dueOn())->format('j M Y') }}</dd>
                        @endif
                        <dt class="text-[var(--color-ink-2)]">Business type</dt>
                        <dd class="font-medium">{{ $detail->businessType?->name ?? '—' }}</dd>
                        <dt class="text-[var(--color-ink-2)]">Member type</dt>
                        <dd class="font-medium">{{ $detail->memberType?->name ?? '—' }}</dd>
                        <dt class="text-[var(--color-ink-2)]">Monthly fee</dt>
                        <dd class="font-medium">{{ config('membership.currency_symbol') }}{{ number_format($detail->effectiveMonthlyFee(), 2) }}</dd>
                        <dt class="text-[var(--color-ink-2)]">Joined</dt>
                        <dd class="font-medium">{{ $detail->join_date->format('j M Y') }}</dd>
                        @if ($detail->contact_person)
                            <dt class="text-[var(--color-ink-2)]">Contact</dt>
                            <dd class="font-medium">
                                {{ $detail->contact_person }}
                                @if ($detail->contact_person_phone) · {{ $detail->contact_person_phone }} @endif
                                @if ($detail->contact_person_position) · {{ $detail->contact_person_position }} @endif
                            </dd>
                        @endif
                        @if ($detail->address)
                            <dt class="text-[var(--color-ink-2)]">Address</dt>
                            <dd class="font-medium whitespace-pre-line">{{ $detail->address }}</dd>
                        @endif
                        <dt class="text-[var(--color-ink-2)]">Registration document</dt>
                        <dd class="font-medium">
                            @if ($detail->registration_document_path)
                                <a href="{{ route('members.registration', $detail) }}" target="_blank" rel="noopener">View document</a>
                            @else
                                —
                            @endif
                        </dd>
                        <dt class="text-[var(--color-ink-2)]">Announcements</dt>
                        <dd class="font-medium">{{ $detail->unsubscribed_at ? 'Unsubscribed' : ($detail->marketing_opt_in ? 'Subscribed' : 'Opted out') }}</dd>
                        <dt class="text-[var(--color-ink-2)]">Member portal</dt>
                        <dd class="font-medium">
                            @if ($detail->hasPortalAccess())
                                Access set up
                            @else
                                Not set up yet
                            @endif
                            @if (! in_array($detail->status, ['cancelled', 'pending'], true))
                                <button wire:click="createPortalAccess({{ $detail->id }})"
                                        @if ($detail->hasPortalAccess())
                                            wire:confirm="Set a new password? The current one will stop working."
                                        @endif
                                        class="ml-1 text-[.8125rem] underline decoration-[var(--color-rule)] hover:decoration-[var(--color-ink)]">
                                    {{ $detail->hasPortalAccess() ? 'Reset password' : 'Create portal login' }}
                                </button>
                            @endif
                        </dd>
                    </dl>

                    @if ($portalCredentials && $portalCredentials['member_id'] === $detail->id)
                        <div class="notice notice-good !mb-6" x-data="{
                                copied: false,
                                everCopied: false,
                                copy() {
                                    const text = this.$refs.portalCreds.innerText.trim();
                                    // navigator.clipboard needs a secure (HTTPS) context — this
                                    // app runs over plain HTTP locally, so it's unavailable here
                                    // and this falls back to the old execCommand approach, which
                                    // works either way.
                                    if (navigator.clipboard && navigator.clipboard.writeText) {
                                        navigator.clipboard.writeText(text).catch(() => this.legacyCopy(text));
                                    } else {
                                        this.legacyCopy(text);
                                    }
                                    this.copied = true;
                                    this.everCopied = true;
                                    setTimeout(() => this.copied = false, 2000);
                                },
                                legacyCopy(text) {
                                    const ta = document.createElement('textarea');
                                    ta.value = text;
                                    ta.style.position = 'fixed';
                                    ta.style.opacity = '0';
                                    document.body.appendChild(ta);
                                    ta.focus();
                                    ta.select();
                                    document.execCommand('copy');
                                    document.body.removeChild(ta);
                                },
                            }"
                            x-init="
                                const el = $el;
                                const handler = (e) => {
                                    // Checked at the moment the browser actually tries to leave,
                                    // not when this registered — if the modal's since been closed
                                    // (element no longer in the page), there's nothing left to
                                    // warn about, and this unhooks itself. 'everCopied' resolves
                                    // against this component's live reactive data, same as copy()
                                    // referencing 'copied' above, so it always reads the current
                                    // value rather than the value at registration time.
                                    if (! el.isConnected) { window.removeEventListener('beforeunload', handler); return; }
                                    if (! everCopied) { e.preventDefault(); e.returnValue = ''; }
                                };
                                window.addEventListener('beforeunload', handler);
                            ">
                            <div>Portal login ready — copy this and send it to them yourself. The password is shown only this once.</div>
                            <div x-ref="portalCreds" class="mt-2 whitespace-pre-line rounded border border-[var(--color-rule)] bg-[var(--color-card)] p-2 font-mono text-[.8125rem]">Login: {{ route('portal.login') }}
Email: {{ $portalCredentials['email'] }}
Password: {{ $portalCredentials['password'] }}</div>
                            <button type="button" class="btn btn-quiet btn-sm mt-2" @click="copy()">
                                <span x-show="!copied">Copy</span>
                                <span x-show="copied">Copied</span>
                            </button>
                        </div>
                    @endif

                    @if ($detail->about)
                        <section class="panel !mb-6">
                            <header><h3 class="text-[.9375rem] font-semibold">About</h3></header>
                            <div class="p-[1.1rem] text-sm whitespace-pre-line">{{ $detail->about }}</div>
                        </section>
                    @endif

                    <section class="panel !mb-6">
                        <header><h3 class="text-[.9375rem] font-semibold">Payments</h3>
                            <span class="hint">{{ $detail->payments->count() }} recorded</span></header>
                        <div class="overflow-x-auto">
                            <table class="ledger">
                                <thead><tr><th>Received</th><th>Covers</th><th>Method</th><th class="num">Amount</th><th></th></tr></thead>
                                <tbody>
                                    @forelse ($detail->payments->sortByDesc('period_end') as $payment)
                                        <tr>
                                            <td>{{ $payment->paid_on->format('j M Y') }}</td>
                                            <td>
                                                {{ $payment->period_start->format('j M') }} – {{ $payment->period_end->format('j M Y') }}
                                                @if ($payment->reference)
                                                    <div class="text-[.8125rem] text-[var(--color-ink-2)]">{{ $payment->reference }}</div>
                                                @endif
                                            </td>
                                            <td>{{ $payment->methodLabel() }}</td>
                                            <td class="num">{{ config('membership.currency_symbol') }}{{ number_format($payment->amount, 2) }}</td>
                                            <td>
                                                @can('delete-payments')
                                                    <button wire:click="deletePayment({{ $payment->id }})"
                                                            wire:confirm="Remove this payment? The member's paid-through date will move back."
                                                            class="text-[.8125rem] text-[var(--color-ink-2)] underline hover:text-[var(--color-ink)]">Remove</button>
                                                @endcan
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5"><div class="empty"><strong>No payments yet.</strong></div></td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="panel !mb-6">
                        <header>
                            <h3 class="text-[.9375rem] font-semibold">Products &amp; brochures</h3>
                            <span class="hint">{{ $detail->products->count() }} listed</span>
                        </header>
                        <div class="overflow-x-auto">
                            <table class="ledger">
                                <thead><tr><th>Name</th><th>File</th><th>Description</th><th></th></tr></thead>
                                <tbody>
                                    @forelse ($detail->products as $product)
                                        <tr>
                                            <td class="font-medium">{{ $product->product_name }}</td>
                                            <td>
                                                @if ($product->fileUrl())
                                                    <a href="{{ $product->fileUrl() }}" target="_blank" rel="noopener">{{ $product->isImage() ? 'Image' : 'Document' }}</a>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td class="text-[.8125rem] text-[var(--color-ink-2)]">{{ Str::limit($product->description, 60) }}</td>
                                            <td class="whitespace-nowrap">
                                                <button wire:click="startEditProduct({{ $product->id }})"
                                                        class="text-[.8125rem] underline decoration-[var(--color-rule)] hover:decoration-[var(--color-ink)]">Edit</button>
                                                <button wire:click="deleteProduct({{ $product->id }})"
                                                        wire:confirm="Remove this product?"
                                                        class="text-[.8125rem] text-[var(--color-ink-2)] underline hover:text-[var(--color-ink)]">Remove</button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4"><div class="empty"><strong>Nothing listed yet.</strong></div></td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="p-[1.1rem] pt-0">
                            @if (! $productFormOpen)
                                <button wire:click="startAddProduct" class="btn btn-quiet btn-sm">Add product</button>
                            @else
                                <form wire:submit="saveProduct" class="mt-3 border-t border-[var(--color-rule)] pt-3">
                                    <div class="field">
                                        <label for="p-name">Product name</label>
                                        <input id="p-name" wire:model="productForm.product_name">
                                        @error('productForm.product_name') <div class="error">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="field">
                                        <label for="p-desc">Description</label>
                                        <textarea id="p-desc" rows="2" wire:model="productForm.description"></textarea>
                                        @error('productForm.description') <div class="error">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="field">
                                        <label for="p-file">Image or PDF</label>
                                        @if ($existingProductFilePath && ! $productFile)
                                            <div class="mb-1.5 text-[.8125rem] text-[var(--color-ink-2)]">
                                                Current file kept. <button type="button" wire:click="removeProductFile" class="underline">Remove it</button>
                                            </div>
                                        @endif
                                        <input id="p-file" type="file" wire:model="productFile" accept=".jpg,.jpeg,.png,.webp,.gif,.pdf">
                                        <div wire:loading wire:target="productFile" class="note">Uploading…</div>
                                        @error('productFile') <div class="error">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <button type="submit" class="btn btn-sm" wire:loading.attr="disabled">Save product</button>
                                        <button type="button" wire:click="closeProductForm" class="btn btn-quiet btn-sm">Cancel</button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    </section>

                    <section class="panel !mb-0">
                        <header><h3 class="text-[.9375rem] font-semibold">Email history</h3></header>
                        <div class="overflow-x-auto">
                            <table class="ledger">
                                <thead><tr><th>Sent</th><th>Subject</th><th>Kind</th><th>Status</th></tr></thead>
                                <tbody>
                                    @forelse ($detail->emails as $email)
                                        <tr>
                                            <td>{{ ($email->sent_at ?? $email->created_at)->format('j M Y') }}</td>
                                            <td>{{ $email->subject }}</td>
                                            <td>{{ $email->kindLabel() }}</td>
                                            <td>
                                                {{ $email->status }}
                                                @if ($email->error)
                                                    <div class="text-[.8125rem] text-[var(--color-ink-2)]">{{ $email->error }}</div>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4"><div class="empty"><strong>No email sent to this member yet.</strong></div></td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>

                <div class="flex flex-wrap gap-2 border-t border-[var(--color-rule)] bg-[var(--color-wash)] p-[1.1rem]">
                    @if ($detail->status === 'pending')
                        <button wire:click="activate({{ $detail->id }})" class="btn">Activate member</button>
                    @else
                        <button wire:click="startPayment({{ $detail->id }})" class="btn">Record payment</button>
                    @endif
                    <button wire:click="startEdit({{ $detail->id }})" class="btn btn-quiet">Edit details</button>
                    @if (! in_array($detail->status, ['cancelled', 'pending'], true))
                        <a href="{{ route('directory.show', $detail) }}" target="_blank" rel="noopener" class="btn btn-quiet">View public profile</a>
                    @endif
                    @can('manage-membership-status')
                        @if ($detail->status === 'cancelled')
                            <button wire:click="reinstate({{ $detail->id }})" class="btn btn-quiet">Reinstate</button>
                        @elseif ($detail->status !== 'pending')
                            <button wire:click="cancelMembership({{ $detail->id }})"
                                    wire:confirm="Cancel this membership? Payment history is kept and it can be reinstated later."
                                    class="btn btn-danger">Cancel membership</button>
                        @endif
                    @endcan
                </div>
            </div>
        </div>
    @endif
</div>
