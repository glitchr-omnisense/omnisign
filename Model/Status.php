<?php

namespace Omnisign\Model;

/** Where an envelope stands. */
enum Status: string
{
    /** Created on the provider, not sent: still editable */
    case DRAFT = 'draft';
    /** Sent: the signers may sign */
    case SENT = 'sent';
    /** Every signer signed */
    case COMPLETED = 'completed';
    /** A signer declined */
    case DECLINED = 'declined';
    /** Past its deadline, unsigned */
    case EXPIRED = 'expired';
    /** Cancelled by the sender, or by the provider after an error */
    case CANCELED = 'canceled';
    case UNKNOWN = 'unknown';

    public function isOver(): bool
    {
        return \in_array($this, [self::COMPLETED, self::DECLINED, self::EXPIRED, self::CANCELED], true);
    }
}
