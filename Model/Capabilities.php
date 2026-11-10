<?php

namespace Omnisign\Model;

/**
 * What a gateway's signatures are, said before anything is sent: the
 * levels it offers, whether a signer may sign in the application's page,
 * in turn, after proving who they are, and through whom. The operations it
 * answers are asked of the gateway itself (supports()).
 *
 * Since 1.1, the family's contracts it keeps ($contracts: signature,
 * identity-check, tenant-file), what its identity verifications read
 * ($identityDocuments, $selfie, $liveness) and the provider's own rules
 * ($terms). A gateway built before 1.1 keeps the signature only.
 */
final readonly class Capabilities
{
    /** Having documents signed: envelopes (GatewayInterface). */
    public const SIGNATURE = 'signature';
    /** Verifying an identity: a document, a selfie, a live capture (IdentityCheckInterface). */
    public const IDENTITY_CHECK = 'identity-check';
    /** A rental applicant's file, through a consented connect flow (TenantFileInterface). */
    public const TENANT_FILE = 'tenant-file';

    /**
     * @param list<Level>        $levels
     * @param list<string>       $contracts         the family's contracts it keeps: SIGNATURE, IDENTITY_CHECK, TENANT_FILE
     * @param list<DocumentType> $identityDocuments the identity documents its verifications read
     * @param bool               $selfie            its verifications match a selfie against the document
     * @param bool               $liveness          its verifications check that a live person is there
     */
    public function __construct(
        public array $levels = [Level::SIMPLE],
        public bool $embedded = false,
        public bool $ordered = true,
        public bool $identityCheck = false,
        public bool $thirdParty = true,
        public bool $documents = true,
        public bool $templates = false,
        public array $contracts = [self::SIGNATURE],
        public array $identityDocuments = [],
        public bool $selfie = false,
        public bool $liveness = false,
        public ?Terms $terms = null,
    ) {
    }

    /** Whether it keeps one of the family's contracts: SIGNATURE, IDENTITY_CHECK, TENANT_FILE. */
    public function keeps(string $contract): bool
    {
        return \in_array($contract, $this->contracts, true);
    }

    public function offers(Level $level): bool
    {
        return \in_array($level, $this->levels, true);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'levels' => array_map(static fn (Level $l) => $l->value, $this->levels),
            'identityDocuments' => array_map(static fn (DocumentType $d) => $d->value, $this->identityDocuments),
            'terms' => $this->terms?->toArray(),
        ] + get_object_vars($this);
    }
}
