<?php

namespace Omnisign\Tests\Bridge;

use Omnisign\Bridge\Symfony\OmnisignBundle;
use Omnisign\GatewayInterface;
use Omnisign\Model\Envelope;
use Omnisign\Registry;
use Omnisign\Tests\StubFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpClient\Retry\GenericRetryStrategy;
use Symfony\Component\HttpClient\RetryableHttpClient;

final class OmnisignBundleTest extends TestCase
{
    public function testTheGatewaysConfiguredAreBuiltAndInjectableByName(): void
    {
        $container = new ContainerBuilder();
        $container->register('http_client', MockHttpClient::class);
        $container->register(StubFactory::class)->setAutoconfigured(true);
        $container->register(Leasing::class)->setAutowired(true)->setPublic(true);
        $container->registerExtension(self::extension());
        $container->loadFromExtension('omnisign', ['gateways' => [
            'platform' => ['factory' => 'stub', 'options' => ['host' => 'sign.example']],
            'other' => ['factory' => 'stub', 'options' => ['host' => 'other.example']],
        ]]);
        $container->compile();

        $registry = $container->get(Registry::class);
        self::assertInstanceOf(Registry::class, $registry);
        self::assertSame(['platform', 'other'], array_keys($registry->all()));
        $leasing = $container->get(Leasing::class);
        self::assertInstanceOf(Leasing::class, $leasing);
        self::assertStringStartsWith('sign.example/', $leasing->platform->create(new Envelope('Bail'))->reference ?? '');
        self::assertStringStartsWith('other.example/', $leasing->other->create(new Envelope('Bail'))->reference ?? '');
    }

    public function testThePackagesInstalledAreRegisteredWithTheApplicationsClient(): void
    {
        if (!class_exists(\Omnisign\Docuseal\DocusealGatewayFactory::class)) {
            self::markTestSkipped('omnisign/docuseal is not installed.');
        }
        $container = new ContainerBuilder();
        $container->register('http_client', MockHttpClient::class);
        $container->registerExtension(self::extension());
        $container->loadFromExtension('omnisign', ['gateways' => ['platform' => ['factory' => 'docuseal', 'options' => ['url' => 'https://sign.example.org/api', 'api_key' => 'k']]]]);
        $container->compile();

        $registry = $container->get(Registry::class);
        self::assertInstanceOf(Registry::class, $registry);
        self::assertContains('docuseal', $registry->factories());
        self::assertFalse($registry->get('platform')->capabilities()->thirdParty);
    }

    /** The application's http_client with retry_failed on: an envelope answered 503 is sent once. */
    public function testAnEnvelopeIsSentOnceThroughTheApplicationsRetryingClient(): void
    {
        if (!class_exists(\Omnisign\Docuseal\DocusealGatewayFactory::class)) {
            self::markTestSkipped('omnisign/docuseal is not installed.');
        }
        $sent = [];
        $transport = new MockHttpClient(static function (string $method, string $url) use (&$sent): MockResponse {
            $sent[] = $method.' '.$url;

            return new MockResponse('{"error":"unavailable"}', ['http_code' => 503]);
        });
        $container = new ContainerBuilder();
        $container->register('http_client', RetryableHttpClient::class)->setSynthetic(true);
        $container->registerExtension(self::extension());
        $container->loadFromExtension('omnisign', ['gateways' => ['platform' => ['factory' => 'docuseal', 'options' => ['url' => 'https://sign.example.org/api', 'api_key' => 'k']]]]);
        $container->compile();
        $container->set('http_client', new RetryableHttpClient($transport, new GenericRetryStrategy(GenericRetryStrategy::DEFAULT_RETRY_STATUS_CODES, 0), 3));

        $registry = $container->get(Registry::class);
        self::assertInstanceOf(Registry::class, $registry);
        try {
            $registry->get('platform')->create(new Envelope('Bail', signers: [new \Omnisign\Model\Signer('tenant', 'Camille Érable', 'camille@erable.example')], template: '1'));
            self::fail('a 503');
        } catch (\Omnisign\Exception\OmnisignException) {
        }
        self::assertCount(1, $sent, 'the 503 was not retried by the client');
    }

    private static function extension(): ExtensionInterface
    {
        $extension = (new OmnisignBundle())->getContainerExtension();
        self::assertNotNull($extension);

        return $extension;
    }
}

final class Leasing
{
    public function __construct(public readonly GatewayInterface $platform, public readonly GatewayInterface $other)
    {
    }
}
