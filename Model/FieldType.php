<?php

namespace Omnisign\Model;

enum FieldType: string
{
    case SIGNATURE = 'signature';
    case INITIALS = 'initials';
    case DATE = 'date';
    case TEXT = 'text';
    /** A handwritten mention: "lu et approuvé", "bon pour accord" */
    case MENTION = 'mention';
}
