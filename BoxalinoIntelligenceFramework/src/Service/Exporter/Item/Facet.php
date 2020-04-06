<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter\Item;

use Doctrine\DBAL\Query\QueryBuilder;

class Facet extends ItemsAbstract
{

    public function export()
    {
       return [];
    }

    public function setFilesDefinitions(){}

    public function getItemRelationQuery(int $page = 1): QueryBuilder
    {
        return $this->connection->createQueryBuilder();
    }

    public function getRequiredFields(): array
    {
        return [];
    }

}
