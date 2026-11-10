# Changelog

## [Unreleased]

### Added

- Two contracts beside the signature's, for two more trust services: `IdentityCheckInterface`
  (`startVerification()`, `fetchVerification()`, `verificationResult()`, `cancelVerification()`,
  `redactVerification()`, `notifyIdentity()`; requests `Request\Identity\*`) and
  `TenantFileInterface` (`tenantFileConnectUrl()`, `connectTenantFile()`, `fetchTenantFile()`,
  `notifyTenantFile()`; requests `Request\TenantFile\*`). `Gateway` implements both; a gateway
  answers what its actions support, `NotSupportedException` otherwise.
- Models: `Verification`, `VerificationStatus`, `VerificationResult`, `Check`, `CheckKind`,
  `DocumentType`, `Person`, `IdentityEvent`, `IdentityNotification`; `TenantFile`,
  `TenantFileStatus`, `Applicant`, `TenantDocument`, `TenantFileEvent`, `TenantFileNotification`;
  `Terms` (a provider's own rules: retention, partners only, price, what must not be kept).
- `Capabilities`: `$contracts` (`SIGNATURE`, `IDENTITY_CHECK`, `TENANT_FILE`; default the signature
  only), `keeps()`, `$identityDocuments`, `$selfie`, `$liveness`, `$terms`; `toArray()` gives them.
- `Registry::identity()` and `Registry::tenantFiles()`: a gateway by its contract, or
  `NotSupportedException`. The bundle registers `omnisign/stripe-identity` and
  `omnisign/dossierfacile` when installed, and aliases each gateway as `IdentityCheckInterface $name`
  and `TenantFileInterface $name`.
- The harness: both providers, the bare script verifies an identity and obtains a tenant file
  through them; `phpstan` runs PHPStan at level max (`phpstan.neon`; the baseline holds the 79
  findings of the code before 1.1, none of the new code).
- Docs: [Identity checks](docs/usage-identity.md), [Tenant files](docs/usage-tenant-files.md), with
  what a site may keep and must not.

### Fixed

- An `UnreachableException` no longer carries the request's secrets. `Answer::send()` put the HTTP
  client's message, the whole URL with a key in its query, in its message, wrapped the client's own
  exception as its previous, and every trace of the chain kept the arguments of the calls under it
  (the query, headers and body with the key, when `zend.exception_ignore_args` is off). The message
  and the previous - now a copy of the client's exception, the same class and code - have the key's
  parameters, the URL's user part and the request's secrets masked as `***` (`Http\Redaction`),
  and no trace of the chain keeps its arguments, a 5xx's or a 429's included.
- composer.json requires `ext-mbstring`, which the package calls (`mb_*` functions): a PHP built without it failed with an undefined function.
