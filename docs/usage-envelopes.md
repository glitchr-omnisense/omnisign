---
title: Envelopes, from creation to download
order: 31
---

# Envelopes, from creation to download

The examples of these usage pages come from one script per gateway, run on 2026-10-26 on the
packages' checkouts (PHP 8.4, no network) with a `MockHttpClient` serving the packages' recorded
answers (`docker/harness/config/recorded.php`, the gateways' `Tests/Fixtures`); the `>` lines are the
mock's log of each request. The answers are fixtures: the ids and dates are theirs, and they answer
"completed" as soon as asked.

## An envelope

```php
use Omnisign\Model\Document;
use Omnisign\Model\Envelope;
use Omnisign\Model\Field;
use Omnisign\Model\FieldType;
use Omnisign\Model\File;
use Omnisign\Model\Level;
use Omnisign\Model\Signer;

$envelope = new Envelope(
    'Bail - 12 rue des Juifs',
    documents: [new Document('lease', new File("%PDF-1.7\n...", 'lease.pdf'))],
    signers: [new Signer('landlord', 'Marie-Anne de La Tour', 'landlord@example.org', '+33612345678')],
    fields: [
        new Field('landlord', 'lease', FieldType::SIGNATURE, page: 2, x: 77, y: 581),
        new Field('landlord', 'lease', FieldType::MENTION, page: 2, x: 77, y: 540, label: 'Lu et approuvé'),
    ],
    level: Level::ADVANCED,
    expiresAt: new DateTimeImmutable('2026-11-30', new DateTimeZone('Europe/Paris')),
    redirectUrl: 'https://site.example/lease/42/signed',
    key: 'lease-42',
);
```

Keys tie it together: a field names its signer and its document by key, and the provider's answers
come back as `states` by signer key.

## Create, then send

```php
$envelope = $gateway->create($envelope);      // keep $envelope->reference, and $envelope->states
$envelope = $gateway->send($envelope);
```

On Yousign (`api_key`, `sandbox: true`):

```
create
  > POST https://api-sandbox.yousign.app/v3/signature_requests  {"name":"Bail - 12 rue des Juifs","delivery_mode":"email","ordered_signers":false,"expiration_date":"2026-11-30","timezone":"Europe\/Paris","external_id":"lease-42"}
  > POST https://api-sandbox.yousign.app/v3/signature_requests/9a2b1c3d-0e4f-4a5b-8c6d-7e8f9a0b1c2d/documents
  > POST https://api-sandbox.yousign.app/v3/signature_requests/9a2b1c3d-0e4f-4a5b-8c6d-7e8f9a0b1c2d/signers  {"info":{"first_name":"Marie-Anne de La","last_name":"Tour","email":"landlord@example.org","phone_number":"+33612345678","locale":"fr"},"signature_level":"advanced_electronic_signature","fields":[{"document_id":"d0c1a2b3-c4d5-4e6f-8a7b-9c0d1e2f3a4b","page":2,"x":77,"y":581,"type":"signature"},{"document_id":"d0c1a2b3-c4d5-4e6f-8a7b-9c0d1e2f3a4b","page":2,"x":77,"y":540,"type":"mention","mention":"Lu et approuvé"}],"redirect_urls":{"success":"https:\/\/site.example\/lease\/42\/signed"}}
  9a2b1c3d-0e4f-4a5b-8c6d-7e8f9a0b1c2d draft landlord=5e1a0b2c-3d4e-4f5a-8b6c-7d8e9f0a1b2c waiting
send
  > POST https://api-sandbox.yousign.app/v3/signature_requests/9a2b1c3d-0e4f-4a5b-8c6d-7e8f9a0b1c2d/activate
  sent landlord notified
```

| Gateway | `create()` | `send()` |
|---|---|---|
| `yousign` | a draft, in one call for the signature request, one per document (multipart; `nature` `signable_document` or `attachment`) and one per signer with their fields. Status `DRAFT` | `POST .../activate`: `SENT`; embedded, each signer's `signature_link` comes back |
| `docusign` | `POST /envelopes`, status `created`: the documents in base64 (`documentId` 1, 2...), the signers (`recipientId` 1, 2..., `routingOrder` their order when ordered, `clientUserId` their key when embedded) and their tabs. Status `DRAFT` | `PUT /envelopes/{id}` status `sent`, then a `GET ?include=recipients` to read it back |
| `docuseal` | **sent at once**: from a template (`POST /submissions`, `template_id`) or from the documents (`POST /submissions/pdf`, DocuSeal Pro and its cloud); e-mails, or none when embedded. Status `SENT` | reads it back (`GET /submissions/{id}`) |

What each one checks first (`InvalidConfigException`, nothing sent): Yousign and DocuSign want a
document and a signer; DocuSeal a signer, and a template or documents; DocuSign and DocuSeal refuse
a level other than simple; DocuSign refuses a signer `authentication` other than none, Yousign one
other than email, sms or none; Yousign places signature, mention and text fields only; a field that
points at no document is refused (Yousign, DocuSign); on a DocuSeal template each signer but the
first names the `role` they take.

Yousign asks a code by e-mail (`otp_email`) of every signer of a simple-level envelope that sets no
`authentication`.

## Fetch

```php
$envelope = $gateway->fetch($envelope);
$envelope->status;                      // Status::COMPLETED
$envelope->state('landlord')->status;   // SignerStatus::SIGNED
```

```
fetch
  > GET https://api-sandbox.yousign.app/v3/signature_requests/9a2b1c3d-0e4f-4a5b-8c6d-7e8f9a0b1c2d
  completed landlord signed, completed 2026-10-09T17:30:00+00:00
```

Each provider's statuses as the family's:

| Family | Yousign | DocuSign | DocuSeal |
|---|---|---|---|
| `DRAFT` | `draft` | `created` | - |
| `SENT` | `ongoing`, `approval`, `paused` | `sent`, `delivered`, `signed` | `pending` |
| `COMPLETED` | `done` | `completed` | `completed` |
| `DECLINED` | `declined`, `rejected` | `declined` | `declined` |
| `EXPIRED` | `expired` | `voided` with a reason containing "expired" | `expired` |
| `CANCELED` | `canceled`, `deleted` | `voided` | archived (`archived_at`) |
| `UNKNOWN` | anything else | anything else | anything else |

| Signer | Yousign | DocuSign | DocuSeal |
|---|---|---|---|
| `WAITING` | `initiated` | `created` | `awaiting` |
| `NOTIFIED` | `notified` | `sent` | `sent` |
| `OPENED` | `verified`, `processing`, `consent_given` | `delivered` | `opened` |
| `SIGNED` | `signed` | `signed`, `completed` | `completed` |
| `DECLINED` | `declined` | `declined` | `declined` |
| `FAILED` | `aborted`, `error` | `autoresponded` | - |

A provider status not listed keeps the signer's previous state. A signer's `signedAt` is
DocuSign's `signedDateTime` and DocuSeal's `completed_at`; Yousign's provider sets none.

## Remind, cancel

```php
$gateway->remind($envelope);               // every signer not signed yet
$gateway->remind($envelope, 'landlord');   // one, by key
$gateway->cancel($envelope, 'The lease was withdrawn.');
```

- **Remind**: Yousign `POST .../signers/{id}/send_reminder` for each signer whose state is
  `NOTIFIED`; DocuSign one `PUT .../recipients?resend_envelope=true` with the signers neither
  signed nor declined (none: no call); DocuSeal `PUT /submitters/{id}` with `send_email: true` for
  each signer neither signed nor declined. The envelope is answered as given: `fetch()` it.
- **Cancel**: Yousign `POST .../cancel` (reason `other`, the text as `custom_note`, 500 characters
  at most) and reads the answer; DocuSign `PUT /envelopes/{id}` status `voided` with
  `voidedReason` (the text, 200 characters at most, `Cancelled by the sender` without one);
  DocuSeal `DELETE /submissions/{id}` (archived). The envelope comes back `CANCELED`.

## Download

```php
$download = $gateway->download($envelope);
foreach ($download->documents as $file) {
    $vault->store($envelope->reference, $file->filename, $file->content);
}
$vault->store($envelope->reference, $download->evidence->filename, $download->evidence->content);
```

```
download
  > GET https://api-sandbox.yousign.app/v3/signature_requests/9a2b1c3d-0e4f-4a5b-8c6d-7e8f9a0b1c2d/documents/download?version=completed&archive=false
  > GET https://api-sandbox.yousign.app/v3/signature_requests/9a2b1c3d-0e4f-4a5b-8c6d-7e8f9a0b1c2d/audit_trails/download?merge=true
  lease.pdf application/pdf, evidence audit-trail.pdf application/pdf
```

| Gateway | Documents | Evidence |
|---|---|---|
| `yousign` | one file: the signed PDF named as the envelope's first document, or `signed-documents.zip` when there are several (`archive=true`); its type from the answer | `audit-trail.pdf` (`merge=true`) |
| `docusign` | `signed.pdf`: the combined documents (`certificate=false`) | `certificate-of-completion.pdf` |
| `docuseal` | each document's URL fetched, named after it (`.pdf` added) - the API key sent only to the API's own host | `audit-log.pdf` (the `audit_log_url`), when given |

DocuSeal refuses a submission that is not completed (`ProviderException`: `The submission is
pending: its signed documents come once it is completed.`); Yousign and DocuSign ask the provider
whatever the status, and pass its refusal on.

The gateway keeps none of it: store the documents and the evidence in the application's vault.
