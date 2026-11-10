<?php

namespace Omnisign\Request\Identity;

use Omnisign\Model\Verification;
use Omnisign\Request\Request;

/** Where a verification stands now - and, while it waits for the person, a fresh address for them. Result: the Verification. */
final class Fetch extends Request
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
