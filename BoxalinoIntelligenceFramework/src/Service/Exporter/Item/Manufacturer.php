<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter\Item;

class Manufacturer extends ItemsAbstract
{

    /**
     * Export item brands/suppliers
     * Create file product_brands.csv
     *
     * @throws Zend_Db_Adapter_Exception
     * @throws Zend_Db_Statement_Exception
     */
    public function exportItemBrands()
    {
        $db = $this->db;
        $files = $this->getFiles();
        $data = array();
        $header = true;
        $sql = $db->select()
            ->from(array('a' => 's_articles'), array())
            ->join(
                array('d' => 's_articles_details'),
                $this->qi('d.articleID') . ' = ' . $this->qi('a.id') . ' AND ' .
                $this->qi('d.kind') . ' <> ' . $db->quote(3),
                array('id')
            )
            ->join(
                array('asup' => 's_articles_supplier'),
                $this->qi('asup.id') . ' = ' . $this->qi('a.supplierID'),
                array('brand' => 'name')
            );
        if ($this->delta) {
            $sql->where('a.id IN(?)', $this->deltaIds);
        }
        $stmt = $db->query($sql);
        while ($row = $stmt->fetch()) {
            if(!isset($this->shopProductIds[$row['id']])) {
                continue;
            }
            if($header) {
                $data[] = array_keys($row);
                $header = false;
            }
            $row['brand'] = trim($row['brand']);
            $data[] = $row;
        }
        $files->savepartToCsv('product_brands.csv', $data);
        $attributeSourceKey = $this->bxData->addCSVItemFile($files->getPath('product_brands.csv'), 'id');
        $this->bxData->addSourceStringField($attributeSourceKey, "brand", "brand");
    }

}