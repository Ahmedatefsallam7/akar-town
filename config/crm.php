<?php

/*
 * The 8x CRM: website leads (contact messages, viewings, sell requests) are sent there as fresh leads.
 * Nothing is sent until the token, the subdomain and the form id are set.
 */
return [
    'token' => env('CRM_TOKEN'),

    'base_url' => env('CRM_SUBDOMAIN') ? 'https://'.env('CRM_SUBDOMAIN').'.8xcrm.com' : null,

    'timeout' => (int) env('CRM_TIMEOUT', 30),

    // The CRM web-form id each kind of lead is routed to. A form without its own id goes to the Contact Us one, so a
    // lead is never dropped.
    'forms' => [
        'contact_us' => env('CRM_FORM_CONTACT_US'),
        'property_enquiry' => env('CRM_FORM_PROPERTY_ENQUIRY'),
        'project_enquiry' => env('CRM_FORM_PROJECT_ENQUIRY'),
        'schedule_viewing' => env('CRM_FORM_SCHEDULE_VIEWING'),
        'sell_property' => env('CRM_FORM_SELL_PROPERTY'),
        'property_matching' => env('CRM_FORM_PROPERTY_MATCHING'),
    ],

    'email_account_type_id' => (int) env('CRM_EMAIL_ACCOUNT_TYPE_ID', 22),

    'default_country_code' => env('CRM_DEFAULT_COUNTRY_CODE', 'EG'),
];
