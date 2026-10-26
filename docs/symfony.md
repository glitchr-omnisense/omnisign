---
title: Symfony
order: 15
---

# Symfony

Omnisign runs without a framework ([installation](installation.md)); in a Symfony application its
bundle does the wiring. Its components - `symfony/config`, `symfony/dependency-injection`,
`symfony/http-kernel` - are not required by `omnisense/omnisign`: the application has them, and
nothing of them is loaded outside Symfony. The bundle is part of the core
(`Omnisign\Bridge\Symfony\OmnisignBundle`); omnibridge has no Omnisign bridge, and none is needed.

## Registering the bundle

Register `Omnisign\Bridge\Symfony\OmnisignBundle` (no Flex recipe):

```php
// config/bundles.php
return [
    // ...
    Omnisign\Bridge\Symfony\OmnisignBundle::class => ['all' => true],
];
```

## Configuration

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

The whole tree, from the bundle's `configure()`:

| Key | Type | Default | |
|---|---|---|---|
| `omnisign.gateways` | map, keyed by the gateway's name | `{}` | the application's gateways |
| `omnisign.gateways.<name>.factory` | string, required, not empty | - | a factory's name: `yousign`, `docusign`, `docuseal`, or an application's own factory's `getName()` |
| `omnisign.gateways.<name>.options` | any value (a map) | `[]` | handed as they are to the factory's `create()` ([Configuration](configuration.md)) |

Processed with nothing set, the tree is `{"gateways": []}`; an empty factory is refused when the
container compiles (`The path "omnisign.gateways.x.factory" cannot contain an empty value, but got
"".`). The options are checked when the gateway is built, the first time it is asked for.

## Services

Every `omnisign/*` package installed registers its factory, **autowired**: the bundle knows
`Omnisign\Yousign\YousignGatewayFactory`, `Omnisign\Docusign\DocusignGatewayFactory` and
`Omnisign\Docuseal\DocusealGatewayFactory`, registers those whose class exists, and gives each the
application's `http_client` (null when it has none: the factory makes its own client).

| Service id | Class | Autowiring | |
|---|---|---|---|
| `Omnisign\Registry` | `Registry` | `Registry $registry` | every configured gateway by name: `get()`, `has()`, `names()`, `options()`, `all()`, `create()` with other options. Public. |
| `omnisign.gateway.<name>` | `GatewayInterface` | `GatewayInterface $<name>` | one gateway, built by `Registry::get('<name>')` the first time it is injected |
| the factories | their class | by their class | tagged `omnisign.gateway_factory` |

The argument's name selects the gateway: `GatewayInterface $contracts` is
`omnisign.gateway.contracts` (Symfony's named autowiring aliases).

Nothing is built when the container compiles: a gateway is built the first time it is asked for,
and an option left empty only shows then (`InvalidConfigException`). An application's own gateway
- a class implementing `GatewayFactoryInterface` - is registered too, autoconfigured (tagged
`omnisign.gateway_factory`), and its `getName()` can be named as a `factory`
([Writing a gateway](providers.md)). The bundle registers no command, event, route or Twig
function.

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

The route above is the application's: the bundle registers none. A callback sent twice has the same
`Notification::$id`: deduplicate on it ([Webhooks](usage-webhooks.md)).
