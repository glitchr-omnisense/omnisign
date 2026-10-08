<?php

namespace Omnisign;

use Omnisign\Action\ActionInterface;
use Omnisign\Exception\NotSupportedException;
use Omnisign\Exception\ProviderException;
use Omnisign\Model\Capabilities;
use Omnisign\Model\Download;
use Omnisign\Model\Envelope;
use Omnisign\Model\Notification;
use Omnisign\Model\SigningUrl;

/** A gateway's actions behind one door: the first action supporting a request answers it. */
final class Gateway implements GatewayInterface
{
    /** @param ActionInterface[] $actions */
    public function __construct(
        private readonly string $name,
        private readonly string $title,
        private readonly Capabilities $capabilities,
        private readonly array $actions,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function capabilities(): Capabilities
    {
        return $this->capabilities;
    }

    public function supports(string $request): bool
    {
        $probe = (new \ReflectionClass($request))->newInstanceWithoutConstructor();
        foreach ($this->actions as $action) {
            if ($action->supports($probe)) {
                return true;
            }
        }

        return false;
    }

    public function execute(Request\Request $request): Request\Request
    {
        foreach ($this->actions as $action) {
            if ($action->supports($request)) {
                $action->execute($request);
                if (!$request->isAnswered()) {
                    throw new ProviderException($this->name, \sprintf('%s left the request unanswered.', $action::class));
                }

                return $request;
            }
        }

        throw NotSupportedException::for($request, $this->name);
    }

    public function create(Envelope $envelope): Envelope
    {
        return $this->execute(new Request\Create($envelope))->getEnvelope();
    }

    public function send(Envelope $envelope): Envelope
    {
        return $this->execute(new Request\Send($envelope))->getEnvelope();
    }

    public function fetch(Envelope $envelope): Envelope
    {
        return $this->execute(new Request\Fetch($envelope))->getEnvelope();
    }

    public function remind(Envelope $envelope, ?string $signer = null): Envelope
    {
        return $this->execute(new Request\Remind($envelope, $signer))->getEnvelope();
    }

    public function cancel(Envelope $envelope, ?string $reason = null): Envelope
    {
        return $this->execute(new Request\Cancel($envelope, $reason))->getEnvelope();
    }

    public function download(Envelope $envelope): Download
    {
        return $this->execute(new Request\Download($envelope))->getDownload();
    }

    public function signingUrl(Envelope $envelope, string $signer, ?string $returnUrl = null): SigningUrl
    {
        return $this->execute(new Request\SigningUrl($envelope, $signer, $returnUrl))->getSigningUrl();
    }

    public function notify(string $body, array $headers = []): Notification
    {
        return $this->execute(new Request\Notify($body, $headers))->getNotification();
    }
}
