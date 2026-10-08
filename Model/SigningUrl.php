<?php

namespace Omnisign\Model;

/** Where one signer signs, in the application's page (a frame) or a tab: made for them, ask again each time. */
final readonly class SigningUrl
{
    public function __construct(
        public string $url,
        public ?\DateTimeImmutable $expiresAt = null,
    ) {
    }
}
