<?php

namespace Omnisign\Tests;

use Omnisign\Exception\InvalidConfigException;
use Omnisign\Exception\NotSupportedException;
use Omnisign\Exception\ProviderException;
use Omnisign\Model\Envelope;
use Omnisign\Model\Signer;
use Omnisign\Model\SignerStatus;
use Omnisign\Model\Status;
use Omnisign\Registry;
use Omnisign\Request\Download;
use Omnisign\Request\Fetch;
use PHPUnit\Framework\TestCase;

final class GatewayTest extends TestCase
{
    private function registry(): Registry
    {
        return new Registry([new StubFactory()], [
            'contracts' => ['factory' => 'stub', 'options' => ['host' => 'sign.example']],
            'quiet' => ['factory' => 'stub', 'options' => ['host' => 'quiet.example', 'silent' => true]],
            'broken' => ['factory' => 'stub'],
        ]);
    }

    public function testAnEnvelopeIsCreatedSentAndFetched(): void
    {
        $registry = $this->registry();
        $gateway = $registry->get('contracts');
        $created = $gateway->create(new Envelope('Bail', signers: [new Signer('tenant', 'Camille Érable', 'camille@erable.example')]));

        self::assertSame([Status::DRAFT, 'r-tenant'], [$created->status, $created->state('tenant')->reference]);
        $sent = $gateway->send($created);
        self::assertSame([Status::SENT, SignerStatus::NOTIFIED], [$sent->status, $sent->state('tenant')->status]);
        self::assertSame(Status::SENT, $gateway->fetch($created)->status);
        self::assertSame($registry->get('contracts'), $gateway, 'built once');
    }

    public function testWhatAGatewayDoesNotDoIsSaidBeforehandAndThrown(): void
    {
        $quiet = $this->registry()->get('quiet');

        self::assertFalse($quiet->supports(Fetch::class));
        self::assertFalse($quiet->supports(Download::class));
        try {
            $quiet->remind(new Envelope('x', reference: 'r'));
            self::fail();
        } catch (NotSupportedException $e) {
            self::assertSame('The "stub" gateway does not support Remind.', $e->getMessage());
        }
        $this->expectException(ProviderException::class);
        $quiet->execute(new Mute());
    }

    public function testMisconfigurationAndAnEnvelopeNeverCreated(): void
    {
        try {
            $this->registry()->get('broken');
            self::fail();
        } catch (InvalidConfigException $e) {
            self::assertSame('The "stub" gateway needs: host.', $e->getMessage());
        }
        $this->expectExceptionMessage('The envelope "Bail" was never created on a provider: create() it first.');
        $this->registry()->get('contracts')->send(new Envelope('Bail'));
    }
}
