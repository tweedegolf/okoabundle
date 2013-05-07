<?php

namespace Tg\OkoaBundle\Search\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use Tg\OkoaBundle\Search\Definition;
use Tg\OkoaBundle\Search\Document;

class MySqlFulltextManager extends SearchManager
{
    const DEFAULT_EXT = '__search';

    public function removeDocument(Definition $definition, $id)
    {
        $table = $this->getSearchTable($definition);
        $query = "DELETE FROM `{$table}` WHERE `id` = ?";
        $db = $this->getConnection($definition);
        $stmt = $db->prepare($query);
        $stmt->bindValue(1, $id);
        $stmt->execute();
    }

    public function addDocument(Definition $definition, $id, Document $document)
    {
        $table = $this->getSearchTable($definition);
        $fields = [];
        $values = [];
        foreach ($document->getFieldNames() as $field) {
            $fields[] = '`' . $field . '`';
            $values[] = $document->getValue($field);
        }
        $fields = implode(', ', $fields);
        $items = implode(', ', array_fill(0, count($values), '?'));
        $query = "INSERT INTO `{$table}`(`id`, {$fields}) VALUES (?, {$items})";
        $db = $this->getConnection($definition);
        $stmt = $db->prepare($query);
        $stmt->bindValue(1, $id);
        foreach ($values as $key => $value) {
            $stmt->bindValue($key + 2, $value);
        }
        $stmt->execute();
    }

    public function getConnection(Definition $definition)
    {
        return $this->registry->getManagerForClass($definition->getMetadata()->getName())->getConnection();
    }

    protected function getSearchTable(Definition $definition)
    {
        return 'search__' . $definition->getMetadata()->getTableName();
    }

    protected function doCreateIndex(Definition $definition, ObjectManager $em)
    {
        $table = $this->getSearchTable($definition);
        $columns = [];
        $ftfields = [];

        foreach ($definition->getColumnsOfType('fulltext') as $column) {
            $columns[] = "`{$column}` text";
            $ftfields[] = "`{$column}`";
        }

        $columns = implode(', ', $columns);
        if (strlen($columns) > 0) {
            $columns .= ', ';
        }
        $ftfields = implode(', ', $ftfields);
        // FULLTEXT KEY `text` (`text`)
        $query = "CREATE TABLE `{$table}` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            {$columns}
            PRIMARY KEY (`id`),
            FULLTEXT KEY `ftsearch` ({$ftfields})
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
        $db = $this->getConnection($definition);
        $db->exec($query);
    }

    protected function doClearIndex(Definition $definition, ObjectManager $em)
    {
        $table = $this->getSearchTable($definition);
        $query = "TRUNCATE TABLE `{$table}`";
        $db = $this->getConnection($definition);
        $db->exec($query);
    }

    protected function doDeleteIndex(Definition $definition, ObjectManager $em)
    {
        $table = $this->getSearchTable($definition);
        $query = "DROP TABLE IF EXISTS `{$table}`";
        $db = $this->getConnection($definition);
        $db->exec($query);
    }

    protected function doGetBuilderForSimpleQuery(Definition $definition, ObjectManager $em, $query)
    {
        $table = $this->getSearchTable($definition);
        $columns = $definition->getColumnsOfType('fulltext');
        $ftcolumns = [];
        foreach ($columns as $column) {
            $ftcolumns[] = "`{$column}`";
        }
        $ftcolumns = implode(', ', $ftcolumns);

        $db = $em->getConnection();
        $stmt = $db->prepare("SELECT `id` FROM `{$table}` WHERE MATCH ({$ftcolumns}) AGAINST (?)");
        $stmt->bindValue(1, $query);
        $stmt->execute();

        $searchResults = [];
        do {
            $result = $stmt->fetchColumn(0);
            if ($result !== false) {
                $searchResults[] = (int)$result;
            }
        } while ($result !== false);

        $id = 'e.' . $definition->getMetadata()->getIdentifier()[0];
        $qb = $em->createQueryBuilder();
        $qb->select('e')
            ->from($definition->getMetadata()->getName(), 'e');

        if (count($searchResults) === 0) {
            $qb->where(
                $qb->expr()->andX(
                    $qb->expr()->isNull($id),
                    $qb->expr()->isNotNull($id)
                )
            );
        } else {
            $qb->where(
                $qb->expr()->in($id, $searchResults)
            );
        }
        return $qb;
    }
}
