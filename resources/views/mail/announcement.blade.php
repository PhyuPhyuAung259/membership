<x-mail::message>
@if ($eventImageUrl)
<img src="{{ $eventImageUrl }}" alt="{{ $event->title }}" style="width:100%;max-width:600px;height:auto;border-radius:4px;">

@endif
# {{ $heading }}

@if ($event && ($eventWhen || $event->location))
<x-mail::panel>
@if ($eventWhen)
**When:** {{ $eventWhen }}
@endif
@if ($event->location)
**Where:** {{ $event->location }}
@endif
</x-mail::panel>
@endif

{{ $bodyText }}

<x-slot:subcopy>
You are receiving this because you are a member of {{ config('membership.org_name') }}.
[Unsubscribe from announcements]({{ $unsubscribeUrl }}).
Payment notices are sent separately, as they concern your account.
</x-slot:subcopy>
</x-mail::message>
