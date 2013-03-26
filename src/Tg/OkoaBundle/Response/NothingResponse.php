<?php

namespace Tg\OkoaBundle\Response;

use Symfony\Component\HttpFoundation\Response;

/**
 * Return an empty response
 */
class NothingResponse extends Response
{
    /**
     * Create an empty response
     */
    public function __construct()
    {
        parent::__construct('', 200, [
            'Content-Type' => 'text/plain'
        ]);
    }
}
