<?php

namespace Omnisign\Model;

/**
 * Documents to sign, by whom, where: what the application asks a provider,
 * and - once created - what the provider made of it: its reference, its
 * status, each signer's state.
 */
final readonly class Envelope
{
    /**
     * @param list<Document>             $documents
     * @param list<Signer>               $signers
     * @param list<Field>                $fields
     * @param bool                       $ordered   each signer in turn (Signer::$order), rather than all at once
     * @param bool                       $embedded  the signers sign in the application's page (SigningUrl), the provider sends them nothing
     * @param string|null                $template  a template of the provider's, instead of documents and fields (DocuSeal's free edition)
     * @param string|null                $key       the application's own name for it, kept by providers that keep one
     * @param string|null                $reference the provider's, once created: keep it
     * @param array<string, SignerState> $states    by signer key
     * @param array<string, string>      $documentReferences the provider's ids of the documents, by key
     * @param array<string, mixed>       $data      what else the provider answered
     */
    public function __construct(
        public string $title,
        public array $documents = [],
        public array $signers = [],
        public array $fields = [],
        public Level $level = Level::SIMPLE,
        public bool $ordered = false,
        public bool $embedded = false,
        public ?\DateTimeImmutable $expiresAt = null,
        public ?string $message = null,
        public ?string $redirectUrl = null,
        public ?string $template = null,
        public ?string $key = null,
        public ?string $reference = null,
        public Status $status = Status::DRAFT,
        public array $states = [],
        public array $documentReferences = [],
        public ?\DateTimeImmutable $completedAt = null,
        public array $data = [],
    ) {
    }

    /**
     * The same envelope as the provider has it now.
     *
     * @param array<string, SignerState>|null $states
     * @param array<string, string>|null      $documentReferences
     * @param array<string, mixed>|null       $data
     */
    public function with(?string $reference = null, ?Status $status = null, ?array $states = null, ?array $documentReferences = null, ?\DateTimeImmutable $completedAt = null, ?array $data = null): self
    {
        return new self($this->title, $this->documents, $this->signers, $this->fields, $this->level, $this->ordered, $this->embedded, $this->expiresAt, $this->message, $this->redirectUrl, $this->template, $this->key, $reference ?? $this->reference, $status ?? $this->status, $states ?? $this->states, $documentReferences ?? $this->documentReferences, $completedAt ?? $this->completedAt, $data ?? $this->data);
    }

    /** The provider's reference; an InvalidConfigException on an envelope never created. */
    public function reference(): string
    {
        return $this->reference ?? throw new \Omnisign\Exception\InvalidConfigException(\sprintf('The envelope "%s" was never created on a provider: create() it first.', $this->title));
    }

    public function signer(string $key): Signer
    {
        foreach ($this->signers as $signer) {
            if ($signer->key === $key) {
                return $signer;
            }
        }

        throw new \Omnisign\Exception\InvalidConfigException(\sprintf('No signer "%s" in the envelope "%s".', $key, $this->title));
    }

    public function state(string $key): ?SignerState
    {
        return $this->states[$key] ?? null;
    }

    /** @return list<Field> */
    public function fieldsOf(string $signer): array
    {
        return array_values(array_filter($this->fields, static fn (Field $f) => $f->signer === $signer));
    }
}
