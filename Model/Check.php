<?php

namespace Omnisign\Model;

/**
 * A check a verification performed and its outcome: passed or not, and the
 * provider's code and words when not. No image, no number: what was seen
 * stays with the provider.
 */
final readonly class Check
{
    /**
     * @param bool|null   $passed null while the provider has not decided
     * @param string|null $code   the provider's code for a failure ("selfie_face_mismatch")
     * @param string|null $reason its words for it, to show the person
     */
    public function __construct(
        public CheckKind $kind,
        public ?bool $passed,
        public ?string $code = null,
        public ?string $reason = null,
    ) {
    }
}
