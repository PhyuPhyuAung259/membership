<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Unsubscribe · {{ config('membership.org_name') }}</title>
    <style>
        :root { color-scheme: light dark; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px;
               background: #eef1f4; color: #16263a;
               font: 400 16px/1.6 system-ui, -apple-system, 'Segoe UI', sans-serif; }
        main { max-width: 30rem; background: #fff; border: 1px solid #cbd5de; padding: 32px; }
        h1 { margin: 0 0 12px; font-size: 1.3rem; font-weight: 600; }
        p { margin: 0 0 20px; }
        button { font: inherit; font-weight: 500; background: #16263a; color: #fff;
                 border: 0; padding: 12px 20px; cursor: pointer; }
        button:hover { background: #24405f; }
        button:focus-visible { outline: 3px solid #b3311f; outline-offset: 2px; }
        small { display: block; color: #5a6b7d; }
        @media (prefers-color-scheme: dark) {
            body { background: #101b26; color: #e6ecf2; }
            main { background: #16263a; border-color: #2c4666; }
            button { background: #e6ecf2; color: #16263a; }
            small { color: #9fb2c4; }
        }
    </style>
</head>
<body>
<main>
    @if (! empty($isTest))
        <h1>Test link</h1>
        <p>This was a test announcement, so there is nothing to unsubscribe.</p>

    @elseif (! empty($done))
        <h1>Unsubscribed</h1>
        <p>{{ $member->email }} will no longer receive announcements. Payment notices will still be
           sent, as they concern your account. To start receiving announcements again, contact us
           and we will switch them back on.</p>

    @elseif ($alreadyDone)
        <h1>Already unsubscribed</h1>
        <p>{{ $member->email }} no longer receives announcements from us.</p>

    @else
        <h1>Unsubscribe from announcements</h1>
        <p>Confirm that <strong>{{ $member->email }}</strong> should stop receiving news about
           events and activities. Notices about membership payments will still be sent, as they
           concern your account.</p>
        <form method="POST" action="{{ $confirmUrl }}">
            @csrf
            <button type="submit">Unsubscribe me</button>
        </form>
    @endif

    <small>{{ config('membership.org_name') }}</small>
</main>
</body>
</html>
