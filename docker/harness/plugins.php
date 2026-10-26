<?php

/**
 * Every gateway package: its slug (omnisign/<slug>, github.com/glitchr-omnisense/omnisign-<slug>),
 * its tests' namespace and its factory class.
 */
return [
    'yousign' => ['Omnisign\\Yousign\\Tests\\', 'Omnisign\\Yousign\\YousignGatewayFactory'],
    'docusign' => ['Omnisign\\Docusign\\Tests\\', 'Omnisign\\Docusign\\DocusignGatewayFactory'],
    'docuseal' => ['Omnisign\\Docuseal\\Tests\\', 'Omnisign\\Docuseal\\DocusealGatewayFactory'],
];
