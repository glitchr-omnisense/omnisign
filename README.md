# glitchr/omnisign

One contract for **having documents signed** - whoever signs: Yousign, DocuSign, or a DocuSeal
instance of one's own.

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
| [`omnisign/yousign`](https://github.com/glitchr-studio/omnisign-yousign) | simple, advanced, qualified | yes | Yousign (Youtrust) |
| [`omnisign/docusign`](https://github.com/glitchr-studio/omnisign-docusign) | simple | yes | DocuSign |
| [`omnisign/docuseal`](https://github.com/glitchr-studio/omnisign-docuseal) | simple | yes | none when self-hosted |

## What a gateway is, and is not

A gateway has documents signed: it creates the envelope, sends it, says where it stands, gives
back the signed documents and the evidence of their signing. **It keeps nothing for the
application**: store what `download()` gives in your own vault, with the envelope's reference.

The levels are eIDAS's (simple, advanced, qualified): a gateway that cannot reach a level refuses
the envelope (`InvalidConfigException`), it never signs lower. A notary's authentic deed and a
lawyer's deed go through their professions' own tools, not this family. It is not a vault, nor a
template editor.

## Documentation

- [Installation and a first envelope](docs/installation.md)
- [Models](docs/models.md)
- [Gateways: the contract, what each does, writing one](docs/gateways.md)
- [Symfony](docs/symfony.md)
- [The Docker harness: bare PHP, the console](docs/harness.md)

## Plain PHP

```sh
composer require glitchr/omnisign omnisign/docuseal
```

```php
$registry = new Registry([new DocusealGatewayFactory()], [
    'contracts' => ['factory' => 'docuseal', 'options' => ['url' => 'https://sign.example.org/api', 'api_key' => getenv('DOCUSEAL_API_KEY')]],
]);
```

No bundle, no container: a factory per gateway package, the registry built by hand.

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

License: MIT since 2026-10-09; earlier versions remain published under LGPL-3.0-or-later.
