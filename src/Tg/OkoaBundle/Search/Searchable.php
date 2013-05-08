<?php

namespace Tg\OkoaBundle\Search;

use Tg\OkoaBundle\Search\Document;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

interface Searchable
{
    public function getSearchDocument(Document $document, ClassMetadata $metadata);
    public function getSearchDefinition(Definition $definition, ClassMetadata $metadata);
}
