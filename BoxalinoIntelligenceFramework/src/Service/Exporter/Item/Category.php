<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter\Item;

class Category extends ItemsAbstract
{
    public function export()
    {
        // TODO: Implement export() method.
    }

    /**
     * Export item categories
     * @throws Zend_Db_Adapter_Exception
     * @throws Zend_Db_Statement_Exception
     */
    public function exportItemCategories()
    {
        $db = $this->db;
        $account = $this->getAccount();
        $files = $this->getFiles();
        $categories = array();
        $header = true;
        $languages = $this->_config->getAccountLanguages($account);
        $select = $db->select()->from(array('c' => 's_categories'), array('id', 'parent', 'description', 'path'));
        $stmt = $db->query($select);
        $this->log->info("BxIndexLog: Preparing products - start categories.");
        if($stmt->rowCount()) {
            while($r = $stmt->fetch()){
                $value = $r['description'];
                $category = array('category_id' => $r['id'], 'parent_id' => $r['parent']);
                foreach ($languages as $language) {
                    $category['value_' . $language] = $value;
                    if($header) {
                        $language_headers[$language] = "value_$language";
                    }
                }
                if($header) {
                    $categories[] = array_keys($category);
                    $header = false;
                }
                $categories[$r['id']] = $category;
            }
        }
        $this->log->info("BxIndexLog: Preparing products - end categories.");
        $files->savePartToCsv('categories.csv', $categories);
        $categories = null;
        $this->bxData->addCategoryFile($files->getPath('categories.csv'), 'category_id', 'parent_id', $language_headers);
        $language_headers = null;
        $data = array();
        $doneCases = array();
        $header = true;
        $categoryShopIds = $this->_config->getShopCategoryIds($account);

        $this->log->info("BxIndexLog: Preparing products - start product categories.");
        foreach ($languages as $shop_id => $language) {
            $category_id = $categoryShopIds[$shop_id];
            $sql = $db->select()
                ->from(array('ac' => 's_articles_categories_ro'), array())
                ->join(
                    array('d' => 's_articles_details'),
                    $this->qi('d.articleID') . ' = ' . $this->qi('ac.articleID') . ' AND ' .
                    $this->qi('d.kind') . ' <> ' . $db->quote(3),
                    array('d.id', 'ac.categoryID')
                )
                ->joinLeft(array('c' => 's_categories'), 'ac.categoryID = c.id', array())
                ->where('c.path LIKE \'%|' . $category_id . '|%\'');
            if ($this->delta) {
                $sql->where('d.articleID IN(?)', $this->deltaIds);
            }
            $stmt = $db->query($sql);
            if($stmt->rowCount()) {
                while ($row = $stmt->fetch()) {
                    $key = $row['id'] . '_' . $row['categoryID'];
                    if(isset($doneCases[$key])) {
                        continue;
                    }
                    $doneCases[$key] = true;
                    if($header) {
                        $data[] = array_keys($row);
                        $header = false;
                    }
                    $data[] = $row;
                    if(sizeof($data) > 10000) {
                        $files->savePartToCsv('product_categories.csv', $data);
                        $data = [];
                    }
                }
                if(sizeof($data)>0) {
                    $files->savePartToCsv('product_categories.csv', $data);
                }
                continue;
            } else {
                break;
            }
        }

        $this->log->info("BxIndexLog: Preparing products - end product categories.");
        $doneCases = null;
        $productToCategoriesSourceKey = $this->bxData->addCSVItemFile($files->getPath('product_categories.csv'), 'id');
        $this->bxData->setCategoryField($productToCategoriesSourceKey, 'categoryID');
    }

}