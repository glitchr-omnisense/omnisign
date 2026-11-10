<?php

namespace Omnisign\Bridge\Symfony;

use Omnisign\Docusign\DocusignGatewayFactory;
use Omnisign\Docuseal\DocusealGatewayFactory;
use Omnisign\Dossierfacile\DossierfacileGatewayFactory;
use Omnisign\StripeIdentity\StripeIdentityGatewayFactory;
use Omnisign\Yousign\YousignGatewayFactory;
use Omnisign\GatewayFactoryInterface;
use Omnisign\GatewayInterface;
use Omnisign\Http\NoRetry;
use Omnisign\IdentityCheckInterface;
use Omnisign\Registry;
use Omnisign\TenantFileInterface;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function Symfony\Component\DependencyInjection\Loader\Configurator\inline_service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

/**
 * Omnisign in a Symfony application: the gateway packages installed
 * (omnisign/yousign, omnisign/docusign, omnisign/docuseal,
 * omnisign/stripe-identity, omnisign/dossierfacile) registered, the
 * gateways built from configuration and injectable by their name:
 *
 *     omnisign:
 *         gateways:
 *             contracts:
 *                 factory: docuseal
 *                 options: { url: 'https://sign.example.org', api_key: '%env(DOCUSEAL_API_KEY)%' }
 *             qualified:
 *                 factory: yousign
 *                 options: { api_key: '%env(YOUSIGN_API_KEY)%', sandbox: true }
 *
 *     public function __construct(GatewayInterface $contracts, GatewayInterface $qualified) {}
 *
 * A gateway that verifies identities (omnisign/stripe-identity) or gives
 * tenant files (omnisign/dossierfacile) is injectable by its contract too,
 * built through Registry::identity() / tenantFiles():
 *
 *     public function __construct(IdentityCheckInterface $kyc, TenantFileInterface $dossierfacile) {}
 *
 * Nothing is built, nor checked, when the container compiles: a gateway is
 * built the first time it is asked for, and an option left empty only shows
 * then (InvalidConfigException). A factory is autowired, the application's
 * http_client given to it when it has one, with its retries off
 * (Omnisign\Http\NoRetry: an envelope is never sent twice by retry_failed). An application's own factories
 * (a GatewayFactoryInterface) are registered too, autoconfigured.
 *
 * Beside "options", a gateway takes "policy" (the site's tightening of the
 * provider's usage policy, never looser) and "usage" (what the site is:
 * commercial, open_source, contract). Its calls count in this process's
 * memory: the Registry service's third argument takes a shared
 * RateLimiterInterface where many processes serve the application.
 */
final class OmnisignBundle extends AbstractBundle
{
    protected string $extensionAlias = 'omnisign';

    /** The gateway packages this bundle knows, registered when installed. */
    private const FACTORIES = [YousignGatewayFactory::class, DocusignGatewayFactory::class, DocusealGatewayFactory::class, StripeIdentityGatewayFactory::class, DossierfacileGatewayFactory::class];

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->arrayNode('gateways')
                    ->info('The gateways, by name: a factory (yousign, docusign, docuseal...) and its options.')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('factory')->isRequired()->cannotBeEmpty()->end()
                            ->variableNode('options')->defaultValue([])->end()
                            ->variableNode('policy')->info('The site\'s tightening of the provider\'s usage policy, in its schema\'s shape (rateLimits, rights...): never looser.')->end()
                            ->variableNode('usage')->info('What the site is: commercial, open_source, contract. A use the policy forbids fails when the gateway is built.')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /** @param array{gateways: array<string, array{factory: string, options: array<string, mixed>, policy?: array<string, mixed>, usage?: array<string, mixed>}>} $config */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $builder->registerForAutoconfiguration(GatewayFactoryInterface::class)->addTag('omnisign.gateway_factory');

        $services = $container->services();
        foreach (self::FACTORIES as $factory) {
            if (class_exists($factory)) {
                $definition = $services->set($factory)->autowire()->tag('omnisign.gateway_factory');
                foreach ((new \ReflectionClass($factory))->getConstructor()?->getParameters() ?? [] as $parameter) {
                    if ('http' === $parameter->getName()) {
                        $definition->arg('$http', inline_service(HttpClientInterface::class)->factory([NoRetry::class, 'of'])->args([service('http_client')->nullOnInvalid()]));
                    }
                }
            }
        }

        $services->set(Registry::class)
            ->args([tagged_iterator('omnisign.gateway_factory'), $config['gateways']])
            ->public();

        foreach (array_keys($config['gateways']) as $name) {
            $id = 'omnisign.gateway.'.$name;
            $services->set($id, GatewayInterface::class)->factory([service(Registry::class), 'get'])->args([$name]);
            $builder->registerAliasForArgument($id, GatewayInterface::class, $name);
            $services->set('omnisign.identity.'.$name, IdentityCheckInterface::class)->factory([service(Registry::class), 'identity'])->args([$name]);
            $builder->registerAliasForArgument('omnisign.identity.'.$name, IdentityCheckInterface::class, $name);
            $services->set('omnisign.tenant_file.'.$name, TenantFileInterface::class)->factory([service(Registry::class), 'tenantFiles'])->args([$name]);
            $builder->registerAliasForArgument('omnisign.tenant_file.'.$name, TenantFileInterface::class, $name);
        }
    }
}
