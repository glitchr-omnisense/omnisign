<?php

namespace Omnisign\Request\Identity;

use Omnisign\Model\Verification;
use Omnisign\Model\VerificationResult;
use Omnisign\Request\Request;

/** What the verification established: every check and its outcome. Result: the VerificationResult. */
final class Result extends Request
{
    public function __construct(public readonly Verification $verification)
    {
    }

    public function getVerificationResult(): ?VerificationResult
    {
        $result = $this->getResult();

        return $result instanceof VerificationResult ? $result : null;
    }
}
