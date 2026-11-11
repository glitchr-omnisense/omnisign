<?php

namespace Omnisign\Model;

/**
 * How a signer takes a statement on (Field::$statement): typing it
 * themselves, or acknowledging it - shown to them on the document and
 * accepted by signing it. Which a given deed needs, and whether a typed
 * field in a simple electronic signature satisfies a rule of form (Code
 * civil, art. 2297 for a guarantor, art. 1174), is the application's
 * lawyer's question: the family only says what each provider can carry.
 */
enum StatementMode: string
{
    /** The signer types the statement themselves, in a field */
    case TYPED = 'typed';
    /** The statement is shown on the document; the signer signs it */
    case ACKNOWLEDGED = 'acknowledged';
}
