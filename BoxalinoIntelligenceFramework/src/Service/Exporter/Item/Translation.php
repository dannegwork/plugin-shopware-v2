<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter\Item;

class Translation extends ItemsAbstract
{

    public function export()
    {
        // TODO: Implement export() method.
    }

    /**
     * Export item translations
     *
     * @throws Zend_Db_Adapter_Exception
     * @throws Zend_Db_Statement_Exception
     */
    public function exportItemTranslationFields()
    {
        $db = $this->db;
        $account = $this->getAccount();
        $files = $this->getFiles();
        $data = array();
        $selectFields = array();
        $attributeValueHeader = array();
        $translationJoins = array();
        $select = $db->select();
        foreach ($this->_config->getAccountLanguages($account) as $shop_id => $language) {
            $select->joinLeft(array("t_{$language}" => "s_articles_translations"), "t_{$language}.articleID = sa.id AND t_{$language}.languageID = {$shop_id}", array());
            $translationJoins[$shop_id] = "t_{$language}";
            foreach ($this->translationFields as $field) {
                if(!isset($attributeValueHeader[$field])){
                    $attributeValueHeader[$field] = array();
                }
                $column = "{$field}_{$language}";
                $attributeValueHeader[$field][$language] = $column;
                $mainTableRef = strpos($field, 'attr') !== false ? 'b.' . $field : 'sa.' . $field;
                $translationRef = 't_' . $language . '.' . $field;
                $selectFields[$column] = new Zend_Db_Expr("CASE WHEN {$translationRef} IS NULL OR CHAR_LENGTH({$translationRef}) < 1 THEN {$mainTableRef} ELSE {$translationRef} END");
            }
        }
        $selectFields[] = 'a.id';
        $header = true;
        $countMax = 2000000;
        $limit = 1000000;
        $doneCases = array();
        $log = true;
        $totalCount = 0;
        $start = microtime(true);
        $page = 1;
        $select->from(array('sa' => 's_articles'), $selectFields)
            ->join(array('a' => 's_articles_details'), 'a.articleID = sa.id', array())
            ->joinLeft(array('b' => 's_articles_attributes'), 'a.id = b.articledetailsID', array())
            ->order('sa.id');

        while($countMax > $totalCount + $limit) {
            $sql = clone $select;
            $sql->limit($limit, ($page - 1) * $limit);

            if ($this->delta) {
                $sql->where('a.articleID IN(?)', $this->deltaIds);
            }

            $currentCount = 0;
            $this->log->info("Translation query: " . $db->quote($sql));
            $stmt = $db->query($sql);
            if($stmt->rowCount()) {
                while ($row = $stmt->fetch()) {
                    $currentCount++;
                    if($currentCount%10000 == 0 || $log) {
                        $end = (microtime(true) - $start) * 1000;
                        $this->log->info("Translation process at count: {$currentCount}, took: {$end} ms, memory: " . memory_get_usage(true));
                        $log = false;
                    }
                    if(!isset($this->shopProductIds[$row['id']])) {
                        continue;
                    }
                    if(isset($doneCases[$row['id']])){
                        continue;
                    }
                    if($header) {
                        $data[] = array_keys($row);
                        $header = false;
                    }
                    $data[] = $row;
                    $doneCases[$row['id']] = true;
                    $totalCount++;
                    if(sizeof($data) > 1000) {
                        $files->savePartToCsv('product_translations.csv', $data);
                        $data = [];
                    }
                }
            } else {
                break;
            }
            if($currentCount < $limit-1) {
                break;
            }
            $files->savepartToCsv('product_translations.csv', $data);
            $page++;
        }

        $files->savepartToCsv('product_translations.csv', $data);
        $doneCases = null;
        $attributeSourceKey = $this->bxData->addCSVItemFile($files->getPath('product_translations.csv'), 'id');
        $end = (microtime(true) - $start) * 1000;
        $this->log->info("Translation process finished and took: {$end} ms, memory: " . memory_get_usage(true));
        foreach ($attributeValueHeader as $field => $values) {
            if ($field == 'name') {
                $this->bxData->addSourceTitleField($attributeSourceKey, $values);
            } else if ($field == 'description_long') {
                $this->bxData->addSourceDescriptionField($attributeSourceKey, $values);
            } else {
                $this->bxData->addSourceLocalizedTextField($attributeSourceKey, $field, $values);
            }
        }
    }

}