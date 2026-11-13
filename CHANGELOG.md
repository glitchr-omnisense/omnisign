# Changelog

## [Unreleased]

### Fixed

- Writes sent once: the Symfony bundle gives each gateway factory the application's `http_client`
  with its retries off (`Http\NoRetry`, over omnisense/contracts' `Http\NoRetry`), so
  Symfony's `RetryableHttpClient` (`retry_failed`) never sends an envelope, a verification or a
  cancellation twice. A `MockHttpClient` is kept as given. docs/symfony.md.

### Added

- 1.2, from omnibase/estate's guarantor's deed (2026-11-11): statements a signer types or
  acknowledges. `Model\StatementMode` (`TYPED`, `ACKNOWLEDGED`), `Model\StatementLimit` (a
  provider's documented limit, in characters or bytes, and its source), `Capabilities::$statements`,
  `statement()`, `fits()`; `Field::$maxLength`, `$statement`, `$statementMode`,
  `Field::forStatement()`, `mode()`. The gateway refuses, before any call, a statement its provider
  cannot carry. `FieldType::MENTION`'s docblock says Yousign writes its mention, the signer does not.

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
  through them; `phpstan` runs PHPStan at level max (`phpstan.neon`; its baseline, empty since: see
  Changed).
- Docs: [Identity checks](docs/usage-identity.md), [Tenant files](docs/usage-tenant-files.md), with
  what a site may keep and must not.
- The family on [omnisense/contracts](https://github.com/glitchr-omnisense/contracts), the kernel
  every omni family builds on, with usage policies. `Config`, `GatewayFactory`, `Registry` and
  `Http\Redaction` keep their names and API over the kernel's (prefix `omnisign`, the family's
  exceptions and messages); `GatewayInterface` and `GatewayFactoryInterface` extend its provider
  contracts; `OmnisignException` extends its `ExceptionInterface`.
- `Capabilities::policy()` (given as `policy:`, or made of `$terms` through `Terms::policy()`),
  `contracts()`; `$terms` read from a policy given alone (`Terms::fromPolicy()`); `toArray()` gives
  `policy`.
- Every request a gateway sends its provider passes its `PolicyGuard` (`Gateway::guard()`, the
  operation named after the typed method; webhooks and the consent page's address not counted):
  `UsageLimitReachedException` with `retryAfter`. `Http\Answer::send()` takes the provider's
  `Backoff`: a 429 and its `Retry-After` hold the next calls. `Registry` takes `policy` and `usage`
  per gateway and a shared limiter as its third argument; `guard()`, `policy()`, `policies()`. The
  bundle takes `policy` and `usage` beside `options`. [Usage policies](docs/policies.md).
- The harness installs omnisense/contracts from `OMNISENSE_CONTRACTS` (or GitHub) and runs its
  tests; it names its entrypoint and runs on the default bridge.

### Changed

- Requires `omnisense/contracts: ^1.0`: stable versions, so `composer require omnisense/omnisign` installs in a project at the default minimum-stability. `extra.branch-alias` names 1.x (`1.x-dev`); the CI (`.github/workflows/ci.yml`, glitchr-studio/ci's shared workflow) and the release on a tag (`release.yml`).
- The package passes its CI alone: require-dev gains `omnisign/docuseal` (the bare check and the bundle's tests sign through it) and `phpstan/phpstan`; PHPStan leaves the bundle's list of the gateway packages it knows, optional, to `class_exists()`. `Http\NoRetry` no longer checks that omnisense/contracts has its `Http\NoRetry` (1.0 has). The README's plain-PHP block is a whole first call, on DocuSeal's answer recorded.
- PHPStan at level max with an empty baseline: the 79 findings of the code before 1.1 are fixed.
  `Gateway`'s envelope methods (`create()` ... `notify()`) throw the gateway's `ProviderException`
  where an action answered no model, rather than returning null from a method typed not to.
- A provider's 429 is a `UsageLimitReachedException` (an `OmnisignException`), no longer an
  `UnreachableException`, when its gateway was built by its factory (every `omnisign/*` provider).

### Fixed

- An `UnreachableException` no longer carries the request's secrets. `Answer::send()` put the HTTP
  client's message, the whole URL with a key in its query, in its message, wrapped the client's own
  exception as its previous, and every trace of the chain kept the arguments of the calls under it
  (the query, headers and body with the key, when `zend.exception_ignore_args` is off). The message
  and the previous - now a copy of the client's exception, the same class and code - have the key's
  parameters, the URL's user part and the request's secrets masked as `***` (`Http\Redaction`),
  and no trace of the chain keeps its arguments, a 5xx's or a 429's included.
- composer.json requires `ext-mbstring`, which the package calls (`mb_*` functions): a PHP built without it failed with an undefined function.
