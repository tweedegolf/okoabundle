<?php

namespace Tg\OkoaBundle\Response;

use Symfony\Component\HttpFoundation\Response;

/**
 * Return a simple text as a response.
 */
class TextResponse extends Response
{
    /**
     * Print the text in content as a result.
     * @param string $content
     */
    public function __construct($content)
    {
        $content = (string)$content;
        parent::__construct($content, 200, [
            'Content-Type' => 'text/plain'
        ]);
    }
}
