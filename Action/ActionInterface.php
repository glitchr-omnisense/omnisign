<?php

namespace Omnisign\Action;

use Omnisign\Request\Request;

/** One thing a gateway does: answers the requests it supports. */
interface ActionInterface
{
    public function supports(Request $request): bool;

    /** Sets the request's result, or throws an Omnisign\Exception\OmnisignException. */
    public function execute(Request $request): void;
}
