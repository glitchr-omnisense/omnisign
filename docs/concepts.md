---
title: Concepts
order: 20
---

# Concepts

## Gateways

A gateway is one way to have documents signed, configured: `Omnisign\GatewayInterface`.

| Method | Request | Answers |
|---|---|---|
| `getName(): string` | - | the factory's name: `yousign`, `docusign`, `docuseal` |
| `getTitle(): string` | - | `Yousign`, `DocuSign`, `DocuSeal` |
| `capabilities(): Capabilities` | - | what its signatures are: levels, in the page, in turn, through whom |
| `supports(string $request): bool` | - | whether it answers that request class |
| `execute(Request $request): Request` | any | the same request, answered |
| `create(Envelope $envelope): Envelope` | `Request\Create` | the envelope with its `reference` and each signer's state: keep them |
| `send(Envelope $envelope): Envelope` | `Request\Send` | sent: the signers are asked (or may sign, embedded) |
| `fetch(Envelope $envelope): Envelope` | `Request\Fetch` | as the provider has it now |
| `remind(Envelope $envelope, ?string $signer = null): Envelope` | `Request\Remind` | the envelope; who has not signed is asked again (one signer by key) |
| `cancel(Envelope $envelope, ?string $reason = null): Envelope` | `Request\Cancel` | `CANCELED` |
| `download(Envelope $envelope): Download` | `Request\Download` | the signed documents and the evidence |
| `signingUrl(Envelope $envelope, string $signer, ?string $returnUrl = null): SigningUrl` | `Request\SigningUrl` | where one signer signs in the application's page, now |
| `notify(string $body, array $headers = []): Notification` | `Request\Notify` | a webhook's call, its signature checked |

Each typed method is `execute(new Request\...)` and the request's result. A request no action
supports is a `NotSupportedException` (`The "x" gateway does not support Remind.`); `supports()`
says so beforehand, by asking each action about an instance of the class built without its
constructor. `Omnisign\Gateway` is the one implementation: `execute()` hands the request to the first
action that supports it; an action that returns without a result is a `ProviderException`.

## Requests

`Omnisign\Request\Request` is abstract: `setResult(mixed)`, `getResult()`, `isAnswered()`. The
requests, with their public `readonly` properties and their typed getter:

| Request | Properties | Result |
|---|---|---|
| `Create`, `Send`, `Fetch` | `envelope` (`Envelope`) | `getEnvelope(): ?Envelope` |
| `Remind` | `envelope`, `signer` (`?string`: a signer's key; null: all) | `getEnvelope(): ?Envelope` |
| `Cancel` | `envelope`, `reason` (`?string`) | `getEnvelope(): ?Envelope` |
| `Download` | `envelope` | `getDownload(): ?Model\Download` |
| `SigningUrl` | `envelope`, `signer` (`string`, a key), `returnUrl` (`?string`) | `getSigningUrl(): ?Model\SigningUrl` |
| `Notify` | `body` (`string`), `headers` (`array<string, string\|string[]>`) | `getNotification(): ?Notification`; `header(string $name): ?string` whatever the case it came in |

## Actions

`Omnisign\Action\ActionInterface`: `supports(Request $request): bool` and `execute(Request
$request): void`, which sets the request's result or throws an `OmnisignException`. An action that
works through the gateway's client implements `Action\ApiAwareInterface` (`setApi(object $api)`);
`Action\ApiAwareTrait` holds it in `$this->api` and refuses a client that is not of the class it
names in `$this->apiClass` (`InvalidConfigException`).

## Factories and their Config

`Omnisign\GatewayFactoryInterface`: `getName(): string` and `create(array $options = []):
GatewayInterface`. The packages extend `Omnisign\GatewayFactory`, whose `create()`:

1. fills a `Config` with the options given and the factory's `populateConfig()` defaults under them;
2. checks every key of `omnisign.required_options` is set and not empty (`InvalidConfigException`:
   `The "yousign" gateway needs: api_key.`);
3. builds the client: `omnisign.api`, a closure of the `Config` called now, or an object;
4. reads `omnisign.capabilities` (a `Capabilities`, or a closure giving one; missing:
   `InvalidConfigException`);
5. collects every `omnisign.action.<name>`: an `ActionInterface`, or a closure of the `Config` giving
   one - or null, which leaves the action out (that is how each gateway drops `notify` without its
   webhook secret); each `ApiAwareInterface` action is handed the client;
6. returns a `Gateway` of `omnisign.factory_name`, `omnisign.factory_title`, the capabilities and
   the actions.

Building calls nothing; a factory reached over HTTP takes its client in its own constructor.

`Omnisign\Config` is an `ArrayObject` of the options, with:

| Method | |
|---|---|
| `defaults(array $defaults): self` | sets each key not set yet |
| `validateNotEmpty(array $keys): void` | `InvalidConfigException` naming the keys unset, null, `''` or `[]` |
| `get(string $key, mixed $default = null)` | |
| `bool(string $key): bool` | a boolean, or `"1"`, `"true"`, `"yes"`, `"on"` (any case) as an environment variable gives it |
| `list(string $key): list<string>` | a list, each value split on commas and trimmed: `'a@x, b@x'` is two |

## The registry

`Omnisign\Registry`, built from the factories and the gateways' configuration by name:

| Method | |
|---|---|
| `get(string $name): GatewayInterface` | built once and kept. `No "x" gateway; configured: contracts.`, `No "x" factory for the "y" gateway; installed: yousign, docusign, docuseal.` |
| `create(string $name, array $overrides = []): GatewayInterface` | built afresh, the configured options with `$overrides` over them (an instance typed in a back office); not kept |
| `has(string $name): bool` | whether the name is configured |
| `options(string $name): array` | its configured options |
| `names(): list<string>`, `all(): array<string, GatewayInterface>` | |
| `factories(): list<string>` | the factories installed: what `factory` may name |

## HTTP helpers

- `Omnisign\Http\Answer::send(HttpClientInterface $http, string $provider, string $method, string
  $url, array $options = []): Answer` - one request, read whole, with a 10-second timeout unless the
  options give one. No connection, a timeout, a transport error, an HTTP 5xx or a 429 is an
  `UnreachableException` (as far as can be told, nothing was done: fetch, then try again); any
  other answer comes back as an `Answer`: `status`, `headers` (lower-case names), `body`,
  `header($name)`, `json()` (the body as a JSON object, or a `ProviderException`).
- `Omnisign\Http\Multipart::build(array $fields, array $files): array{body, contentType}` - a
  `multipart/form-data` body for a few fields and files (`content`, `filename`, `type`), its boundary
  random: no MIME library needed (Yousign's document upload).

The models these requests carry: [Models](models.md).
