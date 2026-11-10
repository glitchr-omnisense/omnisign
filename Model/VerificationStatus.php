<?php

namespace Omnisign\Model;

/**
 * Where an identity verification stands, whoever runs it. PENDING waits for
 * the person (the flow not started, or to be taken again after a failed
 * check: Verification::$error says why); PROCESSING is the provider's turn;
 * VERIFIED and FAILED are the outcome; CANCELED was withdrawn.
 */
enum VerificationStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case VERIFIED = 'verified';
    case FAILED = 'failed';
    case CANCELED = 'canceled';
    case EXPIRED = 'expired';
    case UNKNOWN = 'unknown';

    /** Whether nothing will change any more. */
    public function isFinal(): bool
    {
        return \in_array($this, [self::VERIFIED, self::FAILED, self::CANCELED, self::EXPIRED], true);
    }
}
