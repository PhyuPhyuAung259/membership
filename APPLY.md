# Company members — additive schema change

Adds three tables and new columns on `members`. **Nothing is dropped or
recreated.** Your existing migrations stay exactly where they are, and any data
already in the register survives.

Not run — there is no PHP or Postgres in the session that produced it. Syntax
checked under PHP 8.3. Run `php artisan migrate` and then `php artisan test`.

---

## What this touches

| Table | What happens |
|---|---|
| `business_types` | **new** |
| `member_types` | **new** |
| `products` | **new** |
| `members` | columns added, `name` renamed, `member_type` replaced by a foreign key |
| `payments` | untouched |
| `events` | untouched |
| `broadcasts` | untouched |
| `email_log` | untouched |
| the `paid_through` trigger | untouched |

The billing and email flow is left completely alone.

### On members

| Was | Is |
|---|---|
| `name` | `company_name` — renamed, not recreated, so data survives |
| `member_type` (varchar) | `member_type_id` → `member_types` |
| `monthly_fee` (required) | `monthly_fee` **nullable** — null means "use the tier's fee" |
| — | `business_type_id` → `business_types` |
| — | `contact_person`, `contact_person_position` |
| — | `logo_path`, `registration_document_path` |

If your register already has companies in it, whatever was in the old
`member_type` column is lifted into `member_types` and linked automatically.
Those rows come across with a fee of zero, because the old column carried no
price — set the real tier fees afterwards.

---

## Apply it

```bash
# 1. Copy in the four migrations, four models, service, mailable,
#    controller and seeder. Delete nothing.

# 2. Run only the new migrations — your existing ones are already applied
#    and will be skipped.
php artisan migrate

# 3. Tiers and industries (edit the fees in the seeder first)
php artisan db:seed --class=ReferenceDataSeeder

# 4. Storage symlink for logos and product images
php artisan storage:link
```

No `migrate:fresh`, so your admin login and any data you have stay put.

To check it landed:

```bash
php artisan migrate:status
```

The four `2026_09_21_*` rows should show as Ran.

---

## Read the fee through the accessor

This is the one thing that will bite you. `monthly_fee` on a member is now
**null for most companies** — the price lives on the tier.

```php
$member->monthly_fee;            // null for a company on a standard rate
$member->effectiveMonthlyFee();  // the tier's fee, or the override
```

A bare `$member->monthly_fee` silently produces `0.00` in a reminder email or a
payment suggestion. `PaymentRecorder` and `DuesReminder` are already fixed.
**Your Blade views are not** — the dashboard and members list both print a
Monthly column.

Eager-load the tier wherever you list members, or it costs a query per row:

```php
Member::with('memberType')->overdue()->get();
```

---

## Files

Three file fields, and they do **not** all belong on the same disk.

| Field | Disk | Why |
|---|---|---|
| `members.logo_path` | `public` | Meant to be seen in the directory |
| `products.file_path` | `public` | Same |
| `members.registration_document_path` | **`local`** | Company numbers, director names, addresses |

Anything under `storage/app/public` is served straight off the filesystem by
the web server — no authentication, guessable URL. A business registration
document must not sit there. It goes on the `local` disk and is streamed
through `MemberDocumentController`, behind auth.

Add to `routes/web.php`, inside the existing `auth` group:

```php
Route::get('/members/{member}/registration', [App\Http\Controllers\MemberDocumentController::class, 'registration'])
    ->name('members.registration');
```

---

## Still to wire up

Schema, models and billing logic are done. The UI is not — the views still say
`$member->name`, which no longer exists, so the app will break until they are
updated. Hand this to Claude Code:

> The members table now represents companies. Three new tables were added
> (member_types, business_types, products) and members was altered. Update the
> Livewire components and Blade views to match:
>
> 1. Rename `name` → `company_name` everywhere (dashboard, members list,
>    member detail sheet, DemoSeeder, and the test helpers in
>    tests/Feature/*.php).
> 2. Every place that prints a fee must call `$member->effectiveMonthlyFee()`,
>    not `$member->monthly_fee` — the latter is null for companies on a
>    standard tier rate. Eager-load `memberType` on every member listing.
> 3. The member form needs: company name, business type (select from
>    business_types), email, phone, contact person, contact person's position,
>    member type (select from member_types), join date, notes, a monthly fee
>    override that is blank by default with the tier's fee as placeholder, a
>    logo upload, and a business registration upload.
> 4. Logo and product files go to the `public` disk. The registration document
>    goes to the `local` disk and is only ever served through
>    MemberDocumentController — never link to it directly.
> 5. Add a Products section to the member detail sheet: add, rename, reorder
>    and delete products, each with one file (jpg/png/webp/gif/pdf, max 8MB).
>    Use `Product::kindFor($filename)` to set `file_kind`, and reject anything
>    it returns null for.
> 6. Add simple admin screens for member_types and business_types. A tier that
>    is in use cannot be deleted — the database restricts it — so check
>    `$type->inUse()` and disable the button rather than letting it error.
> 7. Update DemoSeeder to create companies with contact people, tiers and
>    industries.
> 8. Fix the tests, then run `php artisan test` until green.
>
> Do not change the design decisions: the paid_through trigger, the email
> dedupe_key constraint, or the explicit month clamping in BillingPeriod.
> Fix bugs, not architecture.

---

## Rolling back

Every step reverses:

```bash
php artisan migrate:rollback --step=4
```

That puts `member_type` back as a varchar with the tier names in it, renames
`company_name` back to `name`, drops the new columns and the three new tables.
Products and their files are lost, since the table goes — nothing else is.

---

## Two things worth a second thought

**Deleting a company deletes its products and payments.** Both cascade. That is
right for products, but it means a mis-clicked delete takes the payment history
with it. The UI offers *Cancel membership* rather than delete for exactly this
reason — keep it that way.

**One contact person per company.** Fine to start. If you later need a billing
contact separate from the general one, that is a `contacts` table hanging off
members, not more columns — and it changes which address the reminders go to.
Worth deciding before you enter 400 companies, not after.
