<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Customer-Facing Brand Identity
    |--------------------------------------------------------------------------
    |
    | Central source of truth for marketplace branding, transactional email
    | footers, headings, and GST tax invoice document headers.
    |
    */

    'name' => env('MARKETPLACE_BRAND_NAME', 'JDS Mart'),
    'tagline' => env('MARKETPLACE_TAGLINE', 'India Shops Here'),
    'legal_name' => env('MARKETPLACE_LEGAL_NAME', 'JDS Mart Private Limited'),

    'support_phone' => env('MARKETPLACE_SUPPORT_PHONE', '+91 99966 69884'),
    'support_email' => env('MARKETPLACE_SUPPORT_EMAIL', 'support@jsssolutions.in'),
    'website_url' => env('MARKETPLACE_WEBSITE_URL', 'https://jdsmart.in'),
    'help_center_url' => env('MARKETPLACE_HELP_URL', 'https://jdsmart.in/help-center'),

    'fallback_vendor' => 'JDS Authorized Marketplace Vendor',
];
