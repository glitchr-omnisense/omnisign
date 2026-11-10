<?php

/**
 * The providers as their packages' tests answer them: a MockHttpClient
 * callback serving each call from the Tests/Fixtures of the package that
 * makes it - DocuSeal's recorded from an instance, Yousign's, DocuSign's,
 * Stripe Identity's and DossierFacile's written from their documentation. Nothing leaves the machine.
 *
 * @return Closure(string, string, array<string, mixed>): Symfony\Component\HttpClient\Response\MockResponse
 */

use Symfony\Component\HttpClient\Response\MockResponse;

$fixture = static function (string $factory, string $file, int $status = 200): MockResponse {
    return new MockResponse((string) file_get_contents(\dirname((string) (new ReflectionClass($factory))->getFileName()).'/Tests/Fixtures/'.$file), ['http_code' => $status]);
};

return static function (string $method, string $url, array $options) use ($fixture): MockResponse {
    $path = (string) parse_url($url, \PHP_URL_PATH);
    $ys = 'Omnisign\\Yousign\\YousignGatewayFactory';
    $ds = 'Omnisign\\Docusign\\DocusignGatewayFactory';
    $dl = 'Omnisign\\Docuseal\\DocusealGatewayFactory';
    $si = 'Omnisign\\StripeIdentity\\StripeIdentityGatewayFactory';
    $df = 'Omnisign\\Dossierfacile\\DossierfacileGatewayFactory';

    return match (true) {
        // Yousign
        str_contains($url, 'yousign.app') && 'POST' === $method && str_ends_with($path, '/signature_requests') => $fixture($ys, 'signature-request-draft.json', 201),
        str_contains($url, 'yousign.app') && str_ends_with($path, '/documents') => $fixture($ys, 'document.json', 201),
        str_contains($url, 'yousign.app') && 'POST' === $method && str_ends_with($path, '/signers') => $fixture($ys, 'signer-landlord.json', 201),
        str_contains($url, 'yousign.app') && str_ends_with($path, '/activate') => $fixture($ys, 'activate.json', 201),
        str_contains($url, 'yousign.app') && str_ends_with($path, '/download') => new MockResponse('%PDF-1.7 signed', ['response_headers' => ['content-type' => 'application/pdf']]),
        str_contains($url, 'yousign.app') && 'GET' === $method => $fixture($ys, 'signature-request-done.json'),
        // DocuSign
        str_contains($url, 'docusign.com/oauth/token') => $fixture($ds, 'token.json'),
        str_contains($url, 'docusign.net') && 'POST' === $method && str_ends_with($path, '/envelopes') => $fixture($ds, 'envelope-created.json', 201),
        str_contains($url, 'docusign.net') && 'PUT' === $method => $fixture($ds, 'envelope-update.json'),
        str_contains($url, 'docusign.net') && str_contains($path, '/documents/') => new MockResponse('%PDF-1.7 signed'),
        str_contains($url, 'docusign.net') && 'GET' === $method => $fixture($ds, 'envelope-completed.json'),
        // Stripe Identity
        str_contains($url, 'api.stripe.com') && 'POST' === $method && str_ends_with($path, '/identity/verification_sessions') => $fixture($si, 'session-created.json'),
        str_contains($url, 'api.stripe.com') && str_ends_with($path, '/cancel') => $fixture($si, 'session-canceled.json'),
        str_contains($url, 'api.stripe.com') && str_ends_with($path, '/redact') => $fixture($si, 'session-redacted.json'),
        str_contains($url, 'api.stripe.com') && 'GET' === $method => $fixture($si, 'session-verified-report.json'),
        // DossierFacile
        str_contains($url, 'dossierfacile') && str_ends_with($path, '/token') => $fixture($df, str_contains((string) ($options['body'] ?? ''), 'client_credentials') ? 'token-client.json' : 'token-tenant.json'),
        str_contains($url, 'dossierfacile') && str_ends_with($path, '/dfc/tenant/profile') => $fixture($df, 'profile.json'),
        str_contains($url, 'dossierfacile') && str_contains($path, '/dfc/api/v1/tenants/') => $fixture($df, 'tenant-to-process.json'),
        // DocuSeal
        str_contains($path, '/file/') => new MockResponse('%PDF-1.4 signed'),
        str_ends_with($path, '/api/submissions') && 'POST' === $method => $fixture($dl, 'submissions-create.json'),
        str_contains($path, '/api/submissions/') => $fixture($dl, 'submission-completed.json'),
        default => new MockResponse('{"error":"not recorded"}', ['http_code' => 404]),
    };
};
