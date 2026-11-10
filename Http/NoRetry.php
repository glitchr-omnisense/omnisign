<?php

namespace Omnisign\Http;

use Omnisense\Contracts\Http\NoRetry as KernelNoRetry;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * The application's HTTP client as the Symfony bundle hands it to each
 * gateway factory: with its retries off (omnisense/contracts' Http\NoRetry),
 * so that Symfony's RetryableHttpClient (framework.http_client's
 * retry_failed, which resends a POST answered 423, 425, 429, 502 or 503)
 * never sends an envelope, a verification or a cancellation twice. Null
 * (no http_client service) stays null: the factory makes its own client,
 * which retries nothing. A test client (MockHttpClient) is kept as given.
 * Http\NoRetry came with omnisense/contracts 1568f2c: with an older kernel
 * the client is kept as given.
 *
 * A factory built by hand is given NoRetry::of($http) by its caller.
 */
final class NoRetry
{
    public static function of(?HttpClientInterface $http): ?HttpClientInterface
    {
        return null !== $http && class_exists(KernelNoRetry::class) ? KernelNoRetry::of($http) : $http;
    }
}
