<?php

namespace Omnisign\Model;

/**
 * Where a rental applicant's file stands at the service that checks it.
 * Only a VALIDATED file - and a COMPLETED one, where the provider opens it
 * - may have its documents read; any other, the documents are closed.
 */
enum TenantFileStatus: string
{
    case INCOMPLETE = 'incomplete';
    /** Submitted, waiting for the service's check. */
    case TO_PROCESS = 'to_process';
    /** Every document given, not yet checked (only for a partner that opted in). */
    case COMPLETED = 'completed';
    case VALIDATED = 'validated';
    case DECLINED = 'declined';
    case ARCHIVED = 'archived';
    case UNKNOWN = 'unknown';

    /** Whether its documents may be read now. */
    public function isReadable(): bool
    {
        return \in_array($this, [self::VALIDATED, self::COMPLETED], true);
    }
}
