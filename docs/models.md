---
title: Models
order: 2
---

# Models

All models are `readonly`; times are `DateTimeImmutable`. They carry what a provider needs: files,
names, e-mails - never an account of the application's.

## Envelope

| Property | |
|---|---|
| `title` | what the signers see |
| `documents` | `list<Document>`: a `key` of the application's, a `File` (content, filename, MIME type), `signable` (false: attached for reading) |
| `signers` | `list<Signer>` |
| `fields` | `list<Field>`: where each signer signs |
| `level` | `Level::SIMPLE`, `ADVANCED`, `QUALIFIED` (eIDAS) |
| `ordered` | each signer in turn (`Signer::$order`), rather than all at once |
| `embedded` | the signers sign in the application's page (`signingUrl()`); the provider sends them nothing |
| `expiresAt`, `message`, `redirectUrl` | a deadline, a message to the signers, where they go once signed |
| `template` | a template of the provider's, instead of documents and fields (DocuSeal's open-source edition) |
| `key` | the application's own name for it, kept by providers that keep one |
| `reference` | the provider's, once created: keep it. `reference()` throws on an envelope never created |
| `status` | `Status::DRAFT`, `SENT`, `COMPLETED`, `DECLINED`, `EXPIRED`, `CANCELED`, `UNKNOWN`; `isOver()` |
| `states` | `array<string, SignerState>` by signer key |
| `documentReferences`, `completedAt`, `data` | the provider's ids of the documents, when it completed, what else it answered |

`with(reference, status, states, documentReferences, completedAt, data)`, `signer($key)`,
`state($key)`, `fieldsOf($signer)`.

## Signer

`key` (the application's name for them: what fields and states point at), `name`, `email`,
`phone`, `locale`, `order` (from 1), `authentication` (`none`, `email`, `sms`: a code before
signing, where the provider sends one), `role` (the role a provider's template knows them by).
`names()` splits the name: the last word is the last name.

## Field

`signer`, `document` (keys), `type` (`FieldType::SIGNATURE`, `INITIALS`, `DATE`, `TEXT`,
`MENTION`: "lu et approuvé"), `page`, `x`, `y` (from the page's top left corner, in the provider's
unit), `width`, `height`, `anchor` (a text of the document the provider looks for, where it can),
`label` (the mention's text, a text field's label). A provider that cannot place a type refuses
the envelope.

## SignerState

`key`, `reference` (the provider's id), `status` (`SignerStatus::WAITING`, `NOTIFIED`, `OPENED`,
`SIGNED`, `DECLINED`, `FAILED`), `signedAt`, `link` and `linkExpiresAt` (where they sign, when the
provider gave it), `reason` (why they declined).

## Download, SigningUrl, Notification

- `Download`: `documents` (`list<File>`: the signed documents, a ZIP when the provider packs
  several) and `evidence` (`File`: the audit trail, the certificate of completion).
- `SigningUrl`: `url`, `expiresAt` - made for one signer, now: ask again each time.
- `Notification`: `event` (`Event::SENT`, `OPENED`, `SIGNED`, `DECLINED`, `COMPLETED`, `EXPIRED`,
  `CANCELED`, `OTHER`), `type` (the provider's own), `reference`, `signer`, `at`, `id` (what a
  callback sent twice is recognised by), `data`.

## Capabilities

`levels`, `embedded` (signing in the page), `ordered`, `identityCheck` (an identity document
checked: advanced, qualified), `thirdParty`, `documents` (envelopes from the application's PDFs),
`templates` (from a provider's templates). `offers(Level)`, `toArray()`.
