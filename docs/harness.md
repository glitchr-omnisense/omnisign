---
title: The Docker harness
order: 70
---

# The Docker harness

`docker/` runs this package with every `omnisign/*` gateway installed - from GitHub (branch 1.x),
or from the checkouts beside this one when `OMNIBILL_PLUGINS=../..` is set in `docker/.env`.

```sh
cd docker && cp .env.dist .env       # a provider's keys, a DocuSeal instance, when you have them
docker compose run --rm omnisign bare
```

| Command | |
|---|---|
| `gateways` | the gateways installed and configured, what each offers and does - nothing called |
| `bare` | plain PHP: the registry built by hand, a lease signed through each gateway, what PHP loaded |
| `bare --live` | the same, DocuSeal for real (`DOCUSEAL_URL`, `DOCUSEAL_API_KEY`, `DOCUSEAL_TEMPLATE`) |
| `bare --json` | the same, whole: every class and file loaded |
| `test` | every package's tests |

## Bare: no bundle, no container

`docker/harness/bin/bare` requires the autoloader and nothing else. It builds the `Registry` from
the gateway packages installed and has a lease signed through each - created, sent, fetched,
downloaded - the providers answering from their packages' recorded answers, then lists what PHP
loaded, and exits 1 if a class of a framework is among it:

```
$ docker compose run --rm omnisign bare
Omnisign in bare PHP: the registry built by hand, no bundle, no container; the providers' recorded answers.

  yousign   Yousign: levels simple, advanced, qualified; in the page yes; third party yes; does create send fetch remind cancel download signing-url
  docusign  DocuSign: levels simple; in the page yes; third party yes; does create send fetch remind cancel download signing-url
  docuseal  DocuSeal: levels simple; in the page yes; third party no; does create send fetch remind cancel download signing-url

  yousign   created 9a2b1c3d-0e4f-4a5b-8c6d-7e8f9a0b1c2d (draft), sent: sent, fetched: completed; downloaded 1 document(s) (%PDF-1.7), evidence yes
  docusign  created 7c1d2e3f-4a5b-4c6d-8e7f-9a0b1c2d3e4f (draft), sent: completed, fetched: completed; downloaded 1 document(s) (%PDF-1.7), evidence yes
  docuseal  created 4 (sent), sent: completed, fetched: completed; downloaded 1 document(s) (%PDF-1.4), evidence yes

Loaded from Symfony: Symfony\Component\HttpClient, Symfony\Contracts\HttpClient, Symfony\Contracts\Service
Classes of a framework (DependencyInjection, Config, HttpKernel, HttpFoundation, a bundle or bridge, Doctrine, Twig): none
```

(2026-10-08; the recordings answer "completed" as soon as asked) `Tests/BareTest.php` runs
`bare --json` in a process of its own and checks the list.

The image is `php:8.4-cli-alpine` with Composer; the harness's packages live in the `harness`
volume of the `omnisign-harness` project.

## Run on 2026-10-26

`bare` with no option, run on the packages' checkouts through
`OMNISIGN_AUTOLOAD=vendor/autoload.php php core/docker/harness/bin/bare` (PHP 8.4, no network),
printed the lines above unchanged.

## The entrypoint

`docker/entrypoint.sh` runs `docker/harness/setup.php` first: it writes the harness's
`composer.json` and `phpunit.xml` in the `harness` volume - this core from its checkout, each
gateway from its checkout under `OMNISIGN_PLUGINS` when one is there, from
`https://github.com/glitchr-omnisense/omnisign-<slug>.git` (branch 1.x) otherwise. Then
`composer install` the first time, `composer update` when `composer.json` is newer than the lock,
and the command: `test` (PHPUnit), `bare`, `composer`, `sh` or `php` as they are, anything else the
console (`gateways` by default).

`docker/harness/config/gateways.php` says which environment variables configure each gateway
(`YOUSIGN_API_KEY`; `DOCUSIGN_INTEGRATION_KEY`, `DOCUSIGN_USER_ID`, `DOCUSIGN_PRIVATE_KEY`,
`DOCUSIGN_ACCOUNT_ID`; `DOCUSEAL_URL`, `DOCUSEAL_API_KEY`) and the example options `bare` uses
instead (DocuSign's private key generated for the run).
