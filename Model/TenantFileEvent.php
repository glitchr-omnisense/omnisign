<?php

namespace Omnisign\Model;

/** What a provider's webhook says of a rental applicant's file. */
enum TenantFileEvent: string
{
    /** Submitted, or linked to the site for the first time. */
    case CREATED = 'created';
    case COMPLETED = 'completed';
    case VERIFIED = 'verified';
    case DENIED = 'denied';
    case ARCHIVED = 'archived';
    case RETURNED = 'returned';
    case DELETED = 'deleted';
    case MERGED = 'merged';
    case DISSOCIATED = 'dissociated';
    /** The applicant withdrew the site's access: delete what is held of the file. */
    case REVOKED = 'revoked';
    case OTHER = 'other';
}
