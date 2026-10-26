---
title: Testing
order: 60
---

# Testing

An application that uses Omnisign tests its own code, not the providers: every gateway takes the
HTTP client it calls with, so a test hands it a `MockHttpClient` and the answers it wants, and checks
what was sent.

Omnisign ships no fake gateway and no conformance suite: there is no `Testing\` namespace in the
package.

## A test case

```php
use Omnisign\Docuseal\DocusealGatewayFactory;
use Omnisign\Exception\InvalidConfigException;
use Omnisign\Model\Envelope;
use Omnisign\Model\Level;
use Omnisign\Model\Signer;
use Omnisign\Model\SignerStatus;
use Omnisign\Model\Status;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class SigningTest extends TestCase
{
    public function testALeaseIsSentFromTheInstancesTemplate(): void
    {
        $created = new MockResponse('[{"id":11,"submission_id":4,"external_id":"tenant","status":"awaiting","slug":"abc"}]');
        $gateway = (new DocusealGatewayFactory(new MockHttpClient([$created])))->create(['url' => 'https://sign.example.org/api', 'api_key' => 'test']);

        $envelope = $gateway->create(new Envelope('Bail', signers: [new Signer('tenant', 'Camille Érable', 'camille@erable.example')], embedded: true, template: '1'));

        self::assertSame('4', $envelope->reference);
        self::assertSame(Status::SENT, $envelope->status);
        self::assertSame(SignerStatus::WAITING, $envelope->state('tenant')->status);
        self::assertSame('https://sign.example.org/s/abc', $gateway->signingUrl($envelope, 'tenant')->url);
        $sent = json_decode($created->getRequestOptions()['body'], true);
        self::assertSame(['template_id' => 1, 'submitters' => [['role' => 'First Party', 'email' => 'camille@erable.example', 'name' => 'Camille Érable', 'external_id' => 'tenant', 'send_email' => false]], 'send_email' => false, 'order' => 'random'], $sent);
    }

    public function testAQualifiedSignatureIsRefusedBeforeAnyCall(): void
    {
        $http = new MockHttpClient([]);
        $gateway = (new DocusealGatewayFactory($http))->create(['url' => 'https://sign.example.org/api', 'api_key' => 'test']);

        $this->expectException(InvalidConfigException::class);
        try {
            $gateway->create(new Envelope('Caution', signers: [new Signer('surety', 'A B', 'a@b.example')], level: Level::QUALIFIED, template: '2'));
        } finally {
            self::assertSame(0, $http->getRequestsCount());
        }
    }
}
```

```
$ vendor/bin/phpunit --bootstrap vendor/autoload.php SigningTest.php
PHPUnit 11.5.57 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.4.27

..                                                                  2 / 2 (100%)

Time: 00:00.026, Memory: 8.00 MB

OK (2 tests, 7 assertions)
```

(run on 2026-10-26 on the packages' checkouts, no network)

- `MockResponse::getRequestOptions()['body']` is the JSON sent: what the provider receives of the
  envelope.
- A `MockHttpClient` built with an empty list fails on any request: `getRequestsCount()` proves a
  refused envelope sent nothing.
- DocuSign asks its token first (a JWT signed with the RSA key): the first answer is the token
  endpoint's, and a test needs a real RSA key - `openssl_pkey_new()` makes one.
- A webhook is tested by signing the body as the provider does ([Webhooks](usage-webhooks.md#the-signatures)):

```php
$body = json_encode(['event_name' => 'signer.done', 'event_id' => 'e-1', 'data' => ['signature_request' => ['id' => 'r-1'], 'signer' => ['id' => 's-1']]]);
$headers = ['X-Yousign-Signature-256' => 'sha256='.hash_hmac('sha256', $body, 'the-webhook-secret')];
$notification = $yousign->notify($body, $headers);
```

## Recorded answers

The gateways' own tests keep answers written from the providers' documented shapes, in each
package's `Tests/Fixtures`; the core's `docker/harness/config/recorded.php` returns a
`MockHttpClient` callback that serves each call from them by URL - the examples of the
[usage pages](usage-envelopes.md) run on it:

```php
$http = new MockHttpClient(require 'vendor/omnisense/omnisign/docker/harness/config/recorded.php');
```

| Package | Fixtures |
|---|---|
| `omnisign/yousign` | `activate.json`, `canceled.json`, `document.json`, `error-400.json`, `signature-request-done.json`, `signature-request-draft.json`, `signer-landlord.json`, `signer-tenant.json`, `signers.json`, `webhook-signer-done.json` |
| `omnisign/docusign` | `connect-recipient-completed.json`, `consent-required.json`, `envelope-completed.json`, `envelope-created.json`, `envelope-sent.json`, `envelope-update.json`, `error-400.json`, `recipients-update.json`, `token.json`, `userinfo.json`, `view.json` |
| `omnisign/docuseal` | `submission-archived.json`, `submission-completed.json`, `submission-pdf-404.json`, `submission-pending.json`, `submissions-create.json`, `unauthorized.json` |

## In Symfony

The bundle builds the factories on `http_client`: replace it with a `MockHttpClient` in the test
environment (`framework.http_client.mock_response_factory`), or a gateway's service
(`omnisign.gateway.<name>`) with `static::getContainer()->set(...)` before it is first used.

## The package's own tests

`docker compose run --rm omnisign test` runs every package's tests in the [harness](harness.md): the
core's (the gateway and its requests, the factory and its config, the registry, the models, the HTTP
helpers, the bundle, the plain-PHP run with no framework class loaded) and each gateway's on its
fixtures.
