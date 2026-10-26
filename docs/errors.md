---
title: Errors
order: 50
---

# Errors

Every exception implements `Omnisign\Exception\OmnisignException` (an interface that extends
`\Throwable`), except the `\InvalidArgumentException` of `File::fromPath()` for a file it cannot read.

| Exception | Parent | Thrown when |
|---|---|---|
| `InvalidConfigException` | `\LogicException` | a gateway not configured, a factory not installed, a required option missing; a DocuSign key it cannot sign with; an envelope the provider cannot take (a level, a field type, a signer's authentication, no document, no signer, no role on a template); an envelope never created; a signer not in the envelope |
| `NotSupportedException` | `\LogicException` | the gateway does not do that request (`notify()` without its secret): `supports()` says so beforehand |
| `ProviderException` | `\RuntimeException` | the provider refused the request or answered with an error; an action left a request unanswered; DocuSeal's download of a submission not completed; no signature link on Yousign |
| `InvalidKeyException` | `ProviderException` | the provider refused the credentials: HTTP 401 or 403; DocuSign's JWT grant refused (`consent_required` when the user never consented) |
| `UnreachableException` | `ProviderException` | the provider did not answer: no connection, a timeout, a 5xx, a 429. As far as can be told nothing was done: fetch, then try again |
| `InvalidNotificationException` | `ProviderException` | a webhook's call that does not hold: no secret configured, a header missing or malformed, too old, a wrong signature, not JSON |

`ProviderException` and its children carry `provider` (`yousign`, `docusign`, `docuseal`) and
`providerCode` (`?string`: DocuSign's `errorCode`, the HTTP status, the JWT grant's `error`, or
null); the message is `[<provider>] <message>`, e.g. `[yousign] POST /signature_requests: HTTP 400,
<detail> (<param>: <reason>)`, `[docusign] GET /v2.1/accounts/<account>/envelopes/<id>: HTTP 404,
<errorCode> <message>`, `[docuseal] The API key was refused (HTTP 401): ...`.

## By gateway

| | `yousign` | `docusign` | `docuseal` |
|---|---|---|---|
| 401, 403 | `InvalidKeyException` | `InvalidKeyException` | `InvalidKeyException` |
| other 4xx | `ProviderException` (`detail`, `title` or `type`, and the `invalid_params`) | `ProviderException` (`errorCode` and `message`, `providerCode` the `errorCode`) | `ProviderException` (`error` or `message`) |
| the token | - | the JWT grant answering 400 or more, or without `access_token`: `InvalidKeyException`; the account id not among the user's accounts: `InvalidConfigException` | - |
| 5xx, 429, no answer | `UnreachableException` | `UnreachableException` | `UnreachableException` |
| a body that is not JSON | `ProviderException` | `ProviderException` | `ProviderException` |

Error messages carry at most 200 characters of a body that is not the provider's JSON error.

## What to catch

```php
use Omnisign\Exception\InvalidConfigException;
use Omnisign\Exception\InvalidKeyException;
use Omnisign\Exception\ProviderException;
use Omnisign\Exception\UnreachableException;

try {
    $envelope = $gateway->send($gateway->create($envelope));
} catch (UnreachableException) {
    // maybe nothing was done: fetch() if the envelope has a reference, then retry
} catch (InvalidKeyException) {
    // the credentials are refused: an administrator's problem
} catch (ProviderException $e) {
    // the provider refused this envelope: $e->providerCode, $e->getMessage()
} catch (InvalidConfigException $e) {
    // the envelope lacks what this gateway needs, or a configuration error
}
```

A signer who declines, or an envelope that expires, is not an exception: it is a status
(`DECLINED`, `EXPIRED`) of a later `fetch()` or a webhook.
