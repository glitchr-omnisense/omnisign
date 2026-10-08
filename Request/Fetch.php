<?php

namespace Omnisign\Request;

use Omnisign\Model\Envelope;

/** The envelope as the provider has it now: its status, each signer's. Result: the Envelope. */
final class Fetch extends Request
{
    public function __construct(public readonly Envelope $envelope)
    {
    }

    public function getEnvelope(): ?Envelope
    {
        return $this->getResult();
    }
}
