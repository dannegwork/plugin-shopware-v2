<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter\Item;

class Images extends ItemsAbstract
{

    public function export()
    {
        // TODO: Implement export() method.
    }

    /**
     * Export item images link
     *
     * @throws Zend_Db_Adapter_Exception
     * @throws Zend_Db_Statement_Exception
     */
    public function exportItemImages()
    {
        $account = $this->getAccount();
        $files = $this->getFiles();
        $db = $this->db;
        $data = array();
        $pipe = $db->quote('|');
        $fieldMain = $this->qi('s_articles_img.main');
        $imagePath = $this->qi('s_media.path');
        $fieldPosition = $this->qi('s_articles_img.position');
        $header = true;
        $mediaService = Shopware()->Container()->get('shopware_media.media_service');
        $inner_select = $db->select()
            ->from('s_articles_img',
                new Zend_Db_Expr("GROUP_CONCAT(
                CONCAT($imagePath)
                ORDER BY $fieldMain, $fieldPosition
                SEPARATOR $pipe)")
            )
            ->join(array('s_media'), 's_media.id = s_articles_img.media_id', array())
            ->where('s_articles_img.articleID = a.id');

        $sql = $db->select()
            ->from(array('a' => 's_articles'), array('images' => new Zend_Db_Expr("($inner_select)")))
            ->join(
                array('d' => 's_articles_details'),
                $this->qi('d.articleID') . ' = ' . $this->qi('a.id') . ' AND ' .
                $this->qi('d.kind') . ' <> ' . $db->quote(3),
                array('id')
            );
        if ($this->delta) {
            $sql->where('a.id IN(?)', $this->deltaIds);
        }
        $stmt = $db->query($sql);
        while ($row = $stmt->fetch()) {
            if(!isset($this->shopProductIds[$row['id']])){
                continue;
            }
            if($header) {
                $data[] = array_keys($row);
                $header = false;
            }
            $images = explode('|', $row['images']);
            foreach ($images as $index => $image) {
                $images[$index] = $mediaService->getUrl($image);
            }
            $row['images'] = implode('|', $images);
            $data[] = $row;
        }
        $files->savepartToCsv('product_image_url.csv', $data);
        $sourceKey = $this->bxData->addCSVItemFile($files->getPath('product_image_url.csv'), 'id');
        $this->bxData->addSourceStringField($sourceKey, 'image', 'images');
        $this->bxData->addFieldParameter($sourceKey,'image', 'splitValues', '|');
    }

}