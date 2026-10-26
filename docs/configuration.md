---
title: Configuration reference
order: 40
---

# Configuration reference

## The registry

```php
new Registry(iterable $factories, array $config);
```

| Key | Type | Required | |
|---|---|---|---|
| `<name>` | string | - | the gateway's name: `get('<name>')`, and in Symfony the argument's name |
| `<name>.factory` | string | yes | a factory's `getName()`: `yousign`, `docusign`, `docuseal`, or an application's own |
| `<name>.options` | map | no, default `[]` | handed to the factory's `create()`; `create($name, $overrides)` puts other values over them |

The same tree is the Symfony bundle's `omnisign.gateways` ([Symfony](symfony.md#configuration)). A
required option that is unset, null, `''` or `[]` is refused when the gateway is built
(`InvalidConfigException`: `The "yousign" gateway needs: api_key.`). Booleans are read with
`Config::bool()` (`true`, or `"1"`, `"true"`, `"yes"`, `"on"`), lists with `Config::list()` (a list,
or a comma-separated string).

## Yousign (`yousign`)

`new YousignGatewayFactory(?HttpClientInterface $http = null)`.

| Option | Type | Default | Required | |
|---|---|---|---|---|
| `api_key` | string | - | yes | sent as `Authorization: Bearer` |
| `sandbox` | bool | `false` | no | `https://api-sandbox.yousign.app/v3`: signatures without legal value |
| `webhook_secret` | string | none | no | the webhook subscription's secret; without it, `notify()` is not supported |
| `url` | string | `https://api.yousign.app/v3` (or the sandbox's) | no | another API root, over `sandbox` |

Capabilities: simple, advanced, qualified; in the page; ordered; identity check; third party;
documents; no templates.

## DocuSign (`docusign`)

`new DocusignGatewayFactory(?HttpClientInterface $http = null)`.

| Option | Type | Default | Required | |
|---|---|---|---|---|
| `integration_key` | string | - | yes | the integration's client id (the JWT's `iss`) |
| `user_id` | string | - | yes | the user it acts as, who consented once (the JWT's `sub`) |
| `private_key` | string | - | yes | the RSA key's PEM, or the path of a file holding it |
| `account_id` | string | - | yes | the API account id (a GUID) |
| `sandbox` | bool | `true` | no | `account-d.docusign.com` and `https://demo.docusign.net/restapi`; false: `account.docusign.com` and the account's base URI from the userinfo |
| `base_uri` | string | none | no | the account's REST base (`https://<host>/restapi`), over the above |
| `hmac_keys` | list or string | `[]` | no | Connect's HMAC keys; without one, `notify()` is not supported |

Note the default: **`sandbox` is true** - set it false for production.

Capabilities: simple; in the page; ordered; no identity check; third party; documents; no templates.

## DocuSeal (`docuseal`)

`new DocusealGatewayFactory(?HttpClientInterface $http = null)`.

| Option | Type | Default | Required | |
|---|---|---|---|---|
| `url` | string | - | yes | the API's root: `https://<instance>/api`, or DocuSeal's cloud `https://api.docuseal.com`, `https://api.docuseal.eu` |
| `api_key` | string | - | yes | the instance's API key (Settings, API), sent as `X-Auth-Token` |
| `app_url` | string | the `url` without `/api`; for the cloud `https://docuseal.com` or `https://docuseal.eu` | no | where signers' forms open: `<app_url>/s/<slug>` |
| `webhook_secret` | string | none | no | `notify()` checks `X-Docuseal-Signature` with it; without it, not supported |
| `webhook_tolerance` | int | `300` | no | seconds a callback may be old; `0`: any age |
| `third_party` | bool | told from the `url`: yes for a host under `docuseal.com`, `.eu` or `.co`, no otherwise | no | whether the instance is someone else's (`capabilities()->thirdParty`) |

Capabilities: simple; in the page; ordered; no identity check; documents; templates.

## Environment variables

The packages read no environment variable themselves. The names in these pages and in the harness
are conventions: `YOUSIGN_API_KEY`, `YOUSIGN_SANDBOX`, `YOUSIGN_WEBHOOK_SECRET`;
`DOCUSIGN_INTEGRATION_KEY`, `DOCUSIGN_USER_ID`, `DOCUSIGN_PRIVATE_KEY`, `DOCUSIGN_ACCOUNT_ID`,
`DOCUSIGN_HMAC_KEYS`; `DOCUSEAL_URL`, `DOCUSEAL_API_KEY`, `DOCUSEAL_WEBHOOK_SECRET`; the harness's
`OMNISIGN_PLUGINS` and `OMNISIGN_AUTOLOAD`.
