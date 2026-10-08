<?php

namespace Omnisign\Exception;

use Omnisign\Request\Request;

/**
 * The gateway does not do that: no reminder from a provider that sends none,
 * no envelope built from a PDF on DocuSeal's free edition. supports() says
 * so beforehand.
 */
final class NotSupportedException extends \LogicException implements OmnisignException
{
    public static function for(Request $request, string $gateway): self
    {
        return new self(\sprintf('The "%s" gateway does not support %s.', $gateway, (new \ReflectionClass($request))->getShortName()));
    }
}
