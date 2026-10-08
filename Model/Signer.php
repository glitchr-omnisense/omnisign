<?php

namespace Omnisign\Model;

/**
 * Who signs: the application's key for them, a name, an e-mail - a phone
 * for a code by SMS -, their place in the order, how they prove who they
 * are. Never an account of the application's.
 */
final readonly class Signer
{
    /**
     * @param string      $key            the application's own name for this signer: what fields and states point at
     * @param int         $order          their turn, from 1, when the envelope is ordered
     * @param string|null $authentication none, email or sms: a code before signing, where the provider sends one
     * @param string|null $role           the role a provider's template knows them by (DocuSeal)
     */
    public function __construct(
        public string $key,
        public string $name,
        public string $email,
        public ?string $phone = null,
        public string $locale = 'fr',
        public int $order = 1,
        public ?string $authentication = null,
        public ?string $role = null,
    ) {
    }

    /** @return array{string, string} the first name and the last name, from $name: the last word is the last name */
    public function names(): array
    {
        $parts = preg_split('~\s+~', trim($this->name)) ?: [];
        if (\count($parts) < 2) {
            return [$parts[0] ?? $this->name, $parts[0] ?? $this->name];
        }
        $last = array_pop($parts);

        return [implode(' ', $parts), $last];
    }
}
