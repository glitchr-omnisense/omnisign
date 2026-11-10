<?php

namespace Omnisign\Request\TenantFile;

use Omnisign\Model\TenantFileNotification;
use Omnisign\Request\Request;

/**
 * What the provider sent to the application's tenant-file webhook: the raw
 * body and the headers, for its authentication. Result: the
 * TenantFileNotification, or InvalidNotificationException.
 */
final class Notify extends Request
{
    /** @param array<string, string|string[]> $headers */
    public function __construct(public readonly string $body, public readonly array $headers = [])
    {
    }

    /** A header, whatever the case it came in. */
    public function header(string $name): ?string
    {
        foreach ($this->headers as $key => $value) {
            if (0 === strcasecmp((string) $key, $name)) {
                return \is_array($value) ? (string) ($value[0] ?? '') : (string) $value;
            }
        }

        return null;
    }

    public function getNotification(): ?TenantFileNotification
    {
        $result = $this->getResult();

        return $result instanceof TenantFileNotification ? $result : null;
    }
}
