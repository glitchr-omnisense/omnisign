<?php

namespace Omnisign\Tests\Bridge;

use Omnisign\Bridge\Symfony\OmnisignBundle;
use Omnisign\GatewayInterface;
use Omnisign\Model\Envelope;
use Omnisign\Registry;
use Omnisign\Tests\StubFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpClient\MockHttpClient;

final class OmnisignBundleTest extends TestCase
{
    public function testTheGatewaysConfiguredAreBuiltAndInjectableByName(): void
    {
        $container = new ContainerBuilder();
        $container->register('http_client', MockHttpClient::class);
        $container->register(StubFactory::class)->setAutoconfigured(true);
        $container->register(Leasing::class)->setAutowired(true)->setPublic(true);
        $container->registerExtension((new OmnisignBundle())->getContainerExtension());
        $container->loadFromExtension('omnisign', ['gateways' => [
            'platform' => ['factory' => 'stub', 'options' => ['host' => 'sign.example']],
            'other' => ['factory' => 'stub', 'options' => ['host' => 'other.example']],
        ]]);
        $container->compile();

        $registry = $container->get(Registry::class);
        self::assertSame(['platform', 'other'], array_keys($registry->all()));
        $leasing = $container->get(Leasing::class);
        self::assertStringStartsWith('sign.example/', $leasing->platform->create(new Envelope('Bail'))->reference);
        self::assertStringStartsWith('other.example/', $leasing->other->create(new Envelope('Bail'))->reference);
    }

    public function testThePackagesInstalledAreRegisteredWithTheApplicationsClient(): void
    {
        if (!class_exists(\Omnisign\Docuseal\DocusealGatewayFactory::class)) {
            self::markTestSkipped('omnisign/docuseal is not installed.');
        }
        $container = new ContainerBuilder();
        $container->register('http_client', MockHttpClient::class);
        $container->registerExtension((new OmnisignBundle())->getContainerExtension());
        $container->loadFromExtension('omnisign', ['gateways' => ['platform' => ['factory' => 'docuseal', 'options' => ['url' => 'https://sign.example.org/api', 'api_key' => 'k']]]]);
        $container->compile();

        $registry = $container->get(Registry::class);
        self::assertContains('docuseal', $registry->factories());
        self::assertFalse($registry->get('platform')->capabilities()->thirdParty);
    }
}

final class Leasing
{
    public function __construct(public readonly GatewayInterface $platform, public readonly GatewayInterface $other)
    {
    }
}
