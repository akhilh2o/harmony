<?php

return [
    // Android package name — e.g. com.goodhypnosis.app
    'android_package_name' => env('ANDROID_PACKAGE_NAME', 'com.goodhypnosis.name'),

    // Path to Google Service Account JSON file
    // Download from Google Cloud Console → IAM → Service Accounts
    // Enable "Android Publisher API" for this account
    'google_service_account_json' => env('GOOGLE_SERVICE_ACCOUNT_JSON', storage_path('app/google-service-account.json')),

    // Apple Shared Secret
    // Get from App Store Connect → My Apps → Your App → Subscriptions → App-Specific Shared Secret
    'apple_shared_secret' => env('APPLE_SHARED_SECRET', ''),

    // Apple Bundle ID — e.g. com.goodhypnosis.app
    'apple_bundle_id' => env('APPLE_BUNDLE_ID', 'com.goodhypnosis.app'),
];