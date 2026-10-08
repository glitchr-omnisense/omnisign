<?php

namespace Omnisign\Model;

/** A file: a document to sign, a signed one, the evidence of the signature. */
final readonly class File
{
    public function __construct(
        public string $content,
        public string $filename,
        public string $mimeType = 'application/pdf',
    ) {
    }

    public static function fromPath(string $path, ?string $mimeType = null): self
    {
        $content = @file_get_contents($path);
        if (false === $content) {
            throw new \InvalidArgumentException(\sprintf('Cannot read %s.', $path));
        }

        return new self($content, basename($path), $mimeType ?? (str_ends_with(strtolower($path), '.pdf') ? 'application/pdf' : 'application/octet-stream'));
    }

    public function sha256(): string
    {
        return hash('sha256', $this->content);
    }
}
