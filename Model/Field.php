<?php

namespace Omnisign\Model;

/**
 * Where a signer signs, initials or writes: a document's page and a
 * position on it, in points from the top left corner - or an anchor, a
 * text of the document the provider looks for.
 */
final readonly class Field
{
    public function __construct(
        public string $signer,
        public string $document,
        public FieldType $type = FieldType::SIGNATURE,
        public int $page = 1,
        public int $x = 0,
        public int $y = 0,
        public ?int $width = null,
        public ?int $height = null,
        public ?string $anchor = null,
        /** the mention's text, a text field's label */
        public ?string $label = null,
    ) {
    }
}
