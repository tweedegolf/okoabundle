<?php

namespace Tg\OkoaBundle\Search\Manager;

use Tg\OkoaBundle\Search\Document;

class MySqlFulltextManager extends SearchManager
{
    const DEFAULT_EXT = '__search';

    public function removeDocument($type, $id)
    {
        $table = $this->getSearchTable($type);
        $query = "DELETE FROM {$table} WHERE id = ?";
        $db = $this->getConnection();

        // TODO: run query
    }

    public function addDocument($type, $id, Document $document)
    {
        $table = $this->getSearchTable($type);
        $fields = [];
        $values = [];
        foreach ($document->getFieldNames() as $field) {
            $fields[] = '`' . $field . '`';
            $values[] = $document->getValue($field);
        }
        $fields = implode(', ', $fields);
        $items = implode(', ', array_fill(0, count($values), '?'));
        $query = "INSERT INTO {$table}({$fields}) VALUES ({$items})";
        $db = $this->getConnection();
    }

    protected function getConnection()
    {
        return $this->getEntityManager()->getConnection();
    }

    protected function getSearchTable($type)
    {

    }
}
