<?php

namespace Omnisign\Model;

enum FieldType: string
{
    case SIGNATURE = 'signature';
    case INITIALS = 'initials';
    case DATE = 'date';
    case TEXT = 'text';
    /**
     * A mention such as "lu et approuvé", "bon pour accord": what each
     * provider makes of it differs - Yousign writes the mention itself when
     * the signer signs (its "mention" field, 255 characters at most), the
     * signer does not type it; DocuSign and DocuSeal ask the signer to type
     * it in a text field. For a statement the signer must write themselves,
     * use a statement (Field::forStatement()).
     */
    case MENTION = 'mention';
}
