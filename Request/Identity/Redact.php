<?php

namespace Omnisign\Request\Identity;

use Omnisign\Model\Verification;
use Omnisign\Request\Request;

/**
 * Have the provider erase the personal data of a verification - the
 * images, what it read of the document - once its purpose is served; the
 * outcome stays. Result: the Verification.
 */
final class Redact extends Request
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
