# omnisense/omnisign

One contract for **having documents signed** - whoever signs: Yousign, DocuSign, or a DocuSeal
instance of one's own.

Published as `omnisense/omnisign` (formerly `glitchr/omnisign`, which this package replaces: a
`composer.json` that still requires the old name keeps resolving to this package, never both).
`omnisense/` is the vendor of every family's core; the providers keep theirs (`omnisign/docuseal`...).

```php
$gateway = $registry->get('contracts');                      // or 'qualified': the same calls

$envelope = $gateway->create(new Envelope('Bail', $documents, $signers, $fields, Level::SIMPLE, embedded: true));
$envelope->reference;                                        // keep it
$gateway->send($envelope);
$gateway->signingUrl($envelope, 'tenant')->url;              // the tenant signs in your page
$gateway->fetch($envelope)->status;                          // Status::COMPLETED
$signed = $gateway->download($envelope);                     // the signed documents and their evidence
```

This package holds the contract (`GatewayInterface`, the requests `Create`, `Send`, `Fetch`,
`Remind`, `Cancel`, `Download`, `SigningUrl`, `Notify`, `GatewayFactory`, `Registry`), the models
(`Envelope`, `Document`, `File`, `Signer`, `Field`, `Level`, `Status`, `SignerState`, `Download`,
`SigningUrl`, `Notification`, `Capabilities`) and a bridge for Symfony. It needs no framework: PHP
and `symfony/http-client-contracts`. Each gateway is a package of its own:

| Package | Levels | In the page | Third party |
|---|---|---|---|
| [`omnisign/yousign`](https://github.com/glitchr-omnisense/omnisign-yousign) | simple, advanced, qualified | yes | Yousign (Youtrust) |
| [`omnisign/docusign`](https://github.com/glitchr-omnisense/omnisign-docusign) | simple | yes | DocuSign |
| [`omnisign/docuseal`](https://github.com/glitchr-omnisense/omnisign-docuseal) | simple | yes | none when self-hosted |

## What a gateway is, and is not

A gateway has documents signed: it creates the envelope, sends it, says where it stands, gives
back the signed documents and the evidence of their signing. **It keeps nothing for the
application**: store what `download()` gives in your own vault, with the envelope's reference.

The levels are eIDAS's (simple, advanced, qualified): a gateway that cannot reach a level refuses
the envelope (`InvalidConfigException`), it never signs lower. A notary's authentic deed and a
lawyer's deed go through their professions' own tools, not this family. It is not a vault, nor a
template editor.

## Documentation

- [The manual's home](docs/index.md): what it is, the gateways
- [Installation and a first envelope](docs/installation.md)
- [Symfony](docs/symfony.md), [Laravel](docs/laravel.md)
- [Concepts](docs/concepts.md): gateways, requests, actions, factories, the registry, the HTTP helpers
- [Models](docs/models.md)
- [Gateways: the contract, what each does, writing one](docs/gateways.md)
- [Envelopes, from creation to download](docs/usage-envelopes.md), [Signing in the page](docs/usage-embedded.md), [Webhooks](docs/usage-webhooks.md)
- [Identity checks](docs/usage-identity.md) and [tenant files](docs/usage-tenant-files.md) (since 1.1), with what a site must not keep
- [Configuration reference](docs/configuration.md), [Errors](docs/errors.md), [Testing](docs/testing.md)
- [The Docker harness: bare PHP, the console](docs/harness.md), [Writing a gateway](docs/providers.md)
- [Usage policies](docs/policies.md): each provider's limits and rights, the guard, a site's tightening (omnisense/contracts)
- [Verified, and not](docs/verified.md)

## Plain PHP

```sh
composer require omnisense/omnisign omnisign/docuseal
```

```php
require __DIR__.'/vendor/autoload.php';

use Omnisign\Docuseal\DocusealGatewayFactory;
use Omnisign\Model\{Envelope, Signer};
use Omnisign\Registry;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

// DocuSeal's answer to the creation, recorded: with an instance of your own, drop $http (and give a real key).
$http = new MockHttpClient(new MockResponse('[{"id":11,"submission_id":4,"external_id":"tenant","status":"awaiting","slug":"abc"}]'));
$registry = new Registry([new DocusealGatewayFactory($http)], [
    'contracts' => ['factory' => 'docuseal', 'options' => ['url' => 'https://sign.example.org/api', 'api_key' => getenv('DOCUSEAL_API_KEY') ?: 'not-a-real-key']],
]);
$gateway = $registry->get('contracts');
$envelope = $gateway->create(new Envelope('Bail', signers: [new Signer('tenant', 'Camille Érable', 'camille@erable.example')], embedded: true, template: '1'));
echo $envelope->reference, ' ', $envelope->status->value, "\n";      // 4 sent
echo $gateway->signingUrl($envelope, 'tenant')->url, "\n";          // https://sign.example.org/s/abc
```

Every gateway needs an account or an instance of its own: DocuSeal an instance (self-hosted, open
source, or docuseal.com) and its API key, Yousign and DocuSign an account, Stripe Identity a Stripe
account, DossierFacile a partner account. The call above runs without one, on DocuSeal's answer
recorded. No bundle, no container: a factory per gateway package, the registry built by hand.

## Symfony

`Omnisign\Bridge\Symfony\OmnisignBundle` does that wiring ([docs/symfony.md](docs/symfony.md));
its components are not required by this package.

```yaml
omnisign:
    gateways:
        contracts: { factory: docuseal, options: { url: 'https://sign.example.org/api', api_key: '%env(DOCUSEAL_API_KEY)%' } }
```

## Docker

```sh
cd docker && cp .env.dist .env
docker compose run --rm omnisign gateways
docker compose run --rm omnisign bare          # plain PHP: no bundle, no container, and what PHP loaded
docker compose run --rm omnisign test
```

## Responsibility

`omnisense/omnisign` and its `omnisign/*` providers are independent, unofficial clients: none is
affiliated with, endorsed by or sponsored by the services it reaches. Each service keeps its own
terms (accounts and contracts, keys, rate limits), named with a link in the "Responsibility" section
of the README of each provider that reaches one, and declared in its `policy.json`
([usage policies](docs/policies.md)). The MIT licence covers this code only and grants no
right to any service or to its data.

Whoever installs and runs these packages is responsible for complying with those terms, for
obtaining whatever they require, and for any use made of what they return. The authors publish them
free of charge, make no commercial use of them, and accept no liability for how they are used (see
[LICENSE](LICENSE)).

License: MIT since 2026-10-09; earlier versions remain published under LGPL-3.0-or-later.
