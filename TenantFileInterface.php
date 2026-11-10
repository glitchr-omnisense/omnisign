<?php

namespace Omnisign;

use Omnisign\Model\TenantFile;
use Omnisign\Model\TenantFileNotification;

/**
 * The tenant-file contract (Capabilities::TENANT_FILE): a rental
 * applicant's file, obtained with their consent through the provider's
 * connect flow - the consent page, the code exchanged and the file read,
 * the file read again later, the provider's webhooks. Each method is a
 * shortcut for execute() with its request (Request\TenantFile\*).
 *
 * The file is personal data under the provider's rules (Capabilities::$terms):
 * kept no longer than TenantFile::$keepUntil, deleted when the access is
 * revoked, its documents read at the provider and never copied.
 */
interface TenantFileInterface
{
    /** Where the applicant consents: the provider's page, coming back to $redirectUri with a code and $state. */
    public function tenantFileConnectUrl(string $redirectUri, string $state): string;

    /** The applicant came back with $code: their file, now linked to the application. */
    public function connectTenantFile(string $code, string $redirectUri): TenantFile;

    /** A linked applicant's file as it is now. */
    public function fetchTenantFile(string $tenant): TenantFile;

    /**
     * What the provider is telling us of a file (a webhook), its
     * authentication checked.
     *
     * @param array<string, string|string[]> $headers
     *
     * @throws Exception\InvalidNotificationException when it does not hold
     */
    public function notifyTenantFile(string $body, array $headers = []): TenantFileNotification;
}
