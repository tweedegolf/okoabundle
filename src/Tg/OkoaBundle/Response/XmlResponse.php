<?php

namespace Tg\OkoaBundle\Response;

use DOMDocument;
use DOMElement;
use Iterator;
use SimpleXMLElement;
use Symfony\Component\HttpFoundation\Response;
use Tg\OkoaBundle\Util\ArrayUtil;
use Tg\OkoaBundle\Util\StringUtil;

/**
 * Return some xml as a response.
 */
class XmlResponse extends Response
{
    private $root;

    private $data;

    private $numNodePrefix;

    /**
     * Create a simple html page, if title is given, a small skeleton is added.
     * In this case you'll only have to provide the content of the body element.
     * In other cases you'll have to provide the full html.
     * @param string $body
     * @param string $title
     */
    public function __construct($data, $root = 'document', $status = 200, $headers = [])
    {
        parent::__construct('', $status, $headers);
        if (!$this->headers->has('Content-Type')) {
            $this->headers->set('Content-Type', 'text/xml');
        }

        $this->setRootname($root);
        $this->setNumericNodePrefix('node_');
        $this->setData($data);
    }

    /**
     * Set the data to be outputted as xml
     * @param mixed $data
     */
    public function setData($data)
    {
        $this->data = $data;
        $this->update();
    }

    /**
     * Set the name of the root element
     * @param string $name
     */
    public function setRootname($name)
    {
        $this->root = $name;
        $this->update();
    }

    /**
     * Set the prefix to be used for nodes with a numerical key
     * @param string $prefix
     */
    public function setNumericNodePrefix($prefix)
    {
        $this->numNodePrefix = $prefix;
        $this->update();
    }

    protected function update()
    {
        $data = $this->data;
        if (is_object($this->data) && $this->data instanceof SimpleXMLElement) {
            $data = new DOMDocument('1.0', 'utf-8');
            $data->appendChild(dom_import_simplexml($this->data));
        } else if (!is_object($this->data) || !($this->data instanceof DOMDocument)) {
            $data = new DOMDocument('1.0', 'utf-8');
            $root = $data->createElement($this->root);
            $data->appendChild($root);
            $this->toXml($this->data, $root, $data);
        }

        $this->setContent($data->saveXML());
    }

    /**
     * Insert the given item in the given document at the position identified
     * by the given element.
     * @param mixed $item
     * @param DOMElement $element
     * @param DOMDocument $document
     * @return void
     */
    public function toXml($item, DOMElement $element, DOMDocument $document)
    {
        $iterable = is_array($item) || $item instanceof Iterator;
        if ($iterable && ArrayUtil::isIndexed($item)) {
            foreach ($item as $t) {
                $name = StringUtil::singular($element->tagName);
                $node = $document->createElement($name);
                $this->toXml($t, $node, $document);
                $element->appendChild($node);
            }
        } else if ($iterable) {
            $this->assocToXml($item, $element, $document);
        } else if (is_object($item) && $item instanceof DOMDocument) {
            $element->appendChild($item->getDocumentElement());
        } else if (is_object($item) && $item instanceof DOMElement) {
            $element->appendChild($item);
        } else if (is_object($item) && $item instanceof SimpleXMLElement) {
            $element->appendChild(dom_import_simplexml($item));
        }  else if (is_object($item)) {
            $this->toXml(get_object_vars($item), $element, $document);
        } else if (is_bool($item)) {
            $text = $item ? 'true' : 'false';
            $element->appendChild($document->createTextNode($text));
        } else if (is_null($item)) {
            $element->appendChild($document->createTextNode('null'));
        } else {
            $element->appendChild($document->createTextNode($item));
        }
    }

    /**
     * Transform an associative array or associative iterable to a xml document.
     * @param mixed $iterable
     * @param DOMElement $element
     * @param DOMDocument $document
     * @return void
     */
    public function assocToXml($iterable, DOMElement $element, DOMDocument $document) {
        foreach ($iterable as $key => $item) {
            $iterable = is_array($item) || $item instanceof Iterator;
            if ($iterable && ArrayUtil::isIndexed($item)) {
                $key = StringUtil::pluralize($key);
            }

            if (is_int($key)) {
                $key = $this->numNodePrefix . $key;
            }

            $node = $document->createElement($key);
            $element->appendChild($node);
            $this->toXml($item, $node, $document);
        }
    }
}
