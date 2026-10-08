<?php

namespace Omnisign\Exception;

/**
 * The provider did not answer: no connection, a timeout, a server error, a
 * quota exceeded. Nothing was done, as far as can be told: fetch, then try again.
 */
final class UnreachableException extends ProviderException
{
}
