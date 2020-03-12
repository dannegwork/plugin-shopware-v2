<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter\Item;

/**
 * Class Category
 * @package Boxalino\IntelligenceFramework\Service\Exporter\Item
 */
class Category extends ItemsAbstract
{

    CONST EXPORTER_COMPONENT_MAIN_FILE = 'categories.csv';
    CONST EXPORTER_COMPONENT_RELATION_FILE = 'product-categories.csv';

    public function export()
    {
        // TODO: Implement export() method.
    }

    /**
     * Export item categories
     */
    public function exportItemCategories()
    {
        $files = $this->getFiles();
        $categories = [];
        $header = true;
        $languages = $this->config->getAccountLanguages($this->getAccount());
        $select = $db->select()->from(array('c' => 's_categories'), array('id', 'parent', 'description', 'path'));
        $stmt = $db->query($select);
        $this->logger->info("BxIndexLog: Preparing products - start categories.");
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
        $this->logger->info("BxIndexLog: Preparing products - end categories.");
        $files->savePartToCsv('categories.csv', $categories);
        $categories = null;
        $this->getLibrary()->addCategoryFile($files->getPath('categories.csv'), 'category_id', 'parent_id', $language_headers);


        $language_headers = null;
        $data = array();
        $doneCases = array();
        $header = true;
        $categoryShopIds = $this->config->getShopCategoryIds($this->getAccount());

        $this->logger->info("BxIndexLog: Preparing products - start product categories.");
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

        $this->logger->info("BxIndexLog: Preparing products - end product categories.");
        $doneCases = null;
        $productToCategoriesSourceKey = $this->getLibrary()->addCSVItemFile($files->getPath('product_categories.csv'), 'id');
        $this->getLibrary()->setCategoryField($productToCategoriesSourceKey, 'categoryID');
    }


    public function getTranslation()
    {
        $query = $this->connection->createQueryBuilder();
        $query->select([

        ])
            ->from('category_translation')
            ->leftJoin('category_translation', 'language', '');
    }

    public function getLanguageHeaders()
    {
        $languages = $this->config->getAccountLanguages($this->getAccount());
        $headers = [];
        foreach($languages as $language)
        {
            $headers[] = 'value_' . $language;
        }

        return $headers;
    }

    public function exportItemRelation()
    {

    }

    public function getRequiredFields(): array
    {
        return [
            'LOWER(HEX(category.id)) AS id', 'category.auto_increment', 'LOWER(HEX(category.parent_id)) as parent_id',
            'LOWER(HEX(category.media_id)) AS media_id', 'LOWER(HEX(category.cms_page_id)) AS cms_page_id',
            'LOWER(HEX(category.after_category_id)) AS after_category_id', 'category.level', 'category.active', 'category.child_count',
            'category.display_nested_products', 'category.visible', 'category.type', 'category.created_at', 'category.updated_at',
            'category.'
        ];
    }

}