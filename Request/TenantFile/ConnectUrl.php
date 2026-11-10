<?php

namespace Omnisign\Request\TenantFile;

use Omnisign\Request\Request;

/**
 * Where the applicant consents to share their file: the provider's connect
 * page, coming back to $redirectUri with a code and $state. No call is
 * made. Result: the URL, a string.
 */
final class ConnectUrl extends Request
{
    /** @param string $state an unguessable value the application keeps in the session and checks on the way back */
    public function __construct(public readonly string $redirectUri, public readonly string $state)
    {
    }

    public function getUrl(): ?string
    {
        $result = $this->getResult();

        return \is_string($result) ? $result : null;
    }
}
