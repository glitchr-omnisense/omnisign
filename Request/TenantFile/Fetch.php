<?php

namespace Omnisign\Request\TenantFile;

use Omnisign\Model\TenantFile;
use Omnisign\Request\Request;

/** A linked applicant's file as it is now, by the provider's id of the applicant. Result: the TenantFile. */
final class Fetch extends Request
{
    public function __construct(public readonly string $tenant)
    {
    }

    public function getTenantFile(): ?TenantFile
    {
        $result = $this->getResult();

        return $result instanceof TenantFile ? $result : null;
    }
}
