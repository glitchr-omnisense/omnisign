---
title: Tenant files
order: 35
---

# Tenant files

Since 1.1, `TenantFileInterface` (`Capabilities::TENANT_FILE`): a rental applicant's file - who
they are, where they live, their activity, income and tax, their guarantors, as a public service
checked them - shared with the application through the provider's consented connect flow. The
first provider is the French State's DossierFacile (DossierFacile Connect), for partners under its
charter.

| Package | Factory name | Consent | Read again | Webhooks | Retention |
|---|---|---|---|---|---|
| [`omnisign/dossierfacile`](https://github.com/glitchr-omnisense/omnisign-dossierfacile) | `dossierfacile` | OpenID Connect on DossierFacile's Keycloak | the partner's client credentials | with `callback_api_key` (`x-api-key`) | three months at most |

## The flow

```php
$files = $registry->tenantFiles('dossierfacile');

// 1. The "DossierFacile Connect" button leads here; $state is kept in the session.
$state = bin2hex(random_bytes(16));
return new RedirectResponse($files->tenantFileConnectUrl($callbackUrl, $state));

// 2. The applicant consented and came back with ?code=...&state=...: check the state, then
$file = $files->connectTenantFile($code, $callbackUrl);   // the code exchanged, the file read and linked to the site
$file->status;              // TenantFileStatus::VALIDATED, TO_PROCESS, DECLINED...
$file->status->isReadable(); // the documents may be read only when VALIDATED (or COMPLETED, opted in)
$file->tenants();           // Applicant: names, e-mail, status, documents
$file->guarantors();        // Applicant with role GUARANTOR, type NATURAL_PERSON, LEGAL_PERSON or ORGANISM (Visale)
$file->monthlyIncome();     // the tenants' declared monthly income, in euros
$file->url, $file->pdfUrl;  // the whole file at DossierFacile
$file->keepUntil;           // the last moment the site may hold any of it

// 3. Later, while the access holds
$file = $files->fetchTenantFile($file->tenant);

// 4. The provider's callbacks
$event = $files->notifyTenantFile($request->getContent(), $request->headers->all());
// TenantFileEvent::VERIFIED, DENIED, ARCHIVED... - fetch the file again
// TenantFileEvent::REVOKED - the applicant withdrew the access: delete what is held, now
```

## Personal data (GDPR)

A tenant file is personal data of the applicant and their guarantors, obtained with consent for one
purpose: choosing a tenant. The provider's charter sets the rules; the family carries them as
data:

- **Retention.** `TenantFile::$keepUntil` is `$fetchedAt` plus the provider's retention
  (`capabilities()->terms->retention`, `P3M` for DossierFacile). `isExpired()` says when it must be
  gone; a site purges by it (a cron job, a Messenger message).
- **Revocation.** On `TenantFileEvent::REVOKED`, delete what is held of the file at once;
  `fetchTenantFile()` answers HTTP 403 from then on (a `ProviderException`, code `403`).
- **Documents.** The family carries each document's category, kind, status, declared monthly
  amount and the address where it is read, watermarked, at the provider - never its file. Do not
  copy the documents; read them there, and only while the file `isReadable()`.
- **Minimal fields.** Of the applicants, the family keeps names, e-mail, statuses, documents; the
  provider's preferred name, postcode, free text, honour declaration and FranceConnect flag are
  left behind.
- **Third parties.** The link to a file is shown to no third party without the applicant's
  explicit consent; the service is never offered to a third party for a fee.

## Writing a gateway that gives tenant files

Its factory declares `contracts: [Capabilities::TENANT_FILE]`, its `terms` (the retention above
all), and one action per request of `Omnisign\Request\TenantFile`: `ConnectUrl` (no call: the
consent page's address), `Connect` (the code exchanged, the file read), `Fetch`, `Notify`.
