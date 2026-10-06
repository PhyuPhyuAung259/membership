<x-mail::message>
# {{ $isReset ? 'Reset your password' : 'Set up your portal access' }}

Hello {{ $greeting }},

@if ($isReset)
We received a request to reset the portal password for **{{ $companyName }}**. Click below to choose a new one.
@else
You can now manage {{ $companyName }}'s own profile, documents and products online. Click below to set a password.
@endif

<x-mail::button :url="$url">
{{ $isReset ? 'Reset password' : 'Set up access' }}
</x-mail::button>

This link expires in {{ $expireMinutes }} minutes. If you didn't request this, no action is needed.

<x-slot:subcopy>
This is about portal access for your {{ config('membership.org_name') }} membership.
</x-slot:subcopy>
</x-mail::message>
