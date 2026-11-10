<?php

namespace Omnisign\Request\Identity;

use Omnisign\Model\Verification;
use Omnisign\Request\Request;

/** Start an identity verification session. Result: the Verification with its reference and where the person goes. */
final class Start extends Request
{
    public function __construct(public readonly Verification $verification)
    {
    }

    public function getVerification(): ?Verification
    {
        $result = $this->getResult();

        return $result instanceof Verification ? $result : null;
    }
}
