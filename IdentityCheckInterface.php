<?php

namespace Omnisign;

use Omnisign\Model\IdentityNotification;
use Omnisign\Model\Verification;
use Omnisign\Model\VerificationResult;

/**
 * The identity-check contract (Capabilities::IDENTITY_CHECK): a session in
 * which a person shows an identity document and, when asked, a selfie
 * taken live; where it stands; what it established; its webhooks. Each
 * method is a shortcut for execute() with its request (Request\Identity\*);
 * a gateway that does not verify identities throws NotSupportedException.
 *
 * Personal data: the gateway carries the outcome and keeps nothing. The
 * images and what was read of the document stay at the provider, erased
 * there by redact() once the purpose is served (docs/usage-identity.md).
 */
interface IdentityCheckInterface
{
    /** Start the session: the Verification comes back with its reference and the person's address (not to store). */
    public function startVerification(Verification $verification): Verification;

    /** Where it stands; while the person is awaited, a fresh address. */
    public function fetchVerification(Verification $verification): Verification;

    /** The checks performed and their outcome. */
    public function verificationResult(Verification $verification): VerificationResult;

    /** Withdraw it, before the person completes it. */
    public function cancelVerification(Verification $verification): Verification;

    /** Have the provider erase its personal data; the outcome stays. */
    public function redactVerification(Verification $verification): Verification;

    /**
     * What the provider is telling us of a verification (a webhook): checked
     * against its signature, read as one event.
     *
     * @param array<string, string|string[]> $headers
     *
     * @throws Exception\InvalidNotificationException when the signature does not hold
     */
    public function notifyIdentity(string $body, array $headers = []): IdentityNotification;
}
