<?php

namespace Omnisign\Tests;

use Omnisign\Bridge\Symfony\OmnisignBundle;
use Omnisign\Exception\NotSupportedException;
use Omnisign\IdentityCheckInterface;
use Omnisign\Model\Applicant;
use Omnisign\Model\Capabilities;
use Omnisign\Model\Check;
use Omnisign\Model\CheckKind;
use Omnisign\Model\Level;
use Omnisign\Model\TenantDocument;
use Omnisign\Model\TenantFile;
use Omnisign\Model\TenantFileStatus;
use Omnisign\Model\Terms;
use Omnisign\Model\Verification;
use Omnisign\Model\VerificationResult;
use Omnisign\Model\VerificationStatus;
use Omnisign\Registry;
use Omnisign\Request\Identity\Start;
use Omnisign\TenantFileInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpClient\MockHttpClient;

/** The identity-check and tenant-file contracts (1.1), beside the signature's, which stays as it was. */
final class IdentityContractsTest extends TestCase
{
    public function testAGatewayBuiltBefore11KeepsTheSignatureOnly(): void
    {
        $capabilities = new Capabilities([Level::SIMPLE]);
        self::assertSame([Capabilities::SIGNATURE], $capabilities->contracts);
        self::assertTrue($capabilities->keeps(Capabilities::SIGNATURE));
        self::assertFalse($capabilities->keeps(Capabilities::IDENTITY_CHECK));
        self::assertSame(['simple'], $capabilities->toArray()['levels']);
        self::assertNull($capabilities->toArray()['terms']);

        $registry = new Registry([new StubFactory()], ['platform' => ['factory' => 'stub', 'options' => ['host' => 'h']]]);
        $gateway = $registry->get('platform');
        self::assertInstanceOf(IdentityCheckInterface::class, $gateway, 'the gateway has the door');
        self::assertFalse($gateway->supports(Start::class), 'but no action behind it');
        try {
            $registry->identity('platform');
            self::fail();
        } catch (NotSupportedException $e) {
            self::assertStringContainsString('does not verify identities', $e->getMessage());
        }
        $this->expectException(NotSupportedException::class);
        $registry->tenantFiles('platform');
    }

    public function testAGatewayThatDoesNotVerifySaysSoWhenAsked(): void
    {
        $gateway = (new Registry([new StubFactory()], ['p' => ['factory' => 'stub', 'options' => ['host' => 'h']]]))->get('p');
        self::assertInstanceOf(IdentityCheckInterface::class, $gateway);
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('does not support Start');
        $gateway->startVerification(new Verification());
    }

    public function testWhatAVerificationKeepsAndWhatItDropsOnEachAnswer(): void
    {
        $started = (new Verification(key: 'booking-42'))->with(reference: 'vs_1', url: 'https://flow', clientSecret: 'secret');
        self::assertSame(['vs_1', 'https://flow', 'secret', 'booking-42'], [$started->reference(), $started->url, $started->clientSecret, $started->key]);

        $later = $started->with(status: VerificationStatus::VERIFIED);
        self::assertSame(['vs_1', VerificationStatus::VERIFIED, null, null], [$later->reference, $later->status, $later->url, $later->clientSecret], 'the flow\'s address and secret are never carried over');
        self::assertTrue($later->status->isFinal());

        $this->expectException(\LogicException::class);
        (new Verification())->reference();
    }

    public function testAResultSaysItsChecks(): void
    {
        $result = new VerificationResult('vs_1', VerificationStatus::FAILED, [new Check(CheckKind::DOCUMENT, true), new Check(CheckKind::SELFIE, false, 'selfie_face_mismatch')]);

        self::assertFalse($result->verified());
        self::assertSame('selfie_face_mismatch', $result->check(CheckKind::SELFIE)?->code);
        self::assertNull($result->check(CheckKind::LIVENESS));
        self::assertSame([CheckKind::SELFIE], array_map(static fn (Check $c) => $c->kind, $result->failures()));
    }

    public function testATenantFileCarriesItsRetention(): void
    {
        $terms = new Terms(retention: 'P3M');
        $at = new \DateTimeImmutable('2026-11-10 10:00 UTC');
        self::assertSame('2027-02-10T10:00:00+00:00', $terms->keepUntil($at)?->format(\DATE_ATOM));
        self::assertNull((new Terms())->keepUntil($at));

        $file = new TenantFile('8080', '12901', TenantFileStatus::DECLINED, 'COUPLE', [
            new Applicant('1', documents: [new TenantDocument('FINANCIAL', 'SALARY', monthlySum: 2100), new TenantDocument('TAX')]),
            new Applicant('2', documents: [new TenantDocument('FINANCIAL', 'PENSION', monthlySum: 900)]),
            new Applicant('3', Applicant::GUARANTOR, documents: [new TenantDocument('FINANCIAL', 'SALARY', monthlySum: 5000)], guarantorOf: '1'),
        ], keepUntil: $terms->keepUntil($at));
        self::assertSame([3000, 2, 1], [$file->monthlyIncome(), \count($file->tenants()), \count($file->guarantors())], 'the guarantors\' income is theirs');
        self::assertFalse($file->status->isReadable());
        self::assertTrue($file->isExpired(new \DateTimeImmutable('2027-03-01')));
    }

    public function testTheBundleInjectsAGatewayByItsContract(): void
    {
        if (!class_exists(\Omnisign\StripeIdentity\StripeIdentityGatewayFactory::class) || !class_exists(\Omnisign\Dossierfacile\DossierfacileGatewayFactory::class)) {
            self::markTestSkipped('omnisign/stripe-identity and omnisign/dossierfacile are not installed.');
        }
        $container = new ContainerBuilder();
        $container->register('http_client', MockHttpClient::class);
        $container->register(Renting::class)->setAutowired(true)->setPublic(true);
        $container->registerExtension((new OmnisignBundle())->getContainerExtension() ?? throw new \LogicException());
        $container->loadFromExtension('omnisign', ['gateways' => [
            'kyc' => ['factory' => 'stripe-identity', 'options' => ['secret_key' => 'sk_test_x']],
            'dossierfacile' => ['factory' => 'dossierfacile', 'options' => ['client_id' => 'c', 'client_secret' => 's']],
        ]]);
        $container->compile();

        $renting = $container->get(Renting::class);
        self::assertInstanceOf(Renting::class, $renting);
        self::assertInstanceOf(\Omnisign\GatewayInterface::class, $renting->kyc);
        self::assertTrue($renting->kyc->supports(Start::class));
        self::assertStringContainsString('dossier-facile', $renting->dossierfacile->tenantFileConnectUrl('https://x', 's'));
    }
}

final class Renting
{
    public function __construct(public readonly IdentityCheckInterface $kyc, public readonly TenantFileInterface $dossierfacile)
    {
    }
}
