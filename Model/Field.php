<?php

namespace Omnisign\Model;

/**
 * Where a signer signs, initials or writes: a document's page and a
 * position on it, in points from the top left corner - or an anchor, a
 * text of the document the provider looks for.
 *
 * Since 1.2: $maxLength, the characters a signer may type in a TEXT field
 * (the provider's default when null), and $statement - a statement the
 * signer must type themselves (StatementMode::TYPED, the default: a TEXT
 * field showing it to copy) or acknowledge (StatementMode::ACKNOWLEDGED:
 * shown on the document, accepted by signing). Field::forStatement()
 * builds one; the gateway refuses, before any call, a statement its
 * provider cannot carry (Capabilities::fits() says it beforehand).
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
        /** the characters the signer may type in a TEXT field; null: the provider's default */
        public ?int $maxLength = null,
        /** a statement the signer types or acknowledges (since 1.2) */
        public ?string $statement = null,
        /** how: TYPED when null and there is a statement */
        public ?StatementMode $statementMode = null,
    ) {
    }

    /** A statement the signer must type themselves, or acknowledge, at this place: a TEXT field carrying it. */
    public static function forStatement(string $signer, string $document, string $statement, StatementMode $mode = StatementMode::TYPED, int $page = 1, int $x = 0, int $y = 0, ?int $width = null, ?int $height = null, ?string $label = null): self
    {
        return new self($signer, $document, FieldType::TEXT, $page, $x, $y, $width, $height, null, $label, null, $statement, $mode);
    }

    /** How the signer takes its statement on; null when it carries none. */
    public function mode(): ?StatementMode
    {
        return null === $this->statement ? null : ($this->statementMode ?? StatementMode::TYPED);
    }
}
