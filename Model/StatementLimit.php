<?php

namespace Omnisign\Model;

/**
 * What a provider can carry of a statement, in one mode: how long it may
 * be, in characters or in bytes (UTF-8), as its documentation says - null
 * when its documentation states no limit - and where that was read.
 * Capabilities::$statements lists one per mode a gateway carries.
 */
final readonly class StatementLimit
{
    public const CHARACTERS = 'characters';
    public const BYTES = 'bytes';

    /**
     * @param int|null $maxLength the longest statement, in $unit; null: none stated by the provider
     * @param string   $unit      CHARACTERS or BYTES (UTF-8)
     * @param string   $source    where the limit was read, and when
     */
    public function __construct(
        public StatementMode $mode,
        public ?int $maxLength,
        public string $unit = self::CHARACTERS,
        public string $source = '',
    ) {
    }

    /** The statement's length in this limit's unit. */
    public function length(string $text): int
    {
        return self::BYTES === $this->unit ? \strlen($text) : mb_strlen($text, 'UTF-8');
    }

    /** Whether the statement fits in one field: true when no limit is stated. */
    public function fits(string $text): bool
    {
        return null === $this->maxLength || $this->length($text) <= $this->maxLength;
    }

    /** @return array{mode: string, maxLength: int|null, unit: string, source: string} */
    public function toArray(): array
    {
        return ['mode' => $this->mode->value, 'maxLength' => $this->maxLength, 'unit' => $this->unit, 'source' => $this->source];
    }
}
