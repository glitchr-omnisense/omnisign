<?php

namespace Omnisign\Model;

/** The identity document a verification reads: what a provider accepts, what it found. */
enum DocumentType: string
{
    case PASSPORT = 'passport';
    case ID_CARD = 'id_card';
    case DRIVING_LICENSE = 'driving_license';
    case RESIDENCE_PERMIT = 'residence_permit';
    case OTHER = 'other';
}
