<?php

namespace Omnisign\Request;

use Omnisign\Model\Envelope;

/** Send a created envelope: the signers are asked (or, embedded, may sign). Result: the Envelope, SENT. */
final class Send extends Request
{
    public function __construct(public readonly Envelope $envelope)
    {
    }

    public function getEnvelope(): ?Envelope
    {
        return $this->getResult();
    }
}
