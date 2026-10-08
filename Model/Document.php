<?php

namespace Omnisign\Model;

/**
 * A document of an envelope: its file, and the application's key for it -
 * what fields point at.
 */
final readonly class Document
{
    public function __construct(
        public string $key,
        public File $file,
        /** false: attached for reading, not signed */
        public bool $signable = true,
    ) {
    }
}
