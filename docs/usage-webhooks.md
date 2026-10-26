---
title: Webhooks
order: 33
---

# Webhooks

Each provider can call the application when an envelope moves. With the gateway's webhook secret
set, `notify()` checks the call's signature and reads it as one `Notification`:

```php
$notification = $gateway->notify($request->getContent(), $request->headers->all());

$notification->event;       // Event::SIGNED, COMPLETED...
$notification->type;        // the provider's own name for it: "signer.done"
$notification->reference;   // the envelope, as create() named it
$notification->signer;      // the provider's id of the signer (DocuSeal: their key)
$notification->id;          // the same for a callback sent twice: deduplicate on it
```

Without the secret, `notify()` is not supported (`NotSupportedException`). A call that does not hold
is an `InvalidNotificationException`: answer 400. Answer fast, and `fetch()` the envelope out of the
request - then `download()` once it is completed.

## The signatures

| Gateway | Option | Header | Checked |
|---|---|---|---|
| `yousign` | `webhook_secret` | `X-Yousign-Signature-256` | `sha256=` and the hexadecimal HMAC-SHA-256 of the raw body under the secret |
| `docusign` | `hmac_keys` (one or several, comma-separated) | `X-DocuSign-Signature-1`, `-2`... (up to 100) | any of them equal to the base64 HMAC-SHA-256 of the raw body under any key; the message must be JSON (configure Connect to send JSON) |
| `docuseal` | `webhook_secret` (and `webhook_tolerance`, 300 s) | `X-Docuseal-Signature` | `<timestamp>.<hexadecimal HMAC-SHA-256 of "<timestamp>.<body>">`, the timestamp within the tolerance (0: any age) |

Run with signatures computed as each provider does, then a forged one (Yousign):

```
notify
  signed signer.done 9a2b1c3d-0e4f-4a5b-8c6d-7e8f9a0b1c2d 5e1a0b2c-3d4e-4f5a-8b6c-7d8e9f0a1b2c 2026-10-26T09:00:00+00:00
  [yousign] The signature does not hold.
  notify: signed recipient-completed 7c1d2e3f-4a5b-4c6d-8e7f-9a0b1c2d3e4f 1 7c1d2e3f-4a5b-4c6d-8e7f-9a0b1c2d3e4f@recipient-completed@1@2026-10-26T09:00:00Z
  notify: signed form.completed 4 tenant form.completed@7@2026-10-26T09:00:00Z
```

(the second line is DocuSign's, the third DocuSeal's.)

## The events

| Event | Yousign `event_name` | DocuSign `event` | DocuSeal `event_type` |
|---|---|---|---|
| `SENT` | `signature_request.activated` | `envelope-sent` | `submission.created` |
| `OPENED` | `signer.link_opened` | `envelope-delivered`, `recipient-delivered` | `form.viewed`, `form.started` |
| `SIGNED` | `signer.done` | `recipient-completed` | `form.completed` |
| `DECLINED` | `signature_request.declined`, `signature_request.rejected`, `signer.declined` | `envelope-declined`, `recipient-declined` | `form.declined` |
| `COMPLETED` | `signature_request.done` | `envelope-completed` | `submission.completed` |
| `EXPIRED` | `signature_request.expired` | - | `submission.expired` |
| `CANCELED` | `signature_request.canceled` | `envelope-voided` | `submission.archived` |
| `OTHER` | anything else | anything else | anything else |

| Field | Yousign | DocuSign | DocuSeal |
|---|---|---|---|
| `reference` | `data.signature_request.id` | `data.envelopeId` | a form event: `data.submission_id`; else `data.id` |
| `signer` | `data.signer.id` | `data.recipientId` | a form event: `data.external_id` (the signer's key), else `data.id` |
| `at` | `event_time` (epoch seconds) | `generatedDateTime` | `timestamp` |
| `id` | `event_id` | `<envelopeId>@<event>@<recipientId>@<generatedDateTime>` | `<event_type>@<data.id>@<timestamp>` |
