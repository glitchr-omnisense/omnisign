<?php

namespace Omnisign\Model;

/** What a completed envelope leaves: the signed documents, and the evidence of their signing (the audit trail). */
final readonly class Download
{
    /** @param list<File> $documents */
    public function __construct(
        public array $documents,
        public ?File $evidence = null,
    ) {
    }
}
