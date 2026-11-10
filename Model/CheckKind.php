<?php

namespace Omnisign\Model;

/** One check of an identity verification. */
enum CheckKind: string
{
    /** The document is genuine, readable, not expired. */
    case DOCUMENT = 'document';
    /** The face in the selfie is the face on the document. */
    case SELFIE = 'selfie';
    /** A live person took the selfie (no photo of a photo, no screen). */
    case LIVENESS = 'liveness';
    /** A national number matches the name. */
    case ID_NUMBER = 'id_number';
    case EMAIL = 'email';
    case PHONE = 'phone';
}
