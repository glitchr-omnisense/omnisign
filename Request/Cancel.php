<?php

namespace Omnisign\Request;

use Omnisign\Model\Envelope;

/** Withdraw an envelope that is not over. Result: the Envelope, CANCELED. */
final class Cancel extends Request
{
    public function __construct(public readonly Envelope $envelope, public readonly ?string $reason = null)
    {
    }

    public function getEnvelope(): ?Envelope
    {
        return $this->getResult();
    }
}
