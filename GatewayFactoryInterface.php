<?php

namespace Omnisign;

/** Builds a gateway from its options (an API key, an account, an instance's address...). */
interface GatewayFactoryInterface
{
    /** The name gateways are configured with: "yousign", "docusign", "docuseal"... */
    public function getName(): string;

    /** @param array<string, mixed> $options */
    public function create(array $options = []): GatewayInterface;
}
