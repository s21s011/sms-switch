<?php

$app_specific = [
    'application_title' => 'SMS Switch',
    'application_description' => 'Use Your Phone as SMS Gateway',
    'application_version' => '2.00.01',
    'app_version_code' => 20001,
    'company_name' => 'SMS Switch',
    'company_url' => '',
    'application_url' => '',
    'unsubscribe_url' => '%server%/unsubscribe.php',
    'logo_src' => 'logo.png',
    'favicon_src' => 'favicon.ico',
    'get_credits_url' => '',
    'skin' => 'blue',
    'default_language' => 'English',
    'default_use_progressive_queue' => 1,
    'default_credits' => 200,
    'default_devices_limit' => 2,
    'default_contacts_limit' => 200,
    'smtp_ssl_verification' => 1,
    'use_credits_for_received_messages_enabled' => 1,
    // Tier-2 security error strings
    'error_device_unauthorized' => 'Device authorization failed.',
    'error_device_disabled' => 'This device is disabled.',
    'error_generic' => 'An unexpected error occurred. Please try again later.',
    'error_too_many_attempts' => 'Too many failed attempts. Please try again later.',
    'error_invalid_file_type' => 'Unsupported file type.'
];

$lang = array_merge($lang, $app_specific);