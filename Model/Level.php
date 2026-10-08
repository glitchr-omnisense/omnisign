<?php

namespace Omnisign\Model;

/** How strong a signature is, in eIDAS's terms (Regulation (EU) 910/2014). */
enum Level: string
{
    /** Simple electronic signature: an e-mail, a code by e-mail or SMS */
    case SIMPLE = 'simple';
    /** Advanced: uniquely linked to the signer, who is identified (an identity document) */
    case ADVANCED = 'advanced';
    /** Qualified: on a qualified certificate - the equivalent of a handwritten signature */
    case QUALIFIED = 'qualified';
}
