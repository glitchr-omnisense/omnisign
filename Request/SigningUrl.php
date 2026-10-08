<?php

namespace Omnisign\Request;

use Omnisign\Model\Envelope;

/** Where a signer signs in the application's page, now. Result: a SigningUrl. */
final class SigningUrl extends Request
{
    public function __construct(public readonly Envelope $envelope, public readonly string $signer, public readonly ?string $returnUrl = null)
    {
    }

    public function getSigningUrl(): ?\Omnisign\Model\SigningUrl
    {
        return $this->getResult();
    }
}
