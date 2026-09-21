<x-mail::message>
# {{ $heading }}

{{ $lead }}

@if ($instructions)
<x-mail::panel>
**How to pay**

{!! nl2br(e($instructions)) !!}
</x-mail::panel>
@endif

Already paid? No action needed — records are updated by hand, so a recent
payment may not be reflected yet. Reply to this email with your payment
reference if anything looks wrong.

<x-slot:subcopy>
This is a payment notice for your {{ config('membership.org_name') }} membership.
</x-slot:subcopy>
</x-mail::message>
