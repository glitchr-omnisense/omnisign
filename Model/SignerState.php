<?php

namespace Omnisign\Model;

/**
 * A signer as the provider has them: its own id for them, where they
 * stand, and - for a signature in the application's page - the link that
 * opens their signing, when the provider gave one.
 */
final readonly class SignerState
{
    public function __construct(
        public string $key,
        public ?string $reference = null,
        public SignerStatus $status = SignerStatus::WAITING,
        public ?\DateTimeImmutable $signedAt = null,
        public ?string $link = null,
        public ?\DateTimeImmutable $linkExpiresAt = null,
        public ?string $reason = null,
    ) {
    }
}
