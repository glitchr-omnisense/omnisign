<?php

namespace Omnisign\Request\TenantFile;

use Omnisign\Model\TenantFile;
use Omnisign\Request\Request;

/**
 * The applicant came back with a code: exchanged for their consent, the
 * file read and linked to the application. Result: the TenantFile.
 */
final class Connect extends Request
{
    public function __construct(public readonly string $code, public readonly string $redirectUri)
    {
    }

    public function getTenantFile(): ?TenantFile
    {
        $result = $this->getResult();

        return $result instanceof TenantFile ? $result : null;
    }
}
