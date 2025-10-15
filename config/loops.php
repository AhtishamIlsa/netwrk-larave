<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Loops Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Loops email service integration
    |
    */

    'api_key' => env('LOOPS_API_KEY'),
    
    'smtp' => [
        'host' => env('MAIL_HOST', 'smtp.loops.so'),
        'port' => env('MAIL_PORT', 587),
        'username' => env('MAIL_USERNAME', 'loops'),
        'password' => env('MAIL_PASSWORD'),
        'encryption' => env('MAIL_ENCRYPTION', 'tls'),
    ],

    'templates' => [
        'otp' => env('OTP_TEMPLATE_ID'),
        'password_reset' => env('PASSWORD_RESET_TEMPLATE_ID'),
        'welcome' => env('WELCOME_TEMPLATE_ID'),
        'intro' => env('INTRO_TEMPLATE_ID'),
        'login_credentials_from_group' => env('LOGIN_CREDENTIALS_FROM_GROUP'),
        
        // Introduction email templates
        'introduce_from_email_decline' => env('INTRODUCE_FROM_EMAIL_TEMPLATE_DECLINE'),
        'introduce_from_email_decline_introduced_to' => env('INTRODUCE_FROM_EMAIL_TEMPLATE_DECLINE_INTRODUCED_TO'),
        'introduce_from_email_accept' => env('INTRODUCE_FROM_EMAIL_TEMPLATE_ACCEPT'),
        'introduce_from_email_accept_introduced_to' => env('INTRODUCE_FROM_EMAIL_TEMPLATE_ACCEPT_INTRODUCED_TO'),
        
        // Referral email templates
        'reminder' => env('REMINDER_TEMPLATE'),
        'revoke' => env('REVOKE_TEMPLATE'),
        
        // Group email templates
        'join_group_email' => env('JOIN_GROUP_EMAIL_TEMPLATE'),
        'join_group_invite' => env('JOIN_GROUP_INVITE_TEMPLATE'),
        'swap_group_request' => env('SWAP_GROUP_REQUEST_EMAIL_TEMPLATE'),
        'swap_group_request_reject' => env('SWAP_GROUP_REQUEST_REJECT_EMAIL_TEMPLATE'),
        'referral_group_request' => env('REFERRAL_GROUP_REQUEST_EMAIL_TEMPLATE'),
    ],

    'urls' => [
        'frontend' => env('FRONT_END_URL', 'http://localhost:3000'),
        'admin_frontend' => env('ADMIN_FRONT_END_URL', 'http://localhost:3001'),
    ],
];