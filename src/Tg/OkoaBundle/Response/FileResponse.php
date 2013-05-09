<?php

namespace Tg\OkoaBundle\Response;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use RuntimeException;

/**
 * Respond with the contents of a file.
 */
class FileResponse extends Response
{

    protected $disposition;

    protected $filename;

    public function __construct($file, $attach = false, $filename = null)
    {
        parent::__construct(null, 200, [
            'Content-Type' => 'application/octet-stream'
        ]);
        if ($attach) {
            $this->setDispositionAttachment();
        } else {
            $this->setDispositionInline();
        }

        $this->setFilename($filename);
        $this->setPrivate();

        $this->setFile($file);
    }

    public function setContentType($type)
    {
        $this->headers->set('Content-Type', $type);
    }

    public function setDispositionAttachment()
    {
        $this->disposition = ResponseHeaderBag::DISPOSITION_ATTACHMENT;
        $this->updateProps();
    }

    public function setDispositionInline()
    {
        $this->disposition = ResponseHeaderBag::DISPOSITION_INLINE;
        $this->updateProps();
    }

    public function setFilename($name)
    {
        $this->filename = $name;
        $this->updateProps();
    }

    public function setFile($file, $determineType = true)
    {
        if (is_string($file)) {
            if (!file_exists($file) || !is_readable($file)) {
                throw new RuntimeException("Could not read file '$file'");
            }
        }
        $this->setContent($file);
        $this->tryDetermineType();
        $this->updateProps();
    }

    public function tryDetermineType()
    {
        if (is_string($this->content) && strlen($this->content) > 0) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $this->content);
            if (is_string($mime) && strlen($mime) > 0) {
                $this->setContentType($mime);
            }
            finfo_close($finfo);
        }
    }

    public function sendContent()
    {
        readfile($this->content);
    }

    protected function updateProps()
    {
        if (is_string($this->content)) {
            $disp = $this->disposition;
            if ($disp !== ResponseHeaderBag::DISPOSITION_INLINE && $this->filename !== null) {
                $disp .= '; filename=' . $this->filename;
            }
            $this->headers->set('Content-Disposition', $disp);
            $this->headers->set('Content-Length', filesize($this->content));
        } else {
            $this->headers->remove('Content-Disposition');
            $this->headers->remove('Content-Length');
        }
    }
}
