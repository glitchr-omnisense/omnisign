<?php

namespace Omnisign\Request;

use Omnisign\Model\Envelope;

/** Ask again the signers who have not signed - or one of them, by key. Result: the Envelope. */
final class Remind extends Request
{
    public function __construct(public readonly Envelope $envelope, public readonly ?string $signer = null)
    {
    }

    public function getEnvelope(): ?Envelope
    {
        return $this->getResult();
    }
}
