<?php

namespace Omnisign;

use Omnisign\Action\ActionInterface;
use Omnisign\Action\ApiAwareInterface;
use Omnisign\Exception\InvalidConfigException;
use Omnisign\Exception\UsageLimitReachedException;
use Omnisign\Model\Capabilities;
use Omnisense\Contracts\Provider\Config as BaseConfig;
use Omnisense\Contracts\Provider\ProviderFactory;

/**
 * The Omnibus way: a gateway's factory fills a Config - its name, title,
 * capabilities, the options it needs, its client ("omnisign.api", a closure
 * of the Config: a provider's API, a token signer, a store) and its actions
 * ("omnisign.action.<name>") - and the gateway is those actions, the client
 * handed to the ones that ask for it.
 *
 * omnisense/contracts' factory underneath: before the client is built, the
 * Config holds the provider's Backoff (Config::backoff(), for Http\Answer),
 * and the gateway comes out behind its PolicyGuard - its
 * capabilities' usage policy tightened by the "policy" option, checked
 * against the "usage" option (UsageNotPermitted at once when a use the
 * policy forbids is said).
 *
 * Nothing here calls anything: a factory whose provider is reached over
 * HTTP takes its client in its own constructor.
 */
abstract class GatewayFactory extends ProviderFactory implements GatewayFactoryInterface
{
    public function create(array $options = []): GatewayInterface
    {
        $gateway = parent::create($options);
        if (!$gateway instanceof Gateway) {
            throw new InvalidConfigException(\sprintf('The "%s" factory built no omnisign gateway.', $this->getName()));
        }
        if (null !== $guard = $this->guard($gateway)) {
            $gateway->guardWith($guard);
        }

        return $gateway;
    }

    /** @param array<string, mixed> $options */
    public function createConfig(array $options = []): Config
    {
        return self::own(parent::createConfig($options));
    }

    abstract protected function populateConfig(Config $config): void;

    protected function newConfig(array $options): Config
    {
        return new Config($options);
    }

    protected function limitException(): string
    {
        return UsageLimitReachedException::class;
    }

    final protected function configure(BaseConfig $c): void
    {
        $this->populateConfig(self::own($c));
    }

    final protected function make(BaseConfig $c): Gateway
    {
        $config = self::own($c);
        $api = $config->get('omnisign.api');
        if ($api instanceof \Closure) {
            $api = $api($config);
        }
        $capabilities = $config->get('omnisign.capabilities');
        if ($capabilities instanceof \Closure) {
            $capabilities = $capabilities($config);
        }
        if (!$capabilities instanceof Capabilities) {
            throw new InvalidConfigException(\sprintf('The "%s" factory does not say what its signatures are (omnisign.capabilities).', $config->string('omnisign.factory_name') ?? ''));
        }

        $actions = [];
        foreach ($config as $key => $action) {
            if (!str_starts_with((string) $key, 'omnisign.action.')) {
                continue;
            }
            if ($action instanceof \Closure) {
                $action = $action($config);
            }
            if (!$action instanceof ActionInterface) {
                continue;
            }
            if ($action instanceof ApiAwareInterface && \is_object($api)) {
                $action->setApi($api);
            }
            $actions[] = $action;
        }

        return new Gateway($config->string('omnisign.factory_name') ?? '', $config->string('omnisign.factory_title') ?? '', $capabilities, $actions);
    }

    private static function own(BaseConfig $c): Config
    {
        return $c instanceof Config ? $c : throw new InvalidConfigException('An omnisign factory builds from omnisign\'s Config.');
    }
}
