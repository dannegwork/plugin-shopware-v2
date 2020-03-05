<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter\Item;

class Facet extends ItemsAbstract
{

    public function export()
    {
        // TODO: Implement export() method.
    }

    /**
     * Export product facets from s_filter_options, s_filter_values
     * Creating product_<filter>.csv and optionID_mapped.csv files
     *
     * @throws Zend_Db_Adapter_Exception
     * @throws Zend_Db_Statement_Exception
     */
    public function exportItemFacets()
    {
        $db = $this->db;
        $account = $this->getAccount();
        $files = $this->getFiles();
        $mapped_option_values = array();
        $option_values = array();
        $languages = $this->_config->getAccountLanguages($account);
        $sql = $db->select()->from(array('f_o' => 's_filter_options'));
        $facets = $db->fetchAll($sql);
        foreach ($facets as $facet) {
            $log = true;
            $facet_id = $facet['id'];
            $facet_name = "option_{$facet_id}";

            $data = array();
            $localized_columns = array();
            $foreachstart = microtime(true);
            foreach ($languages as $shop_id => $language) {
                $localized_columns[$language] = "value_{$language}";
                $sql = $db->select()
                    ->from(array('f_v' => 's_filter_values'))
                    ->joinLeft(
                        array('c_t' => 's_core_translations'),
                        'c_t.objectkey = f_v.id AND c_t.objecttype = \'propertyvalue\' AND c_t.objectlanguage = ' . $shop_id,
                        array('objectdata')
                    )
                    ->where('f_v.optionId = ?', $facet_id);
                $start = microtime(true);
                $stmt = $db->query($sql);
                while ($facet_value = $stmt->fetch()) {
                    if($log){
                        $end = (microtime(true) - $start) * 1000;
                        $this->log->info("Facets option ($facet_name) time for query with {$language}: $end ms, memory: " . memory_get_usage(true));
                        $log = false;
                    }
                    $value = trim(reset(unserialize($facet_value['objectdata'])));
                    $value = $value == '' ? trim($facet_value['value']) : $value;
                    if (isset($option_values[$facet_value['id']])) {
                        $option_values[$facet_value['id']]["value_{$language}"] = $value;
                        $mapped_option_values[$facet_value['id']]["value_{$language}"] = "{$value}_bx_{$facet_value['id']}";
                        continue;
                    }
                    $option_values[$facet_value['id']] = array("{$facet_name}_id" => $facet_value['id'], "value_{$language}" => $value);
                    $mapped_option_values[$facet_value['id']] = array("{$facet_name}_id" => $facet_value['id'], "value_{$language}" => "{$value}_bx_{$facet_value['id']}");
                }
                $end = (microtime(true) - $start) * 1000;
                $this->log->info("Facets option ($facet_name) time for data processing with {$language}: $end ms, memory: " . memory_get_usage(true));
            }
            $option_values = array_merge(array(array_keys(end($option_values))), $option_values);
            $files->savepartToCsv("{$facet_name}.csv", $option_values);

            $mapped_option_values = array_merge(array(array_keys(end($mapped_option_values))), $mapped_option_values);
            $files->savepartToCsv("{$facet_name}_bx_mapped.csv", $mapped_option_values);

            $optionSourceKey = $this->bxData->addResourceFile($files->getPath("{$facet_name}.csv"), "{$facet_name}_id", $localized_columns);
            $optionMappedSourceKey = $this->bxData->addResourceFile($files->getPath("{$facet_name}_bx_mapped.csv"), "{$facet_name}_id", $localized_columns);

            $foreachstartend = (microtime(true) - $foreachstart) * 1000;
            $this->log->info("Facets option (" . $facet_name.") time for filter values with translation: " . $foreachstartend . "ms, memory: " . memory_get_usage(true));

            $sql = $db->select()
                ->from(array('a' => 's_articles'),
                    array()
                )
                ->join(
                    array('d' => 's_articles_details'),
                    $this->qi('d.articleID') . ' = ' . $this->qi('a.id') . ' AND ' .
                    $this->qi('d.kind') . ' <> ' . $db->quote(3),
                    array('d.id')
                )
                ->join(array('f_v' => 's_filter_values'),
                    "f_v.optionID = {$facet['id']}",
                    array("{$facet_name}_id" => 'f_v.id')
                )
                ->join(array('f_a' => 's_filter_articles'),
                    'f_a.articleID = a.id  AND f_v.id = f_a.valueID',
                    array()
                );
            if ($this->delta) {
                $sql->where('a.id IN(?)', $this->deltaIds);
            }
            $log = true;
            $start = microtime(true);
            $stmt = $db->query($sql);

            $header = true;
            while ($row = $stmt->fetch()) {
                if($log) {
                    $end = (microtime(true) -$start) * 1000;
                    $this->log->info("Facets option ($facet_name) query time for products: " . $end . "ms, memory: " . memory_get_usage(true));
                    $log = false;
                }
                if($header) {
                    $data[] = array_keys($row);
                    $header = false;
                }
                if(isset($this->shopProductIds[$row['id']])){
                    $data[] = $row;
                }
            }

            $second_reference = $data;
            $files->savepartToCsv("product_{$facet_name}.csv", $data);
            $attributeSourceKey = $this->bxData->addCSVItemFile($files->getPath("product_{$facet_name}.csv"), 'id');
            $this->bxData->addSourceLocalizedTextField($attributeSourceKey, "optionID_{$facet_id}", "{$facet_name}_id", $optionSourceKey);
            $this->bxData->addSourceStringField($attributeSourceKey, "optionID_{$facet_id}_id", "{$facet_name}_id");

            $files->savepartToCsv("product_{$facet_name}_mapped.csv", $second_reference);
            $secondAttributeSourceKey = $this->bxData->addCSVItemFile($files->getPath("product_{$facet_name}_mapped.csv"), 'id');
            $this->bxData->addSourceLocalizedTextField($secondAttributeSourceKey, "optionID_mapped_{$facet_id}", "{$facet_name}_id", $optionMappedSourceKey);
            $this->bxData->addSourceStringField($secondAttributeSourceKey, "optionID_{$facet_id}_id_mapped", "{$facet_name}_id");
            $end = (microtime(true) - $start) * 1000;

            $this->log->info("Facets option ($facet_name) data processing time for products: " . $end . "ms, memory: " . memory_get_usage(true));
        }
    }


}