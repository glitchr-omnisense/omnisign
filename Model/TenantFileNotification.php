<?php

namespace Omnisign\Model;

/**
 * What a provider told the application's webhook about a rental
 * applicant's file, its authentication checked: one event, one applicant.
 * Fetch the file for the rest - on REVOKED, delete what is held instead.
 */
final readonly class TenantFileNotification
{
    /**
     * @param string      $type      the provider's own name for the event
     * @param string|null $tenant    the applicant, as TenantFile::$tenant names them
     * @param string|null $reference the file, as TenantFile::$reference names it
     */
    public function __construct(
        public TenantFileEvent $event,
        public string $type,
        public ?string $tenant = null,
        public ?string $reference = null,
        public ?TenantFileStatus $status = null,
        public ?\DateTimeImmutable $at = null,
    ) {
    }
}
