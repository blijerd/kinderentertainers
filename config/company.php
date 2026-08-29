<?php

return [
    'legal_name' => env('COMPANY_LEGAL_NAME') ?: 'Blijevent B.V.',
    'kvk' => env('COMPANY_KVK') ?: '82010633',
    'btw' => env('COMPANY_BTW') ?: 'NL862303722B01',
    'email' => env('COMPANY_EMAIL', 'info@kinderentertainers.nl'),
    'phone' => env('COMPANY_PHONE'),
    'address' => env('COMPANY_ADDRESS'),
    'related_brands' => [
        [
            'name' => 'Kidsdisco.eu',
            'url' => 'https://kidsdisco.eu/',
            'logo' => 'brand/kidsdisco-mark.svg',
            'tagline' => 'Interactieve kinderdisco met hits, spel, licht en geluid.',
        ],
        [
            'name' => 'Kids DJ Edwin',
            'url' => 'https://kidsdj-edwin.nl/',
            'logo' => 'brand/kidsdj-edwin-mark.svg',
            'tagline' => 'Kinder-DJ voor feestjes, schoolfeesten en events.',
        ],
    ],
];
