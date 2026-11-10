<?php

namespace Omnisign\Model;

/**
 * A rental applicant's file, obtained with the applicant's consent through
 * the provider's connect flow: the tenants and their guarantors, their
 * documents and statuses, where the whole file is read.
 *
 * Personal data, kept by the rules the provider sets: $keepUntil is the
 * last moment the site may hold any of it (DossierFacile: three months
 * from when it was obtained); a revoked access (TenantFileEvent::REVOKED)
 * ends it at once. The documents are read at the provider, never copied.
 */
final readonly class TenantFile
{
    /**
     * @param string          $reference   the file at the provider (DossierFacile: the apartment sharing's id)
     * @param string|null     $tenant      the provider's id of the applicant who consented: what fetch() asks for again
     * @param string|null     $application ALONE, COUPLE, GROUP
     * @param list<Applicant> $applicants  the tenants first, then the guarantors
     * @param string|null     $url         the whole file, at the provider
     * @param string|null     $pdfUrl      the whole file as one PDF, at the provider
     */
    public function __construct(
        public string $reference,
        public ?string $tenant = null,
        public TenantFileStatus $status = TenantFileStatus::UNKNOWN,
        public ?string $application = null,
        public array $applicants = [],
        public ?string $url = null,
        public ?string $pdfUrl = null,
        public ?\DateTimeImmutable $fetchedAt = null,
        public ?\DateTimeImmutable $keepUntil = null,
    ) {
    }

    /** @return list<Applicant> */
    public function tenants(): array
    {
        return array_values(array_filter($this->applicants, static fn (Applicant $a) => Applicant::TENANT === $a->role));
    }

    /** @return list<Applicant> */
    public function guarantors(): array
    {
        return array_values(array_filter($this->applicants, static fn (Applicant $a) => Applicant::GUARANTOR === $a->role));
    }

    /** The tenants' declared monthly income, in euros. */
    public function monthlyIncome(): int
    {
        return array_sum(array_map(static fn (Applicant $a) => $a->monthlyIncome(), $this->tenants()));
    }

    /** Whether it must be deleted by now. */
    public function isExpired(?\DateTimeImmutable $now = null): bool
    {
        return null !== $this->keepUntil && ($now ?? new \DateTimeImmutable()) > $this->keepUntil;
    }
}
