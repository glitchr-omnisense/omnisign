---
title: Identity checks
order: 34
---

# Identity checks

Since 1.1, Omnisign has a second contract beside the signature: `IdentityCheckInterface`
(`Capabilities::IDENTITY_CHECK`). Signing a document and verifying an identity are both eIDAS
trust services, and the same providers offer both; the family therefore carries both, with one
registry, one configuration, one bundle. A gateway that verifies identities may sign nothing
(`omnisign/stripe-identity` signs nothing: its `levels` are empty), and a signature gateway built
before 1.1 keeps the signature only.

| Package | Factory name | Documents | Selfie | Webhooks | Test mode |
|---|---|---|---|---|---|
| [`omnisign/stripe-identity`](https://github.com/glitchr-omnisense/omnisign-stripe-identity) | `stripe-identity` | passport, ID card, driving licence | matched against the document | with `webhook_secret` (`Stripe-Signature`) | a `sk_test_` key: nothing is really checked |
| `omnisign/veriff` | reserved | | | | |
| `omnisign/entrust` (ex-Onfido) | reserved, a contract with Entrust | | | | |

Yousign and DocuSign verify identities too, inside their signature flows (Yousign's verifications
API, DocuSign's ID Verification workflows); their packages do not carry it yet - see each
package's "Next steps".

## A session

```php
use Omnisign\Model\DocumentType;
use Omnisign\Model\Verification;

$kyc = $registry->identity('kyc');            // NotSupportedException when the gateway does not verify identities

$verification = $kyc->startVerification(new Verification(
    documents: [DocumentType::PASSPORT, DocumentType::ID_CARD],   // empty: the provider's default
    selfie: true,                                                 // a selfie matched against the document's photo
    liveCapture: true,                                            // the document photographed live, no file upload
    returnUrl: 'https://solstay.example/bookings/42/identity',
    key: 'booking-42',                                            // the application's handle: the provider's client reference
));
// keep: $verification->reference, ->status, ->key
// hand over, never store: $verification->url (the hosted flow), $verification->clientSecret (the provider's script in the page)

$verification = $kyc->fetchVerification($verification);   // PENDING (again, with ->error, after a failed attempt), PROCESSING, VERIFIED, CANCELED
$result = $kyc->verificationResult($verification);          // the checks performed
$result->verified();                                         // true when VERIFIED
$result->checks;                                             // [Check(DOCUMENT, passed: true), Check(SELFIE, passed: false, code: 'selfie_face_mismatch')]
$result->documentType;                                       // DocumentType::ID_CARD
$result->issuingCountry;                                     // 'FR'

$kyc->cancelVerification($verification);                     // before the person completes it
$kyc->redactVerification($verification);                     // the provider erases its personal data; the outcome stays
$kyc->notifyIdentity($request->getContent(), $request->headers->all());   // IdentityNotification: event, reference, key, id
```

`VerificationStatus`: `PENDING` waits for the person (the flow not started, or to be taken again
after a failed check: `Verification::$error` gives the provider's code and words), `PROCESSING` is
the provider's turn, `VERIFIED` and `FAILED` are outcomes, `CANCELED` and `EXPIRED` end it;
`isFinal()` says whether anything can still change. `Verification::with()` never carries the
flow's `url` nor its `clientSecret` over: each answer brings fresh ones or none.

## Personal data (GDPR)

An identity verification handles an identity document, a face and, for some checks, a national
number: special care under the GDPR, and some countries forbid keeping identity images at all. The
family is built so that a site keeps only the outcome:

| A site keeps | A site must not keep |
|---|---|
| the provider's reference, the status, the date | the session's `url` and `clientSecret` (single use, expiring, never logged) |
| the checks and their outcome (`VerificationResult::$checks`) | the document's and the selfie's images (the family never carries them, not even their file ids) |
| the document's type and issuing country | the document's number, expiry, the person's address |
| the application's key | personal data in `metadata`: identifiers only |

- `VerificationResult::$person` (first and last name, birth date) is empty unless the gateway is
  configured to ask for it (Stripe Identity's `person`, `birth_date`). Compare it with the
  account's and drop it; do not store it beside the account.
- `IdentityNotification` carries no payload: the event, the reference, the application's key and
  the event's id (to deduplicate) are all a webhook gives.
- Erase at the provider once the purpose is served: `redactVerification()`. Stripe keeps
  biometric identifiers up to a year and images and extracted data typically seven years, and does
  not delete on the site's behalf.
- Each provider's own rules are in `capabilities()->terms` (`Terms`: price, banned uses, what must
  not be kept, where they are written); the site's privacy notice says what it keeps and how long.

## Writing a gateway that verifies identities

Its factory declares `contracts: [Capabilities::IDENTITY_CHECK]` (with `SIGNATURE` too if it signs),
what its verifications read (`identityDocuments`, `selfie`, `liveness`), its `terms`, and one action
per request of `Omnisign\Request\Identity`: `Start`, `Fetch`, `Result`, `Cancel`, `Redact`,
`Notify`. The gateway (`Omnisign\Gateway`) answers the contract's methods through them; a request
without an action is a `NotSupportedException`, as for envelopes ([Writing a gateway](providers.md)).
