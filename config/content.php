<?php

return [
    'path' => env('CONTENT_PATH', base_path('content')),
    'pages_directory' => 'pages',
    'blog_directory' => 'blog',
    'redirects_directory' => 'redirects',
    'media_directory' => 'media',
    'media_disk' => env('CONTENT_MEDIA_DISK', 'public'),
    'media_directory_on_disk' => 'content-media',
    'max_media_kilobytes' => (int) env('CONTENT_MAX_MEDIA_KILOBYTES', 8192),
    'allowed_media_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
];
