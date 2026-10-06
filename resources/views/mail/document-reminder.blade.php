<x-mail::message>
# {{ $isMissing ? 'Upload your registration document' : 'Confirm your document is current' }}

Hello {{ $greeting }},

@if ($isMissing)
We don't have a business registration document on file for you yet. Keeping one on file means it's ready whenever it's needed.
@else
It's been a while since your business registration document was last updated. If anything has changed, please upload the current version.
@endif

<x-mail::button :url="$portalUrl">
Go to your documents
</x-mail::button>

If it's still current, no action is needed.

<x-slot:subcopy>
This is about the paperwork on file for your {{ config('membership.org_name') }} membership.
</x-slot:subcopy>
</x-mail::message>
