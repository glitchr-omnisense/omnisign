<?php

namespace Omnisign\Model;

/**
 * One piece of a rental applicant's file - its category (identification,
 * residency, professional, financial, tax), its kind, whether the service
 * validated it, and for income the monthly amount it states. $url opens
 * the watermarked document at the service, for as long as the file's
 * status lets it: read it there, do not copy it.
 */
final readonly class TenantDocument
{
    /**
     * @param string      $category    the service's category: IDENTIFICATION, RESIDENCY, PROFESSIONAL, FINANCIAL, TAX...
     * @param string|null $kind        its sub-category: FRENCH_IDENTITY_CARD, SALARY, VISALE...
     * @param int|null    $monthlySum  for an income, the monthly amount in euros, as the applicant declared it
     */
    public function __construct(
        public string $category,
        public ?string $kind = null,
        public TenantFileStatus $status = TenantFileStatus::UNKNOWN,
        public ?int $monthlySum = null,
        public ?string $url = null,
        public ?string $id = null,
    ) {
    }
}
