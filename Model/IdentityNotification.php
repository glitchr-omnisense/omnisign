<?php

namespace Omnisign\Model;

/**
 * What a provider told the application's webhook about an identity
 * verification, its signature checked: one event, one session. Fetch the
 * verification for the rest. No payload is carried: it may hold personal
 * data, and the event and the reference are all a site needs.
 */
final readonly class IdentityNotification
{
    /**
     * @param string      $type      the provider's own name for the event
     * @param string|null $reference the verification, as start() named it
     * @param string|null $key       the application's handle given at start, when the provider sends it back
     * @param string|null $id        the same for a callback sent twice: what to deduplicate on
     */
    public function __construct(
        public IdentityEvent $event,
        public string $type,
        public ?string $reference = null,
        public ?string $key = null,
        public ?\DateTimeImmutable $at = null,
        public ?string $id = null,
    ) {
    }
}
