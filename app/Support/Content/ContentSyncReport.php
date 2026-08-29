<?php

namespace App\Support\Content;

final class ContentSyncReport
{
    /**
     * @param  list<string>  $errors
     */
    public function __construct(
        public int $media = 0,
        public int $pages = 0,
        public int $posts = 0,
        public array $errors = [],
        public bool $dryRun = false,
    ) {}

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }
}
