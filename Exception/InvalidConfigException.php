<?php

namespace Omnisign\Exception;

/** A gateway misconfigured: an option missing, an unknown factory, an envelope without what the gateway needs. */
final class InvalidConfigException extends \Omnisense\Contracts\Exception\InvalidConfigException implements OmnisignException
{
}
