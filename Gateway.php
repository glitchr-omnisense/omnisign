<?php

namespace Omnisign;

use Omnisign\Action\ActionInterface;
use Omnisign\Exception\NotSupportedException;
use Omnisign\Exception\ProviderException;
use Omnisign\Model\Capabilities;
use Omnisign\Model\Download;
use Omnisign\Model\Envelope;
use Omnisign\Model\IdentityNotification;
use Omnisign\Model\Notification;
use Omnisign\Model\SigningUrl;
use Omnisign\Model\TenantFile;
use Omnisign\Model\TenantFileNotification;
use Omnisign\Model\Verification;
use Omnisign\Model\VerificationResult;
use Omnisense\Contracts\Policy\PolicyGuard;

/**
 * A gateway's actions behind one door: the first action supporting a
 * request answers it. Since 1.1 the door has the identity-check and
 * tenant-file contracts too; a gateway answers those its actions support.
 *
 * Built by a GatewayFactory, it runs behind the provider's PolicyGuard: each
 * request that reaches the provider is an operation named after its typed
 * method (create, send, fetch, remind, cancel, download, signingUrl,
 * startVerification, fetchVerification, verificationResult,
 * cancelVerification, redactVerification, connectTenantFile,
 * fetchTenantFile; a request of the application's own, its short name with
 * a lower-case initial), checked against the policy's rate limits and the
 * provider's wait before, counted after; Exception\UsageLimitReachedException
 * refuses it before it is made. A webhook read (notify, notifyIdentity,
 * notifyTenantFile) and the consent page's address (tenantFileConnectUrl)
 * reach nothing and are not counted.
 */
final class Gateway implements GatewayInterface, IdentityCheckInterface, TenantFileInterface
{
    /** The operation each request is, for the guard: its typed method's name; null for what reaches no provider. */
    private const OPERATIONS = [
        Request\Create::class => 'create',
        Request\Send::class => 'send',
        Request\Fetch::class => 'fetch',
        Request\Remind::class => 'remind',
        Request\Cancel::class => 'cancel',
        Request\Download::class => 'download',
        Request\SigningUrl::class => 'signingUrl',
        Request\Notify::class => null,
        Request\Identity\Start::class => 'startVerification',
        Request\Identity\Fetch::class => 'fetchVerification',
        Request\Identity\Result::class => 'verificationResult',
        Request\Identity\Cancel::class => 'cancelVerification',
        Request\Identity\Redact::class => 'redactVerification',
        Request\Identity\Notify::class => null,
        Request\TenantFile\ConnectUrl::class => null,
        Request\TenantFile\Connect::class => 'connectTenantFile',
        Request\TenantFile\Fetch::class => 'fetchTenantFile',
        Request\TenantFile\Notify::class => null,
    ];

    private ?PolicyGuard $guard = null;

    /** @param ActionInterface[] $actions */
    public function __construct(
        private readonly string $name,
        private readonly string $title,
        private readonly Capabilities $capabilities,
        private readonly array $actions,
    ) {
    }

    /**
     * The provider's guard: its usage policy as the site runs it, the
     * limiter's counts; null for a gateway built by hand.
     */
    public function guard(): ?PolicyGuard
    {
        return $this->guard;
    }

    /**
     * @internal the factory hands the gateway it built its guard, once
     */
    public function guardWith(PolicyGuard $guard): void
    {
        $this->guard ??= $guard;
    }

    /** The operation a request is, for the guard; null when it reaches no provider. */
    public static function operation(Request\Request $request): ?string
    {
        return \array_key_exists($request::class, self::OPERATIONS) ? self::OPERATIONS[$request::class] : lcfirst((new \ReflectionClass($request))->getShortName());
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
                $operation = null === $this->guard ? null : self::operation($request);
                if (null === $this->guard || null === $operation) {
                    $action->execute($request);
                } else {
                    $this->guard->call($operation, static fn () => $action->execute($request));
                }
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
        return $this->execute(new Request\Create($envelope))->getEnvelope() ?? throw new ProviderException($this->name, 'No envelope answered.');
    }

    public function send(Envelope $envelope): Envelope
    {
        return $this->execute(new Request\Send($envelope))->getEnvelope() ?? throw new ProviderException($this->name, 'No envelope answered.');
    }

    public function fetch(Envelope $envelope): Envelope
    {
        return $this->execute(new Request\Fetch($envelope))->getEnvelope() ?? throw new ProviderException($this->name, 'No envelope answered.');
    }

    public function remind(Envelope $envelope, ?string $signer = null): Envelope
    {
        return $this->execute(new Request\Remind($envelope, $signer))->getEnvelope() ?? throw new ProviderException($this->name, 'No envelope answered.');
    }

    public function cancel(Envelope $envelope, ?string $reason = null): Envelope
    {
        return $this->execute(new Request\Cancel($envelope, $reason))->getEnvelope() ?? throw new ProviderException($this->name, 'No envelope answered.');
    }

    public function download(Envelope $envelope): Download
    {
        return $this->execute(new Request\Download($envelope))->getDownload() ?? throw new ProviderException($this->name, 'No download answered.');
    }

    public function signingUrl(Envelope $envelope, string $signer, ?string $returnUrl = null): SigningUrl
    {
        return $this->execute(new Request\SigningUrl($envelope, $signer, $returnUrl))->getSigningUrl() ?? throw new ProviderException($this->name, 'No signing URL answered.');
    }

    public function notify(string $body, array $headers = []): Notification
    {
        return $this->execute(new Request\Notify($body, $headers))->getNotification() ?? throw new ProviderException($this->name, 'No notification answered.');
    }

    public function startVerification(Verification $verification): Verification
    {
        return $this->execute(new Request\Identity\Start($verification))->getVerification() ?? throw new ProviderException($this->name, 'No verification answered.');
    }

    public function fetchVerification(Verification $verification): Verification
    {
        return $this->execute(new Request\Identity\Fetch($verification))->getVerification() ?? throw new ProviderException($this->name, 'No verification answered.');
    }

    public function verificationResult(Verification $verification): VerificationResult
    {
        return $this->execute(new Request\Identity\Result($verification))->getVerificationResult() ?? throw new ProviderException($this->name, 'No result answered.');
    }

    public function cancelVerification(Verification $verification): Verification
    {
        return $this->execute(new Request\Identity\Cancel($verification))->getVerification() ?? throw new ProviderException($this->name, 'No verification answered.');
    }

    public function redactVerification(Verification $verification): Verification
    {
        return $this->execute(new Request\Identity\Redact($verification))->getVerification() ?? throw new ProviderException($this->name, 'No verification answered.');
    }

    public function notifyIdentity(string $body, array $headers = []): IdentityNotification
    {
        return $this->execute(new Request\Identity\Notify($body, $headers))->getNotification() ?? throw new ProviderException($this->name, 'No notification answered.');
    }

    public function tenantFileConnectUrl(string $redirectUri, string $state): string
    {
        return $this->execute(new Request\TenantFile\ConnectUrl($redirectUri, $state))->getUrl() ?? throw new ProviderException($this->name, 'No URL answered.');
    }

    public function connectTenantFile(string $code, string $redirectUri): TenantFile
    {
        return $this->execute(new Request\TenantFile\Connect($code, $redirectUri))->getTenantFile() ?? throw new ProviderException($this->name, 'No file answered.');
    }

    public function fetchTenantFile(string $tenant): TenantFile
    {
        return $this->execute(new Request\TenantFile\Fetch($tenant))->getTenantFile() ?? throw new ProviderException($this->name, 'No file answered.');
    }

    public function notifyTenantFile(string $body, array $headers = []): TenantFileNotification
    {
        return $this->execute(new Request\TenantFile\Notify($body, $headers))->getNotification() ?? throw new ProviderException($this->name, 'No notification answered.');
    }
}
