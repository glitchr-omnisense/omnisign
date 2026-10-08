<?php

namespace Omnisign\Tests;

use Omnisign\Action\ActionInterface;
use Omnisign\Action\ApiAwareInterface;
use Omnisign\Action\ApiAwareTrait;
use Omnisign\Config;
use Omnisign\GatewayFactory;
use Omnisign\Model\Capabilities;
use Omnisign\Model\SignerState;
use Omnisign\Model\SignerStatus;
use Omnisign\Model\Status;
use Omnisign\Request\Create;
use Omnisign\Request\Fetch;
use Omnisign\Request\Request;
use Omnisign\Request\Send;

/**
 * A signature service kept in memory, for the tests: it creates, sends and
 * fetches; "silent: true" leaves out fetch; "mute" answers nothing.
 */
final class StubFactory extends GatewayFactory
{
    protected function populateConfig(Config $config): void
    {
        $config->defaults([
            'omnisign.factory_name' => 'stub',
            'omnisign.factory_title' => 'Stub',
            'omnisign.required_options' => ['host'],
            'silent' => false,
            'omnisign.capabilities' => new Capabilities(embedded: true),
            'omnisign.api' => static fn (Config $c) => new StubApi((string) $c['host']),
            'omnisign.action.create' => new StubAction(Create::class),
            'omnisign.action.send' => new StubAction(Send::class),
            'omnisign.action.fetch' => static fn (Config $c) => $c['silent'] ? null : new StubAction(Fetch::class),
            'omnisign.action.mute' => new MuteAction(),
        ]);
    }
}

final class StubApi
{
    /** @var array<string, Status> */
    public array $envelopes = [];

    public function __construct(public readonly string $host)
    {
    }
}

final class StubAction implements ActionInterface, ApiAwareInterface
{
    /** @use ApiAwareTrait<StubApi> */
    use ApiAwareTrait;

    /** @param class-string<Request> $request */
    public function __construct(private readonly string $request)
    {
        $this->apiClass = StubApi::class;
    }

    public function supports(Request $request): bool
    {
        return $request instanceof $this->request;
    }

    public function execute(Request $request): void
    {
        if ($request instanceof Create) {
            $reference = $this->api->host.'/'.substr(sha1($request->envelope->title), 0, 8);
            $this->api->envelopes[$reference] = Status::DRAFT;
            $states = [];
            foreach ($request->envelope->signers as $signer) {
                $states[$signer->key] = new SignerState($signer->key, 'r-'.$signer->key);
            }
            $request->setResult($request->envelope->with(reference: $reference, status: Status::DRAFT, states: $states));
        } elseif ($request instanceof Send) {
            $this->api->envelopes[$request->envelope->reference()] = Status::SENT;
            $request->setResult($request->envelope->with(status: Status::SENT, states: array_map(static fn (SignerState $s) => new SignerState($s->key, $s->reference, SignerStatus::NOTIFIED), $request->envelope->states)));
        } elseif ($request instanceof Fetch) {
            $request->setResult($request->envelope->with(status: $this->api->envelopes[$request->envelope->reference()] ?? Status::UNKNOWN));
        }
    }
}

final class Mute extends Request
{
}

final class MuteAction implements ActionInterface
{
    public function supports(Request $request): bool
    {
        return $request instanceof Mute;
    }

    public function execute(Request $request): void
    {
    }
}
