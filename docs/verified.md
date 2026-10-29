---
title: Verified, and not
order: 90
---

# Verified, and not

## What the tests prove

Run on 2026-10-26 on the four packages' checkouts (the workspace's autoloader), PHP 8.4.27, PHPUnit
11.5.57, no network: 27 tests, 150 assertions, green.

- The core (10 tests): a signer's names and an envelope's parts; statuses, files, capabilities and a
  multipart body; an envelope created, sent and fetched through a gateway; what a gateway does not do
  said beforehand and thrown; misconfiguration and an envelope never created; the Symfony bundle
  (gateways built and injectable by name, the packages installed registered with the application's
  client); the plain-PHP run with no framework class loaded and a lease through each gateway
  (`Tests/BareTest.php`).
- `omnisign/yousign` (6 tests), `omnisign/docusign` (5) and `omnisign/docuseal` (6): each operation
  on its fixtures, the webhooks' signatures, the errors mapped.

The examples of these pages were run on 2026-10-26 on the same checkouts, the providers answering
from the packages' fixtures through the harness's `recorded.php`.

## What was run live, and what was not

- **DocuSeal**: run for real on 2026-10-08 against an instance of the open-source edition in Docker
  - created from a template, the signing link, completed, downloaded, archived - its answers recorded
  as the tests' fixtures; the [installation](installation.md) script's output is that run's.
  `/submissions/pdf` (DocuSeal Pro and the cloud) is refused by an open-source instance and was not
  run.
- **Yousign** and **DocuSign**: not run against the services; their fixtures are written from the
  providers' documented shapes.
- The [Laravel](laravel.md) page was written from the registry's API; it was not run in a Laravel
  application.
- The known defect found while writing these pages, fixed since: `ext-mbstring` not required though
  `mb_substr()` is called (composer.json requires it).

## Responsibility

`omnisense/omnisign` and its `omnisign/*` gateways are independent, unofficial clients: none is
affiliated with, endorsed by or sponsored by the services it reaches. Each keeps its own terms
(accounts, keys, the legal value of each level, what may be signed), named with a link in each
gateway's README. The MIT licence covers this code only; whoever runs these packages complies with
those terms and with the law that applies to the signatures they collect.
