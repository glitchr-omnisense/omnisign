---
title: Signing in the page
order: 32
---

# Signing in the page

With `embedded: true`, the provider sends the signers nothing: the application shows each one the
provider's signing page - in a frame, or by a redirection - from `signingUrl()`.

```php
$envelope = $gateway->send($gateway->create(new Envelope('Devis 2026-042', $documents, $signers, $fields, embedded: true, redirectUrl: $back)));

$url = $gateway->signingUrl($envelope, 'client', $back)->url;    // made for this signer, now: ask again each time
```

| Gateway | What `embedded` changes at creation | `signingUrl()` |
|---|---|---|
| `yousign` | `delivery_mode: none` | the signer's `signature_link` from `GET .../signers`, with its expiry; none (not embedded, not activated) is a `ProviderException` |
| `docusign` | each signer's `clientUserId` is their key | `POST .../views/recipient` (`authenticationMethod: none`, the signer's e-mail, name and key); the return URL is the call's, else the envelope's `redirectUrl`, else an `InvalidConfigException`. An envelope not created embedded is refused |
| `docuseal` | `send_email: false` | the submitter's `embed_src`, or `<app_url>/s/<slug>`, from the creation's answer, else `GET /submitters/{id}` |

`returnUrl` is read by DocuSign only; Yousign and DocuSeal send the signer to the envelope's
`redirectUrl`. A signer not created with the envelope (no state) is an `InvalidConfigException`.

On DocuSeal and DocuSign, on the recorded answers:

```
docusign
  > POST https://account-d.docusign.com/oauth/token
  > POST https://demo.docusign.net/restapi/v2.1/accounts/0a1b2c3d-4e5f-4a6b-8c7d-9e0f1a2b3c4d/envelopes
  7c1d2e3f-4a5b-4c6d-8e7f-9a0b1c2d3e4f draft client=1 waiting
docuseal
  > POST http://localhost:13300/api/submissions
  4 sent tenant=4 waiting http://localhost:13300/s/KKjaQ1uucspGL5
```

The DocuSeal link came with the creation's answer: `signingUrl()` called nothing.

## In the page

The provider is a third party (but a DocuSeal instance of one's own: `capabilities()->thirdParty`
is false there): name it to the signer, and allow its host in the page's Content-Security-Policy
(`frame-src`). DocuSeal's form can also be its `docuseal-form` web component, whose `src` is this
URL. A DocuSign view is to be used at once (the action's own note): ask it right before showing it.
