---
title: Laravel
order: 16
---

# Laravel

omnibridge/laravel, the bridge that hosts omnibase in Laravel, has no Omnisign provider, and there
is no other Laravel package for it. The registry is plain PHP: a service provider of the application
builds it once, from a config file, with a `symfony/http-client` client (every gateway requires it;
Laravel's own HTTP client is Guzzle's, which the gateways do not take).

```php
// config/omnisign.php
return [
    'gateways' => [
        'contracts' => ['factory' => 'docuseal', 'options' => [
            'url' => env('DOCUSEAL_URL'),
            'api_key' => env('DOCUSEAL_API_KEY'),
            'webhook_secret' => env('DOCUSEAL_WEBHOOK_SECRET'),
        ]],
        'qualified' => ['factory' => 'yousign', 'options' => [
            'api_key' => env('YOUSIGN_API_KEY'),
            'sandbox' => env('YOUSIGN_SANDBOX', false),
        ]],
    ],
];
```

```php
// app/Providers/OmnisignServiceProvider.php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Omnisign\Docuseal\DocusealGatewayFactory;
use Omnisign\Registry;
use Omnisign\Yousign\YousignGatewayFactory;
use Symfony\Component\HttpClient\HttpClient;

final class OmnisignServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Registry::class, function (): Registry {
            $http = HttpClient::create();

            return new Registry([
                new DocusealGatewayFactory($http),
                new YousignGatewayFactory($http),
            ], config('omnisign.gateways'));
        });
    }
}
```

Register the provider (`bootstrap/providers.php` on Laravel 11 and later), then inject the registry:

```php
use Omnisign\Registry;

final class LeaseController
{
    public function sign(Registry $registry, Lease $lease)
    {
        $gateway = $registry->get('contracts');
        $envelope = $gateway->send($gateway->create($lease->envelope()));
        // store $envelope->reference and $envelope->states with the lease
    }
}
```

A webhook route reads the raw body and the headers - `$gateway->notify($request->getContent(),
$request->headers->all())` - and answers 400 on an `InvalidNotificationException`.

Differences from the Symfony bundle: list in the provider every factory the application uses, and
ask the registry for a gateway by name. An `env()` left unset is `null`: a required option that is
null is refused when the gateway is built; the boolean options (`sandbox`, `third_party`) also read
`"1"`, `"true"`, `"yes"`, `"on"`. This page was written from the registry's API; it was not run in a
Laravel application.
