<?php

namespace Tg\OkoaBundle\Response;

use Symfony\Component\HttpFoundation\Response;

/**
 * Return some html as a response.
 */
class HtmlResponse extends Response
{
    /**
     * Create a simple html page, if title is given, a small skeleton is added.
     * In this case you'll only have to provide the content of the body element.
     * In other cases you'll have to provide the full html.
     * @param string $body
     * @param string $title
     */
    public function __construct($body, $title = null)
    {
        if ($title !== null) {
            $body = sprintf(
                '<!DOCTYPE html><html><head><title>%s</title></head><body>%s</body></html>',
                $title,
                $body
            );
        }
        parent::__construct($body, 200, [
            'Content-Type' => 'text/html'
        ]);
    }
}
