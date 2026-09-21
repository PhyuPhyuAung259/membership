<?php

return [

    'org_name' => env('ORG_NAME', 'Our Association'),
    'currency_symbol' => env('CURRENCY_SYMBOL', '$'),

    // A mailbox someone actually reads. Every reminder invites members to
    // reply with their payment reference, and those replies are how you catch
    // recording mistakes.
    'reply_to' => env('ORG_REPLY_TO'),

    // Shown in every dues reminder. Newlines are preserved.
    'payment_instructions' => env('PAYMENT_INSTRUCTIONS', ''),

    /*
    | Reminder schedule.
    |
    | With monthly billing this runs twelve times a year, so resist adding
    | more notices: four emails a month reads as nagging and gets you marked
    | as spam by your own members.
    */
    'reminders' => [

        // Days BEFORE the due date to send an advance notice.
        'before_days' => array_map('intval', array_filter(
            explode(',', (string) env('REMINDER_BEFORE_DAYS', '3')), 'strlen'
        )),

        // Days AFTER the due date. 0 means on the due date itself.
        'after_days' => array_map('intval', array_filter(
            explode(',', (string) env('REMINDER_AFTER_DAYS', '0,7,14')), 'strlen'
        )),

        // Chasing emails are held back until this many days have passed,
        // covering the lag between a member transferring money and an admin
        // keying it in. The notice on the due date itself always goes out.
        'grace_days' => (int) env('REMINDER_GRACE_DAYS', 3),

        // Days overdue at which the membership is marked lapsed and chasing
        // stops for good.
        'lapse_after_days' => (int) env('REMINDER_LAPSE_AFTER_DAYS', 14),
    ],

    // Unsubscribe links are signed rather than stored in a column, and stay
    // valid this long. Long enough that a member reading an old email still
    // gets a working link.
    'unsubscribe_link_days' => (int) env('UNSUBSCRIBE_LINK_DAYS', 365),
];
