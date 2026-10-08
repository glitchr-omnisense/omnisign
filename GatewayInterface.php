<?php

namespace Omnisign;

use Omnisign\Model\Capabilities;
use Omnisign\Model\Download;
use Omnisign\Model\Envelope;
use Omnisign\Model\Notification;
use Omnisign\Model\SigningUrl;
use Omnisign\Request\Request;

/**
 * One way to have documents signed, configured - a signature service, an
 * instance of one's own: the same questions for all of them. Each typed
 * method is a shortcut for execute() with its request; a gateway that does
 * not do something throws NotSupportedException, and supports() says so
 * beforehand.
 *
 * A gateway has documents signed. It keeps nothing for the application -
 * neither the documents nor their proof: the application stores what
 * download() gives it, in its own vault.
 */
interface GatewayInterface
{
    public function getName(): string;

    public function getTitle(): string;

    /** What its signatures are: the levels, in the page or by e-mail, in turn, through whom. */
    public function capabilities(): Capabilities;

    /** @param class-string<Request> $request */
    public function supports(string $request): bool;

    /**
     * @template T of Request
     *
     * @param T $request
     *
     * @return T answered
     *
     * @throws Exception\NotSupportedException
     * @throws Exception\ProviderException
     */
    public function execute(Request $request): Request;

    /** Create the envelope: it comes back with its reference on this gateway, to keep. */
    public function create(Envelope $envelope): Envelope;

    /** Send it: the signers are asked. */
    public function send(Envelope $envelope): Envelope;

    /** Where it stands now. */
    public function fetch(Envelope $envelope): Envelope;

    /** Ask again who has not signed; one signer by key. */
    public function remind(Envelope $envelope, ?string $signer = null): Envelope;

    /** Withdraw it. */
    public function cancel(Envelope $envelope, ?string $reason = null): Envelope;

    /** The signed documents and their evidence. */
    public function download(Envelope $envelope): Download;

    /** Where a signer signs in the application's page, now. */
    public function signingUrl(Envelope $envelope, string $signer, ?string $returnUrl = null): SigningUrl;

    /**
     * What the provider is telling us (a webhook): checked against its
     * signature, read as one event.
     *
     * @param array<string, string|string[]> $headers
     *
     * @throws Exception\InvalidNotificationException when the signature does not hold
     */
    public function notify(string $body, array $headers = []): Notification;
}
