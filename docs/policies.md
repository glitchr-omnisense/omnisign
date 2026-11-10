# Usage policies

Since the migration onto [omnisense/contracts](https://github.com/glitchr-omnisense/contracts)
(the kernel every omni family builds on), each gateway carries its provider's **usage policy**:
its rate limits, what its terms allow (commercial use, storage, caching, redistribution,
attribution) and what a request needs (a key), declared as data and enforced by a guard.

## Where a policy comes from

Each provider package ships a `policy.json` in the schema of omnisense/contracts
(`resources/usage-policy.schema.json`) and names it in its `composer.json`
(`extra.omnisense.policy`): `vendor/bin/omnisense-policies` prints every one an application has
installed. Its factory gives it to the gateway's `Capabilities` (`policy:`), and
`Capabilities::policy()` reads it. A gateway written before that (an application's own) gets a
policy made of its `Terms` (`Terms::policy()`: commercial use, a partnership as a contract, the
retention as caching's longest time to live, the attribution, the price and the rest in the
notes), or nothing known. `Capabilities::$terms` stays, read from the policy when only the policy
is given.

| Gateway | Rate limits | Rights | Sources (read 2026-11-10) |
|---|---|---|---|
| `yousign` | 60 a minute and 1,200 an hour (sandbox: 30 and 200, declared when `sandbox` is true) | a paid API plan (`contract`); the rest unknown | developers.youtrust.com, rate limits and limits |
| `docusign` | 3,000 an hour per account; a burst of 500 in 30 seconds (developer environment: 200, declared when `sandbox` is true) | a paid plan and the Go-Live review (`contract`); one status poll per envelope every 15 minutes, in the notes | DocuSign's rules and limits, developer terms |
| `docuseal` | unknown: none published (an instance of one's own sets its own) | unknown | DocuSeal's API reference |
| `stripe-identity` | 25 a second; reads at least 10,000 a month (the floor of 500 per transaction); 50 in flight | commercial allowed; reselling forbidden; EUR 1.25 a verification, the first 50 free, in the notes | Stripe's rate limits, Identity's page and use cases |
| `dossierfacile` | unknown: none published | partners only (`contract`); kept three months at most (caching 89 days, the shortest three months); the Connect button and its text required; never resold | DossierFacile's partner portal |

What the terms do not say is `unknown`, never guessed; each file's `provenance.notes` says the
rest in words: the per-envelope polling rule (not expressible as a limit), the quotas on files and
signers, the banned uses, what must not be kept.

## The guard

A gateway built by its factory runs behind its `PolicyGuard`. Each request that reaches the
provider is an operation named after its typed method - `create`, `send`, `fetch`, `remind`,
`cancel`, `download`, `signingUrl`, `startVerification`, `fetchVerification`,
`verificationResult`, `cancelVerification`, `redactVerification`, `connectTenantFile`,
`fetchTenantFile` (`Gateway::operation()`) - checked against the policy's limits and the
provider's wait before it is made, counted after. A webhook read (`notify()`, `notifyIdentity()`,
`notifyTenantFile()`) and the consent page's address (`tenantFileConnectUrl()`) reach nothing and
are not counted.

- A limit reached: `Exception\UsageLimitReachedException` (an `OmnisignException`, with
  `retryAfter`), the request never made.
- A 429 with `Retry-After`, or a `RateLimit` field with nothing left: the same exception, and every
  call of the gateway held until the wait is over (`Http\Answer::send()` given the provider's
  `Backoff`).
- Without a Backoff (an application's own client calling `Answer::send()` as before), a 429 is
  still an `UnreachableException`.

## A site's configuration

```php
$registry = new Registry($factories, [
    'kyc' => [
        'factory' => 'stripe-identity',
        'options' => ['secret_key' => '...'],
        'policy' => ['rateLimits' => [['max' => 200, 'window' => 'month', 'operations' => ['startVerification']]]],
        'usage' => ['commercial' => true],
    ],
], $limiter);
```

- `policy` tightens the declared policy, never loosens it: a limit counting the same calls with a
  higher maximum, or a permission less strict, is refused (`InvalidPolicyException`).
- `usage` says what the site is; a use the policy forbids fails when the gateway is built
  (`UsageNotPermitted`): DossierFacile and the paid signature services ask
  `'usage' => ['commercial' => true, 'contract' => true]` of a business.
- The third argument is where calls are counted: an in-memory limiter by default, a shared one
  (omnibridge/symfony's, over Symfony's rate limiter) for an application served by many processes.
- `Registry::guard()`, `policy()` and `policies()` say how each gateway is run; a credits or terms
  page can list `policies()`. In Symfony, each gateway entry takes `policy` and `usage` beside
  `options`.
