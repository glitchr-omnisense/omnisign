<?php

namespace Omnisign\Model;

enum Event: string
{
    case SENT = 'sent';
    case OPENED = 'opened';
    case SIGNED = 'signed';
    case DECLINED = 'declined';
    case COMPLETED = 'completed';
    case EXPIRED = 'expired';
    case CANCELED = 'canceled';
    case OTHER = 'other';
}
