<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter\Item;

class Price extends ItemsAbstract
{

    public function export()
    {
        // TODO: Implement export() method.
    }

    /**
     * Export item prices to product_price.csv
     * Creating logical fields for DI integration: discounted, bx_grouped_price
     *
     * @throws Zend_Db_Adapter_Exception
     * @throws Zend_Db_Statement_Exception
     */
    public function exportItemPrices()
    {
        $account = $this->getAccount();
        $files = $this->getFiles();
        $customer_group_key = $this->_config->getCustomerGroupKey($account);
        $customer_group_id = $this->_config->getCustomerGroupId($account);
        $header = true;
        $db = $this->db;
        $sql = $db->select()
            ->from(array('a' => 's_articles'),array('pricegroupActive', 'laststock')
            )
            ->join(
                array('d' => 's_articles_details'),
                $this->qi('d.articleID') . ' = ' . $this->qi('a.id') . ' AND ' .
                $this->qi('d.kind') . ' <> ' . $db->quote(3),
                array('d.id', 'd.articleID', 'd.instock', 'd.active')
            )
            ->joinLeft(array('a_p' => 's_articles_prices'), 'a_p.articledetailsID = d.id', array('price', 'pseudoprice'))
            ->joinLeft(array('c_c' => 's_core_customergroups'), 'c_c.groupkey = a_p.pricegroup',array())
            ->joinLeft(array('c_t' => 's_core_tax'), 'c_t.id = a.taxID', array('tax'))
            ->joinLeft(
                array('p_d' => 's_core_pricegroups_discounts'),
                'p_d.groupID = a.pricegroupID AND p_d.customergroupID = ' . $customer_group_id ,
                array('pg_discounts' => 'discount')
            )
            ->where('a_p.pricegroup = ?', $customer_group_key)
            ->where('a_p.from = ?', 1);
        if ($this->delta) {
            $sql->where('a.id IN(?)', $this->deltaIds);
        }

        $grouped_price = array();
        $data = array();
        $stmt = $db->query($sql);
        while ($row = $stmt->fetch()) {
            if(!isset($this->shopProductIds[$row['id']])){
                continue;
            }
            $taxFactor = ((floatval($row['tax']) + 100.0) /100);
            if ($row['pseudoprice'] == 0) $row['pseudoprice'] = $row['price'];
            $pseudo = floatval($row['pseudoprice']) * $taxFactor;
            $discount = floatval($row['price']) * $taxFactor;
            if (!is_null($row['pg_discounts']) && $row['pricegroupActive'] == 1) {
                $discount = $discount - ($discount * ((floatval($row['pg_discounts'])) /100));
            }
            $price = $pseudo > $discount ? $pseudo : $discount;
            if($header) {
                $data[] = ["id", "price", "discounted", "articleID", "grouped_price"];
                $header = false;
            }
            $data[$row['id']] = array("id" => $row['id'], "price" => number_format($price,2, '.', ''), "discounted" => number_format($discount,2, '.', ''), "articleID" => $row['articleID']);

            if ($row['active'] == 1) {
                if(isset($grouped_price[$row['articleID']]) && ($grouped_price[$row['articleID']] < number_format($discount,2, '.', ''))) {
                    continue;
                }
                $grouped_price[$row['articleID']] = number_format($discount,2, '.', '');
            }
        }

        foreach ($data as $index => $d) {
            if($index == 0) continue;
            $articleID = $d['articleID'];
            if(isset($grouped_price[$articleID])){
                $data[$index]['grouped_price'] = $grouped_price[$articleID];
                continue;
            }
            $data[$index]['grouped_price'] = $data[$index]['discounted'];
        }

        $grouped_price = null;
        $files->savepartToCsv('product_price.csv', $data);
        $sourceKey = $this->bxData->addCSVItemFile($files->getPath('product_price.csv'), 'id');
        $this->bxData->addSourceDiscountedPriceField($sourceKey, 'discounted');
        $this->bxData->addSourceListPriceField($sourceKey, 'price');
        $this->bxData->addSourceNumberField($sourceKey, 'bx_grouped_price', 'grouped_price');
        $this->bxData->addFieldParameter($sourceKey,'bx_grouped_price', 'multiValued', 'false');
    }

}