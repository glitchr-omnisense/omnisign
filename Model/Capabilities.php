<?php

namespace Omnisign\Model;

use Omnisense\Contracts\Policy\UsagePolicy;
use Omnisense\Contracts\Provider\CapabilitiesInterface;

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
 *
 * Since 1.2, $statements: the statements a signer types or acknowledges
 * (Field::$statement) the provider carries, one StatementLimit per mode,
 * with its documented limit - fits() says beforehand whether a statement
 * goes in one field, so that the application chooses its fallback (several
 * fields, an acknowledged statement) rather than failing at send time.
 *
 * Its usage policy (policy()) is omnisense/contracts' UsagePolicy: given
 * ($policy, read from the provider's policy.json), or made of $terms
 * (Terms::policy()), or nothing known. $terms stays: given, or read from the
 * policy when only the policy is given (Terms::fromPolicy()).
 */
final readonly class Capabilities implements CapabilitiesInterface
{
    /** Having documents signed: envelopes (GatewayInterface). */
    public const SIGNATURE = 'signature';
    /** Verifying an identity: a document, a selfie, a live capture (IdentityCheckInterface). */
    public const IDENTITY_CHECK = 'identity-check';
    /** A rental applicant's file, through a consented connect flow (TenantFileInterface). */
    public const TENANT_FILE = 'tenant-file';

    /** The provider's own rules, in words: given, or read from the policy. */
    public ?Terms $terms;

    /**
     * @param list<Level>        $levels
     * @param list<string>       $contracts         the family's contracts it keeps: SIGNATURE, IDENTITY_CHECK, TENANT_FILE
     * @param list<DocumentType> $identityDocuments the identity documents its verifications read
     * @param bool               $selfie            its verifications match a selfie against the document
     * @param bool               $liveness          its verifications check that a live person is there
     * @param Terms|null         $terms             the provider's own rules, in words; read from $policy when null and it is given
     * @param UsagePolicy|null   $policy            its usage policy; made of $terms when null
     * @param list<StatementLimit> $statements      the statements it carries, one per mode (since 1.2)
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
        ?Terms $terms = null,
        private ?UsagePolicy $policy = null,
        public array $statements = [],
    ) {
        $this->terms = $terms ?? (null === $policy ? null : Terms::fromPolicy($policy));
    }

    /** Its usage policy as the provider declares it (a site's tightening is the registry's: Registry::policy()). */
    public function policy(): UsagePolicy
    {
        return $this->policy ?? $this->terms?->policy() ?? UsagePolicy::described(UsagePolicy::UNNAMED);
    }

    /** @return list<string> the family's contracts it keeps (the $contracts field, for omnisense/contracts' interface) */
    public function contracts(): array
    {
        return $this->contracts;
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

    /** What it carries of a statement in this mode; null: it does not carry one so. */
    public function statement(StatementMode $mode = StatementMode::TYPED): ?StatementLimit
    {
        foreach ($this->statements as $limit) {
            if ($limit->mode === $mode) {
                return $limit;
            }
        }

        return null;
    }

    /** Whether this statement goes in one field of this mode: false when the mode is not carried. */
    public function fits(string $statement, StatementMode $mode = StatementMode::TYPED): bool
    {
        return $this->statement($mode)?->fits($statement) ?? false;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'levels' => array_map(static fn (Level $l) => $l->value, $this->levels),
            'identityDocuments' => array_map(static fn (DocumentType $d) => $d->value, $this->identityDocuments),
            'terms' => $this->terms?->toArray(),
            'embedded' => $this->embedded,
            'ordered' => $this->ordered,
            'identityCheck' => $this->identityCheck,
            'thirdParty' => $this->thirdParty,
            'documents' => $this->documents,
            'templates' => $this->templates,
            'contracts' => $this->contracts,
            'selfie' => $this->selfie,
            'liveness' => $this->liveness,
            'statements' => array_map(static fn (StatementLimit $s) => $s->toArray(), $this->statements),
            'policy' => $this->policy()->toArray(),
        ];
    }
}
