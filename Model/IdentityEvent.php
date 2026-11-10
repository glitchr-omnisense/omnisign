<?php

namespace Omnisign\Model;

/** What a provider's webhook says of an identity verification. */
enum IdentityEvent: string
{
    case CREATED = 'created';
    case PROCESSING = 'processing';
    case VERIFIED = 'verified';
    /** A check failed or the person left: the session waits for another attempt. */
    case REQUIRES_INPUT = 'requires_input';
    case CANCELED = 'canceled';
    /** Its personal data were erased at the provider. */
    case REDACTED = 'redacted';
    case OTHER = 'other';
}
