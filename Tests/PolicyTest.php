<?php

namespace Omnisign\Tests;

use Omnisign\Action\ActionInterface;
use Omnisign\Config;
use Omnisign\Exception\OmnisignException;
use Omnisign\Exception\UnreachableException;
use Omnisign\Exception\UsageLimitReachedException;
use Omnisign\Gateway;
use Omnisign\GatewayFactory;
use Omnisign\Http\Answer;
use Omnisign\Model\Capabilities;
use Omnisign\Model\Envelope;
use Omnisign\Model\Event;
use Omnisign\Model\Notification;
use Omnisign\Model\Signer;
use Omnisign\Model\Terms;
use Omnisign\Registry;
use Omnisign\Request\Notify;
use Omnisign\Request\Request;
use Omnisense\Contracts\Policy\Backoff;
use Omnisense\Contracts\Policy\InvalidPolicyException;
use Omnisense\Contracts\Policy\Permission;
use Omnisense\Contracts\Policy\RateLimit;
use Omnisense\Contracts\Policy\UsageNotPermitted;
use Omnisense\Contracts\Policy\UsagePolicy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/** Every call a gateway makes to its provider is guarded by the provider's usage policy, as the site tightened it. */
final class PolicyTest extends TestCase
{
    public function testCallsCountUnderTheSitesLimitAndTheCallOverItIsNeverMade(): void
    {
        $registry = new Registry([new StubFactory()], ['contracts' => ['factory' => 'stub', 'options' => ['host' => 'sign.example'], 'policy' => ['rateLimits' => [['max' => 2, 'window' => 'minute', 'operations' => ['create', 'fetch']]]]]]);
        $gateway = $registry->get('contracts');

        self::assertInstanceOf(Gateway::class, $gateway);
        self::assertSame($registry->guard('contracts'), $gateway->guard(), 'the registry and the gateway share one guard');
        $created = $gateway->create(new Envelope('Bail', signers: [new Signer('tenant', 'Camille Érable', 'camille@erable.example')]));
        $gateway->fetch($created);
        $gateway->send($created);
        try {
            $gateway->create(new Envelope('Avenant'));
            self::fail('A third counted call went through.');
        } catch (OmnisignException $e) {
            self::assertInstanceOf(UsageLimitReachedException::class, $e);
            self::assertStringContainsString('allows 2 a minute for create, fetch: reached', $e->getMessage());
            self::assertSame(60, $e->retryAfter);
        }
        self::assertSame(0, $registry->guard('contracts')->remaining('create'));
        self::assertNull($registry->guard('contracts')->remaining('send'), 'send is not counted');
    }

    public function testA429AndItsRetryAfterHoldEveryCallUntilTheWaitIsOver(): void
    {
        $requests = 0;
        $http = new MockHttpClient(function () use (&$requests): MockResponse {
            ++$requests;

            return new MockResponse('{"error":"too many requests"}', ['http_code' => 429, 'response_headers' => ['retry-after' => '120']]);
        });
        $registry = new Registry([self::overHttp($http)], ['platform' => ['factory' => 'http']]);

        try {
            $registry->get('platform')->create(new Envelope('Bail'));
            self::fail('A 429 was read as an envelope.');
        } catch (UsageLimitReachedException $e) {
            self::assertSame(120, $e->retryAfter);
            self::assertSame('http answered 429 Too Many Requests: try again in 120 s.', $e->getMessage());
        }
        try {
            $registry->get('platform')->fetch(new Envelope('Bail', reference: 'e-1'));
            self::fail('A call went out while the provider asked to wait.');
        } catch (UsageLimitReachedException $e) {
            self::assertGreaterThanOrEqual(119, $e->retryAfter);
        }
        self::assertSame(1, $requests, 'the wait held the next request back');
    }

    public function testWithoutABackoffA429StaysUnreachable(): void
    {
        $this->expectException(UnreachableException::class);
        Answer::send(new MockHttpClient(new MockResponse('', ['http_code' => 429])), 'p', 'GET', 'https://sign.example/envelopes');
    }

    public function testAWebhookIsNotCounted(): void
    {
        $registry = new Registry([self::overHttp(new MockHttpClient())], ['platform' => ['factory' => 'http', 'policy' => ['rateLimits' => [['max' => 1, 'window' => 'day']]]]]);
        $gateway = $registry->get('platform');

        self::assertNull(Gateway::operation(new Notify('{}')));
        $gateway->notify('{}');
        $gateway->notify('{}');
        self::assertSame(1, $registry->guard('platform')->remaining('create'));
    }

    public function testASiteMayTightenAPolicyNeverLoosenIt(): void
    {
        $declared = UsagePolicy::builder('http')->limit(RateLimit::perDay(1000))->commercial(Permission::Forbidden)->build();
        $tighter = new Registry([self::overHttp(new MockHttpClient(), $declared)], ['platform' => ['factory' => 'http', 'policy' => ['rateLimits' => [['max' => 100, 'window' => 'day']], 'rights' => ['storage' => 'forbidden']]]]);

        self::assertSame(100, $tighter->guard('platform')->remaining('create'));
        self::assertSame(Permission::Forbidden, $tighter->policy('platform')->rights->storage);
        self::assertSame(['platform' => $tighter->policy('platform')], $tighter->policies());

        $looser = new Registry([self::overHttp(new MockHttpClient(), $declared)], ['platform' => ['factory' => 'http', 'policy' => ['rateLimits' => [['max' => 5000, 'window' => 'day']]]]]);
        try {
            $looser->get('platform');
            self::fail('A higher limit than the provider\'s was accepted.');
        } catch (InvalidPolicyException $e) {
            self::assertStringContainsString('5000 a day', $e->getMessage());
        }

        $commercial = new Registry([self::overHttp(new MockHttpClient(), $declared)], ['platform' => ['factory' => 'http', 'options' => ['usage' => ['commercial' => true]]]]);
        $this->expectException(UsageNotPermitted::class);
        $commercial->get('platform');
    }

    public function testTermsWrittenBeforeThePolicyGiveOne(): void
    {
        $terms = new Terms(retention: 'P3M', partner: true, price: 'EUR 1 a call', attribution: 'Data from Example', mustNotKeep: ['the token'], rules: ['Internal use only'], source: 'https://example.org/terms');
        $capabilities = new Capabilities(contracts: [Capabilities::TENANT_FILE], terms: $terms);
        $policy = $capabilities->policy();

        self::assertSame(UsagePolicy::UNNAMED, $policy->provider);
        self::assertNull($policy->rateLimits, 'no limit known');
        self::assertSame(Permission::Contract, $policy->rights->commercial);
        self::assertSame(Permission::Contract, $policy->rights->storage);
        self::assertSame(89 * 86400, $policy->rights->caching->maxTtl, 'three months at their shortest');
        self::assertSame('Data from Example', $policy->rights->attribution->text);
        self::assertSame('https://example.org/terms', $policy->provenance->terms);
        self::assertSame('Price: EUR 1 a call. Retention: P3M. Must not keep: the token. Internal use only.', $policy->provenance->notes);
        self::assertSame($policy->toArray(), $capabilities->toArray()['policy']);
        self::assertSame($terms, $capabilities->terms);
        self::assertSame(Permission::Forbidden, (new Terms(commercial: false))->policy()->rights->commercial);
        self::assertSame(Permission::Unknown, (new Terms(commercial: null))->policy()->rights->commercial);
        self::assertSame(UsagePolicy::described(UsagePolicy::UNNAMED)->toArray(), (new Capabilities())->policy()->toArray(), 'nothing said, nothing known');
        $registry = new Registry([new StubFactory()], ['platform' => ['factory' => 'stub', 'options' => ['host' => 'sign.example']]]);
        self::assertSame('stub', $registry->policy('platform')->provider, 'the registry names it after its gateway');
    }

    public function testTheTermsAreReadFromAPolicyGivenAlone(): void
    {
        $policy = UsagePolicy::builder('x')->commercial(Permission::Allowed)->attribution('X data')->provenance(terms: 'https://x.example/terms')->build();
        $capabilities = new Capabilities(policy: $policy);

        self::assertSame($policy, $capabilities->policy());
        self::assertNotNull($capabilities->terms);
        self::assertSame([true, false, 'X data', 'https://x.example/terms'], [$capabilities->terms->commercial, $capabilities->terms->partner, $capabilities->terms->attribution, $capabilities->terms->source]);
        self::assertSame(['contracts' => [Capabilities::SIGNATURE]], array_intersect_key($capabilities->toArray(), ['contracts' => 1]));
        self::assertSame([Capabilities::SIGNATURE], $capabilities->contracts());
    }

    /** A gateway over HTTP, its Answer given the provider's Backoff: every request but a webhook is one GET, a webhook reaches nothing. */
    private static function overHttp(HttpClientInterface $http, ?UsagePolicy $policy = null): GatewayFactory
    {
        return new class($http, $policy) extends GatewayFactory {
            public function __construct(private readonly HttpClientInterface $http, private readonly ?UsagePolicy $policy)
            {
            }

            protected function populateConfig(Config $config): void
            {
                $config->defaults([
                    'omnisign.factory_name' => 'http',
                    'omnisign.factory_title' => 'Over HTTP',
                    'omnisign.capabilities' => new Capabilities(policy: $this->policy),
                    'omnisign.action.http' => fn (Config $c) => new HttpAction($this->http, $c->backoff()),
                ]);
            }
        };
    }
}

final class HttpAction implements ActionInterface
{
    public function __construct(private readonly HttpClientInterface $http, private readonly ?Backoff $backoff)
    {
    }

    public function supports(Request $request): bool
    {
        return true;
    }

    public function execute(Request $request): void
    {
        if ($request instanceof Notify) {
            $request->setResult(new Notification(Event::cases()[0], 'test'));

            return;
        }
        Answer::send($this->http, 'Example', 'GET', 'https://sign.example/envelopes', [], $this->backoff);
        $request->setResult(new Envelope('Bail', reference: 'e-1'));
    }
}
