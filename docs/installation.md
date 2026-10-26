---
title: Installation
order: 10
---

# Installation and a first envelope

```sh
composer require omnisense/omnisign omnisign/docuseal   # an instance of one's own: nothing leaves for a third party
composer require omnisign/yousign                        # simple, advanced, qualified
composer require omnisign/docusign
```

## Requirements

- PHP 8.2 or later, with `mbstring` (titles and error messages are cut with `mb_substr()`).
- `omnisense/omnisign` requires `symfony/http-client-contracts` (^3.0) and nothing else.
- Each gateway requires `omnisense/omnisign` and `symfony/http-client` (6.4, 7 or 8), and takes the
  application's client when it is given one; `omnisign/docusign` also requires `ext-openssl` (it
  signs its JWT with an RSA key).
- For the Symfony bundle: `symfony/config`, `symfony/dependency-injection`, `symfony/http-kernel`
  (6.4, 7 or 8), suggested, not required.

> [!WARNING]
> Known defect: `composer.json` does not require `ext-mbstring`, where `Http\Answer` and the gateways call `mb_substr()`: on a PHP built without it, an error answer is a fatal error.

Omnisign runs the same in plain PHP, in a worker, in Laravel or Slim, and in Symfony, where a bundle
does the wiring ([Symfony](symfony.md)).

## Plain PHP

```php
<?php // sign.php

require __DIR__.'/vendor/autoload.php';

use Omnisign\Docuseal\DocusealGatewayFactory;
use Omnisign\Model\Envelope;
use Omnisign\Model\Signer;
use Omnisign\Registry;

$registry = new Registry([new DocusealGatewayFactory()], [
    'contracts' => ['factory' => 'docuseal', 'options' => ['url' => 'http://localhost:13300/api', 'api_key' => getenv('DOCUSEAL_API_KEY')]],
]);
$gateway = $registry->get('contracts');
printf("third party: %s\n", $gateway->capabilities()->thirdParty ? 'yes' : 'no');

// A lease from the instance's template 1, the tenant signing in the application's page.
$envelope = $gateway->create(new Envelope('Bail - harness', signers: [new Signer('tenant', 'Camille Érable', 'camille@erable.example')], embedded: true, template: '1', key: 'lease-42'));
printf("created: submission %s, %s; tenant %s\n", $envelope->reference, $envelope->status->value, $envelope->state('tenant')->status->value);
printf("signing url: %s\n", $gateway->signingUrl($envelope, 'tenant')->url);

// ... the tenant signs ...
$envelope = $gateway->fetch($envelope);
printf("fetched: %s; tenant %s at %s\n", $envelope->status->value, $envelope->state('tenant')->status->value, $envelope->state('tenant')->signedAt?->format(DATE_ATOM));
$download = $gateway->download($envelope);
foreach ($download->documents as $file) {
    printf("signed: %s, %d bytes\n", $file->filename, strlen($file->content));
}
printf("evidence: %s, %d bytes\n", $download->evidence->filename, strlen($download->evidence->content));
```

```
$ php sign.php
third party: no
created: submission 2, sent; tenant waiting
signing url: http://localhost:13300/s/RKDjxDp8cpxaBA
fetched: completed; tenant signed at 2026-10-08T01:14:25+00:00
signed: lease.pdf, 17829 bytes
evidence: audit-log.pdf, 54542 bytes
```

(2026-10-08, against a DocuSeal instance of the open-source edition run in Docker; the tenant's
part done through DocuSeal's API. Not run again for this manual: no instance was running on
2026-10-26; the same calls on the recorded answers are in [Envelopes](usage-envelopes.md).)

That is all there is to it:

- a **factory** per gateway package, which takes the application's HTTP client when given one;
- the **registry**, built by hand from the factories and the gateways' options, by name;
- the **gateways** it gives, each answering the same questions ([gateways](gateways.md)).

No class of a framework is loaded on the way - a test of this package checks it in a process of
its own (`Tests/BareTest.php`), and so does `docker compose run --rm omnisign bare`
([harness](harness.md)).

## What the application does around a gateway

1. **Decide** who signs what, and at which level: a lease, a quote, a surety.
2. **Create** the envelope and **keep its reference** with the application's own record; **send**
   it.
3. Let each signer sign - the provider's e-mail, or `signingUrl()` in the application's page
   (`embedded: true`), asked again each time.
4. **Follow** it: `fetch()`, or the provider's webhook (`notify()`); `remind()` who is late,
   `cancel()` what is withdrawn.
5. Once `COMPLETED`, **download** the signed documents and their evidence, and store them: the
   provider is not the archive.

## In a framework

- **Symfony**: `Omnisign\Bridge\Symfony\OmnisignBundle` registers the gateway factories on the
  application's `http_client`, builds the registry from `config/packages/omnisign.yaml` and makes
  each gateway injectable by its name: [Symfony](symfony.md).
- **Laravel**: there is no bridge package; a service provider builds the registry once:
  [Laravel](laravel.md).
- **Any other**: build the `Registry` once, where the framework builds its services.

## Errors

The whole list, and what each gateway maps to which: [Errors](errors.md).

| Exception | When |
|---|---|
| `NotSupportedException` | the gateway does not do that: `supports()` says so beforehand |
| `InvalidConfigException` | a gateway not configured, a factory not installed, an option missing; an envelope the provider cannot take (a level, a field, a template missing) |
| `InvalidKeyException` | the provider refused the application's credentials |
| `UnreachableException` | the provider did not answer |
| `InvalidNotificationException` | a callback whose signature does not hold, or too old |
| `ProviderException` | the provider refused the request (the three above extend it) |

All implement `Omnisign\Exception\OmnisignException`.
