<?php

namespace Omnisign\Bridge\Symfony;

use Omnisign\Docusign\DocusignGatewayFactory;
use Omnisign\Docuseal\DocusealGatewayFactory;
use Omnisign\Yousign\YousignGatewayFactory;
use Omnisign\GatewayFactoryInterface;
use Omnisign\GatewayInterface;
use Omnisign\Registry;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

/**
 * Omnisign in a Symfony application: the gateway packages installed
 * (omnisign/yousign, omnisign/docusign, omnisign/docuseal) registered, the
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
 * Nothing is built, nor checked, when the container compiles: a gateway is
 * built the first time it is asked for, and an option left empty only shows
 * then (InvalidConfigException). A factory is autowired, the application's
 * http_client given to it when it has one. An application's own factories
 * (a GatewayFactoryInterface) are registered too, autoconfigured.
 */
final class OmnisignBundle extends AbstractBundle
{
    protected string $extensionAlias = 'omnisign';

    /** The gateway packages this bundle knows, registered when installed. */
    private const FACTORIES = [YousignGatewayFactory::class, DocusignGatewayFactory::class, DocusealGatewayFactory::class];

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
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /** @param array{gateways: array<string, array{factory: string, options: array<string, mixed>}>} $config */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $builder->registerForAutoconfiguration(GatewayFactoryInterface::class)->addTag('omnisign.gateway_factory');

        $services = $container->services();
        foreach (self::FACTORIES as $factory) {
            if (class_exists($factory) && is_subclass_of($factory, GatewayFactoryInterface::class)) {
                $definition = $services->set($factory)->autowire()->tag('omnisign.gateway_factory');
                foreach ((new \ReflectionClass($factory))->getConstructor()?->getParameters() ?? [] as $parameter) {
                    if ('http' === $parameter->getName()) {
                        $definition->arg('$http', service('http_client')->nullOnInvalid());
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
        }
    }
}
