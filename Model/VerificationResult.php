<?php

namespace Omnisign\Model;

/**
 * What a verification established: its status, every check performed and
 * its outcome, the kind of document and the country that issued it - and
 * the person it names only when asked for (Person).
 *
 * What a site keeps: the outcome ($status, $checks, $documentType,
 * $issuingCountry), the reference, the date. What it must not keep: the
 * images, the document's number, the person's address - they stay with the
 * provider, redacted there once the purpose is served (redact()). A Person,
 * when asked for, is compared and dropped, not stored beside the account.
 */
final readonly class VerificationResult
{
    /** @param list<Check> $checks */
    public function __construct(
        public string $reference,
        public VerificationStatus $status,
        public array $checks = [],
        public ?DocumentType $documentType = null,
        public ?string $issuingCountry = null,
        public ?Person $person = null,
        public ?\DateTimeImmutable $at = null,
        public ?string $report = null,
        public bool $live = true,
    ) {
    }

    public function verified(): bool
    {
        return VerificationStatus::VERIFIED === $this->status;
    }

    public function check(CheckKind $kind): ?Check
    {
        foreach ($this->checks as $check) {
            if ($check->kind === $kind) {
                return $check;
            }
        }

        return null;
    }

    /** @return list<Check> the checks that did not pass */
    public function failures(): array
    {
        return array_values(array_filter($this->checks, static fn (Check $c) => false === $c->passed));
    }
}
