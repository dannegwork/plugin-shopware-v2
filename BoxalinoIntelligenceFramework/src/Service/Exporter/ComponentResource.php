<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter;

use Doctrine\DBAL\Connection;

class ComponentResource
{

    CONST BOXALINO_EXPORTER_TYPE_DELTA = "delta";
    CONST BOXALINO_EXPORTER_TYPE_FULL = "full";

    CONST BOXALINO_EXPORTER_STATUS_SUCCESS = "success";
    CONST BOXALINO_EXPORTER_STATUS_FAIL = "fail";
    CONST BOXALINO_EXPORTER_STATUS_PROCESSING = "processing";

    /**
     * @var Connection
     */
    protected $connection;


    /**
     * @var Psr\Log\LoggerInterface
     */
    protected $logger;


    /**
     * @param Connection $connection
     */
    public function __construct(
        Connection $connection,
        Psr\Log\LoggerInterface $logger
    ) {
        $this->connection = $connection;
        $this->logger = $logger;
    }


    /**
     * Getting a list of product attributes and the table it comes from
     * To be used in the general SQL select
     *
     * @return array
     */
    public function getProductAttributes()
    {
        $account = $this->getAccount();
        $all_attributes = array();
        $exclude = array_merge($this->translationFields, array('articleID','id','active', 'articledetailsID'));
        $db = $this->db;
        $db_name = $db->getConfig()['dbname'];
        $tables = ['s_articles', 's_articles_details', 's_articles_attributes'];
        $select = $db->select()
            ->from(
                array('col' => 'information_schema.columns'),
                array('COLUMN_NAME', 'TABLE_NAME')
            )
            ->where('col.TABLE_SCHEMA = ?', $db_name)
            ->where("col.TABLE_NAME IN (?)", $tables);

        $attributes = $db->fetchAll($select);
        foreach ($attributes as $attribute) {

            if (in_array($attribute['COLUMN_NAME'], $exclude)) {
                if ($attribute['TABLE_NAME'] != 's_articles_details') {
                    continue;
                }
            }
            $key = "{$attribute['TABLE_NAME']}.{$attribute['COLUMN_NAME']}";
            $all_attributes[$key] = $attribute['COLUMN_NAME'];
        }

        $requiredProperties = array('id','articleID');
        $filteredAttributes = $this->_config->getAccountProductsProperties($account, $all_attributes, $requiredProperties);
        $filteredAttributes['s_articles.active'] = 'bx_parent_active';

        return $filteredAttributes;
    }

}