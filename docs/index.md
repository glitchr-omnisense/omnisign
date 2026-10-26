# omnisense/omnisign

Omnisign is one PHP contract for electronic signatures. An application asks every signature
service the same questions - create this envelope, send it, where does it stand, remind who is
late, withdraw it, give me the signed documents and their evidence, where does this signer sign in
my page, what is this webhook telling me - and gets the same answers back: an `Envelope` with its
reference and each signer's state, a `Download`, a `SigningUrl`, a `Notification`. Each service is a
package of its own: `omnisign/yousign`, `omnisign/docusign`, `omnisign/docuseal` (an instance of
one's own, or DocuSeal's cloud).

```php
$gateway = $registry->get('contracts');

$envelope = $gateway->create(new Envelope('Bail', $documents, $signers, $fields, level: Level::SIMPLE));   // keep $envelope->reference
$envelope = $gateway->send($envelope);                                    // the signers are asked
$url = $gateway->signingUrl($envelope, 'tenant', $back)->url;            // or: sign in the application's page
$envelope = $gateway->fetch($envelope);                                   // COMPLETED, each signer SIGNED
$download = $gateway->download($envelope);                                // the signed documents and the audit trail
$gateway->notify($request->getContent(), $request->headers->all());       // a webhook, its signature checked
```

The levels are eIDAS's (simple, advanced, qualified). A gateway has documents signed and keeps
nothing for the application: neither the documents nor their proof - the application stores what
`download()` gives it, in its own vault. It produces no PDF either: the documents are the
application's.

The design is Payum's, as in Omnibus and Omnibill: a gateway's **factory** fills a configuration
with its name, title, capabilities, options, its client (`omnisign.api`) and one **action** per
request it answers; the **gateway** runs the action that supports each request; a **registry** holds
the application's gateways by name. The core needs no framework: it requires
`symfony/http-client-contracts` and nothing else. A Symfony bundle is included ([Symfony](symfony.md)).

Published as `omnisense/omnisign`, formerly `glitchr/omnisign` and `omniscient/omnisign`: the package
`replace`s both names, so a `composer.json` that still requires one of them resolves to this package
and never installs two. The namespace is `Omnisign\`; the gateways keep the `omnisign/` vendor and the
`Omnisign\<Provider>\` namespace.

## When to use it

- A site that has leases, quotes, mandates or sureties signed, and wants to change signature service
  (or move from a cloud service to an instance of its own) without rewriting the code that sends
  them.
- Signing in the application's own page (embedded), the provider sending the signers nothing.
- A qualified or advanced signature where the law asks one (Yousign).

It is not a signature service itself, and not an archive: it does not seal documents, keep evidence,
or store anything.

## Gateways

Three packages. The operations are what each gateway's `supports()` answers with its required
options set (the harness's `bare` lists them: [harness](harness.md)); the capabilities are each
factory's `capabilities()`.

| Package | Factory name | Levels | In the page | Third party | Create | Send | Fetch | Remind | Cancel | Download | Signing URL | Notify |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| [`omnisign/yousign`](https://github.com/glitchr-omnisense/omnisign-yousign) | `yousign` | simple, advanced, qualified | yes | yes | yes (a draft) | yes | yes | yes | yes | yes | yes | with `webhook_secret` |
| [`omnisign/docusign`](https://github.com/glitchr-omnisense/omnisign-docusign) | `docusign` | simple | yes | yes | yes (a draft) | yes | yes | yes | yes | yes | yes (embedded only) | with `hmac_keys` |
| [`omnisign/docuseal`](https://github.com/glitchr-omnisense/omnisign-docuseal) | `docuseal` | simple | yes | no for an instance of one's own, yes for DocuSeal's cloud | yes (sent at once) | yes (reads it back) | yes | yes | yes | yes | yes | with `webhook_secret` |

DocuSeal alone was run against a real service (an instance of its open-source edition, in Docker);
Yousign and DocuSign were not ([Verified, and not](verified.md)).

## How this manual is organised

- [Installation](installation.md): requirements, a first envelope in plain PHP.
- [Symfony](symfony.md) and [Laravel](laravel.md): wiring the registry in a framework.
- [Concepts](concepts.md): gateways, requests and actions, factories, the registry, the HTTP helpers.
- [Models](models.md): envelopes, documents, signers, fields, states, every field.
- [Gateways](gateways.md): the contract and what each gateway does with it.
- Usage: [envelopes, from creation to download](usage-envelopes.md),
  [signing in the page](usage-embedded.md), [webhooks](usage-webhooks.md).
- [Configuration reference](configuration.md): every option of every factory.
- [Errors](errors.md): the exceptions and when each is thrown.
- [Testing](testing.md): testing an application that uses Omnisign.
- [The Docker harness](harness.md): every gateway installed, the bare script.
- [Writing a gateway](providers.md).
- [Verified, and not](verified.md).
