<?php

namespace Omnisign\Request\Identity;

use Omnisign\Model\IdentityNotification;
use Omnisign\Request\Request;

/**
 * What the provider sent to the application's identity webhook: the raw
 * body and the headers, for the signature. Result: the IdentityNotification,
 * or InvalidNotificationException when the signature does not hold.
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

    public function getNotification(): ?IdentityNotification
    {
        $result = $this->getResult();

        return $result instanceof IdentityNotification ? $result : null;
    }
}
