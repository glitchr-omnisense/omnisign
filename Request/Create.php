<?php

namespace Omnisign\Request;

use Omnisign\Model\Envelope;

/** Create the envelope on the provider: its documents, signers and fields. Result: the Envelope with its reference - a DRAFT, unless the provider sends at once. */
final class Create extends Request
{
    public function __construct(public readonly Envelope $envelope)
    {
    }

    public function getEnvelope(): ?Envelope
    {
        return $this->getResult();
    }
}
