---
title: Gateways
order: 3
---

# Gateways

One contract, `Omnisign\GatewayInterface`:

| Method | Request | Answers |
|---|---|---|
| `create(Envelope)` | `Request\Create` | the envelope with its `reference`: keep it |
| `send(Envelope)` | `Request\Send` | `SENT`: the signers are asked |
| `fetch(Envelope)` | `Request\Fetch` | its status, each signer's |
| `remind(Envelope, ?signer)` | `Request\Remind` | who has not signed is asked again |
| `cancel(Envelope, ?reason)` | `Request\Cancel` | `CANCELED` |
| `download(Envelope)` | `Request\Download` | the signed documents and their evidence |
| `signingUrl(Envelope, signer, ?returnUrl)` | `Request\SigningUrl` | where one signer signs in the page, now |
| `notify($body, $headers)` | `Request\Notify` | a `Notification`, once the callback's signature holds |

| | `omnisign/yousign` | `omnisign/docusign` | `omnisign/docuseal` |
|---|---|---|---|
| Levels | simple, advanced, qualified | simple | simple |
| `create` | a draft: request, documents, signers and fields | an envelope, status `created` | sent at once: from a template (open source) or from PDFs (Pro, cloud) |
| `send` | activate | status `sent` | reads it back |
| `signingUrl` | the signer's `signature_link` | a recipient view (`clientUserId`) | the form `/s/<slug>` |
| `remind` | `send_reminder` per signer | `resend_envelope` | `send_email` per submitter |
| `cancel` | cancel (reason `other`) | void | archive |
| `download` | the signed PDF (a ZIP when several), the audit trail | the combined PDF, the certificate of completion | the documents, the audit log |
| `notify` | `X-Yousign-Signature-256` | `X-DocuSign-Signature-n` (Connect, JSON) | `X-Docuseal-Signature` |
| Needs | an API key | an integration, an RSA key, a user's consent, an account | an instance's URL and key |

## Signing in the page

```php
$envelope = $gateway->send($gateway->create(new Envelope('Bail', $documents, $signers, $fields, embedded: true, redirectUrl: $back)));
$url = $gateway->signingUrl($envelope, 'tenant', $back)->url;   // a frame, or a redirection - asked each time
```

The provider is a third party (but a DocuSeal of one's own): name it, and allow it in the page's
Content-Security-Policy (`frame-src`).

## Writing a gateway

A package `omnisign/<provider>`: a factory extending `Omnisign\GatewayFactory` that fills a
`Config` - its name, title, capabilities, the options it needs, its client (`omnisign.api`) and one
action per request it answers - exactly as the three here do. Call the provider through
`Omnisign\Http\Answer::send()`, build an upload with `Omnisign\Http\Multipart`, and leave out what
the provider's documentation does not show.
