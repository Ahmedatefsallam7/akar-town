<?php

/*
 * The 8x CRM: website leads (contact messages, viewings, sell requests) are sent there as fresh leads.
 * Nothing is sent until the token, the subdomain and the form id are set.
 */
return [
    'token' => env('CRM_TOKEN'),

    'base_url' => env('CRM_SUBDOMAIN') ? 'https://'.env('CRM_SUBDOMAIN').'.8xcrm.com' : null,

    'timeout' => (int) env('CRM_TIMEOUT', 30),

    // The CRM web-form id each kind of lead is routed to.
    'forms' => [
        'contact_us' => env('CRM_FORM_CONTACT_US'),
        'schedule_viewing' => env('CRM_FORM_SCHEDULE_VIEWING'),
    ],

    'email_account_type_id' => (int) env('CRM_EMAIL_ACCOUNT_TYPE_ID', 22),

    'default_country_code' => env('CRM_DEFAULT_COUNTRY_CODE', 'EG'),
];
