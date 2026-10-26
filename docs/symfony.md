---
title: Symfony
order: 4
---

# Symfony

Omnisign runs without a framework ([installation](installation.md)); in a Symfony application its
bundle does the wiring. Its components - `symfony/config`, `symfony/dependency-injection`,
`symfony/http-kernel` - are not required by `omnisense/omnisign`: the application has them, and
nothing of them is loaded outside Symfony.

Register `Omnisign\Bridge\Symfony\OmnisignBundle` (no Flex recipe):

```php
// config/bundles.php
return [
    // ...
    Omnisign\Bridge\Symfony\OmnisignBundle::class => ['all' => true],
];
```

```yaml
# config/packages/omnisign.yaml
omnisign:
    gateways:                      # by name: a factory and its options
        contracts:
            factory: docuseal
            options: { url: '%env(DOCUSEAL_URL)%', api_key: '%env(DOCUSEAL_API_KEY)%', webhook_secret: '%env(default::DOCUSEAL_WEBHOOK_SECRET)%' }
        qualified:
            factory: yousign
            options: { api_key: '%env(YOUSIGN_API_KEY)%', sandbox: '%env(bool:YOUSIGN_SANDBOX)%' }
```

Every `omnisign/*` package installed registers its factory, **autowired**: the application's
`http_client` is given to it.

What is autowired:

| Service | |
|---|---|
| `GatewayInterface $contracts`, `GatewayInterface $qualified` | one gateway by the argument's name (the configured name) |
| `Registry` | every configured gateway by name (`get()`, `has()`, `names()`, `options()`, `create()`) |

Nothing is built when the container compiles: a gateway is built the first time it is asked for,
and an option left empty only shows then (`InvalidConfigException`). An application's own gateway
- a class implementing `GatewayFactoryInterface` - is registered too, autoconfigured.

## A provider's webhook

```php
#[Route('/signatures/webhook', methods: ['POST'])]
public function __invoke(Request $request, GatewayInterface $contracts): Response
{
    try {
        $notification = $contracts->notify($request->getContent(), $request->headers->all());
    } catch (InvalidNotificationException) {
        return new Response('', 400);
    }
    $this->bus->dispatch(new FetchEnvelope($notification->reference));   // fetch, then download once completed
    return new Response('', 200);
}
```
