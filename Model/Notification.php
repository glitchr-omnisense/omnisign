<?php

namespace Omnisign\Model;

/**
 * What a provider told the application's webhook, its signature checked:
 * an event about one envelope - and a signer, for SIGNED, OPENED, DECLINED.
 * Fetch the envelope for the rest.
 */
final readonly class Notification
{
    /**
     * @param string               $type      the provider's own name for the event
     * @param string|null          $reference the envelope, as create() named it
     * @param string|null          $signer    the provider's id of the signer, or their e-mail
     * @param string|null          $id        the same for a callback sent twice: what to deduplicate on
     * @param array<string, mixed> $data      the provider's payload
     */
    public function __construct(
        public Event $event,
        public string $type,
        public ?string $reference = null,
        public ?string $signer = null,
        public ?\DateTimeImmutable $at = null,
        public ?string $id = null,
        public array $data = [],
    ) {
    }
}
