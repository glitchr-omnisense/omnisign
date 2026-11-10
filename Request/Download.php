<?php

namespace Omnisign\Request;

use Omnisign\Model\Envelope;

/** The signed documents and the evidence of their signing, once COMPLETED. Result: a Download. */
final class Download extends Request
{
    public function __construct(public readonly Envelope $envelope)
    {
    }

    public function getDownload(): ?\Omnisign\Model\Download
    {
        $result = $this->getResult();

        return $result instanceof \Omnisign\Model\Download ? $result : null;
    }
}
