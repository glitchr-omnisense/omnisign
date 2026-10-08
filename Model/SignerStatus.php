<?php

namespace Omnisign\Model;

enum SignerStatus: string
{
    /** Not asked yet: a draft, or not their turn */
    case WAITING = 'waiting';
    /** Asked: a link was sent, or can be given */
    case NOTIFIED = 'notified';
    /** Opened the document, or proving who they are */
    case OPENED = 'opened';
    case SIGNED = 'signed';
    case DECLINED = 'declined';
    /** The provider gave up on them: an error, an identity not verified */
    case FAILED = 'failed';
}
