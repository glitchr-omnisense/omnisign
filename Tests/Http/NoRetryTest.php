<?php

namespace Omnisign\Tests\Http;

use Omnisign\Http\NoRetry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpClient\Retry\GenericRetryStrategy;
use Symfony\Component\HttpClient\RetryableHttpClient;

/** The client the bundle hands each factory: a write sent once through a retrying client. */
final class NoRetryTest extends TestCase
{
    public function testAWriteAnswered503IsSentOnceThroughARetryingClient(): void
    {
        $sent = 0;
        $answers = [new MockResponse('{"error":"unavailable"}', ['http_code' => 503]), new MockResponse('{"id":1}')];
        $transport = new MockHttpClient(static function () use (&$sent, &$answers): MockResponse {
            ++$sent;

            return array_shift($answers) ?? new MockResponse('{}', ['http_code' => 500]);
        });
        $http = NoRetry::of(new RetryableHttpClient($transport, new GenericRetryStrategy(GenericRetryStrategy::DEFAULT_RETRY_STATUS_CODES, 0), 3));
        self::assertNotNull($http);

        self::assertSame(503, $http->request('POST', 'https://sign.example/api/submissions', ['json' => ['template_id' => 1]])->getStatusCode());
        self::assertSame(1, $sent, 'the 503 was not retried by the client');
        self::assertCount(1, $answers, 'the second answer was never asked for');
    }

    public function testAMockHttpClientOrNoClientIsKeptAsGiven(): void
    {
        $mock = new MockHttpClient();

        self::assertSame($mock, NoRetry::of($mock));
        self::assertNull(NoRetry::of(null));
    }
}
