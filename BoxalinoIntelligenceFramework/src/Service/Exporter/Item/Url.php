<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter\Item;

class Url extends ItemsAbstract
{

    public function export()
    {
        // TODO: Implement export() method.
    }

    /**
     * Export product URL
     * Create url.csv and products_url.csv
     *
     * @throws Zend_Db_Adapter_Exception
     * @throws Zend_Db_Statement_Exception
     */
    public function exportItemUrls()
    {
        $db = $this->db;
        $account = $this->getAccount();
        $files = $this->getFiles();
        $main_shopId = $this->_config->getAccountStoreId($account);
        $repository = Shopware()->Container()->get('models')->getRepository('Shopware\Models\Shop\Shop');
        $shop = $repository->getActiveById($main_shopId);
        $defaultPath = 'http://'. $shop->getHost() . $shop->getBasePath() . '/';
        $languages = $this->_config->getAccountLanguages($account);
        $lang_header = array();
        $lang_productPath = array();
        $data = array();
        foreach ($languages as $shopId => $language) {
            $lang_header[$language] = "value_$language";
            $shop = $repository->getActiveById($shopId);
            $productPath = 'http://' . $shop->getHost() . $shop->getBasePath()  . $shop->getBaseUrl() . '/' ;
            $lang_productPath[$language] = $productPath;
            $shop = null;

            $sql = $db->select()
                ->from(array('r_u' => 's_core_rewrite_urls'),
                    array('subshopID', 'path', 'org_path', 'main',
                        new Zend_Db_Expr("SUBSTR(org_path, LOCATE('sArticle=', org_path) + CHAR_LENGTH('sArticle=')) as articleID")
                    )
                )
                ->where("r_u.subshopID = {$shopId} OR r_u.subshopID = ?", $main_shopId)
                ->where("r_u.main = ?", 1)
                ->where("org_path like '%sArticle%'");
            if ($this->delta) {
                $sql->having('articleID IN(?)', $this->deltaIds);
            }

            $stmt = $db->query($sql);
            if ($stmt->rowCount()) {
                while ($row = $stmt->fetch()) {
                    $basePath = $row['subshopID'] == $shopId ? $productPath : $defaultPath;
                    if (isset($data[$row['articleID']])) {
                        if (isset($data[$row['articleID']]['value_' . $language])) {
                            if ($data[$row['articleID']]['subshopID'] < $row['subshopID']) {
                                $data[$row['articleID']]['value_' . $language] = $basePath . $row['path'];
                                $data[$row['articleID']]['subshopID'] = $row['subshopID'];
                            }
                        } else {
                            $data[$row['articleID']]['value_' . $language] = $basePath . $row['path'];
                            $data[$row['articleID']]['subshopID'] = $row['subshopID'];
                        }
                        continue;
                    }
                    $data[$row['articleID']] = array(
                        'articleID' => $row['articleID'],
                        'subshopID' => $row['subshopID'],
                        'value_' . $language => $basePath . $row['path']
                    );
                }
            }
        }
        $sql = $db->select()
            ->from(array('a' => 's_articles'), array())
            ->join(
                array('d' => 's_articles_details'),
                $this->qi('d.articleID') . ' = ' . $this->qi('a.id') . ' AND ' .
                $this->qi('d.kind') . ' <> ' . $db->quote(3),
                array('id', 'articleID')
            );
        if ($this->delta) {
            $sql->where('a.id IN(?)', $this->deltaIds);
        }
        $stmt = $db->query($sql);
        if ($stmt->rowCount()) {
            while ($row = $stmt->fetch()) {
                if(!isset($data[$row['articleID']])){
                    $articleID = $row['articleID'];
                    $item = ["articleID" => $articleID, "subshopID" => null];
                    foreach ($lang_productPath as $language => $path) {
                        $item["value_{$language}"] = "{$path}detail/index/sArticle/{$articleID}";
                    }
                    $data[$row['articleID']] = $item;
                }
            }
        }
        if (count($data) > 0) {
            $data = array_merge(array(array_merge(array('articleID', 'subshopID'), $lang_header)), $data);
        } else {
            $data = (array(array_merge(array('articleID', 'subshopID'), $lang_header)));
        }
        $files->savepartToCsv('url.csv', $data);
        $referenceKey = $this->bxData->addResourceFile($files->getPath('url.csv'), 'articleID', $lang_header);
        $sql = $db->select()
            ->from(array('a' => 's_articles'), array())
            ->join(
                array('d' => 's_articles_details'),
                $this->qi('d.articleID') . ' = ' . $this->qi('a.id') . ' AND ' .
                $this->qi('d.kind') . ' <> ' . $db->quote(3),
                array('id', 'articleID')
            );
        if ($this->delta) {
            $sql->where('a.id IN(?)', $this->deltaIds);
        }
        $stmt = $db->query($sql);
        if ($stmt->rowCount()) {
            while ($row = $stmt->fetch()) {
                $data[$row['id']] = array('id' => $row['id'], 'articleID' => $row['articleID']);
            }
        }
        $data = array_merge(array(array_keys(end($data))), $data);
        $files->savepartToCsv('products_url.csv', $data);
        $attributeSourceKey = $this->bxData->addCSVItemFile($files->getPath('products_url.csv'), 'id');
        $this->bxData->addSourceLocalizedTextField($attributeSourceKey, "url", "articleID", $referenceKey);
    }


}