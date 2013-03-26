<?php

namespace Tg\OkoaBundle\Response;

use Symfony\Component\HttpFoundation\Response;

/**
 * Dump a variable as a response.
 */
class DumpResponse extends Response
{
    /**
     * Construct using any number of variables which will all be var_dumped as
     * a response.
     */
    public function __construct()
    {
        parent::__construct('', 200, [
            'Content-Type' => $this->usesHtml() ? 'text/html' : 'text/plain'
        ]);
        $this->resolveContent(func_get_args());
    }

    /**
     * Execute the php var_dump function and set the content to the result of this.
     * @param  array $args
     * @return void
     */
    private function resolveContent($args)
    {
        ob_start();
        call_user_func_array('var_dump', $args);
        $ob = ob_get_clean();
        $this->setContent($ob);
    }

    /**
     * Whether or not html will be used in the dump results.
     * @return boolean
     */
    public function usesHtml()
    {
        return (boolean)ini_get('html_errors');
    }
}
