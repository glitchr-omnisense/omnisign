<?php

namespace Omnisign\Model;

/**
 * What a gateway's signatures are, said before anything is sent: the
 * levels it offers, whether a signer may sign in the application's page,
 * in turn, after proving who they are, and through whom. The operations it
 * answers are asked of the gateway itself (supports()).
 */
final readonly class Capabilities
{
    /** @param list<Level> $levels */
    public function __construct(
        public array $levels = [Level::SIMPLE],
        public bool $embedded = false,
        public bool $ordered = true,
        public bool $identityCheck = false,
        public bool $thirdParty = true,
        public bool $documents = true,
        public bool $templates = false,
    ) {
    }

    public function offers(Level $level): bool
    {
        return \in_array($level, $this->levels, true);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['levels' => array_map(static fn (Level $l) => $l->value, $this->levels)] + get_object_vars($this);
    }
}
