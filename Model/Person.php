<?php

namespace Omnisign\Model;

/**
 * Who a verified document names - given only when the application asked
 * for it (the provider's outputs option), and only what is asked: a name
 * to compare with the account's, a birth date for an age. Neither the
 * document's number, nor its address, nor its images are carried.
 */
final readonly class Person
{
    public function __construct(
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?\DateTimeImmutable $birthDate = null,
    ) {
    }
}
