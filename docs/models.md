---
title: Models
order: 21
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

Defaults: no documents, signers or fields; `Level::SIMPLE`; not ordered; not embedded; status
`DRAFT`; no states. Only `title` is required by the constructor: what each provider needs on top
(documents, signers, a template) it checks at `create()` (`InvalidConfigException`).

`with(reference, status, states, documentReferences, completedAt, data)` gives the envelope as the
provider has it now (each argument null keeps the envelope's); `signer($key)` the signer, or an
`InvalidConfigException` (`No signer "x" in the envelope "Bail".`); `state($key)` the signer's state
or null; `fieldsOf($signer)` the signer's fields; `reference()` the provider's reference, or an
`InvalidConfigException` (`The envelope "Bail" was never created on a provider: create() it
first.`) - what every action after `create()` calls.

`Status` values are lower case (`draft`, `sent`, `completed`, `declined`, `expired`, `canceled`,
`unknown`); `isOver()` is true for `COMPLETED`, `DECLINED`, `EXPIRED` and `CANCELED`.

## Document and File

`new Document(string $key, File $file, bool $signable = true)`: the application's key for it (what
fields point at), the file, and `signable` (false: attached for reading, not signed - Yousign uploads
it as an attachment; DocuSign and DocuSeal send it as any document).

`new File(string $content, string $filename, string $mimeType = 'application/pdf')`.
`File::fromPath($path, ?$mimeType)` reads a file (an `\InvalidArgumentException` when it cannot:
`Cannot read <path>.`), its MIME type `application/pdf` for a `.pdf`, `application/octet-stream`
otherwise; `sha256()`.

## Signer

`key` (the application's name for them: what fields and states point at), `name`, `email`,
`phone`, `locale`, `order` (from 1), `authentication` (`none`, `email`, `sms`: a code before
signing, where the provider sends one), `role` (the role a provider's template knows them by).
`names()` splits the name: the last word is the last name, the rest the first name - `Camille
Érable` is `Camille` and `Érable`, `Marie-Anne de La Tour` is `Marie-Anne de La` and `Tour` (what
Yousign receives); a single word is both. Defaults: `phone` null, `locale` `fr`, `order` 1,
`authentication` null, `role` null. Which provider reads what: [Gateways](gateways.md).

## Field

`signer`, `document` (keys), `type` (`FieldType::SIGNATURE`, `INITIALS`, `DATE`, `TEXT`,
`MENTION`: "lu et approuvé"), `page`, `x`, `y` (from the page's top left corner, in the provider's
unit), `width`, `height`, `anchor` (a text of the document the provider looks for, where it can),
`label` (the mention's text, a text field's label). Defaults: `SIGNATURE`, page 1, at 0, 0. A
provider that cannot place a type refuses the envelope. `FieldType` values: `signature`,
`initials`, `date`, `text`, `mention`.

## SignerState

`key`, `reference` (the provider's id), `status` (`SignerStatus::WAITING`, `NOTIFIED`, `OPENED`,
`SIGNED`, `DECLINED`, `FAILED`; values lower case; default `WAITING`), `signedAt`, `link` and `linkExpiresAt` (where they sign, when the
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
`templates` (from a provider's templates). Defaults: simple only, not embedded, ordered, no
identity check, third party, documents yes, templates no. `offers(Level)`, `toArray()` (the levels as
their values, and the other fields). `Level` values: `simple`, `advanced`, `qualified`.
