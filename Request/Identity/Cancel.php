<?php

namespace Omnisign\Request\Identity;

use Omnisign\Model\Verification;
use Omnisign\Request\Request;

/** Withdraw a verification the person has not completed. Result: the Verification, CANCELED. */
final class Cancel extends Request
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
