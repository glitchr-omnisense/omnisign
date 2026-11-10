<?php

namespace Omnisign\Model;

/**
 * An identity verification session: what the application asks (which
 * documents, a selfie, a live capture), and once started, the provider's
 * reference, where the person goes, where it stands.
 *
 * What to keep of it: $key, $reference, $status, $error. Never $url nor
 * $clientSecret - they open the person's flow, expire within hours, and
 * every provider says not to store or log them: ask fetch() for fresh ones.
 */
final readonly class Verification
{
    /**
     * @param list<DocumentType> $documents    the documents accepted; empty: the provider's default
     * @param bool               $selfie       a selfie matched against the document's photo
     * @param bool               $liveCapture  the document photographed live, no upload of a file
     * @param string|null        $email        the person's e-mail, when the provider pre-fills or checks it
     * @param string|null        $returnUrl    where the person comes back to once done
     * @param string|null        $key          the application's handle (a booking, a lease): the provider's client reference, the idempotency key on start
     * @param array<string, string> $metadata  the provider's metadata: identifiers only, never personal data
     * @param string|null        $reference    the provider's id, once started
     * @param string|null        $url          the hosted flow's address: hand it to the person, do not store it
     * @param string|null        $clientSecret for the provider's script in the application's page: do not store it
     * @param array{code: string|null, reason: string|null}|null $error why the last attempt failed, when it did
     * @param bool               $live         false in a provider's test mode: nothing was really checked
     */
    public function __construct(
        public array $documents = [],
        public bool $selfie = true,
        public bool $liveCapture = true,
        public ?string $email = null,
        public ?string $returnUrl = null,
        public ?string $key = null,
        public array $metadata = [],
        public ?string $reference = null,
        public VerificationStatus $status = VerificationStatus::PENDING,
        public ?string $url = null,
        public ?string $clientSecret = null,
        public ?array $error = null,
        public ?\DateTimeImmutable $createdAt = null,
        public bool $live = true,
    ) {
    }

    /** The provider's id; a LogicException before start(). */
    public function reference(): string
    {
        return $this->reference ?? throw new \LogicException('This verification was not started: no reference yet.');
    }

    /**
     * A copy with what a provider answered.
     *
     * @param array{code: string|null, reason: string|null}|null $error
     */
    public function with(
        ?string $reference = null,
        ?VerificationStatus $status = null,
        ?string $url = null,
        ?string $clientSecret = null,
        ?array $error = null,
        ?\DateTimeImmutable $createdAt = null,
        ?bool $live = null,
    ): self {
        return new self($this->documents, $this->selfie, $this->liveCapture, $this->email, $this->returnUrl, $this->key, $this->metadata,
            $reference ?? $this->reference, $status ?? $this->status, $url, $clientSecret, $error, $createdAt ?? $this->createdAt, $live ?? $this->live);
    }
}
