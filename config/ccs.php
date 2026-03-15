<?php

return [
    'password_reset' => [
        /*
         * When enabled, the forgot-password form will show an error if the email
         * is not registered. This can expose whether an email exists in the
         * system (account enumeration). Keep disabled for better security.
         */
        'reveal_email_existence' => (bool) env('PASSWORD_RESET_REVEAL_EMAIL_EXISTENCE', false),

        /*
         * How to deliver password reset emails:
         * - "sync": send immediately during the request (simplest / real-time).
         * - "queue": queue email jobs (requires a running queue worker).
         */
        'mail_delivery' => env('PASSWORD_RESET_MAIL_DELIVERY', 'sync'),
    ],

    'admin_account_security' => [
        'local_email_domains' => array_values(array_filter(array_map('trim', explode(',', (string) env('ADMIN_LOCAL_EMAIL_DOMAINS', 'ccs.local'))))),
    ],

    'announcements' => [
        'notify_on_publish' => (bool) env('ANNOUNCEMENTS_NOTIFY_ON_PUBLISH', false),

        /*
         * How to deliver announcement publish emails:
         * - "sync": send immediately during the request.
         * - "queue": queue notification jobs (requires a running queue worker).
         */
        'notification_delivery' => env('ANNOUNCEMENTS_NOTIFY_DELIVERY', 'sync'),
    ],
];
