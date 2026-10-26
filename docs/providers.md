---
title: Writing a gateway
order: 80
---

# Writing a gateway

A gateway is a factory that extends `Omnisign\GatewayFactory` and one action per request it
answers. It can live in the application or be a package of its own, as `omnisign/yousign`,
`omnisign/docusign` and `omnisign/docuseal` are.

## The rules

- **One action per request, only those the provider documents.** A request no action supports is a
  `NotSupportedException`, and `supports()` says so beforehand; a factory may leave an action out
  when an option is missing (a closure of the `Config` that returns null - as `notify` without a
  webhook secret).
- **Every action answers.** `execute()` sets the request's result, or throws an
  `OmnisignException`; returning without a result is a `ProviderException` of the gateway.
- **Refuse before sending.** What the provider cannot take - a level, a field type, a signer's
  authentication, an envelope without document or signer - is an `InvalidConfigException` before
  any call.
- **Give back what to keep.** `create()` answers the envelope `with()` its `reference` and a
  `SignerState` per signer key, holding the provider's id of each signer: the later calls find
  them by it. Map the provider's statuses to `Status` and `SignerStatus`, `UNKNOWN` for the rest.
- **Through the helpers.** Call the provider with `Omnisign\Http\Answer::send()`: no answer, a 5xx or
  a 429 is then an `UnreachableException`; build an upload with `Omnisign\Http\Multipart`.
- **Errors.** Refused credentials (401, 403) are an `InvalidKeyException`; any other refusal a
  `ProviderException` with the provider's code; a callback that does not hold an
  `InvalidNotificationException`.
- **Keep nothing.** The documents and the evidence go back to the application in a `Download`.

## A gateway of the application's

A sketch of a service with an API key and two routes:

```php
<?php // signing.php

require __DIR__.'/vendor/autoload.php';

use Omnisign\Action\ActionInterface;
use Omnisign\Action\ApiAwareInterface;
use Omnisign\Action\ApiAwareTrait;
use Omnisign\Config;
use Omnisign\Exception\InvalidConfigException;
use Omnisign\Exception\ProviderException;
use Omnisign\GatewayFactory;
use Omnisign\Http\Answer;
use Omnisign\Model\Capabilities;
use Omnisign\Model\Document;
use Omnisign\Model\Envelope;
use Omnisign\Model\File;
use Omnisign\Model\Level;
use Omnisign\Model\Signer;
use Omnisign\Model\Status;
use Omnisign\Request\Create;
use Omnisign\Request\Fetch;
use Omnisign\Request\Remind;
use Omnisign\Request\Request;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/** A signature service's API: a key, two routes. */
final class AcmeSignApi
{
    public function __construct(private readonly HttpClientInterface $http, private readonly string $url, #[\SensitiveParameter] private readonly string $key)
    {
    }

    public function call(string $method, string $path, array $json = []): array
    {
        $answer = Answer::send($this->http, 'acme', $method, $this->url.$path, ['headers' => ['Authorization' => 'Bearer '.$this->key]] + ($json ? ['json' => $json] : []));
        if ($answer->status >= 400) {
            throw new ProviderException('acme', sprintf('%s %s: HTTP %d', $method, $path, $answer->status), (string) $answer->status);
        }

        return $answer->json();
    }
}

abstract class AcmeAction implements ActionInterface, ApiAwareInterface
{
    /** @use ApiAwareTrait<AcmeSignApi> */
    use ApiAwareTrait;

    public function __construct()
    {
        $this->apiClass = AcmeSignApi::class;
    }

    protected static function status(string $status): Status
    {
        return ['open' => Status::SENT, 'signed' => Status::COMPLETED][$status] ?? Status::UNKNOWN;
    }
}

final class AcmeCreateAction extends AcmeAction
{
    public function supports(Request $request): bool
    {
        return $request instanceof Create;
    }

    public function execute(Request $request): void
    {
        \assert($request instanceof Create);
        $envelope = $request->envelope;
        if (Level::SIMPLE !== $envelope->level || !$envelope->documents || !$envelope->signers) {
            throw new InvalidConfigException('The "acme" gateway signs one simple envelope with a document and a signer at least.');
        }
        $made = $this->api->call('POST', '/requests', [
            'title' => $envelope->title,
            'file' => base64_encode($envelope->documents[0]->file->content),
            'signers' => array_map(static fn (Signer $s) => ['name' => $s->name, 'email' => $s->email], $envelope->signers),
        ]);
        $request->setResult($envelope->with(reference: (string) $made['id'], status: self::status((string) $made['status'])));
    }
}

final class AcmeFetchAction extends AcmeAction
{
    public function supports(Request $request): bool
    {
        return $request instanceof Fetch;
    }

    public function execute(Request $request): void
    {
        \assert($request instanceof Fetch);
        $data = $this->api->call('GET', '/requests/'.$request->envelope->reference());
        $request->setResult($request->envelope->with(status: self::status((string) $data['status']), data: $data));
    }
}

final class AcmeGatewayFactory extends GatewayFactory
{
    public function __construct(private readonly ?HttpClientInterface $http = null)
    {
    }

    protected function populateConfig(Config $config): void
    {
        $config->defaults([
            'omnisign.factory_name' => 'acme',
            'omnisign.factory_title' => 'Acme Sign',
            'omnisign.required_options' => ['api_key'],
            'url' => 'https://api.acme-sign.example/v1',
            'omnisign.capabilities' => new Capabilities([Level::SIMPLE], embedded: false, ordered: false),
            'omnisign.api' => fn (Config $c) => new AcmeSignApi($this->http ?? HttpClient::create(), rtrim((string) $c['url'], '/'), (string) $c['api_key']),
            'omnisign.action.create' => new AcmeCreateAction(),
            'omnisign.action.fetch' => new AcmeFetchAction(),
        ]);
    }
}

$http = new MockHttpClient([new MockResponse('{"id":"r-7","status":"open"}', ['http_code' => 201]), new MockResponse('{"id":"r-7","status":"signed"}')]);
$gateway = (new AcmeGatewayFactory($http))->create(['api_key' => 'test']);

$envelope = $gateway->create(new Envelope('Devis', [new Document('quote', new File('%PDF-1.7', 'quote.pdf'))], [new Signer('client', 'Camille Érable', 'camille@erable.example')]));
echo $gateway->getTitle(), ': ', $envelope->reference, ' ', $envelope->status->value, "\n";
echo $gateway->fetch($envelope)->status->value, "\n";
var_dump($gateway->supports(Remind::class));
try {
    $gateway->fetch(new Envelope('Never created'));
} catch (InvalidConfigException $e) {
    echo $e->getMessage(), "\n";
}
```

```
$ php signing.php
Acme Sign: r-7 sent
completed
bool(false)
The envelope "Never created" was never created on a provider: create() it first.
```

(run on 2026-10-26 on a `MockHttpClient`, PHP 8.4; the service is made up, nothing was called.)
`Envelope::reference()` refuses an envelope never created before any call.

In Symfony, the factory is autoconfigured (tagged `omnisign.gateway_factory`) as soon as it is a
service, and named in `omnisign.gateways.<name>.factory`.

## The factory's Config

`populateConfig(Config $config)` sets, with `$config->defaults([...])`:

| Key | |
|---|---|
| `omnisign.factory_name` | the factory's name, what `factory:` names |
| `omnisign.factory_title` | a readable name, `getTitle()` |
| `omnisign.required_options` | the options `create()` refuses to go without |
| `omnisign.capabilities` | a `Capabilities` (or a closure of the `Config` giving one): required |
| `omnisign.api` | the client the actions work with, or a closure of the `Config` building it at `create()` |
| `omnisign.action.<name>` | an action, or a closure of the `Config` giving one or null |
| every other option | its default |

## A package of its own

The shipped gateways' layout, to copy: name `omnisign/<provider>`, namespace
`Omnisign\<Provider>\`, `require` `php` `>=8.2`, `omnisense/omnisign` `^1.0@dev`,
`symfony/http-client` and what the provider needs (`ext-openssl` for a JWT); an `Api` class and an
`Action/` directory with an `AbstractAction` holding the `ApiAwareTrait` and the status maps; tests
on answers written from the provider's documented shapes, in `Tests/Fixtures`; a README with a
"Responsibility" section. For the bundle to register the factory without the application declaring
it, its class must be in `OmnisignBundle::FACTORIES`; for the harness, in
`docker/harness/plugins.php`, `config/gateways.php` and `config/recorded.php`.
