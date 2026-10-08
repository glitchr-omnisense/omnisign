<?php

/**
 * The gateways, their options from the environment (.env): a gateway is
 * configured only when every key under "needs" is set. "example" is what
 * the bare script uses instead - the providers then answer from their
 * packages' recorded answers.
 *
 * @return array<string, array{factory: string, needs: list<string>, options: array<string, mixed>, example: array<string, mixed>}>
 */
$env = static fn (string $key, mixed $default = null): mixed => (false !== ($v = getenv($key)) && '' !== $v) ? $v : $default;

return [
    'yousign' => [
        'factory' => 'yousign',
        'needs' => ['YOUSIGN_API_KEY'],
        'options' => ['api_key' => $env('YOUSIGN_API_KEY'), 'sandbox' => $env('YOUSIGN_SANDBOX', '1')],
        'example' => ['api_key' => 'example', 'sandbox' => true],
    ],
    'docusign' => [
        'factory' => 'docusign',
        'needs' => ['DOCUSIGN_INTEGRATION_KEY', 'DOCUSIGN_USER_ID', 'DOCUSIGN_PRIVATE_KEY', 'DOCUSIGN_ACCOUNT_ID'],
        'options' => ['integration_key' => $env('DOCUSIGN_INTEGRATION_KEY'), 'user_id' => $env('DOCUSIGN_USER_ID'), 'private_key' => $env('DOCUSIGN_PRIVATE_KEY'), 'account_id' => $env('DOCUSIGN_ACCOUNT_ID')],
        // A key made for the run: the recorded token endpoint does not check it.
        'example' => ['integration_key' => 'example', 'user_id' => 'example', 'private_key' => '@generate', 'account_id' => '0a1b2c3d-4e5f-4a6b-8c7d-9e0f1a2b3c4d'],
    ],
    'docuseal' => [
        'factory' => 'docuseal',
        'needs' => ['DOCUSEAL_URL', 'DOCUSEAL_API_KEY'],
        'options' => ['url' => $env('DOCUSEAL_URL'), 'api_key' => $env('DOCUSEAL_API_KEY')],
        'example' => ['url' => 'http://localhost:13300/api', 'api_key' => 'example'],
    ],
];
