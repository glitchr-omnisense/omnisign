<?php

namespace Omnisign\Model;

/**
 * A person, or a body, in a rental applicant's file: a tenant, or the
 * guarantor of one ($guarantorOf names the tenant). The fields are the
 * service's, no more: names to address them, an e-mail, the status, the
 * documents.
 */
final readonly class Applicant
{
    public const TENANT = 'tenant';
    public const GUARANTOR = 'guarantor';

    /**
     * @param string               $role        TENANT or GUARANTOR
     * @param string|null          $type        for a guarantor: NATURAL_PERSON, LEGAL_PERSON, ORGANISM (Visale...)
     * @param string|null          $name        a legal person's or an organism's name
     * @param list<TenantDocument> $documents
     */
    public function __construct(
        public string $id,
        public string $role = self::TENANT,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $email = null,
        public ?string $type = null,
        public ?string $name = null,
        public TenantFileStatus $status = TenantFileStatus::UNKNOWN,
        public array $documents = [],
        public ?string $guarantorOf = null,
    ) {
    }

    /** The monthly income its FINANCIAL documents declare, in euros. */
    public function monthlyIncome(): int
    {
        $sum = 0;
        foreach ($this->documents as $document) {
            if ('FINANCIAL' === $document->category) {
                $sum += (int) $document->monthlySum;
            }
        }

        return $sum;
    }
}
